<?php

namespace App\Services\Tenant;

use App\Models\TelemedicinaTenant;
use App\Models\TenantPlano;
use App\Models\TenantQuantidadeParceiro;
use App\Support\SiprovPlanos;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cotas de associados por plano. Cada tenant tem, por plano, a quantidade
 * contratada e um saldo gravado (`tenant_planos.saldo`) que decrementa a cada
 * associado vinculado e volta ao desvincular. Todo movimento é registrado em
 * `tenants_quantidade_parceiros` e as alterações do saldo são auditadas.
 * Um associado ocupa uma vaga em cada plano que possui.
 */
class TenantPlanoCotaService
{
    /**
     * Códigos de plano de um vínculo. Vínculos antigos só gravaram `cod_plano`.
     *
     * @return array<int, string>
     */
    public static function codigosDoVinculo(array $data): array
    {
        $codigos = $data['cod_planos'] ?? (isset($data['cod_plano']) ? [$data['cod_plano']] : []);

        return array_values(array_unique(array_map('strval', array_filter($codigos, fn ($c) => $c !== null && $c !== ''))));
    }

    /**
     * Códigos de plano de um associado vindo da busca SIPROV.
     *
     * @return array<int, string>
     */
    public static function codigosDoItemSiprov(array $item): array
    {
        return self::codigosDoVinculo([
            'cod_planos' => array_map(fn ($plano) => $plano['codPlano'] ?? null, $item['planos'] ?? []),
        ]);
    }

    /**
     * Vagas em uso por plano: contratado - saldo para planos configurados;
     * para planos não configurados (vínculos antigos), a contagem de vínculos.
     *
     * @return array<string, int>
     */
    public function uso(string $tenantId): array
    {
        $uso = $this->usoPorVinculos($tenantId);

        TenantPlano::where('tenant_id', $tenantId)
            ->get(['cod_plano', 'quantidade', 'saldo'])
            ->each(function (TenantPlano $plano) use (&$uso) {
                $emUso = $plano->quantidade - $plano->saldo;

                // Só planos com vagas ocupadas (quem consome o uso trata ausência como 0).
                if ($emUso > 0) {
                    $uso[$plano->cod_plano] = $emUso;
                } else {
                    unset($uso[$plano->cod_plano]);
                }
            });

        return $uso;
    }

    /**
     * Retrato dos planos do tenant: contratado, saldo, vagas em uso e total de
     * pacientes (associados vinculados) por plano. Usado na auditoria de registros.
     *
     * @return array<string, array{plano: string, quantidade: int, saldo: int, em_uso: int, pacientes: int}>
     */
    public function resumo(string $tenantId): array
    {
        $pacientes = $this->usoPorVinculos($tenantId);

        return TenantPlano::where('tenant_id', $tenantId)
            ->orderBy('cod_plano')
            ->get(['cod_plano', 'quantidade', 'saldo'])
            ->mapWithKeys(fn (TenantPlano $plano) => [$plano->cod_plano => [
                'plano' => self::label($plano->cod_plano),
                'quantidade' => $plano->quantidade,
                'saldo' => $plano->saldo,
                'em_uso' => max(0, $plano->quantidade - $plano->saldo),
                'pacientes' => $pacientes[$plano->cod_plano] ?? 0,
            ]])
            ->all();
    }

    /**
     * Contagem de vínculos por plano (usada para saldo inicial e planos não configurados).
     *
     * @return array<string, int>
     */
    public function usoPorVinculos(string $tenantId): array
    {
        $uso = [];

        TelemedicinaTenant::where('tenant_id', $tenantId)
            ->whereNotNull('data->siprov_id')
            ->get(['data'])
            ->each(function (TelemedicinaTenant $vinculo) use (&$uso) {
                foreach (self::codigosDoVinculo($vinculo->data ?? []) as $codigo) {
                    $uso[$codigo] = ($uso[$codigo] ?? 0) + 1;
                }
            });

        return $uso;
    }

    /**
     * Garante que os associados cabem no saldo do tenant; senão, nenhum é vinculado.
     * Prévia sem lock: o consumo em si (consumir) revalida com lock.
     *
     * @throws ValidationException
     */
    public function validarVinculos(string $tenantId, array $itens): void
    {
        $planos = TenantPlano::where('tenant_id', $tenantId)->get()->keyBy('cod_plano');
        $novos = [];
        $erros = [];

        foreach ($itens as $item) {
            $nome = $item['nomePessoa'] ?? 'Associado';
            $codigos = self::codigosDoItemSiprov($item);

            if (! $codigos) {
                $erros[] = "{$nome} não possui plano.";

                continue;
            }

            foreach ($codigos as $codigo) {
                if (! $planos->has($codigo)) {
                    $erros[] = "{$nome}: o plano ".self::label($codigo).' não está habilitado para este tenant.';

                    continue;
                }

                $novos[$codigo] = ($novos[$codigo] ?? 0) + 1;
            }
        }

        foreach ($novos as $codigo => $quantidade) {
            $erros = [...$erros, ...$this->errosDeSaldo($planos[$codigo], $quantidade)];
        }

        if ($erros) {
            throw ValidationException::withMessages(['siprov_items' => implode(' ', array_unique($erros))]);
        }
    }

