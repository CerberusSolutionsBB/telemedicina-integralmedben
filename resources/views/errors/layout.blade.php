<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('code') · @yield('title')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,800&display=swap" rel="stylesheet" />

    {{-- CSS inline: a página de erro não pode depender do build do Vite. --}}
    <style>
        :root {
            --fundo: #f1f7f8;
            --cartao: #ffffff;
            --borda: #e5edef;
            --texto: #111827;
            --suave: #4b5563;
            --fraco: #9ca3af;
            --marca: #0f7a85;
            --marca-hover: #0c6770;
            --marca-claro: rgba(15, 122, 133, 0.1);
            --destaque: #e11d2e;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --fundo: #0b1416;
                --cartao: #111c1f;
                --borda: #1f2f33;
                --texto: #f3f4f6;
                --suave: #cbd5e1;
                --fraco: #6b7280;
                --marca: #22b8cf;
                --marca-hover: #3cc7dc;
                --marca-claro: rgba(34, 184, 207, 0.12);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Figtree', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--fundo);
            color: var(--texto);
            -webkit-font-smoothing: antialiased;
        }

        .cartao {
            position: relative;
            width: 100%;
            max-width: 460px;
            overflow: hidden;
            padding: 40px 32px 28px;
            border: 1px solid var(--borda);
            border-radius: 20px;
            background: var(--cartao);
            box-shadow: 0 20px 45px rgba(15, 122, 133, 0.08);
            text-align: center;
            animation: entrar 0.45s ease both;
        }

        .cartao::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 6px;
            background: var(--marca);
        }

        .icone {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin: 0 auto 18px;
            border-radius: 18px;
            background: var(--marca-claro);
            color: var(--marca);
        }

        .icone svg {
            width: 30px;
            height: 30px;
        }

        .codigo {
            margin: 0;
            font-size: 64px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.02em;
            color: var(--marca);
        }

        h1 {
            margin: 12px 0 8px;
            font-size: 22px;
            font-weight: 800;
        }

        p {
            margin: 0 auto;
            max-width: 360px;
            font-size: 15px;
            line-height: 1.55;
            color: var(--suave);
        }

        .acoes {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-primario {
            background: var(--marca);
            color: #fff;
        }

        .btn-primario:hover {
            background: var(--marca-hover);
        }

        .btn-secundario {
            border-color: var(--borda);
            background: transparent;
            color: var(--texto);
        }

        .btn-secundario:hover {
            border-color: var(--fraco);
        }

        .btn:focus-visible {
            outline: 2px solid var(--marca);
            outline-offset: 2px;
        }

        .rodape {
            margin-top: 28px;
            font-size: 12px;
            color: var(--fraco);
        }

        @keyframes entrar {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .cartao {
                animation: none;
            }
        }
    </style>
</head>

<body>
    <main class="cartao" role="main">
        <div class="icone" aria-hidden="true">@yield('icon')</div>

        <p class="codigo">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>

        <div class="acoes">
            @hasSection('actions')
                @yield('actions')
            @else
                <button type="button" class="btn btn-secundario" onclick="history.length > 1 ? history.back() : location.assign('/')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7" /><path d="M19 12H5" /></svg>
                    Voltar
                </button>
                <a href="{{ url('/') }}" class="btn btn-primario">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5" /><path d="M5 9.5V21h14V9.5" /></svg>
                    Ir para o início
                </a>
            @endif
        </div>

        <div class="rodape">© {{ date('Y') }} · {{ config('app.name') }}</div>
    </main>
</body>

</html>
