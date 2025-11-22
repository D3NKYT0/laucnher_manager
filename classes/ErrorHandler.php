<?php
/**
 * Classe para Manipulação de Erros HTTP
 * 
 * Utilitários para redirecionar e exibir páginas de erro
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class ErrorHandler {
    
    /**
     * Redireciona para página de erro
     * 
     * @param int $code Código de erro HTTP
     * @param string|null $message Mensagem personalizada (opcional)
     */
    public static function showError(int $code, ?string $message = null): void {
        http_response_code($code);
        
        $errorPages = [
            400 => '/errors/400.php',
            401 => '/errors/401.php',
            403 => '/errors/403.php',
            404 => '/errors/404.php',
            500 => '/errors/500.php',
            503 => '/errors/503.php'
        ];
        
        if (isset($errorPages[$code])) {
            header('Location: ' . $errorPages[$code]);
            exit;
        }
        
        // Se não houver página específica, exibe erro genérico
        self::showGenericError($code, $message);
    }
    
    /**
     * Exibe erro genérico quando não há página específica
     */
    private static function showGenericError(int $code, ?string $message): void {
        http_response_code($code);
        
        $errorMessages = [
            400 => 'Solicitação Inválida',
            401 => 'Não Autorizado',
            403 => 'Acesso Negado',
            404 => 'Página Não Encontrada',
            500 => 'Erro Interno do Servidor',
            503 => 'Serviço Indisponível'
        ];
        
        $title = $errorMessages[$code] ?? 'Erro';
        $message = $message ?? $title;
        
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title><?= $code ?> - <?= htmlspecialchars($title) ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: #f5f5f5;
                }
                .error-container {
                    text-align: center;
                    padding: 40px;
                }
                .error-code {
                    font-size: 72px;
                    font-weight: bold;
                    color: #333;
                    margin-bottom: 10px;
                }
                .error-message {
                    font-size: 24px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-code"><?= $code ?></div>
                <div class="error-message"><?= htmlspecialchars($message) ?></div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Registra manipulador de erros PHP
     */
    public static function register(): void {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            
            Logger::error("Erro PHP: {$errstr}", [
                'file' => $errfile,
                'line' => $errline,
                'errno' => $errno
            ]);
            
            // Em produção, redireciona para 500
            if (!ini_get('display_errors')) {
                self::showError(500);
            }
            
            return false;
        });
        
        set_exception_handler(function($exception) {
            Logger::error("Exceção: {$exception->getMessage()}", [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
            
            // Em produção, redireciona para 500
            if (!ini_get('display_errors')) {
                self::showError(500);
            } else {
                throw $exception;
            }
        });
    }
}

