@extends('emails.layout', ['preview' => "Use o link para criar uma nova senha. Ele vale por {$expira} minutos."])

@section('titulo', 'Redefinição de senha')

@section('conteudo')
    <h1 style="margin:0 0 16px; font-size:22px; line-height:30px; font-weight:700; color:#111827;">
        Redefinição de senha
    </h1>

    <p style="margin:0 0 12px; font-size:16px; line-height:24px; color:#374151;">Olá, {{ $nome }}!</p>

    <p style="margin:0 0 24px; font-size:16px; line-height:24px; color:#374151;">
        Recebemos um pedido para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha.
    </p>

    {{-- Botão: cyan-600 para garantir contraste com o texto branco --}}
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
        <tr>
            <td align="center" style="border-radius:8px; background-color:#0891b2;">
                <a href="{{ $url }}" target="_blank"
                    style="display:inline-block; padding:14px 28px; font-size:16px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:8px;">
                    Redefinir senha
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="background-color:#ecfeff; border:1px solid #a5f3fc; border-radius:8px; padding:12px 16px; font-size:14px; line-height:20px; color:#155e75;">
                &#9200; O link vale por <strong>{{ $expira }} minutos</strong> e só pode ser usado uma vez.
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; font-size:14px; line-height:22px; color:#4b5563;">
        Não foi você? Ignore este e-mail. Sua senha continua a mesma.
    </p>

    <p style="margin:0; padding-top:16px; border-top:1px solid #e5e7eb; font-size:12px; line-height:18px; color:#6b7280;">
        O botão não funciona? Copie e cole este endereço no navegador:<br>
        <a href="{{ $url }}" style="color:#0e7490; word-break:break-all;">{{ $url }}</a>
    </p>
@endsection
