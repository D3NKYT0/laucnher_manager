<?php
/**
 * Bootstrap do Sistema
 * 
 * Arquivo inicial que carrega todas as dependências
 */

// Define constante de acesso
define('SYSTEM_ACCESS', true);

// Carrega configurações
require_once __DIR__ . '/../config.php';

// Carrega classes
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/FileUploader.php';

// Inicializa logger (após todas as constantes estarem definidas)
Logger::init();

// Inicializa sessão
Auth::initSession();

// Define headers de segurança
Security::setSecurityHeaders();

