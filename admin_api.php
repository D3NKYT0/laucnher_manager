<?php
/**
 * API de Administração de Usuários
 * 
 * Endpoints para gerenciar usuários (CRUD)
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Verifica autenticação e permissão de admin
Auth::requireAdmin();

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verifica CSRF
$csrfToken = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!Security::validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token de segurança inválido']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Lista todos os usuários
            $users = User::getAll();
            echo json_encode(['success' => true, 'data' => $users]);
            break;
            
        case 'get':
            // Busca usuário por ID
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }
            
            $user = User::getById($id);
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
                break;
            }
            
            // Remove hash da senha
            unset($user['password_hash']);
            echo json_encode(['success' => true, 'data' => $user]);
            break;
            
        case 'create':
            // Cria novo usuário
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : true;
            
            $result = User::create($username, $password, $role, $active);
            echo json_encode($result);
            break;
            
        case 'update':
            // Atualiza usuário
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }
            
            $username = isset($_POST['username']) ? trim($_POST['username']) : null;
            $password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;
            $role = isset($_POST['role']) ? $_POST['role'] : null;
            $active = isset($_POST['active']) ? (bool)$_POST['active'] : null;
            
            $result = User::update($id, $username, $password, $role, $active);
            echo json_encode($result);
            break;
            
        case 'delete':
            // Deleta usuário
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                break;
            }
            
            // Não permite deletar a si mesmo
            $currentUser = Auth::getCurrentUser();
            if ($currentUser && $currentUser['id'] == $id) {
                echo json_encode(['success' => false, 'error' => 'Você não pode deletar sua própria conta']);
                break;
            }
            
            $result = User::delete($id);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    Logger::error("Erro na API de admin: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
}

