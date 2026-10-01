<?php

namespace App\Http\Services\Patient;

use App\Enums\QuestionRoleEnum;
use App\Models\Patient;
use App\Models\Tenant;
use App\Services\Tenant\PacientePlanoService;
use App\Support\Formatar;
use App\Support\Planos;
use App\Support\RodapePdf;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Ficha do beneficiário em PDF: todos os dados já formatados em pt-BR
 * (máscaras de CPF/telefone/CEP, datas dd/mm/aaaa no fuso de Brasília).
 */
class FichaBeneficiarioPdfService
{
    private const ORIGENS_REGISTRO = [
        'formulario' => 'Formulário (cadastro manual)',
        'form-dinamico' => 'Formulário dinâmico',
        'form-publico' => 'Formulário público',
        'importacao' => 'Importação',
        'vinculo' => 'Vínculo SIPROV',
    ];

    public function __construct(
        private readonly PacientePlanoService $pacientePlanoService,
    ) {}

    public function gerar(Patient $patient, string $tenantId)
    {
        $tenant = Tenant::find($tenantId);
        // Nome exibido: "name" do tenant, senão a descrição da página do parceiro.
        $parceiro = $tenant?->name ?: ($tenant?->details()->first()?->descricao ?? $tenantId);
        $ficha = $this->dados($patient, $tenantId);

        $pdf = Pdf::loadView('pdf.ficha-beneficiario', [
            'ficha' => $ficha,
            'tenant' => $tenant,
            'parceiro' => $parceiro,
            'logoBase64' => $this->logo($tenant),
        ])->setPaper('a4');

        return RodapePdf::aplicar(
            $pdf,
            'Gerado por '.($ficha['gerado_por'] ?? 'sistema').' em '.$ficha['gerado_em'],
            $parceiro.' · Ficha do beneficiário nº '.$ficha['id'],
            30,
        );
    }

    /**
     * Dados da ficha, prontos para exibir.
     */
    public function dados(Patient $patient, string $tenantId): array
    {
        $patient->loadMissing('answers.question');
        $endereco = is_array($patient->enderecos) ? $patient->enderecos : [];
        $detalhes = $this->pacientePlanoService->detalhes($tenantId, $patient);
        $nascimento = $patient->data_nascimento;
        $idade = Formatar::idade($nascimento);

        return [
            'id' => $patient->id,
            // Iniciais só de palavras (ignora números como em "TESTE 01").
            'iniciais' => collect(preg_split('/\s+/', trim((string) $patient->nome)))
                ->filter(fn ($parte) => preg_match('/^\pL/u', $parte))
                ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
                ->pipe(fn ($partes) => $partes->first().($partes->count() > 1 ? $partes->last() : '')) ?: 'B',
            'ativo' => (bool) $patient->status,
            'plano_siprov' => $detalhes['plano']['siprov'] ?? null,
            'identificacao' => [
                'Nome' => $patient->nome,
                'CPF' => Formatar::cpf($patient->cpf),
                'RG' => $patient->rg,
                'Data de nascimento' => Formatar::data($nascimento)
                    ? Formatar::data($nascimento).($idade !== null ? " ({$idade} anos)" : '')
                    : null,
                'Sexo' => Formatar::sexo($patient->sexo?->value ?? $patient->sexo),
                'Status' => $patient->status ? 'Ativo' : 'Inativo',
            ],
            'contato' => [
                'E-mail' => $patient->email,
                'Celular' => Formatar::telefone($patient->numero),
            ],
            'endereco' => [
                'CEP' => Formatar::cep($endereco['cep'] ?? null),
                'Logradouro' => trim(implode(', ', array_filter([$endereco['logradouro'] ?? null, $endereco['numero'] ?? null]))) ?: null,
                'Complemento' => $endereco['complemento'] ?? null,
                'Bairro' => $endereco['bairro'] ?? null,
                'Cidade/UF' => trim(implode('/', array_filter([$endereco['cidade'] ?? null, $endereco['estado'] ?? null]))) ?: null,
            ],
            'plano' => $detalhes['plano'] ? [
                'Plano' => $detalhes['plano']['plano'].($detalhes['plano']['siprov'] ? ' (SIPROV)' : ' (próprio do sistema)'),
                'Origem' => $detalhes['plano']['origem'],
                'Registrado por' => $detalhes['plano']['usuario']
                    ?? ($detalhes['plano']['origem_tipo'] === 'formulario_publico' ? 'Formulário público (sem login)' : 'Não registrado'),
                'Registrado em' => $detalhes['plano']['data_hora'],
            ] : null,
            'cadastro' => [
                'Origem do cadastro' => self::ORIGENS_REGISTRO[$patient->status_registro?->value ?? $patient->status_registro] ?? null,
                'Criado por' => $detalhes['cadastro']['usuario']
                    ?? ($detalhes['cadastro']['auditado'] ? 'Sem login (formulário ou importação)' : 'Não registrado'),
                'Criado em' => $detalhes['cadastro']['data_hora'],
                'Atualizado em' => Formatar::dataHora($patient->updated_at),
            ],
            'respostas' => $patient->answers
                ->map(fn ($answer) => [
                    'pergunta' => $answer->question?->title ?? 'Pergunta',
                    'resposta' => $this->formatarResposta($answer->answer, $answer->question?->role),
                ])
                ->values()
                ->all(),
            'gerado_por' => auth()->user()?->name,
            'gerado_em' => Formatar::dataHora(now()),
        ];
    }

    private function formatarResposta(?string $resposta, ?QuestionRoleEnum $papel): ?string
    {
        if ($resposta === null || trim($resposta) === '') {
            return null;
        }

        return match ($papel) {
            QuestionRoleEnum::Cpf => Formatar::cpf($resposta),
            QuestionRoleEnum::Tel => Formatar::telefone($resposta),
            QuestionRoleEnum::BirthDate => Formatar::data($resposta) ?? $resposta,
            QuestionRoleEnum::Sexo => Formatar::sexo($resposta),
            QuestionRoleEnum::Plan => collect(Planos::options())->firstWhere('value', (string) $resposta)['label'] ?? $resposta,
            // Sem papel: data ISO (aaaa-mm-dd) vira dd/mm/aaaa.
            default => preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($resposta)) ? Formatar::data($resposta) : $resposta,
        };
    }

    private function logo(?Tenant $tenant): ?string
    {
        if (! $tenant?->photo_path) {
            return null;
        }

        $caminho = base_path('storage/app/public/'.$tenant->photo_path);

        return file_exists($caminho)
            ? 'data:'.mime_content_type($caminho).';base64,'.base64_encode(file_get_contents($caminho))
            : null;
    }
}
