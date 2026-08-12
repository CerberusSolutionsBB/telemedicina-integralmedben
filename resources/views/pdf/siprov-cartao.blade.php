<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 0;
            size: 243pt 153pt;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #fff;
        }

        .card {
            width: 243pt;
            height: 153pt;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            background: #22d3ee;
            border-radius: 10pt;
        }

        .card:last-child {
            page-break-after: auto;
        }

        /* ====== ÍCONE CORAÇÃO ====== */
        .icon-heart {
            width: 26pt;
            height: 17pt;
            /* background: #fff; */
            /* border-radius: 3pt; */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-heart img {
            width: 20pt;
            height: 12.5pt;
        }

        /* ====== FRENTE ====== */
        .frente {
            background-image: url({{ $fundo_frente }});
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .frente .logo-topo {
            position: absolute;
            top: 12pt;
            right: 14%;
        }

        .frente .campos {
            position: absolute;
            left: 16pt;
            top: 63pt;
            right: 16pt;
        }

        .frente .campo-row {
            position: relative;
            height: 22pt;
            margin-bottom: 5pt;
        }

        .frente .campo-row:last-child {
            margin-bottom: 0;
        }

        .frente .campo-label {
            position: absolute;
            left: 0;
            top: 0;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.4pt;
        }

        .frente .campo-valor {
            position: absolute;
            left: 0;
            right: 0;
            top: 9pt;
            font-size: 10pt;
            font-weight: bold;
            padding-bottom: 2pt;
            border-bottom: 0.8pt solid rgba(255, 255, 255, 0.7);
            white-space: nowrap;
        }

        /* ====== VERSO ====== */
        .verso {
            background-image: url({{ $fundo_verso }});
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Textos verticais usam rotate(-90deg) em vez de writing-mode: o
           dompdf não recalcula a caixa de layout no modo vertical e
           sobrepõe o conteúdo; com rotate a caixa original (width/height)
           é posicionada e então girada, então o "top" soma o width
           original (que se torna a extensão vertical após o giro). */

        .verso .marca-vertical {
            position: absolute;
            left: 12pt;
            top: 111pt;
            width: 70pt;
            height: 17pt;
            transform: rotate(-90deg);
            transform-origin: left top;
        }

        .verso .marca-vertical img {
            width: 100%;
            height: 100%;
        }

        .verso .qr-central {
            position: absolute;
            left: 48pt;
            top: 42pt;
            width: 70pt;
            height: 70pt;
            background: #fff;
            border-radius: 4pt;
            padding: 4pt;
        }

        .verso .qr-central img {
            width: 100%;
            height: 100%;
        }

        .verso .info-vertical {
            position: absolute;
            left: 130pt;
            top: 116pt;
            width: 80pt;
            height: 35pt;
            transform: rotate(-90deg);
            transform-origin: left top;
            white-space: nowrap;
        }

        .verso .info-vertical .txt1 {
            font-size: 6pt;
            opacity: 0.85;
            margin-bottom: 3pt;
        }

        .verso .info-vertical .fone {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 3pt;
        }

        .verso .info-vertical .cpf {
            font-size: 6pt;
            opacity: 0.85;
        }

        .verso .selo-vertical {
            position: absolute;
            left: 228pt;
            top: 132pt;
            width: 112pt;
            height: 7pt;
            transform: rotate(-90deg);
            transform-origin: left top;
            white-space: nowrap;
            text-align: center;
            font-size: 5.5pt;
            font-weight: bold;
            letter-spacing: 0.7pt;
            opacity: 0.7;
            text-transform: uppercase;
        }
    </style>
    <base target="_blank">
</head>

<body>

    <!-- FRENTE -->
    <div class="card frente">
        <div class="logo-topo">
            <div class="icon-heart">
                <img src="{{ $logo }}" alt="Logo" style="height: 50px; width: 70px; ">
            </div>
        </div>
        <div class="campos">
            <div class="campo-row">
                <span class="campo-label">NOME:</span>
                <span class="campo-valor">{{ $nome }}</span>
            </div>
            <div class="campo-row">
                <span class="campo-label">CPF:</span>
                <span class="campo-valor">{{ $cpf }}</span>
            </div>
            <div class="campo-row">
                <span class="campo-label">EMPRESA:</span>
                <span class="campo-valor">{{ $plano }}</span>
            </div>
        </div>
    </div>

    <!-- VERSO -->
    <div class="card verso">
        <div class="marca-vertical">
            <img src="{{ $logo_vertical }}" alt="Logo">
        </div>

        <div class="qr-central">
            <img src="{{ $qr }}" alt="QR Code">
        </div>

        <div class="info-vertical">
            <div class="txt1">Solicite atendimento 24h</div>
            {{-- <div class="fone">{{ $telefone }}</div> --}}
            <div class="cpf">CPF: {{ $cpf }}</div>
        </div>

        <div class="selo-vertical">Cartão pessoal e intransferível</div>
    </div>

</body>

</html>
