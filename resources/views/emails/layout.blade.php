{{--
    Layout base dos e-mails. Só tabelas e estilos inline: Gmail/Outlook ignoram <style> e flex/grid.
    Variáveis: $remetente (MEDBEM ou nome do parceiro), $logo (caminho do arquivo; padrão MEDBEM), $preview (texto da prévia).
    O logo vai anexado inline (cid:) para aparecer mesmo sem servidor público; sem $message (prévia/teste) vira data URI.
--}}
@php
    $logoArquivo = $logo ?? public_path('images/logo-med-ben-email.jpg');
    $logoSrc = isset($message)
        ? $message->embed($logoArquivo)
        : 'data:'.mime_content_type($logoArquivo).';base64,'.base64_encode(file_get_contents($logoArquivo));
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('titulo', $remetente)</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Figtree, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#111827;">
    {{-- Texto que aparece na prévia da caixa de entrada --}}
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">{{ $preview ?? '' }}</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;">
                    {{-- Cabeçalho --}}
                    <tr>
                        <td align="center" style="padding-bottom:20px;">
                            {{-- Altura fixa: logos de parceiro podem ser quadrados ou altos --}}
                            <img src="{{ $logoSrc }}" alt="{{ $remetente }}" height="64"
                                style="display:block; margin:0 auto; height:64px; width:auto; max-width:100%; border:0; outline:none; text-decoration:none;">
                        </td>
                    </tr>

                    {{-- Cartão --}}
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #e5e7eb; border-top:4px solid #22B9CE; border-radius:12px; padding:32px 28px;">
                            @yield('conteudo')
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td align="center" style="padding:20px 8px 0; font-size:12px; line-height:18px; color:#6b7280;">
                            Este é um e-mail automático de {{ $remetente }}. Não responda esta mensagem.<br>
                            &copy; {{ date('Y') }} MEDBEM
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
