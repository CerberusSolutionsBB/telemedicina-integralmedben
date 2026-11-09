{{--
    Voucher de cupom (cupom de venda e cupom de benefício). Recebe os textos prontos:
    $rotulo, $titulo, $desconto, $textoDesconto, $codigo, $validade, $condicoes (lista),
    $comoUsar (lista; ":codigo" vira o código em negrito), $aviso (ou null),
    $logoBase64 (logomarca da página do parceiro tenant).
    Opcionais (cupom de benefício): $nome (título do cupom), $tipoDesconto, $infos (rótulo => valor,
    substitui a validade na lateral) e $locais (convênio/serviço com descrição).
--}}
@php
    $nome = $nome ?? null;
    $tipoDesconto = $tipoDesconto ?? null;
    $infos = $infos ?? ['Validade' => $validade];
    $locais = $locais ?? [];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cupom {{ $codigo }}</title>
    <style>
        @page { margin: 40px 0 48px; } /* rodapé desenhado pelo RodapePdf na margem inferior */
        /* Sem reset universal (*): no dompdf ele zera também a margem da página. */
        html, body, h1, h2, p, div { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.5; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }

        .voucher { margin: 0 48px; border: 1px solid #cbd5e1; border-radius: 14px; overflow: hidden; }

        .topo { margin: 0 32px; padding: 24px 0 18px; border-bottom: 2px solid #0e7490; }
        .topo td { vertical-align: middle; }
        .topo img { max-width: 170px; max-height: 72px; }
        .topo .identificacao { text-align: right; }
        .topo .sem-logo { text-align: left; }
        .topo h1 { font-size: 20px; font-weight: bold; line-height: 1.2; color: #0e7490; }
        .topo .rotulo { margin-top: 3px; font-size: 12px; color: #475569; }

        .legenda { font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #0e7490; }

        .oferta { padding: 24px 32px 26px; }
        .oferta .principal { width: 62%; padding-right: 24px; vertical-align: middle; }
        .nome { font-size: 15px; font-weight: bold; color: #0f172a; }
        .desconto { margin-top: 2px; font-size: 46px; font-weight: bold; line-height: 1.1; color: #0e7490; }
        .tipo-desconto { display: inline-block; margin-top: 6px; padding: 2px 9px; border-radius: 9px; background: #ecfeff;
            font-size: 10px; font-weight: bold; color: #0e7490; }
        .instrucao { margin-top: 6px; font-size: 13px; color: #334155; }

        .oferta .lateral { width: 38%; padding: 4px 0 4px 20px; border-left: 1px solid #e2e8f0; }
        .info { margin-bottom: 10px; }
        .info:last-child { margin-bottom: 0; }
        .info .valor-info { font-size: 13px; font-weight: bold; color: #0f172a; }
        .situacao-ativo { color: #15803d !important; }
        .situacao-alerta { color: #b91c1c !important; }

        /* Faixa com serrilha tracejada: é o que o cliente apresenta. */
        .codigo { padding: 18px 16px 20px; background: #f0f9fb; border-top: 1px dashed #0e7490; border-bottom: 1px dashed #0e7490;
            text-align: center; }
        .codigo .valor { margin-top: 4px; font-family: 'Courier', monospace; font-size: 30px; font-weight: bold; line-height: 1.2;
            letter-spacing: 4px; color: #0f172a; }
        /* A coluna aceita até 50 caracteres sem quebra: acima de 24 o tamanho normal estoura a largura. */
        .codigo .valor-longo { font-size: 18px; letter-spacing: 1px; }

        .bloco { padding: 22px 32px 0; }
        .bloco:last-child { padding-bottom: 26px; }
        .bloco h2 { margin-bottom: 10px; font-size: 10px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #0e7490; }

        .locais td.cartao-celula { width: 50%; }
        .locais td.cartao-celula + td.cartao-celula { padding-left: 12px; }
        .cartao { padding: 12px 14px; border: 1px solid #e2e8f0; border-left: 3px solid #0e7490; border-radius: 8px; background: #fbfdfe; }
        .cartao .tipo { font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #64748b; }
        .cartao .titulo-local { margin-top: 1px; font-size: 14px; font-weight: bold; color: #0f172a; }
        .cartao .texto { margin-top: 4px; color: #475569; }

        .detalhes .coluna { width: 50%; }
        .detalhes .coluna + .coluna { padding-left: 24px; }
        .detalhes li { margin-bottom: 5px; }
        ul, ol { margin: 0; padding-left: 16px; }

        .status { margin: 0 48px 14px; padding: 10px 14px; border-radius: 8px; background: #fef2f2; color: #b91c1c;
            font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    @if ($aviso)
        {{-- Aviso para não distribuir um cupom que não vale mais. --}}
        <div class="status">{{ $aviso }}</div>
    @endif

    <div class="voucher">
        <div class="topo">
            <table>
                <tr>
                    @if ($logoBase64)
                        <td><img src="{{ $logoBase64 }}" alt="Logomarca do parceiro"></td>
                    @endif
                    <td class="identificacao {{ $logoBase64 ? '' : 'sem-logo' }}">
                        <h1>{{ $titulo }}</h1>
                        <div class="rotulo">{{ $rotulo }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="oferta">
            <table>
                <tr>
                    <td class="principal">
                        @if ($nome)
                            <div class="nome">{{ $nome }}</div>
                        @endif
                        <div class="desconto">{{ $desconto }}</div>
                        @if ($tipoDesconto)
                            <span class="tipo-desconto">{{ $tipoDesconto }}</span>
                        @endif
                        <p class="instrucao">{{ $textoDesconto }}</p>
                    </td>
                    <td class="lateral">
                        @foreach ($infos as $rotuloInfo => $valorInfo)
                            <div class="info">
                                <div class="legenda">{{ $rotuloInfo }}</div>
                                <div @class([
                                    'valor-info',
                                    'situacao-ativo' => $rotuloInfo === 'Situação' && $valorInfo === 'Ativo',
                                    'situacao-alerta' => $rotuloInfo === 'Situação' && $valorInfo !== 'Ativo',
                                ])>{{ $valorInfo }}</div>
                            </div>
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>

        <div class="codigo">
            <div class="legenda">Seu código</div>
            <div class="valor {{ strlen($codigo) > 24 ? 'valor-longo' : '' }}">{{ $codigo }}</div>
        </div>

        @if ($locais)
            <div class="bloco">
                <h2>Onde usar</h2>
                <table class="locais">
                    <tr>
                        @foreach ($locais as $local)
                            <td class="cartao-celula">
                                <div class="cartao">
                                    <div class="tipo">{{ $local['tipo'] }}</div>
                                    <div class="titulo-local">{{ $local['titulo'] }}</div>
                                    @if ($local['descricao'])
                                        <p class="texto">{!! nl2br(e($local['descricao'])) !!}</p>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </div>
        @endif

        <div class="bloco detalhes">
            <table>
                <tr>
                    @if ($condicoes)
                        <td class="coluna">
                            <h2>Condições</h2>
                            <ul>
                                @foreach ($condicoes as $condicao)
                                    <li>{{ $condicao }}</li>
                                @endforeach
                            </ul>
                        </td>
                    @endif
                    <td class="coluna">
                        <h2>Como usar</h2>
                        <ol>
                            {{-- ":codigo" no texto vira o código em negrito. --}}
                            @foreach ($comoUsar as $passo)
                                <li>{!! str_replace(':codigo', '<strong>'.e($codigo).'</strong>', e($passo)) !!}</li>
                            @endforeach
                        </ol>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
