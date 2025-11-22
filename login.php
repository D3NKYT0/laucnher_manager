<?php
/**
 * API de Login (AJAX)
 * 
 * Endpoint para autenticação via AJAX
 */

require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Se já está logado
if (Auth::isAuthenticated()) {
    echo json_encode(['success' => true]);
    exit;
}

// Processa login
$username = $_POST['usuario'] ?? '';
$password = $_POST['senha'] ?? '';

$result = Auth::login($username, $password);

// Retorna JSON
echo json_encode($result);
exit;
