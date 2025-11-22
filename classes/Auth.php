<?php
/**
 * Classe de Autenticação
 * 
 * Sistema seguro de login com hash de senha
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class Auth {
    
    /**
     * Inicializa sessão segura
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurações de sessão seguras
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_name(SESSION_NAME);
            session_start();
            
            // Regenera ID da sessão periodicamente
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Verifica se usuário está logado
     */
    public static function isAuthenticated(): bool {
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        
        // Verifica tempo de sessão
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }
        
        // Atualiza última atividade
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Autentica usuário
     */
    public static function login(string $username, string $password): array {
        self::initSession();
        
        // Validação básica
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Usuário e senha são obrigatórios'];
        }
        
        // Verifica rate limiting
        if (!Security::checkRateLimit('login', 5, 300)) {
            return ['success' => false, 'error' => 'Muitas tentativas de login. Tente novamente mais tarde.'];
        }
        
        // Valida credenciais usando banco de dados
        $user = User::verifyCredentials($username, $password);
        
        if (!$user) {
            Logger::warning("Tentativa de login falhou para usuário: {$username}", ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return ['success' => false, 'error' => 'Usuário ou senha incorretos'];
        }
        
        // Login bem-sucedido
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        
        // Regenera ID da sessão após login
        session_regenerate_id(true);
        
        Logger::info("Login bem-sucedido para usuário: {$username}", [
            'id' => $user['id'],
            'role' => $user['role'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        return ['success' => true];
    }
    
    /**
     * Faz logout
     */
    public static function logout(): void {
        self::initSession();
        
        $username = $_SESSION['username'] ?? 'unknown';
        
        // Limpa sessão
        $_SESSION = [];
        
        // Destroi cookie de sessão
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroi sessão
        session_destroy();
        
        Logger::info("Logout realizado para usuário: {$username}");
    }
    
    /**
     * Requer autenticação (redirect se não autenticado)
     */
    public static function requireAuth(): void {
        if (!self::isAuthenticated()) {
            http_response_code(401);
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Requer role de administrador
     */
    public static function requireAdmin(): void {
        self::requireAuth();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            header('Location: /errors/403.php');
            exit;
        }
    }
    
    /**
     * Verifica se usuário é admin
     */
    public static function isAdmin(): bool {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Retorna informações do usuário logado
     */
    public static function getCurrentUser(): ?array {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['user_role'] ?? null
        ];
    }
}

