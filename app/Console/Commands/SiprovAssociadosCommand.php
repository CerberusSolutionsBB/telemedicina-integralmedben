<?php

namespace App\Console\Commands;

use App\Data\SiprovAssociadoQueryData;
use App\Exceptions\SiprovException;
use App\Services\Siprov\SiprovAssociadoService;
use App\Services\Siprov\SiprovPlanoVinculoService;
use Illuminate\Console\Command;

class SiprovAssociadosCommand extends Command
{
    protected $signature = 'siprov:associados
                            {--situacao=Todos : Situação do benefício na SIPROV (ex.: ATIVO, INATIVO, Todos)}
                            {--simular : Só lista o que seria alterado nos parceiros, sem gravar}';

    protected $description = 'Lista os associados com benefício na SIPROV e leva o plano para os parceiros (mesmo CPF): completa vínculos sem plano e cria os que faltam';

    public function handle(SiprovAssociadoService $associadoService, SiprovPlanoVinculoService $planoVinculoService): int
    {
        try {
            $associados = $associadoService->todos($this->option('situacao') ?: SiprovAssociadoQueryData::TODAS_SITUACOES);
        } catch (SiprovException $e) {
            $this->error('Não foi possível consultar a SIPROV: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(count($associados).' associado(s) com benefício na SIPROV.');
        $this->table(
            ['Nome', 'CPF', 'Benefício', 'Situação', 'Plano(s)'],
            collect($associados)->map(fn (array $item) => [
                $item['nomePessoa'] ?? '',
                $item['cpfCnpj'] ?? '',
                $item['codBeneficio'] ?? '',
                $item['situacao'] ?? '',
                collect($item['planos'] ?? [])->pluck('nome')->filter()->implode(', ') ?: '—',
            ])->all()
        );

        $simular = (bool) $this->option('simular');
        $atualizados = $planoVinculoService->sincronizar($associados, $simular);

        $this->newLine();

        if (! $atualizados) {
            $this->info('Nenhum parceiro com beneficiário sem plano para atualizar.');

            return self::SUCCESS;
        }

        $this->info(count($atualizados).($simular ? ' beneficiário(s) de parceiro seriam atualizados:' : ' beneficiário(s) de parceiro atualizados:'));
        $this->table(
            ['Ação', 'Parceiro', 'Nome', 'CPF', 'Plano'],
            collect($atualizados)->map(fn (array $vinculo) => array_values($vinculo))->all()
        );

        return self::SUCCESS;
    }
}
