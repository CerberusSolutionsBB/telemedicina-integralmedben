<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório geral de beneficiários</title>
    <style>
        /* Margem de página: dá respiro às páginas 2+ (cabeçalho da tabela repetido). */
        @page { margin: 24px 0 48px; } /* rodapé desenhado pelo RodapePdf na margem inferior */
        /* Sem reset universal (*): no dompdf ele zera também a margem da página. */
        html, body, h1, h2, p, div { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 9px; line-height: 1.35; }
        table { border-collapse: collapse; width: 100%; }
        .conteudo { padding: 0 32px; }

        /* Cabeçalho em faixa (mesma identidade da ficha do beneficiário) */
        .topo { background: #0e7490; color: #fff; padding: 18px 32px; }
        .topo td { vertical-align: middle; }
        .topo .logo { width: 58px; }
        .topo .logo-box { width: 46px; height: 46px; background: #fff; border-radius: 9px; text-align: center; }
        .topo .logo-box img { max-width: 40px; max-height: 40px; margin-top: 3px; }
        .topo .parceiro { font-size: 8px; letter-spacing: 1.2px; text-transform: uppercase; color: #cffafe; }
        .topo h1 { font-size: 18px; font-weight: bold; margin-top: 2px; }
        .topo .meta { text-align: right; font-size: 8px; color: #cffafe; }
        .topo .meta strong { display: block; font-size: 15px; color: #fff; }

        .filtros { margin-top: 12px; font-size: 8px; color: #475569; }
        .filtro { display: inline-block; padding: 2px 7px; border-radius: 9px; background: #ecfeff;
            border: 1px solid #a5f3fc; color: #0e7490; font-weight: bold; margin-right: 4px; }

        /* Cards de resumo */
        .resumo { margin: 12px 0 14px; }
        .resumo > tbody > tr > td { vertical-align: top; padding-right: 10px; }
        .resumo > tbody > tr > td:last-child { padding-right: 0; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 11px; }
        .card .rotulo { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; }
        .card .numero { font-size: 20px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .card .numero.verde { color: #15803d; }
        .card .numero.vermelho { color: #b91c1c; }
        .card.destaque { background: #ecfeff; border-color: #a5f3fc; }
        .lista td { padding: 2px 0; font-size: 8.5px; }
        .lista .qtd { text-align: right; font-weight: bold; color: #0f172a; width: 30px; }
        .barra { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 1px; }
        .barra div { height: 4px; background: #0e7490; border-radius: 2px; }

        /* Tabela */
        .tabela th { background: #0f172a; color: #fff; text-align: left; font-size: 7px; text-transform: uppercase;
            letter-spacing: 0.4px; padding: 6px 5px; white-space: nowrap; }
        .tabela td { padding: 5px 5px; border-bottom: 1px solid #f1f5f9; vertical-align: top; white-space: nowrap;
            font-size: 8.5px; }
        .tabela .data { white-space: nowrap; }
        .tabela tbody tr:nth-child(even) td { background: #f8fafc; }
        .tabela .num { color: #64748b; text-align: center; }
        .tabela .nome { font-weight: bold; color: #0f172a; }
        .tabela .mono { font-family: DejaVu Sans Mono, monospace; font-size: 7.5px; }
        .vazio { color: #94a3b8; }
        .selo { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
        .selo.ativo { background: #dcfce7; color: #166534; }
        .selo.inativo { background: #fee2e2; color: #991b1b; }
        .selo.plano { background: #ecfeff; color: #0e7490; }
        .nenhum { padding: 24px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 8px; }

    </style>
</head>
<body>
    @php
        $porcentagem = fn (int $valor) => $resumo['total'] ? round($valor / $resumo['total'] * 100) : 0;
        $traco = '<span class="vazio">—</span>';
    @endphp


    <div class="topo">
        <table>
            <tr>
                @if($logoBase64)
                    <td class="logo"><div class="logo-box"><img src="{{ $logoBase64 }}" alt="Logo"></div></td>
                @endif
                <td>
                    <div class="parceiro">{{ $parceiro }}</div>
                    <h1>Relatório Geral de Beneficiários</h1>
                </td>
                <td class="meta">
                    Beneficiários no relatório
                    <strong>{{ $resumo['total'] }}</strong>
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
                Sem filtros: todos os beneficiários cadastrados.
            @endif
        </div>

        <table class="resumo">
            <tr>
                <td style="width: 15%;">
                    <div class="card destaque">
                        <div class="rotulo">Total</div>
                        <div class="numero">{{ $resumo['total'] }}</div>
                    </div>
                </td>
                <td style="width: 15%;">
                    <div class="card">
                        <div class="rotulo">Ativos</div>
                        <div class="numero verde">{{ $resumo['ativos'] }}</div>
                    </div>
                    <div class="card" style="margin-top: 8px;">
                        <div class="rotulo">Inativos</div>
                        <div class="numero vermelho">{{ $resumo['inativos'] }}</div>
                    </div>
                </td>
                <td style="width: 35%;">
                    <div class="card">
                        <div class="rotulo">Por plano</div>
                        <table class="lista">
                            @foreach($resumo['planos'] as $item)
                                <tr>
                                    <td>{{ $item['label'] }}
                                        <div class="barra"><div style="width: {{ $porcentagem($item['total']) }}%;"></div></div>
                                    </td>
                                    <td class="qtd">{{ $item['total'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </td>
                <td style="width: 35%;">
                    <div class="card">
                        <div class="rotulo">Por origem do cadastro</div>
                        <table class="lista">
                            @forelse($resumo['origens'] as $item)
                                <tr>
                                    <td>{{ $item['label'] }}
                                        <div class="barra"><div style="width: {{ $porcentagem($item['total']) }}%;"></div></div>
                                    </td>
                                    <td class="qtd">{{ $item['total'] }}</td>
                                </tr>
                            @empty
                                <tr><td class="vazio">Sem dados.</td></tr>
                            @endforelse
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        @if(count($linhas))
            @foreach($paginas as $pagina)
                {{-- Uma tabela por página (paginação feita no service). --}}
                <table class="tabela" @if(! $loop->first) style="page-break-before: always;" @endif>
                    <thead>
                    <tr>
                        <th style="width: 30px; text-align: center;">Nº</th>
                        <th>Nome</th>
                        <th style="width: 82px;">CPF</th>
                        <th style="width: 84px;">Nascimento</th>
                        <th style="width: 52px;">Sexo</th>
                        <th style="width: 82px;">Celular</th>
                        <th>E-mail</th>
                        <th style="width: 92px;">Plano</th>
                        <th style="width: 82px;">Origem</th>
                        <th style="width: 44px;">Status</th>
                        <th style="width: 76px;">Cadastro</th>
                    </tr>
                </thead>
                    <tbody>
                    @foreach($pagina as $linha)
                        <tr>
                            <td class="num">{{ $linha['id'] }}</td>
                            <td class="nome">{!! filled($linha['nome']) ? e($linha['nome']) : $traco !!}</td>
                            <td class="mono">{!! $linha['cpf'] ? e($linha['cpf']) : $traco !!}</td>
                            <td>
                                @if($linha['nascimento'])
                                    {{ $linha['nascimento'] }}@if($linha['idade'] !== null) <span class="vazio">({{ $linha['idade'] }})</span>@endif
                                @else{!! $traco !!}@endif
                            </td>
                            <td>{!! $linha['sexo'] ? e($linha['sexo']) : $traco !!}</td>
                            <td class="mono">{!! $linha['celular'] ? e($linha['celular']) : $traco !!}</td>
                            <td>{!! $linha['email'] ? e($linha['email']) : $traco !!}</td>
                            <td>{!! $linha['plano'] ? '<span class="selo plano">'.e($linha['plano']).'</span>' : $traco !!}</td>
                            <td>{!! $linha['origem'] ? e($linha['origem']) : $traco !!}</td>
                            <td><span class="selo {{ $linha['ativo'] ? 'ativo' : 'inativo' }}">{{ $linha['ativo'] ? 'Ativo' : 'Inativo' }}</span></td>
                            <td class="data">{{ $linha['cadastro'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            @endforeach
        @else
            <div class="nenhum">Nenhum beneficiário encontrado{{ $filtros ? ' com os filtros aplicados' : '' }}.</div>
        @endif
    </div>
</body>
</html>
