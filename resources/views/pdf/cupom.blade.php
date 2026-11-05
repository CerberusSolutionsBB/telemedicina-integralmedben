{{--
    Voucher de cupom (cupom de venda e cupom de benefício). Recebe os textos prontos:
    $rotulo, $titulo, $desconto, $textoDesconto, $codigo, $validade, $condicoes (lista),
    $comoUsar (lista; ":codigo" vira o código em negrito), $aviso (ou null),
    $logoBase64 (logomarca da página do parceiro tenant).
--}}
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

        .voucher { margin: 0 48px; border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden; }

        .topo { margin: 0 32px; padding: 24px 0 18px; border-bottom: 2px solid #0e7490; }
        .topo td { vertical-align: middle; }
        .topo img { max-width: 170px; max-height: 72px; }
        .topo .identificacao { text-align: right; }
        .topo .sem-logo { text-align: left; }
        .topo h1 { font-size: 20px; font-weight: bold; line-height: 1.2; color: #0e7490; }
        .topo .rotulo { margin-top: 3px; font-size: 12px; color: #475569; }

        .oferta { padding: 26px 32px 28px; }
        .oferta td { vertical-align: middle; }
        .oferta .principal { width: 64%; padding-right: 24px; }
        .desconto { font-size: 46px; font-weight: bold; line-height: 1.1; color: #0e7490; }
        .instrucao { margin-top: 6px; font-size: 14px; color: #334155; }
        .oferta .prazo { width: 36%; padding-left: 20px; border-left: 1px solid #e2e8f0; }
        .legenda { font-size: 9px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #0e7490; }
        .validade { margin-top: 4px; font-size: 13px; font-weight: bold; color: #0f172a; }

        /* Faixa com serrilha tracejada: é o que o cliente apresenta. */
        .codigo { padding: 18px 16px 20px; background: #f0f9fb; border-top: 1px dashed #0e7490; border-bottom: 1px dashed #0e7490;
            text-align: center; }
        .codigo .valor { margin-top: 4px; font-family: 'Courier', monospace; font-size: 30px; font-weight: bold; line-height: 1.2;
            letter-spacing: 4px; color: #0f172a; }
        /* A coluna aceita até 50 caracteres sem quebra: acima de 24 o tamanho normal estoura a largura. */
        .codigo .valor-longo { font-size: 18px; letter-spacing: 1px; }

        .detalhes { padding: 22px 32px 26px; }
        .detalhes .coluna { width: 50%; }
        .detalhes .coluna + .coluna { padding-left: 24px; }
        .detalhes h2 { margin-bottom: 8px; font-size: 10px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #0e7490; }
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
                        <div class="desconto">{{ $desconto }}</div>
                        <p class="instrucao">{{ $textoDesconto }}</p>
                    </td>
                    <td class="prazo">
                        <div class="legenda">Validade</div>
                        <p class="validade">{{ $validade }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="codigo">
            <div class="legenda">Seu código</div>
            <div class="valor {{ strlen($codigo) > 24 ? 'valor-longo' : '' }}">{{ $codigo }}</div>
        </div>

        <div class="detalhes">
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
