<?php
/**
 * Classe de Usuário
 * 
 * Gerencia operações CRUD de usuários
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class User {
    
    /**
     * Lista todos os usuários
     */
    public static function getAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT id, username, role, active, created_at, updated_at 
            FROM users 
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Busca usuário por ID
     */
    public static function getById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE id = ?", [$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    /**
     * Busca usuário por username
     */
    public static function getByUsername(string $username): ?array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM users WHERE username = ?", [$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    /**
     * Cria novo usuário
     */
    public static function create(string $username, string $password, string $role = 'user', bool $active = true): array {
        // Validações
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Usuário e senha são obrigatórios'];
        }
        
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['success' => false, 'error' => 'Usuário deve ter entre 3 e 50 caracteres'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Senha deve ter no mínimo 6 caracteres'];
        }
        
        if (!in_array($role, ['admin', 'user'])) {
            return ['success' => false, 'error' => 'Role inválida'];
        }
        
        // Verifica se usuário já existe
        if (self::getByUsername($username) !== null) {
            return ['success' => false, 'error' => 'Usuário já existe'];
        }
        
        // Cria hash da senha
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $db = Database::getInstance();
            $stmt = $db->query("
                INSERT INTO users (username, password_hash, role, active, updated_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ", [$username, $passwordHash, $role, $active ? 1 : 0]);
            
            $userId = $db->lastInsertId();
            
            Logger::info("Usuário criado: {$username}", ['id' => $userId, 'role' => $role]);
            
            return ['success' => true, 'id' => $userId];
        } catch (Exception $e) {
            Logger::error("Erro ao criar usuário: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao criar usuário'];
        }
    }
    
    /**
     * Atualiza usuário
     */
    public static function update(int $id, ?string $username = null, ?string $password = null, ?string $role = null, ?bool $active = null): array {
        $user = self::getById($id);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        $updates = [];
        $params = [];
        
        if ($username !== null && $username !== $user['username']) {
            if (strlen($username) < 3 || strlen($username) > 50) {
                return ['success' => false, 'error' => 'Usuário deve ter entre 3 e 50 caracteres'];
            }
            
            // Verifica se outro usuário já usa esse username
            $existing = self::getByUsername($username);
            if ($existing && $existing['id'] != $id) {
                return ['success' => false, 'error' => 'Usuário já existe'];
            }
            
            $updates[] = "username = ?";
            $params[] = $username;
        }
        
        if ($password !== null && !empty($password)) {
            if (strlen($password) < 6) {
                return ['success' => false, 'error' => 'Senha deve ter no mínimo 6 caracteres'];
            }
            $updates[] = "password_hash = ?";
            $params[] = password_hash($password, PASSWORD_BCRYPT);
        }
        
        if ($role !== null && $role !== $user['role']) {
            if (!in_array($role, ['admin', 'user'])) {
                return ['success' => false, 'error' => 'Role inválida'];
            }
            $updates[] = "role = ?";
            $params[] = $role;
        }
        
        if ($active !== null && $active != $user['active']) {
            $updates[] = "active = ?";
            $params[] = $active ? 1 : 0;
        }
        
        if (empty($updates)) {
            return ['success' => true, 'message' => 'Nenhuma alteração necessária'];
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $id;
        
        try {
            $db = Database::getInstance();
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            $db->query($sql, $params);
            
            Logger::info("Usuário atualizado: ID {$id}", ['updates' => $updates]);
            
            return ['success' => true, 'message' => 'Usuário atualizado com sucesso'];
        } catch (Exception $e) {
            Logger::error("Erro ao atualizar usuário: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao atualizar usuário'];
        }
    }
    
    /**
     * Deleta usuário
     */
    public static function delete(int $id): array {
        $user = self::getById($id);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        // Não permite deletar se for o único admin
        if ($user['role'] === 'admin') {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id != ?", [$id]);
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                return ['success' => false, 'error' => 'Não é possível deletar o último administrador'];
            }
        }
        
        try {
            $db = Database::getInstance();
            $db->query("DELETE FROM users WHERE id = ?", [$id]);
            
            Logger::info("Usuário deletado: ID {$id}", ['username' => $user['username']]);
            
            return ['success' => true, 'message' => 'Usuário deletado com sucesso'];
        } catch (Exception $e) {
            Logger::error("Erro ao deletar usuário: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erro ao deletar usuário'];
        }
    }
    
    /**
     * Verifica credenciais
     */
    public static function verifyCredentials(string $username, string $password): ?array {
        $user = self::getByUsername($username);
        
        if (!$user) {
            return null;
        }
        
        if (!$user['active']) {
            return null; // Usuário inativo
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        
        // Remove hash da senha do retorno
        unset($user['password_hash']);
        return $user;
    }
}

