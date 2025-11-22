<?php
/**
 * Página de Logout
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Faz logout
Auth::logout();

// Redireciona para login
header('Location: index.php');
exit;

