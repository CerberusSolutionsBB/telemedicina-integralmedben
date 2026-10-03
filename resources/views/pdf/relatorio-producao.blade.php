<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Produção por usuário</title>
    <style>
        @page { margin: 24px 0 48px; } /* rodapé desenhado pelo RodapePdf na margem inferior */
        html, body, h1, h2, p, div { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 9px; line-height: 1.35; }
        table { border-collapse: collapse; width: 100%; }
        .conteudo { padding: 0 32px; }

        .topo { background: #0e7490; color: #fff; padding: 18px 32px; }
        .topo td { vertical-align: middle; }
        .topo .parceiro { font-size: 8px; letter-spacing: 1.2px; text-transform: uppercase; color: #cffafe; }
        .topo h1 { font-size: 18px; font-weight: bold; margin-top: 2px; }
        .topo .periodo { font-size: 9px; color: #cffafe; margin-top: 2px; }
        .topo .meta { text-align: right; font-size: 8px; color: #cffafe; }
        .topo .meta strong { display: block; font-size: 15px; color: #fff; }

        .filtros { margin-top: 12px; font-size: 8px; color: #475569; }
        .filtro { display: inline-block; padding: 2px 7px; border-radius: 9px; background: #ecfeff;
            border: 1px solid #a5f3fc; color: #0e7490; font-weight: bold; margin-right: 4px; }

        h2 { font-size: 11px; font-weight: bold; color: #0f172a; margin: 14px 0 6px; }

        .resumo { margin-top: 12px; }
        .resumo > tbody > tr > td { vertical-align: top; padding-right: 10px; }
        .resumo > tbody > tr > td:last-child { padding-right: 0; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 11px; }
        .card .rotulo { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; }
        .card .numero { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .card.destaque { background: #ecfeff; border-color: #a5f3fc; }
        .lista td { padding: 2px 0; font-size: 8.5px; }
        .lista .qtd { text-align: right; font-weight: bold; color: #0f172a; width: 46px; }
        .barra { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 1px; }
        .barra div { height: 4px; background: #0e7490; border-radius: 2px; }

        .tabela th { background: #0f172a; color: #fff; text-align: left; font-size: 7px; text-transform: uppercase;
            letter-spacing: 0.4px; padding: 6px 5px; white-space: nowrap; }
        .tabela td { padding: 5px 5px; border-bottom: 1px solid #f1f5f9; vertical-align: top; font-size: 8.5px; }
        .tabela tbody tr:nth-child(even) td { background: #f8fafc; }
        .tabela .pos { color: #64748b; text-align: center; width: 24px; }
        .tabela .nome { font-weight: bold; color: #0f172a; }
        .tabela .num { text-align: right; white-space: nowrap; }
        .vazio { color: #94a3b8; }
        .nenhum { padding: 24px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 8px; margin-top: 14px; }

        /* Evolução: barras verticais */
        .evolucao td { vertical-align: bottom; text-align: center; padding: 0 1px; font-size: 6.5px; color: #64748b; }
        .evolucao .coluna { background: #0e7490; border-radius: 2px 2px 0 0; margin: 0 auto; width: 70%; }
        .evolucao .valor { font-size: 6.5px; color: #0f172a; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $maiorEvolucao = max(1, ...array_column($evolucao['itens'], 'total'));
        $pct = fn ($valor) => number_format($valor, 1, ',', '.').'%';
    @endphp

    <div class="topo">
        <table>
            <tr>
                <td>
                    <div class="parceiro">{{ $parceiro }}</div>
                    <h1>Produção por usuário</h1>
                    <div class="periodo">Registros de beneficiários em planos · {{ $periodo['de'] }} a {{ $periodo['ate'] }}</div>
                </td>
                <td class="meta">
                    Registros no período
                    <strong>{{ $total }}</strong>
                    Emitido em {{ $gerado_em }}
                </td>
            </tr>
        </table>
    </div>

    <div class="conteudo">
        <div class="filtros">
            @if($filtros)
                Filtros aplicados:
                @foreach($filtros as $filtro)<span class="filtro">{{ $filtro }}</span>@endforeach
            @else
                Sem filtros: todos os usuários, planos e origens do período.
            @endif
        </div>

        @if($total)
            <table class="resumo">
                <tr>
                    <td style="width: 16%;">
                        <div class="card destaque">
                            <div class="rotulo">Total de registros</div>
                            <div class="numero">{{ $total }}</div>
                        </div>
                        <div class="card" style="margin-top: 8px;">
                            <div class="rotulo">Usuários com registros</div>
                            <div class="numero">{{ count($usuarios) }}</div>
                        </div>
                    </td>
                    <td style="width: 28%;">
                        <div class="card">
                            <div class="rotulo">Por plano</div>
                            <table class="lista">
                                @foreach($planos as $item)
                                    <tr>
                                        <td>{{ $item['label'] }}
                                            <div class="barra"><div style="width: {{ $item['percentual'] }}%;"></div></div>
                                        </td>
                                        <td class="qtd">{{ $item['total'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </td>
                    <td style="width: 24%;">
                        <div class="card">
                            <div class="rotulo">Por origem</div>
                            <table class="lista">
                                @foreach($origens as $item)
                                    <tr>
                                        <td>{{ $item['label'] }}
                                            <div class="barra"><div style="width: {{ $item['percentual'] }}%;"></div></div>
                                        </td>
                                        <td class="qtd">{{ $item['total'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </td>
                    <td style="width: 32%;">
                        <div class="card">
                            <div class="rotulo">Evolução {{ $evolucao['tipo'] === 'dia' ? 'por dia' : 'por semana' }}</div>
                            <table class="evolucao" style="margin-top: 6px;">
                                <tr>
                                    @foreach($evolucao['itens'] as $item)
                                        <td>
                                            @if($item['total'])<div class="valor">{{ $item['total'] }}</div>@endif
                                            <div class="coluna" style="height: {{ max(1, round($item['total'] / $maiorEvolucao * 48)) }}px; {{ $item['total'] ? '' : 'background: #e2e8f0;' }}"></div>
                                        </td>
                                    @endforeach
                                </tr>
                                @if(count($evolucao['itens']) <= 16)
                                    <tr>
                                        @foreach($evolucao['itens'] as $item)
                                            <td>{{ str_replace('Semana de ', '', $item['label']) }}</td>
                                        @endforeach
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            <h2>Ranking por usuário</h2>
            <table class="tabela">
                <thead>
                    <tr>
                        <th style="text-align: center;">#</th>
                        <th>Usuário</th>
                        <th>Perfis</th>
                        @foreach($planosColunas as $label)
                            <th class="num" style="text-align: right;">{{ $label }}</th>
                        @endforeach
                        <th class="num" style="text-align: right;">Total</th>
                        <th class="num" style="text-align: right;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usuarios as $u)
                        <tr>
                            <td class="pos">{{ $loop->iteration }}º</td>
                            <td class="nome">{{ $u['usuario'] }}</td>
                            <td>{!! $u['perfis'] ? e($u['perfis']) : '<span class="vazio">—</span>' !!}</td>
                            @foreach(array_keys($planosColunas) as $codigo)
                                <td class="num">{{ $u['planos'][$codigo] ?? 0 }}</td>
                            @endforeach
                            <td class="num nome">{{ $u['total'] }}</td>
                            <td class="num">{{ $pct($u['percentual']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @foreach($paginas as $pagina)
                {{-- Lista detalhada sempre em página nova, uma tabela por página. --}}
                <div style="page-break-before: always;">
                    @if($loop->first)<h2 style="margin-top: 0;">Registros do período</h2>@endif
                    <table class="tabela">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Data</th>
                                <th>Usuário</th>
                                <th>Beneficiário</th>
                                <th style="width: 120px;">Plano</th>
                                <th style="width: 100px;">Origem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pagina as $r)
                                <tr>
                                    <td>{{ $r['data'] }}</td>
                                    <td>{{ $r['usuario'] }}</td>
                                    <td class="nome">{{ $r['paciente'] }}</td>
                                    <td>{{ $r['plano'] }}</td>
                                    <td>{{ $r['origem'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            <div class="nenhum">Nenhum registro de beneficiário em plano no período{{ $filtros ? ' com os filtros aplicados' : '' }}.</div>
        @endif
    </div>
</body>
</html>