    /**
     * Decrementa o saldo de cada plano do vínculo e registra o movimento.
     *
     * @param  array<int, string>  $codigos
     *
     * @throws ValidationException quando algum plano não tem saldo (nada é alterado)
     */
    public function consumir(string $tenantId, array $codigos, ?int $parceiroId = null, ?int $vinculoId = null): void
    {
        DB::connection('mysql')->transaction(function () use ($tenantId, $codigos, $parceiroId, $vinculoId) {
            $planos = $this->travarPlanos($tenantId, $codigos);

            $erros = [];
            foreach ($codigos as $codigo) {
                $plano = $planos[$codigo] ?? null;
                $erros = [...$erros, ...($plano
                    ? $this->errosDeSaldo($plano, 1)
                    : ['O plano '.self::label($codigo).' não está habilitado para este tenant.'])];
            }

            if ($erros) {
                throw ValidationException::withMessages(['cod_plano' => implode(' ', $erros)]);
            }

            foreach ($codigos as $codigo) {
                $this->movimentar($planos[$codigo], -1, TenantQuantidadeParceiro::TIPO_CONSUMO, $parceiroId, $vinculoId);
            }
        });
    }

    /**
     * Devolve as vagas de um vínculo que está sendo removido.
     */
    public function devolver(TelemedicinaTenant $vinculo, ?int $parceiroId = null): void
    {
        $codigos = self::codigosDoVinculo($vinculo->data ?? []);

        if (! $codigos) {
            return;
        }

        DB::connection('mysql')->transaction(function () use ($vinculo, $codigos, $parceiroId) {
            $planos = $this->travarPlanos($vinculo->tenant_id, $codigos);

            foreach ($codigos as $codigo) {
                $plano = $planos[$codigo] ?? null;

                // Plano removido ou já com saldo cheio (vínculo anterior à cota): nada a devolver.
                if (! $plano || $plano->saldo >= $plano->quantidade) {
                    continue;
                }

                $this->movimentar($plano, 1, TenantQuantidadeParceiro::TIPO_DEVOLUCAO, $parceiroId, $vinculo->id);
            }
        });
    }

    /**
     * Ajusta a quantidade contratada de um plano, levando o saldo junto.
     * Plano novo começa com saldo = quantidade - vínculos já existentes.
     */
    public function ajustarQuantidade(string $tenantId, string $codPlano, int $quantidade): void
    {
        $plano = TenantPlano::where('tenant_id', $tenantId)->where('cod_plano', $codPlano)->lockForUpdate()->first();

        if (! $plano) {
            $emUso = $this->usoPorVinculos($tenantId)[$codPlano] ?? 0;

            $plano = TenantPlano::create([
                'tenant_id' => $tenantId,
                'cod_plano' => $codPlano,
                'quantidade' => $quantidade,
                'saldo' => $quantidade - $emUso,
            ]);

            $this->registrar($plano, $plano->saldo, TenantQuantidadeParceiro::TIPO_AJUSTE);

            return;
        }

        $variacao = $quantidade - $plano->quantidade;

        if ($variacao === 0) {
            return;
        }

        $plano->quantidade = $quantidade;
        $this->movimentar($plano, $variacao, TenantQuantidadeParceiro::TIPO_AJUSTE);
    }

    /**
     * @return \Illuminate\Support\Collection<string, TenantPlano>
     */
    private function travarPlanos(string $tenantId, array $codigos)
    {
        return TenantPlano::where('tenant_id', $tenantId)
            ->whereIn('cod_plano', $codigos)
            ->lockForUpdate()
            ->get()
            ->keyBy('cod_plano');
    }

    private function movimentar(TenantPlano $plano, int $variacao, string $tipo, ?int $parceiroId = null, ?int $vinculoId = null): void
    {
        // save() (e não decrement()) para a alteração passar pela auditoria.
        $plano->saldo += $variacao;
        $plano->save();

        $this->registrar($plano, $variacao, $tipo, $parceiroId, $vinculoId);
    }

    private function registrar(TenantPlano $plano, int $variacao, string $tipo, ?int $parceiroId = null, ?int $vinculoId = null): void
    {
        TenantQuantidadeParceiro::create([
            'tenant_id' => $plano->tenant_id,
            'cod_plano' => $plano->cod_plano,
            'parceiro_id' => $parceiroId,
            'telemedicina_tenant_id' => $vinculoId,
            'tipo' => $tipo,
            'variacao' => $variacao,
            'quantidade' => $plano->saldo,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function errosDeSaldo(TenantPlano $plano, int $necessario): array
    {
        if ($necessario <= $plano->saldo) {
            return [];
        }

        $disponivel = max(0, $plano->saldo);

        return ['Limite do plano '.self::label($plano->cod_plano)." excedido: {$necessario} selecionado(s), {$disponivel} vaga(s) disponível(is) de {$plano->quantidade}."];
    }

    private static function label(string $codigo): string
    {
        return collect(SiprovPlanos::options())->pluck('label', 'value')[$codigo] ?? "Plano {$codigo}";
    }
}
