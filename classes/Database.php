<?php
/**
 * Classe de Banco de Dados SQLite
 * 
 * Gerencia conexão e operações com SQLite
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class Database {
    
    private static $instance = null;
    private $db = null;
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct() {
        $dbDir = defined('DATABASE_DIR') ? DATABASE_DIR : (BASE_DIR . DIRECTORY_SEPARATOR . 'database');
        $dbPath = $dbDir . DIRECTORY_SEPARATOR . 'system.db';
        
        // Cria diretório se não existir
        if (!file_exists($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Cria tabelas se não existirem
            $this->createTables();
        } catch (PDOException $e) {
            Logger::error("Erro ao conectar ao banco de dados: " . $e->getMessage());
            throw new Exception("Erro ao conectar ao banco de dados");
        }
    }
    
    /**
     * Retorna instância singleton
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna conexão PDO
     */
    public function getConnection(): PDO {
        return $this->db;
    }
    
    /**
     * Cria tabelas necessárias
     */
    private function createTables(): void {
        // Tabela de usuários
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'user' NOT NULL,
                active INTEGER DEFAULT 1 NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
            )
        ");
        
        // Índices para melhor performance
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_username ON users(username)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_role ON users(role)");
        
        // Cria usuário admin padrão se não existir
        $this->createDefaultAdmin();
    }
    
    /**
     * Cria usuário admin padrão se não houver nenhum admin
     */
    private function createDefaultAdmin(): void {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Usa hash padrão do config.php se existir
            $defaultHash = defined('ADMIN_PASSWORD_HASH') ? ADMIN_PASSWORD_HASH : password_hash('admin', PASSWORD_BCRYPT);
            $defaultUsername = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, role, active) 
                VALUES (?, ?, 'admin', 1)
            ");
            $stmt->execute([$defaultUsername, $defaultHash]);
            
            Logger::info("Usuário admin padrão criado: {$defaultUsername}");
        }
    }
    
    /**
     * Executa query preparada
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error("Erro na query SQL: " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw new Exception("Erro ao executar consulta no banco de dados");
        }
    }
    
    /**
     * Retorna último ID inserido
     */
    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
    
    /**
     * Inicia transação
     */
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }
    
    /**
     * Confirma transação
     */
    public function commit(): bool {
        return $this->db->commit();
    }
    
    /**
     * Desfaz transação
     */
    public function rollback(): bool {
        return $this->db->rollBack();
    }
}

