<?php
/**
 * Página de Erro 500 - Internal Server Error
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>500 - Erro Interno do Servidor</title>
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
            color: #fd7e14;
            text-shadow: 0 4px 20px rgba(253, 126, 20, 0.5);
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
            background: #fd7e14;
            color: white;
        }
        .btn-primary:hover {
            background: #e8680e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(253, 126, 20, 0.4);
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
            <div class="error-code">500</div>
            <h1 class="error-title">Erro Interno do Servidor</h1>
            <p class="error-message">Ocorreu um erro inesperado no servidor.</p>
            <p class="error-description">
                Nosso servidor encontrou um problema ao processar sua solicitação. 
                Nossa equipe foi notificada e está trabalhando para resolver o problema. 
                Tente novamente em alguns instantes.
            </p>
            <div class="btn-group">
                <a href="/" class="btn btn-primary">Voltar ao Início</a>
                <button onclick="location.reload()" class="btn btn-secondary">Tentar Novamente</button>
            </div>
        </div>
    </div>
</body>
</html>

