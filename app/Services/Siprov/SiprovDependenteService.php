<?php

namespace App\Services\Siprov;

use App\Exceptions\SiprovException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SiprovDependenteService
{
    /**
     * O GET de dependentes pode devolver o parentesco em formato de exibição,
     * mas o POST só aceita os valores do enum.
     */
    private const PARENTESCO_MAP = [
        'CÔNJUGE' => 'CONJUGE',
        'MÃE/PAI' => 'PAI',
        'FILHO(A)' => 'FILHO',
        'IRMÃ/IRMÃO' => 'IRMAO',
        'AVÓ/AVÔ' => 'AVO',
        'TIO(A)' => 'TIO',
        'SOBRINHO(A)' => 'SOBRINHO',
        'PRIMO(A)' => 'PRIMO',
        'NETO(A)' => 'NETO',
        'SOGRO(A)' => 'SOGRO',
        'PET - CANINO' => 'PET_CANINO',
        'PET - FELINO' => 'PET_FELINO',
        'PET - OUTROS' => 'PET_OUTROS',
        'CUNHADO(A)' => 'CUNHADO',
        'GENRO/NORA' => 'GENRO',
        'ENTEADO(A)' => 'ENTEADO',
        'PADRASTO/MADRASTA' => 'PADRASTO',
    ];

    public function __construct(
        private readonly SiprovAuthService $authService,
    ) {}

    public function listar(int $codBeneficio): array
    {
        $endpoint = '/ext/beneficio/'.$codBeneficio.'/dependentes';

        try {
            $response = $this->send('get', $endpoint);

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao listar dependentes', [
                    'status'        => $response->status(),
                    'response'      => $response->body(),
                    'cod_beneficio' => $codBeneficio,
                ]);

                throw SiprovException::dependenteFailed($response->body());
            }

            return $response->json() ?? [];

        } catch (SiprovException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao listar dependentes', [
                'message'       => $e->getMessage(),
                'cod_beneficio' => $codBeneficio,
            ]);

            throw SiprovException::dependenteFailed($e->getMessage());
        }
    }

    /**
     * Inativa o dependente regravando-o com "ativo" = false.
     */
    public function inativar(int $codBeneficio, int $codDependente): array
    {
        $dependente = collect($this->listar($codBeneficio))
            ->firstWhere('codDependente', $codDependente);

        if (! $dependente) {
            throw SiprovException::dependenteFailed('Dependente não encontrado neste benefício.');
        }

        return $this->gravarInativo($codBeneficio, $dependente);
    }

    /**
     * O POST da SIPROV sobrescreve o cadastro, então reenviamos os dados atuais.
     */
    private function gravarInativo(int $codBeneficio, array $dependente): array
    {
        $payload = array_filter([
            'ativo'                => false,
            'codBeneficio'         => $codBeneficio,
            'codDependente'        => (int) $dependente['codDependente'],
            'nome'                 => $dependente['nome'] ?? null,
            'cpf'                  => $dependente['cpf'] ?? null,
            'dataNascimento'       => $dependente['dataNascimento'] ?? null,
            'identidade'           => $dependente['identidade'] ?? null,
            'numeroCartaoDesconto' => $dependente['numeroCartaoDesconto'] ?? null,
            'parentesco'           => $this->normalizeParentesco($dependente['parentesco'] ?? null),
            'sexo'                 => $dependente['sexo'] ?? null,
            'telefone'             => $dependente['telefone'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            Log::info('SIPROV | Inativando dependente', [
                'endpoint' => '/ext/beneficio/dependente',
                'payload'  => $payload,
            ]);

            $response = $this->send('post', '/ext/beneficio/dependente', $payload);

            if ($response->failed()) {
                Log::error('SIPROV | Erro ao inativar dependente', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                    'payload'  => $payload,
                ]);

                throw SiprovException::dependenteFailed($response->body());
            }

            Log::info('SIPROV | Dependente inativado com sucesso', [
                'status'         => $response->status(),
                'cod_beneficio'  => $codBeneficio,
                'cod_dependente' => $payload['codDependente'],
            ]);

            return $response->json() ?? [];

        } catch (SiprovException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::critical('SIPROV | Exception ao inativar dependente', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw SiprovException::dependenteFailed($e->getMessage());
        }
    }

    private function send(string $method, string $endpoint, array $payload = []): Response
    {
        $request = fn () => Http::withToken($this->authService->token())
            ->acceptJson()
            ->{$method}(config('siprov.base_url').$endpoint, $payload);

        $response = $request();

        if ($response->unauthorized()) {
            Log::warning('SIPROV | Token expirado, renovando token', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            $this->authService->forgetToken();

            $response = $request();
        }

        return $response;
    }

    private function normalizeParentesco(?string $parentesco): ?string
    {
        if (! $parentesco) {
            return null;
        }

        $upper = mb_strtoupper(trim($parentesco));

        return self::PARENTESCO_MAP[$upper] ?? $upper;
    }
}
