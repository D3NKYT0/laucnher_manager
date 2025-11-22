<?php
/**
 * Configurações do Sistema de Upload
 * 
 * Arquivo centralizado de configuração para fácil manutenção
 */

// Previne acesso direto
if (!defined('SYSTEM_ACCESS')) {
    define('SYSTEM_ACCESS', true);
}

// =========================
// Configurações de Segurança
// =========================

// NOTA: As constantes abaixo são OPCIONAIS e usadas apenas na primeira inicialização do banco
// para criar o usuário admin padrão. Depois disso, todos os usuários são gerenciados via
// painel admin (admin.php) e armazenados no banco SQLite.
// 
// Se você quiser definir credenciais personalizadas para o primeiro admin:
// Exemplo: password_hash('sua_senha_segura', PASSWORD_BCRYPT)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // senha: password

// Token CSRF (gerado automaticamente)
define('CSRF_TOKEN_NAME', 'csrf_token');

// Sessão
define('SESSION_NAME', 'upload_system');
define('SESSION_LIFETIME', 3600); // 1 hora

// =========================
// Configurações de Upload
// =========================

// Limites de tamanho (em bytes) - Aumentado para suportar arquivos grandes
define('MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024); // 5 GB
define('MAX_TOTAL_SIZE', 10 * 1024 * 1024 * 1024); // 10 GB total

// Diretórios
define('BASE_DIR', __DIR__);
define('UPLOAD_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'uploads');
// Diretório onde os arquivos ZIP serão extraídos
// Por padrão, extrai no diretório base (htdocs)
// Para extrair em outro diretório, altere o caminho abaixo
define('EXTRACT_DIR', BASE_DIR);
define('LOG_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'logs');
define('DATABASE_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'database');

// Extensões permitidas
define('ALLOWED_EXTENSIONS', ['zip']);

// MIME types permitidos
define('ALLOWED_MIME_TYPES', [
    'application/zip',
    'application/x-zip-compressed',
    'application/x-zip'
]);

// =========================
// Configurações de Log
// =========================

define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// =========================
// Configurações PHP
// =========================

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Erros (desativar em produção)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros ao usuário
ini_set('log_errors', 1);
// error_log será configurado após LOG_DIR ser criado

// Upload - Configurações para arquivos grandes (gigabytes)
ini_set('upload_max_filesize', '5G'); // 5 GB
ini_set('post_max_size', '5G'); // 5 GB
ini_set('max_execution_time', 0); // Sem limite de tempo (0 = ilimitado)
ini_set('max_input_time', 0); // Sem limite de tempo de entrada
ini_set('memory_limit', '1G'); // 1 GB de memória
ini_set('default_socket_timeout', 3600); // 1 hora para sockets
set_time_limit(0); // Remove limite de execução do script

// =========================
// Helpers
// =========================

// Criar diretórios necessários
$dirs = [UPLOAD_DIR, LOG_DIR, DATABASE_DIR];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Configurar error_log após criar diretório de logs
ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'php_errors.log');

