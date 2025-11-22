<?php
/**
 * Página de Login
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Se já está logado, redireciona
if (Auth::isAuthenticated()) {
    header('Location: upload.php');
    exit;
}

// Processa login se houver POST
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['usuario'] ?? '';
    $password = $_POST['senha'] ?? '';
    $result = Auth::login($username, $password);
    
    if ($result['success']) {
        header('Location: upload.php');
        exit;
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Sistema de Upload</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
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
            filter: brightness(60%);
        }
        .login-box {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            padding: 30px;
            background: rgba(0,0,0,0.75);
            border-radius: 12px;
            text-align: center;
            color: white;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        input {
            width: calc(100% - 24px);
            padding: 12px;
            margin: 10px 0;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            color: white;
            font-size: 14px;
        }
        input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        input:focus {
            outline: none;
            border-color: #007bff;
            background: rgba(255,255,255,0.15);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.2s;
        }
        button:hover {
            background: #0056b3;
        }
        button:active {
            transform: scale(0.98);
        }
        #msg {
            margin-top: 15px;
            color: #ff6b6b;
            font-size: 14px;
            min-height: 20px;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="bg-video">
        <source src="/video/mp4/video-bg.mp4" type="video/mp4">
    </video>

    <div class="login-box">
        <h2>Acessar Sistema</h2>
        <form method="POST" action="" id="loginForm">
            <input type="text" name="usuario" id="user" placeholder="Usuário" required autocomplete="username">
            <input type="password" name="senha" id="pass" placeholder="Senha" required autocomplete="current-password">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= Security::generateCSRFToken() ?>">
            <button type="submit">Entrar</button>
        </form>
        <p id="msg"><?= htmlspecialchars($error) ?></p>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const msg = document.getElementById('msg');
        
        form.addEventListener('submit', function(e) {
            msg.textContent = '';
        });
    </script>
</body>
</html>

