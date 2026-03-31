<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Sistema em Manutenção</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #eaf6ff, #f7fbff);
            color: #2d3748;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #ffffff;
            border-radius: 18px;
            padding: 45px 35px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            text-align: center;
            animation: fadeIn 0.6s ease;
        }

        .logo {
            margin-bottom: 10px;
        }

        .logo img {
            width: 90px;
        }

        .icon {
            width: 75px;
            height: 75px;
            margin: 10px auto 20px;
            border-radius: 50%;
            background: rgba(75, 180, 255, 0.1);
            color: #4bb4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            animation: bounce 2s infinite;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1a202c;
        }

        p {
            margin: 6px 0;
            font-size: 14px;
            color: #4a5568;
        }

        .loader {
            margin: 25px auto 10px;
            width: 42px;
            height: 42px;
            border: 3px solid #e2e8f0;
            border-top: 3px solid #4bb4ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn {
            margin-top: 18px;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: #4bb4ff;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn:hover {
            background: #3399e6;
        }

        .status {
            margin-top: 10px;
            font-size: 13px;
            color: #718096;
        }

        .footer {
            margin-top: 25px;
            font-size: 12px;
            color: #a0aec0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- LOGO (opcional) -->
        <!-- <div class="logo">
            <img src="/logo.png" alt="Logo">
        </div> -->

        <div class="icon">🩺</div>

        <h1>Sistema em manutenção</h1>

        <p>Estamos realizando melhorias para oferecer um atendimento ainda melhor.</p>
        <p>Voltamos em breve 🙏</p>

        <div class="loader"></div>

        <div class="status">
            Atualizando serviços...
        </div>

        <button class="btn" onclick="location.reload()">
            Atualizar página
        </button>

        <div class="footer">
            © <?= date('Y') ?> - Sistema Médico
        </div>

    </div>

</body>

</html>
