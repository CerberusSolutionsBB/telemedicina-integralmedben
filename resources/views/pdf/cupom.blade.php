<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cupom {{ $cupom->codigo }}</title>
    <style>
        @page { margin: 40px 0 48px; } /* rodapé desenhado pelo RodapePdf na margem inferior */
        /* Sem reset universal (*): no dompdf ele zera também a margem da página. */
        html, body, h1, h2, p, div { margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.45; }
        table { border-collapse: collapse; width: 100%; }

        .voucher { margin: 0 48px; border: 1px solid #cbd5e1; border-radius: 14px; overflow: hidden; }

        .topo { background: #0e7490; color: #fff; padding: 22px 28px; }
        .topo td { vertical-align: middle; }
        .topo .logo { width: 64px; }
        .topo .logo-box { width: 52px; height: 52px; background: #fff; border-radius: 10px; text-align: center; }
        .topo .logo-box img { max-width: 44px; max-height: 44px; margin-top: 4px; }
        .topo .rotulo { font-size: 9px; letter-spacing: 1.4px; text-transform: uppercase; color: #cffafe; }
        .topo h1 { font-size: 22px; font-weight: bold; margin-top: 2px; }

        .corpo { padding: 28px; text-align: center; }
        .desconto { font-size: 40px; font-weight: bold; color: #0e7490; letter-spacing: 1px; }
        .instrucao { margin-top: 6px; font-size: 11px; color: #64748b; }
        .codigo { margin: 18px auto 0; width: 78%; border: 2px dashed #0e7490; border-radius: 10px; padding: 16px 10px;
            background: #ecfeff; }
        .codigo .rotulo { font-size: 9px; letter-spacing: 1.4px; text-transform: uppercase; color: #0e7490; }
        .codigo .valor { font-family: 'Courier', monospace; font-size: 30px; font-weight: bold; letter-spacing: 4px; color: #0f172a;
            margin-top: 4px; }
        .validade { margin-top: 16px; font-size: 12px; font-weight: bold; color: #0f172a; }

        .secao { border-top: 1px solid #e2e8f0; padding: 18px 28px; }
        .secao h2 { font-size: 10px; letter-spacing: 1.2px; text-transform: uppercase; color: #0e7490; margin-bottom: 8px; }
        .secao li { margin-bottom: 4px; }
        ul, ol { margin: 0; padding-left: 16px; }

        .status { margin: 0 48px 14px; padding: 10px 14px; border-radius: 8px; background: #fef2f2; color: #b91c1c;
            font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    @if ($cupom->situacao() !== \App\Models\Cupom::STATUS_ATIVO && $cupom->situacao() !== 'agendado')
        {{-- Aviso para não distribuir um cupom que não vale mais. --}}
        <div class="status">Atenção: este cupom está {{ $cupom->situacao() }} e não pode ser usado.</div>
    @endif

    <div class="voucher">
        <div class="topo">
            <table>
                <tr>
                    @if ($logoBase64)
                        <td class="logo"><div class="logo-box"><img src="{{ $logoBase64 }}" alt=""></div></td>
                    @endif
                    <td>
                        <div class="rotulo">{{ $parceiro ?? 'Válido em todos os parceiros' }}</div>
                        <h1>Cupom de desconto</h1>
                    </td>
                </tr>
            </table>
        </div>

        <div class="corpo">
            <div class="desconto">{{ $desconto }}</div>
            <p class="instrucao">na adesão ao plano de telemedicina</p>

            <div class="codigo">
                <div class="rotulo">Seu código</div>
                <div class="valor">{{ $cupom->codigo }}</div>
            </div>

            <p class="validade">{{ $validade }}</p>
        </div>

        <div class="secao">
            <h2>Condições</h2>
            <ul>
                @foreach ($condicoes as $condicao)
                    <li>{{ $condicao }}</li>
                @endforeach
            </ul>
        </div>

        <div class="secao">
            <h2>Como usar</h2>
            <ol>
                <li>Informe o código <strong>{{ $cupom->codigo }}</strong> no campo "Cupom" do cadastro ou do formulário de adesão.</li>
                <li>Clique em "Aplicar" para conferir o desconto antes de enviar.</li>
                <li>O desconto é aplicado sobre o valor do plano escolhido.</li>
            </ol>
        </div>
    </div>
</body>
</html>
