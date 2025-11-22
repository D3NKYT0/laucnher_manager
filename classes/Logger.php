<?php
/**
 * Classe de Logging
 * 
 * Sistema de logs para auditoria e debug
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class Logger {
    
    private static $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];
    private static $levelIndex = 1; // INFO por padrão
    
    /**
     * Inicializa logger
     */
    public static function init(): void {
        $level = strtoupper(LOG_LEVEL);
        self::$levelIndex = array_search($level, self::$levels) ?: 1;
    }
    
    /**
     * Log genérico
     */
    private static function log(string $level, string $message, array $context = []): void {
        if (!LOG_ENABLED) {
            return;
        }
        
        $levelIdx = array_search($level, self::$levels) ?: 999;
        if ($levelIdx < self::$levelIndex) {
            return; // Nível muito baixo
        }
        
        $logFile = LOG_DIR . DIRECTORY_SEPARATOR . 'system_' . date('Y-m-d') . '.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user = $_SESSION['username'] ?? 'guest';
        
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logLine = "[{$timestamp}] [{$level}] [{$ip}] [{$user}] {$message}{$contextStr}" . PHP_EOL;
        
        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log de debug
     */
    public static function debug(string $message, array $context = []): void {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Log de informação
     */
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log de aviso
     */
    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log de erro
     */
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
        // Também registra no log de erros do PHP
        error_log("Logger ERROR: {$message}");
    }
}

