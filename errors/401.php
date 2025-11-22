<?php
/**
 * Página de Erro 401 - Unauthorized
 */
http_response_code(401);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>401 - Não Autorizado</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body, html {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow: hidden;
        }
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(40%);
        }
        .error-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: white;
            z-index: 1;
            max-width: 600px;
            padding: 40px;
        }
        .error-box {
            background: rgba(0, 0, 0, 0.75);
            padding: 50px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 20px;
            color: #ffc107;
            text-shadow: 0 4px 20px rgba(255, 193, 7, 0.5);
        }
        .error-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-description {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 30px;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #ffc107;
            color: #000;
        }
        .btn-primary:hover {
            background: #ffca2c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .error-code {
                font-size: 80px;
            }
            .error-title {
                font-size: 24px;
            }
            .error-container {
                padding: 20px;
            }
            .error-box {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="bg-video">
        <source src="/video/mp4/video-bg.mp4" type="video/mp4">
    </video>

    <div class="error-container">
        <div class="error-box">
            <div class="error-code">401</div>
            <h1 class="error-title">Não Autorizado</h1>
            <p class="error-message">Você precisa estar autenticado para acessar este recurso.</p>
            <p class="error-description">
                Esta página requer autenticação. Por favor, faça login com suas credenciais para continuar.
            </p>
            <div class="btn-group">
                <a href="/login.php" class="btn btn-primary">Fazer Login</a>
                <a href="/" class="btn btn-secondary">Voltar ao Início</a>
            </div>
        </div>
    </div>
</body>
</html>

