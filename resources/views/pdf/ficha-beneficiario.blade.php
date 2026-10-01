@php
    // Seções de rótulo/valor (3 colunas). Seção sem nenhum valor vira uma linha "não informado".
    $secoes = [
        ['titulo' => 'Identificação', 'campos' => $ficha['identificacao'], 'vazio' => 'Dados de identificação não informados.'],
        ['titulo' => 'Contato', 'campos' => $ficha['contato'], 'vazio' => 'Nenhum contato informado.'],
        ['titulo' => 'Endereço', 'campos' => $ficha['endereco'], 'vazio' => 'Endereço não informado.'],
    ];
    $preenchida = fn (array $campos) => collect($campos)->filter(fn ($valor) => filled($valor))->isNotEmpty();
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ficha do Beneficiário #{{ $ficha['id'] }}</title>
    <style>
        @page { margin: 28px 0 48px; } /* rodapé desenhado pelo RodapePdf na margem inferior */
        /* Sem reset universal (*): no dompdf ele zera também a margem da página. */
        html, body, h1, h2, p, div { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 10.5px; line-height: 1.4; }
        table { border-collapse: collapse; width: 100%; }
        .conteudo { padding: 0 40px; }

        /* Cabeçalho em faixa */
        .topo { background: #0e7490; color: #fff; padding: 22px 40px; }
        .topo td { vertical-align: middle; }
        .topo .logo { width: 64px; }
        .topo .logo-box { width: 52px; height: 52px; background: #fff; border-radius: 10px; text-align: center; }
        .topo .logo-box img { max-width: 44px; max-height: 44px; margin-top: 4px; }
        .topo .parceiro { font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; color: #cffafe; }
        .topo h1 { font-size: 20px; font-weight: bold; margin-top: 2px; }
        .topo .meta { text-align: right; font-size: 9px; color: #cffafe; }
        .topo .meta strong { display: block; font-size: 16px; color: #fff; }

        /* Cartão do beneficiário */
        .perfil { margin: 22px 0 18px; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 18px; }
        .perfil td { vertical-align: middle; }
        .avatar { width: 54px; }
        .avatar div { width: 46px; height: 33px; padding-top: 13px; border-radius: 23px; background: #cffafe;
            color: #0e7490; font-size: 17px; font-weight: bold; text-align: center; line-height: 1; }
        .perfil .nome { font-size: 17px; font-weight: bold; color: #0f172a; }
        .etiquetas { margin-top: 6px; }
        .etiqueta { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold;
            margin-right: 4px; border: 1px solid #e2e8f0; color: #334155; background: #f8fafc; }
        .etiqueta.ativo { background: #dcfce7; border-color: #bbf7d0; color: #166534; }
        .etiqueta.inativo { background: #fee2e2; border-color: #fecaca; color: #991b1b; }
        .etiqueta.siprov { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
        .etiqueta.interno { background: #f3e8ff; border-color: #e9d5ff; color: #6b21a8; }

        /* Seções */
        .secao { margin-bottom: 14px; border: 1px solid #e2e8f0; border-radius: 8px; page-break-inside: avoid; }
        .secao-titulo { background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-left: 4px solid #0e7490;
            padding: 7px 12px; font-size: 10px; font-weight: bold; letter-spacing: 0.8px; text-transform: uppercase;
            color: #0e7490; }
        .secao-corpo { padding: 10px 12px 4px; }
        .campos td { width: 33.33%; vertical-align: top; padding: 0 10px 10px 0; }
        .rotulo { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .valor { font-size: 11px; color: #0f172a; }
        .vazio { color: #94a3b8; font-style: italic; }
        .linha-vazia { padding: 2px 0 8px; }

        /* Plano em destaque */
        .plano-nome { font-size: 13px; font-weight: bold; color: #0f172a; }

        /* Respostas */
        .respostas th { background: #f1f5f9; text-align: left; font-size: 8px; text-transform: uppercase;
            letter-spacing: 0.5px; color: #475569; padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
        .respostas td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .respostas tr:nth-child(even) td { background: #fafafa; }
        .respostas .pergunta { color: #475569; width: 45%; }

    </style>
</head>
<body>

    <div class="topo">
        <table>
            <tr>
                @if($logoBase64)
                    <td class="logo"><div class="logo-box"><img src="{{ $logoBase64 }}" alt="Logo"></div></td>
                @endif
                <td>
                    <div class="parceiro">{{ $parceiro }}</div>
                    <h1>Ficha do Beneficiário</h1>
                </td>
                <td class="meta">
                    Nº do cadastro
                    <strong>#{{ $ficha['id'] }}</strong>
                    Emitida em {{ $ficha['gerado_em'] }}
                </td>
            </tr>
        </table>
    </div>

    <div class="conteudo">
        <div class="perfil">
            <table>
                <tr>
                    <td class="avatar"><div>{{ $ficha['iniciais'] }}</div></td>
                    <td>
                        <div class="nome">{{ $ficha['identificacao']['Nome'] ?: 'Beneficiário' }}</div>
                        <div class="etiquetas">
                            <span class="etiqueta">CPF {{ $ficha['identificacao']['CPF'] ?: 'não informado' }}</span>
                            <span class="etiqueta {{ $ficha['ativo'] ? 'ativo' : 'inativo' }}">{{ $ficha['ativo'] ? 'Ativo' : 'Inativo' }}</span>
                            @if($ficha['plano'])
                                <span class="etiqueta {{ $ficha['plano_siprov'] ? 'siprov' : 'interno' }}">{{ $ficha['plano']['Plano'] }}</span>
                            @else
                                <span class="etiqueta">Sem plano</span>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        @foreach($secoes as $secao)
            <div class="secao">
                <div class="secao-titulo">{{ $secao['titulo'] }}</div>
                <div class="secao-corpo">
                    @if($preenchida($secao['campos']))
                        <table class="campos">
                            @foreach(array_chunk($secao['campos'], 3, true) as $linha)
                                <tr>
                                    @foreach($linha as $rotulo => $valor)
                                        <td>
                                            <div class="rotulo">{{ $rotulo }}</div>
                                            <div class="valor {{ filled($valor) ? '' : 'vazio' }}">{{ filled($valor) ? $valor : 'Não informado' }}</div>
                                        </td>
                                    @endforeach
                                    @for($i = count($linha); $i < 3; $i++)<td></td>@endfor
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <div class="linha-vazia vazio">{{ $secao['vazio'] }}</div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="secao">
            <div class="secao-titulo">Plano e registro</div>
            <div class="secao-corpo">
                <table class="campos">
                    <tr>
                        <td>
                            <div class="rotulo">Plano</div>
                            @if($ficha['plano'])
                                <div class="plano-nome">{{ $ficha['plano']['Plano'] }}</div>
                            @else
                                <div class="valor vazio">Sem plano vinculado</div>
                            @endif
                        </td>
                        <td>
                            <div class="rotulo">Registrado por</div>
                            <div class="valor">{{ $ficha['plano']['Registrado por'] ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="rotulo">Registrado em</div>
                            <div class="valor">{{ $ficha['plano']['Registrado em'] ?? '—' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="rotulo">Origem do cadastro</div>
                            <div class="valor">{{ $ficha['cadastro']['Origem do cadastro'] ?? 'Não informado' }}</div>
                        </td>
                        <td>
                            <div class="rotulo">Criado por</div>
                            <div class="valor">{{ $ficha['cadastro']['Criado por'] }}</div>
                        </td>
                        <td>
                            <div class="rotulo">Criado em · Atualizado em</div>
                            <div class="valor">{{ $ficha['cadastro']['Criado em'] ?? '—' }} · {{ $ficha['cadastro']['Atualizado em'] ?? '—' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if(count($ficha['respostas']))
            <div class="secao" style="page-break-inside: auto;">
                <div class="secao-titulo">Respostas do formulário</div>
                <table class="respostas">
                    <thead>
                        <tr><th>Pergunta</th><th>Resposta</th></tr>
                    </thead>
                    <tbody>
                        @foreach($ficha['respostas'] as $item)
                            <tr>
                                <td class="pergunta">{{ $item['pergunta'] }}</td>
                                <td class="{{ filled($item['resposta']) ? 'valor' : 'vazio' }}">{{ $item['resposta'] ?? 'Não respondido' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</body>
</html>
