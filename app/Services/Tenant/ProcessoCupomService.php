<?php

namespace App\Services\Tenant;

use App\Models\CupomBeneficio;
use App\Models\Processo;
use App\Models\ProcessoCupomUso;
use App\Models\User;
use App\Support\Formatar;
use App\Support\Planos;
use Illuminate\Validation\ValidationException;

/**
 * Cupom de benefício aplicado a um processo (aba Processos do Relatório): o cupom
 * precisa estar ativo, no prazo e valer para um dos serviços do processo.
 * aplicar() só calcula a prévia; registrar() grava o uso com quem usou.
 */
class ProcessoCupomService
{
    /**
     * Grava o uso do cupom no processo (histórico), validando de novo na hora.
     *
     * @throws ValidationException com a mensagem para o usuário em "cupom"
     */
    public function registrar(Processo $processo, string $codigo, ?User $usuario): ProcessoCupomUso
    {
        $calculo = $this->aplicar($processo, $codigo);

        return $processo->usosCupom()->create([
            'cupom_beneficio_id' => $calculo['cupom_id'],
            'user_id' => $usuario?->id,
            'usuario_nome' => $usuario?->name,
            'codigo' => $calculo['cupom'],
            'desconto_texto' => $calculo['desconto_texto'],
            'valor_original' => $calculo['valor_original'],
            'desconto' => $calculo['desconto'],
            'valor_final' => $calculo['valor_final'],
        ]);
    }

    /**
     * Uso no formato das telas.
     *
     * @return array<string, mixed>
     */
    public function paraTela(ProcessoCupomUso $uso): array
    {
        return [
            'id' => $uso->id,
            'codigo' => $uso->codigo,
            'cupom_titulo' => $uso->cupom?->titulo,
            'desconto_texto' => $uso->desconto_texto,
            'valor_original' => (float) $uso->valor_original,
            'desconto' => (float) $uso->desconto,
            'valor_final' => (float) $uso->valor_final,
            'usuario' => $uso->usuario?->name ?? $uso->usuario_nome,
            'data' => Formatar::dataHora($uso->created_at),
        ];
    }

    /**
     * @return array{cupom_id: int, cupom: string, titulo: string, desconto_texto: string, plano: ?string, valor_original: float, desconto: float, valor_final: float}
     *
     * @throws ValidationException com a mensagem para o usuário em "cupom"
     */
    public function aplicar(Processo $processo, string $codigo): array
    {
        $cupom = CupomBeneficio::where('codigo', CupomBeneficio::normalizarCodigo($codigo))->first();

        $erro = match (true) {
            $cupom === null => 'Cupom não encontrado. Confira o código.',
            ! $cupom->status => 'Este cupom está inativo.',
            $cupom->vencido() => 'Este cupom venceu em '.Formatar::data($cupom->prazo).'.',
            default => $this->erroDeServico($cupom, $processo),
        };

        if ($erro) {
            throw ValidationException::withMessages(['cupom' => $erro]);
        }

        $valor = (float) $processo->valor;
        $desconto = $cupom->tipo_desconto === CupomBeneficio::TIPO_VALOR_FIXO
            ? min((float) $cupom->valor_desconto, $valor)
            : round($valor * (float) $cupom->valor_desconto / 100, 2);

        return [
            'cupom_id' => $cupom->id,
            'cupom' => $cupom->codigo,
            'titulo' => $cupom->titulo,
            // Serviço do cupom que está no processo (conferido acima).
            'servico' => $cupom->servico->titulo,
            'desconto_texto' => $cupom->descontoFormatado(),
            // Cupom de um plano só: o atendimento confere o plano do beneficiário.
            'plano' => $cupom->cod_plano ? (collect(Planos::options())->pluck('label', 'value')[$cupom->cod_plano] ?? "Plano {$cupom->cod_plano}") : null,
            'valor_original' => $valor,
            'desconto' => $desconto,
            'valor_final' => round($valor - $desconto, 2),
        ];
    }

    /**
     * O cupom só vale se o serviço dele estiver entre os serviços do processo.
     * Mensagem com o motivo concreto; null = o serviço confere.
     */
    private function erroDeServico(CupomBeneficio $cupom, Processo $processo): ?string
    {
        $servicosDoProcesso = $processo->servicos()->pluck('titulo', 'servicos.id');

        if ($cupom->servico_id === null) {
            $convenio = $cupom->convenio?->titulo;

            return $convenio
                ? "Este cupom é do convênio \"{$convenio}\", não de um serviço."
                : 'Este cupom não é de nenhum serviço.';
        }

        if ($servicosDoProcesso->isEmpty()) {
            return 'Este processo não tem serviços vinculados. Edite o processo e vincule o serviço do cupom.';
        }

        if (! $servicosDoProcesso->has($cupom->servico_id)) {
            $servicoDoCupom = $cupom->servico?->titulo ?? 'outro serviço';

            return "Este cupom é do serviço \"{$servicoDoCupom}\", que não faz parte deste processo (serviços do processo: {$servicosDoProcesso->implode(', ')}).";
        }

        return null;
    }
}
