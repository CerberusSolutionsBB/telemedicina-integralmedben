<?php

namespace App\Http\Services\ExternalApi;

use App\Data\SiprovIntegrationData;
use App\Interfaces\ExternalApiInterface;
use App\Models\Siprov;
use App\Services\Siprov\SiprovIntegrationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovExternalService implements ExternalApiInterface
{
    public function __construct(
        private SiprovIntegrationService $siprovIntegrationService,
    ) {}

    /**
     * Integra um paciente na SIPROV (associado + benefício).
     *
     * Campos esperados no payload (normalizados pelo listener via QuestionRoleEnum):
     *   - nome        → nomePessoa
     *   - cpf         → cpfCnpj
     *   - email       → email
     *   - tel         → telefones[0].numero
     *   - sexo        → sexo
     *   - birth_date  → dataNascimento
     *   - plan        → plano (deve ser um dos valores de config('siprov.planos'))
     *
     * Campos com defaults:
     *   - codigoIntegracao = "USR-{cpf}"
     *   - ativo            = true
     *   - diaVencimento    = 10
     *   - situacao         = "Ativo"
     */
    public function registerPatient(array $data): array
    {
        // --- Valida campos obrigatórios ---
        $nome = $data['nome'] ?? $data['nome_completo'] ?? $data['name'] ?? null;
        $cpf = $data['cpf'] ?? null;
        $email = $data['email'] ?? null;
        $tel = $data['tel'] ?? $data['whatsapp'] ?? $data['telefone'] ?? $data['celular'] ?? null;
        $sexo = $data['sexo'] ?? 'I';
        $birthDate = $data['birth_date'] ?? $data['data_nascimento'] ?? null;
        $plano = $data['plan'] ?? null;

        if (! $nome || ! $cpf || ! $email) {
            throw new \InvalidArgumentException(
                'SIPROV: campos obrigatórios ausentes (nome, cpf, email).'
            );
        }

        if (! $plano) {
            throw new \InvalidArgumentException(
                'SIPROV: plano não informado.'
            );
        }

        $cpfClean = preg_replace('/\D/', '', $cpf);

        // --- Mapear plano do formulário para código SIPROV ---
        $siprovPlano = $this->resolvePlano($plano);

        // --- Construir telefones ---
        $telefones = [];
        if ($tel) {
            $telLimpo = preg_replace('/\D/', '', $tel);
            $telefones[] = [
                'ddi' => 55,
                'numero' => $telLimpo,
                'tipo' => 'Celular',
            ];
        }

        // --- Normalizar sexo ---
        $sexoNormalizado = $this->normalizeSexo($sexo);

        // --- Normalizar data de nascimento ---
        $dataNascimento = $this->normalizeBirthDate($birthDate);

        // --- Construir DTO ---
        $dto = new SiprovIntegrationData(
            codigoIntegracao: 'USR-'.$cpfClean,
            nomePessoa: $nome,
            cpfCnpj: $cpfClean,
            email: $email,
            sexo: $sexoNormalizado,
            dataNascimento: $dataNascimento,
            telefones: $telefones,
            plano: $siprovPlano,
            ativo: true,
            diaVencimento: 10,
            situacao: 'Ativo',
        );

        Log::info('SIPROV External | Iniciando integração', [
            'codigoIntegracao' => $dto->codigoIntegracao,
            'plano' => $dto->plano,
        ]);

        try {
            $result = $this->siprovIntegrationService->execute($dto);

            // Persistir no Siprov model
            $siprov = Siprov::withTrashed()
                ->where('codigo_integracao', $dto->codigoIntegracao)
                ->where('cpf_cnpj', $cpfClean)
                ->where('cod_plano', (string) $dto->codPlano())
                ->first();

            $attributes = [
                'user_id' => null,
                'nome_pessoa' => $dto->nomePessoa,
                'email' => $dto->email,
                'sexo' => $sexoNormalizado,
                'data_nascimento' => $dataNascimento,
                'cod_loja' => (int) config('siprov.cod_loja'),
                'dia_vencimento' => $dto->diaVencimento,
                'ativo' => $dto->ativo,
                'situacao' => $dto->situacao,
                'associado' => $result['associado'] ?? [],
                'beneficio' => $result['beneficio'] ?? [],
                'status' => Siprov::STATUS_SUCCESS,
                'integrated_at' => now(),
            ];

            if ($siprov) {
                $siprov->update($attributes);
            } else {
                $siprov = Siprov::create(array_merge([
                    'codigo_integracao' => $dto->codigoIntegracao,
                    'cpf_cnpj' => $cpfClean,
                    'cod_plano' => (string) $dto->codPlano(),
                ], $attributes));
            }

            Log::info('SIPROV External | Integração concluída', [
                'siprov_id' => $siprov->id,
                'codigoIntegracao' => $dto->codigoIntegracao,
            ]);

            return $result;
        } catch (Throwable $e) {
            Log::error('SIPROV External | Falha na integração', [
                'codigoIntegracao' => $dto->codigoIntegracao,
                'error' => $e->getMessage(),
            ]);

            // Tenta persistir o erro
            try {
                Siprov::create([
                    'codigo_integracao' => $dto->codigoIntegracao,
                    'cpf_cnpj' => $cpfClean,
                    'cod_plano' => (string) $dto->codPlano(),
                    'nome_pessoa' => $dto->nomePessoa,
                    'email' => $dto->email,
                    'sexo' => $sexoNormalizado,
                    'data_nascimento' => $dataNascimento,
                    'status' => Siprov::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                ]);
            } catch (Throwable $logError) {
                Log::error('SIPROV External | Erro ao persistir falha', [
                    'error' => $logError->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Mapeia o valor do plano do formulário para o código interno do config('siprov.planos').
     *
     * O valor do plano pode vir como:
     *   - Código numérico (ex: "331385")
     *   - Nome legível (ex: "Clínica Familiar")
     *   - Chave do config (ex: "clinica_familiar")
     */
    private function resolvePlano(string $plano): string
    {
        $planos = config('siprov.planos', []);

        // Se já for uma chave válida do config
        if (isset($planos[$plano])) {
            return $plano;
        }

        // Se for um código numérico, faz lookup reverso
        $flipped = array_flip($planos);
        $clean = preg_replace('/\D/', '', $plano);
        if (isset($flipped[(int) $clean])) {
            return $flipped[(int) $clean];
        }

        // Tenta match por nome — remove acentos para comparação
        $lower = $this->removeAccents(mb_strtolower(trim($plano)));
        foreach ($planos as $key => $code) {
            $keyStr = $this->removeAccents(str_replace('_', ' ', $key));
            if (str_contains($lower, $keyStr) || str_contains($keyStr, $lower)) {
                return $key;
            }
        }

        throw new \InvalidArgumentException("SIPROV: plano não mapeado — {$plano}");
    }

    private function removeAccents(string $str): string
    {
        $unwanted = ['À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß',
                      'à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','þ','ÿ'];
        $wanted   = ['A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','N','O','O','O','O','O','O','U','U','U','U','Y','P','ss',
                      'a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','d','n','o','o','o','o','o','o','u','u','u','u','y','p','y'];

        return str_replace($unwanted, $wanted, $str);
    }

    /**
     * Normaliza sexo para um dos valores aceitos: M, F, I.
     */
    private function normalizeSexo(string $sexo): string
    {
        $s = mb_strtolower(trim($sexo));

        return match (true) {
            in_array($s, ['m', 'masculino', 'homme', 'male']) => 'M',
            in_array($s, ['f', 'feminino', 'femme', 'female']) => 'F',
            default => 'I',
        };
    }

    /**
     * Normaliza data de nascimento para string compatível com SIPROV.
     */
    private function normalizeBirthDate(?string $date): string
    {
        if (! $date) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (Throwable) {
            return $date;
        }
    }
}
