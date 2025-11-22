<?php
/**
 * Classe de Segurança
 * 
 * Utilitários para proteção contra ataques comuns
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class Security {
    
    /**
     * Gera token CSRF
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Valida token CSRF
     */
    public static function validateCSRFToken(?string $token): bool {
        if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Sanitiza nome de arquivo
     */
    public static function sanitizeFileName(string $filename): string {
        // Remove caracteres perigosos
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Limita tamanho
        $filename = substr($filename, 0, 255);
        return $filename;
    }
    
    /**
     * Valida extensão de arquivo
     */
    public static function isValidExtension(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ALLOWED_EXTENSIONS);
    }
    
    /**
     * Valida MIME type
     */
    public static function isValidMimeType(string $mimeType): bool {
        return in_array($mimeType, ALLOWED_MIME_TYPES);
    }
    
    /**
     * Protege contra path traversal
     */
    public static function preventPathTraversal(string $path): bool {
        // Normaliza o caminho
        $basedir = realpath(EXTRACT_DIR);
        
        if ($basedir === false) {
            return false;
        }
        
        // Se o arquivo existe, usa realpath
        if (file_exists($path)) {
            $realpath = realpath($path);
            if ($realpath === false) {
                return false;
            }
            return strpos($realpath, $basedir) === 0;
        }
        
        // Se o arquivo não existe, valida o caminho baseado no diretório base
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $basedir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $basedir);
        
        // Remove .. e caminhos absolutos
        if (strpos($path, '..') !== false) {
            return false;
        }
        
        // Verifica se o caminho começa com o diretório base
        $normalizedPath = $path;
        if (strpos($normalizedPath, $basedir) !== 0) {
            // Se não começa, tenta montar o caminho relativo
            $normalizedPath = $basedir . DIRECTORY_SEPARATOR . ltrim(str_replace($basedir, '', $path), DIRECTORY_SEPARATOR);
        }
        
        // Remove componentes .. do caminho normalizado
        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $normalizedPath) as $part) {
            if ($part === '..') {
                if (empty($parts)) {
                    return false; // Tentativa de path traversal
                }
                array_pop($parts);
            } elseif ($part !== '.' && $part !== '') {
                $parts[] = $part;
            }
        }
        
        $finalPath = implode(DIRECTORY_SEPARATOR, $parts);
        return strpos($finalPath, $basedir) === 0;
    }
    
    /**
     * Valida ZIP contra ZIP bomb
     */
    public static function validateZipBomb(string $zipPath, int $maxEntries = 10000, int $maxCompressionRatio = 100): bool {
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== true) {
            return false;
        }
        
        $entryCount = $zip->numFiles;
        $totalUncompressed = 0;
        $totalCompressed = 0;
        
        // Limite de entradas
        if ($entryCount > $maxEntries) {
            $zip->close();
            return false;
        }
        
        // Verifica taxa de compressão
        for ($i = 0; $i < $entryCount; $i++) {
            $stats = $zip->statIndex($i);
            if ($stats === false) continue;
            
            $uncompressed = $stats['size'];
            $compressed = $stats['comp_size'];
            
            if ($uncompressed > 0) {
                $ratio = $uncompressed / $compressed;
                if ($ratio > $maxCompressionRatio) {
                    $zip->close();
                    return false; // ZIP bomb detectado
                }
            }
            
            $totalUncompressed += $uncompressed;
            $totalCompressed += $compressed;
        }
        
        $zip->close();
        
        // Verifica tamanho total descomprimido
        if ($totalUncompressed > MAX_FILE_SIZE * 10) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitiza caminho de extração
     */
    public static function sanitizeExtractPath(string $entryName): ?string {
        // Remove path traversal - remove todas as ocorrências de ..
        $entryName = str_replace('..', '', $entryName);
        
        // Remove barras iniciais (evita paths absolutos)
        $entryName = ltrim($entryName, '/\\');
        
        // Remove caracteres perigosos do Windows/Linux
        $entryName = preg_replace('/[<>:"|?*\x00-\x1f]/', '', $entryName);
        
        // Se ficou vazio após sanitização, rejeita
        if (empty($entryName)) {
            return null;
        }
        
        // Valida contra paths absolutos do Windows (C:\ ou C:/)
        if (preg_match('/^[A-Z]:[\\\\\/]/i', $entryName)) {
            return null;
        }
        
        // Normaliza separadores
        $entryName = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $entryName);
        
        // Monta caminho completo
        $fullPath = EXTRACT_DIR . DIRECTORY_SEPARATOR . $entryName;
        
        // Valida que o caminho final não saia do diretório base
        // Normaliza ambos os caminhos para comparação
        $extractDirReal = realpath(EXTRACT_DIR);
        $extractDirNormalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $extractDirReal ?: EXTRACT_DIR);
        $fullPathNormalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $fullPath);
        
        // Resolve componentes .. do caminho manualmente
        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $fullPathNormalized) as $part) {
            if ($part === '..') {
                if (empty($parts)) {
                    return null; // Tentativa de path traversal - não pode voltar além da raiz
                }
                array_pop($parts);
            } elseif ($part !== '.' && $part !== '') {
                $parts[] = $part;
            }
        }
        
        $resolvedPath = implode(DIRECTORY_SEPARATOR, $parts);
        
        // Verifica se o caminho resolvido está dentro do diretório de extração
        // Usa comparação normalizada para evitar problemas com diferentes separadores
        if (strpos($resolvedPath, $extractDirNormalized) !== 0) {
            return null;
        }
        
        // Retorna o caminho completo normalizado
        return $resolvedPath;
    }
    
    /**
     * Headers de segurança HTTP
     */
    public static function setSecurityHeaders(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // CSRF token em header (para AJAX)
        if (!empty($_SESSION[CSRF_TOKEN_NAME])) {
            header('X-CSRF-Token: ' . $_SESSION[CSRF_TOKEN_NAME]);
        }
    }
    
    /**
     * Rate limiting simples (baseado em IP)
     */
    public static function checkRateLimit(string $action = 'upload', int $maxAttempts = 5, int $windowSeconds = 60): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset' => time() + $windowSeconds];
        }
        
        $limit = &$_SESSION[$key];
        
        // Reset se a janela expirou
        if (time() > $limit['reset']) {
            $limit['count'] = 0;
            $limit['reset'] = time() + $windowSeconds;
        }
        
        // Verifica limite
        if ($limit['count'] >= $maxAttempts) {
            return false;
        }
        
        $limit['count']++;
        return true;
    }
    
    /**
     * Retorna lista de extensões de arquivos perigosos bloqueados
     */
    public static function getDangerousExtensions(): array {
        // Extensões permitidas (liberadas explicitamente)
        $allowedExtensions = [
            'exe', 'dll', 'bin', 'dat', 'ini', 'txt', 'xml', 'zip', 'u', 'int', 'ttf',
            'pak', 'l2', 'sys', 'cfg', 'log', 'bak', 'tmp', 'cache', 'idx', 'grp',
            'pck', 'ukx', 'ifr', 'htm', 'unr', 'ogg', 'uax', 'usx', 'utx', 'bmp', 'ddf',
            'des', 'ffe', 'gly', 'vxd', 'dmp', 'xdat', 'i64', 'bm', 'ugx'
        ];
        
        return [
            // Scripts e executáveis (exceto os permitidos acima)
            'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
            'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
            'sh', 'bash', 'zsh', 'csh', 'ksh', 'fish',
            'ps1', 'psm1', 'psd1', 'ps1xml',
            'py', 'pyc', 'pyw', 'pyo', 'pyd',
            'rb', 'rbw',
            'pl', 'pm', 'cgi',
            'asp', 'aspx', 'ashx', 'asmx',
            'jsp', 'jspx', 'jspf',
            'jar', 'war', 'ear',
            'run', 'deb', 'rpm',
            'app', 'dmg', 'pkg',
            'msi', 'msm', 'msp',
            'so', 'dylib',
            
            // Arquivos de configuração e sistema (exceto os permitidos acima)
            'htaccess', 'htpasswd',
            'conf', 'config',
            'sql', 'db', 'sqlite', 'sqlite3',
            
            // Outros perigosos
            'git', 'svn',
        ];
    }
    
    /**
     * Retorna lista de padrões de conteúdo malicioso conhecidos
     */
    public static function getMaliciousPatterns(): array {
        return [
            // Padrões PHP maliciosos
            '/<\?php\s*eval\s*\(/i',
            '/<\?php\s*exec\s*\(/i',
            '/<\?php\s*system\s*\(/i',
            '/<\?php\s*shell_exec\s*\(/i',
            '/<\?php\s*passthru\s*\(/i',
            '/<\?php\s*proc_open\s*\(/i',
            '/<\?php\s*popen\s*\(/i',
            '/<\?php\s*file_get_contents\s*\(\s*[\'"](http|ftp|php|data):/i',
            '/<\?php\s*file_put_contents\s*\(\s*[\'"]\/\//i',
            '/<\?php\s*file_put_contents\s*\(\s*[\'"]\/etc\/passwd/i',
            '/<\?php\s*base64_decode\s*\(/i',
            '/<\?php\s*gzinflate\s*\(/i',
            '/<\?php\s*str_rot13\s*\(/i',
            '/<\?php\s*assert\s*\(/i',
            '/<\?php\s*create_function\s*\(/i',
            '/<\?php\s*\$_(GET|POST|COOKIE|REQUEST|FILES|SERVER)\[/i',
            '/@eval\s*\(/i',
            '/@assert\s*\(/i',
            
            // Webshells conhecidos
            '/c99shell/i',
            '/r57shell/i',
            '/WSO\s*Shell/i',
            '/PHPShell/i',
            '/Crystal/i',
            
            // Backdoors
            '/backdoor/i',
            '/backconnect/i',
            '/cmd\.php/i',
            '/shell\.php/i',
            '/hack\.php/i',
            '/bypass\.php/i',
        ];
    }
    
    /**
     * Retorna lista de pastas e arquivos protegidos do sistema
     */
    public static function getProtectedPaths(): array {
        return [
            // Pastas do sistema
            'database',
            'logs',
            'uploads',
            'errors',
            'includes',
            'classes',
            'video',
            
            // Arquivos do sistema
            'config.php',
            'index.php',
            'login.php',
            'logout.php',
            'upload.php',
            'admin.php',
            'admin_api.php',
            '.htaccess',
            'bootstrap.php', // incluído por precaução
        ];
    }
    
    /**
     * Verifica se um caminho está protegido (não pode ser sobrescrito)
     * 
     * @param string $path Caminho a verificar (relativo ou absoluto)
     * @return bool True se está protegido
     */
    public static function isProtectedPath(string $path): bool {
        // Remove barras iniciais e normaliza
        $path = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        
        // Se caminho vazio, está na raiz e pode ser perigoso
        if (empty($path)) {
            return false; // Raiz não é protegida, mas validações posteriores vão proteger
        }
        
        // Pega a primeira parte do caminho (pasta ou arquivo raiz)
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $firstPart = $parts[0] ?? '';
        
        if (empty($firstPart)) {
            return false;
        }
        
        // Lista de itens protegidos (case-insensitive)
        $protected = self::getProtectedPaths();
        
        // Verifica se começa com algum item protegido
        foreach ($protected as $protectedItem) {
            // Compara case-insensitive
            if (strcasecmp($firstPart, $protectedItem) === 0) {
                return true;
            }
            
            // Verifica se é um arquivo protegido
            if (strcasecmp($path, $protectedItem) === 0) {
                return true;
            }
            
            // Verifica se o caminho está dentro de uma pasta protegida
            if (strpos(strtolower($path), strtolower($protectedItem) . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Valida se é seguro extrair/apagar um caminho
     * 
     * @param string $path Caminho a validar
     * @return array ['safe' => bool, 'reason' => string]
     */
    public static function validatePathSafety(string $path): array {
        if (self::isProtectedPath($path)) {
            return [
                'safe' => false,
                'reason' => 'Caminho protegido do sistema não pode ser modificado'
            ];
        }
        
        // Verifica se está dentro do diretório de extração
        $extractDir = realpath(EXTRACT_DIR);
        if ($extractDir === false) {
            return [
                'safe' => false,
                'reason' => 'Diretório de extração inválido'
            ];
        }
        
        // Normaliza caminho completo
        $fullPath = EXTRACT_DIR . DIRECTORY_SEPARATOR . ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $realPath = realpath(dirname($fullPath));
        
        if ($realPath === false || strpos($realPath, $extractDir) !== 0) {
            return [
                'safe' => false,
                'reason' => 'Caminho fora do diretório de extração'
            ];
        }
        
        return ['safe' => true, 'reason' => ''];
    }
    
    /**
     * Retorna lista de extensões permitidas explicitamente
     */
    public static function getAllowedExtensions(): array {
        return [
            'exe', 'dll', 'bin', 'dat', 'ini', 'txt', 'xml', 'zip', 'u', 'int', 'ttf',
            'pak', 'l2', 'sys', 'cfg', 'log', 'bak', 'tmp', 'cache', 'idx', 'grp',
            'pck', 'ukx', 'ifr', 'htm', 'unr', 'ogg', 'uax', 'usx', 'utx', 'bmp', 'ddf',
            'des', 'ffe', 'gly', 'vxd', 'dmp', 'xdat', 'i64', 'bm', 'ugx'
        ];
    }
    
    /**
     * Verifica se uma extensão de arquivo é perigosa
     * 
     * @param string $filename Nome do arquivo
     * @return bool True se for perigoso
     */
    public static function isDangerousExtension(string $filename): bool {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Extensões permitidas explicitamente não são bloqueadas
        $allowed = self::getAllowedExtensions();
        if (in_array($ext, $allowed)) {
            return false;
        }
        
        $dangerous = self::getDangerousExtensions();
        return in_array($ext, $dangerous);
    }
    
    /**
     * Valida conteúdo de arquivo contra padrões maliciosos
     * 
     * @param string $content Conteúdo do arquivo
     * @param string $filename Nome do arquivo (opcional, para contexto)
     * @return array ['safe' => bool, 'reason' => string, 'pattern' => string]
     */
    public static function validateFileContent(string $content, string $filename = ''): array {
        // Se a extensão está na lista de permitidas, valida apenas padrões PHP maliciosos
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = self::getAllowedExtensions();
        $isAllowedExtension = in_array($ext, $allowedExtensions);
        
        // Se for extensão permitida e não for texto/HTML, pula validação de conteúdo malicioso
        // (arquivos binários como .exe, .dll, .bin, etc não devem ser validados como texto)
        $textExtensions = ['txt', 'xml', 'htm', 'ini', 'cfg', 'log', 'bak', 'dat'];
        $isTextFile = in_array($ext, $textExtensions);
        
        // Verifica apenas uma amostra do conteúdo (primeiros 50KB para performance)
        $sampleSize = min(51200, strlen($content)); // 50KB
        $sample = substr($content, 0, $sampleSize);
        
        // Se for arquivo binário permitido (ex: .exe, .dll, .bin), não valida conteúdo como texto
        if ($isAllowedExtension && !$isTextFile) {
            // Apenas verifica se não contém tags PHP maliciosas (caso alguém tente ofuscar)
            $phpPatterns = [
                '/<\?php/i',
                '/<\?=/i',
                '/eval\s*\(/i',
                '/exec\s*\(/i',
                '/system\s*\(/i',
            ];
            
            foreach ($phpPatterns as $pattern) {
                if (preg_match($pattern, $sample)) {
                    return [
                        'safe' => false,
                        'reason' => 'Arquivo binário contém código PHP suspeito',
                        'pattern' => $pattern
                    ];
                }
            }
            
            // Permite arquivos binários permitidos sem validação adicional
            return ['safe' => true, 'reason' => '', 'pattern' => ''];
        }
        
        // Para arquivos de texto ou não permitidos, faz validação completa
        $patterns = self::getMaliciousPatterns();
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sample)) {
                return [
                    'safe' => false,
                    'reason' => 'Conteúdo malicioso detectado no arquivo',
                    'pattern' => $pattern
                ];
            }
        }
        
        // Verifica se é um arquivo PHP e contém código suspeito
        if (self::isDangerousExtension($filename)) {
            // Verifica se contém tags PHP
            if (preg_match('/<\?php|<\?=/i', $sample)) {
                // Verifica padrões de código perigoso
                $dangerousPHP = [
                    '/\$_GET|\$_POST|\$_COOKIE|\$_REQUEST/i',
                    '/eval\s*\(/i',
                    '/exec\s*\(/i',
                    '/system\s*\(/i',
                    '/shell_exec\s*\(/i',
                    '/passthru\s*\(/i',
                    '/proc_open\s*\(/i',
                    '/popen\s*\(/i',
                    '/file_get_contents\s*\(\s*[\'"](http|ftp|data):/i',
                    '/curl_exec\s*\(/i',
                    '/fopen\s*\(\s*[\'"](http|ftp|php|data):/i',
                ];
                
                foreach ($dangerousPHP as $pattern) {
                    if (preg_match($pattern, $sample)) {
                        return [
                            'safe' => false,
                            'reason' => 'Arquivo PHP contém código potencialmente perigoso',
                            'pattern' => $pattern
                        ];
                    }
                }
            }
        }
        
        // Verifica assinatura de executável apenas para extensões não permitidas
        if (!$isAllowedExtension) {
            $magicBytes = substr($content, 0, 4);
            $executableSignatures = [
                "\x4D\x5A", // MZ (PE executável - Windows)
                "\x7F\x45\x4C\x46", // ELF (Linux)
                "\xFE\xED\xFA", // Mach-O (macOS)
                "#!", // Shebang (scripts Unix)
            ];
            
            foreach ($executableSignatures as $signature) {
                if (strpos($magicBytes, $signature) === 0) {
                    return [
                        'safe' => false,
                        'reason' => 'Arquivo executável detectado',
                        'pattern' => 'executable_signature'
                    ];
                }
            }
        }
        
        return ['safe' => true, 'reason' => '', 'pattern' => ''];
    }
    
    /**
     * Valida arquivo completo (extensão e conteúdo)
     * 
     * @param string $filename Nome do arquivo
     * @param string $content Conteúdo do arquivo (opcional, será lido se não fornecido)
     * @param string $filePath Caminho completo do arquivo (para ler conteúdo)
     * @return array ['safe' => bool, 'reason' => string]
     */
    public static function validateFile(string $filename, ?string $content = null, ?string $filePath = null): array {
        // Verifica extensão
        if (self::isDangerousExtension($filename)) {
            return [
                'safe' => false,
                'reason' => 'Extensão de arquivo perigosa não permitida: ' . pathinfo($filename, PATHINFO_EXTENSION)
            ];
        }
        
        // Se não tem conteúdo, tenta ler do arquivo
        if ($content === null && $filePath !== null && file_exists($filePath)) {
            // Lê apenas os primeiros 50KB para validação (performance)
            $content = @file_get_contents($filePath, false, null, 0, 51200);
        }
        
        // Valida conteúdo se disponível
        if ($content !== null && !empty($content)) {
            $contentValidation = self::validateFileContent($content, $filename);
            if (!$contentValidation['safe']) {
                return $contentValidation;
            }
        }
        
        return ['safe' => true, 'reason' => ''];
    }
}

