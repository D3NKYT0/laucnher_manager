<?php
/**
 * Classe de Upload de Arquivos
 * 
 * Sistema seguro de upload e extração de arquivos ZIP
 */

if (!defined('SYSTEM_ACCESS')) {
    die('Acesso negado');
}

class FileUploader {
    
    private $errors = [];
    private $uploadedFile = null;
    
    /**
     * Processa upload de arquivo
     */
    public function processUpload(): array {
        try {
            // Verifica autenticação
            Auth::requireAuth();
            
            // Verifica rate limiting
            if (!Security::checkRateLimit('upload', 10, 3600)) {
                throw new Exception('Muitos uploads realizados. Aguarde antes de tentar novamente.');
            }
            
            // Verifica CSRF
            $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Security::validateCSRFToken($csrfToken)) {
                throw new Exception('Token de segurança inválido. Recarregue a página e tente novamente.');
            }
            
            // Verifica arquivo
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = $this->getUploadErrorMessage($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);
                throw new Exception("Erro no upload: {$errorMsg}");
            }
            
            $file = $_FILES['file'];
            
            // Validações
            $this->validateFile($file);
            
            if (!empty($this->errors)) {
                throw new Exception(implode('; ', $this->errors));
            }
            
            // Processa arquivo
            $result = $this->saveFile($file);
            
            // Se for ZIP, extrai
            if ($result['isZip']) {
                // Verifica modo de overwrite (apagar pasta existente ou apenas sobrescrever)
                $overwriteMode = $_POST['overwrite_mode'] ?? 'merge'; // 'delete' ou 'merge'
                $extractResult = $this->extractZip($result['path'], $overwriteMode);
                $result = array_merge($result, $extractResult);
            }
            
            Logger::info("Upload realizado com sucesso: {$result['originalName']}", [
                'size' => $result['size'],
                'extracted' => $result['isZip']
            ]);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            Logger::error("Erro no upload: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Valida arquivo antes do upload
     */
    private function validateFile(array $file): void {
        $name = $file['name'];
        $size = $file['size'];
        $tmpPath = $file['tmp_name'];
        
        // Valida nome
        if (empty($name)) {
            $this->errors[] = 'Nome de arquivo inválido';
            return;
        }
        
        // Valida extensão
        if (!Security::isValidExtension($name)) {
            $this->errors[] = 'Extensão não permitida. Apenas arquivos ZIP são aceitos.';
        }
        
        // Valida tamanho
        if ($size > MAX_FILE_SIZE) {
            $maxMB = round(MAX_FILE_SIZE / 1024 / 1024, 2);
            $this->errors[] = "Arquivo muito grande. Tamanho máximo: {$maxMB} MB";
        }
        
        if ($size === 0) {
            $this->errors[] = 'Arquivo vazio não é permitido';
        }
        
        // Valida MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        
        if (!Security::isValidMimeType($mimeType)) {
            // Validação secundária: verifica conteúdo real do arquivo
            $fileContent = @file_get_contents($tmpPath, false, null, 0, 4);
            $zipSignature = "\x50\x4B\x03\x04"; // PK (ZIP signature)
            
            if (substr($fileContent, 0, 4) !== $zipSignature) {
                $this->errors[] = 'Arquivo não é um ZIP válido';
            }
        }
        
        // Verifica se é um arquivo válido
        if (!is_uploaded_file($tmpPath)) {
            $this->errors[] = 'Arquivo inválido ou corrompido';
        }
    }
    
    /**
     * Salva arquivo no servidor
     */
    private function saveFile(array $file): array {
        $originalName = Security::sanitizeFileName($file['name']);
        $size = $file['size'];
        $tmpPath = $file['tmp_name'];
        
        // Gera nome único e seguro
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($originalName, '.' . $extension));
        $safeName .= '.' . $extension;
        
        $destination = UPLOAD_DIR . DIRECTORY_SEPARATOR . $safeName;
        
        // Move arquivo
        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new Exception('Falha ao salvar arquivo no servidor');
        }
        
        // Define permissões
        @chmod($destination, 0644);
        
        return [
            'originalName' => $originalName,
            'savedName' => $safeName,
            'path' => $destination,
            'size' => $size,
            'sizeMB' => round($size / 1024 / 1024, 2),
            'isZip' => $extension === 'zip'
        ];
    }
    
    /**
     * Extrai arquivo ZIP de forma segura
     * 
     * @param string $zipPath Caminho do arquivo ZIP
     * @param string $overwriteMode Modo de sobrescrita: 'delete' (apaga pasta existente) ou 'merge' (apenas sobrescreve)
     */
    private function extractZip(string $zipPath, string $overwriteMode = 'merge'): array {
        if (!file_exists($zipPath)) {
            throw new Exception('Arquivo ZIP não encontrado');
        }
        
        // Valida ZIP bomb
        if (!Security::validateZipBomb($zipPath)) {
            @unlink($zipPath);
            throw new Exception('ZIP rejeitado: possível ZIP bomb ou arquivo malicioso');
        }
        
        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);
        
        if ($openResult !== true) {
            $errorMsg = [
                ZipArchive::ER_OK => 'OK',
                ZipArchive::ER_MULTIDISK => 'Multi-disk',
                ZipArchive::ER_RENAME => 'Rename failed',
                ZipArchive::ER_CLOSE => 'Close failed',
                ZipArchive::ER_SEEK => 'Seek error',
                ZipArchive::ER_READ => 'Read error',
                ZipArchive::ER_WRITE => 'Write error',
                ZipArchive::ER_CRC => 'CRC error',
                ZipArchive::ER_ZIPCLOSED => 'ZIP closed',
                ZipArchive::ER_NOENT => 'No entry',
                ZipArchive::ER_EXISTS => 'Already exists',
                ZipArchive::ER_OPEN => 'Open error',
                ZipArchive::ER_TMPOPEN => 'Temp open error',
                ZipArchive::ER_ZLIB => 'Zlib error',
                ZipArchive::ER_MEMORY => 'Memory error',
                ZipArchive::ER_CHANGED => 'Changed',
                ZipArchive::ER_COMPNOTSUPP => 'Compression not supported',
                ZipArchive::ER_EOF => 'Premature EOF',
                ZipArchive::ER_INVAL => 'Invalid argument',
                ZipArchive::ER_NOZIP => 'Not a ZIP',
                ZipArchive::ER_INTERNAL => 'Internal error',
                ZipArchive::ER_INCONS => 'Inconsistent',
                ZipArchive::ER_REMOVE => 'Cannot remove',
                ZipArchive::ER_DELETED => 'Entry deleted'
            ];
            
            $error = $errorMsg[$openResult] ?? 'Unknown error';
            throw new Exception("Erro ao abrir ZIP: {$error}");
        }
        
        $startTime = microtime(true);
        $entryCount = $zip->numFiles;
        $extractedFiles = [];
        $totalExtractedSize = 0;
        $failedEntries = [];
        $validEntries = [];
        
        // Detecta pastas raiz do ZIP para verificar se já existem
        $rootFolders = [];
        $allEntryNames = [];
        
        // Primeiro, valida todas as entradas e detecta pastas raiz
        for ($i = 0; $i < $entryCount; $i++) {
            $entryName = $zip->getNameIndex($i);
            
            if ($entryName === false) {
                $failedEntries[] = "Entrada #{$i}";
                continue;
            }
            
            $allEntryNames[] = $entryName;
            
            // Detecta pasta raiz (primeiro nível do ZIP)
            $parts = explode('/', str_replace('\\', '/', $entryName));
            $firstPart = $parts[0] ?? '';
            
            if (!empty($firstPart) && !in_array($firstPart, $rootFolders)) {
                // Verifica se é realmente uma pasta raiz (não apenas um arquivo na raiz)
                $isRootFolder = false;
                if (substr($entryName, -1) === '/') {
                    // É um diretório
                    $isRootFolder = true;
                } elseif (count($parts) > 1) {
                    // Tem subpastas, então a primeira parte é uma pasta
                    $isRootFolder = true;
                }
                
                if ($isRootFolder) {
                    $rootFolders[] = $firstPart;
                }
            }
            
            // Sanitiza caminho
            $extractPath = Security::sanitizeExtractPath($entryName);
            
            if ($extractPath === null) {
                Logger::warning("Entrada ZIP rejeitada (path traversal): {$entryName}");
                $failedEntries[] = $entryName;
                continue;
            }
            
            // Verifica se o caminho está protegido (arquivos/pastas do sistema)
            if (Security::isProtectedPath($entryName)) {
                Logger::warning("Entrada ZIP rejeitada (caminho protegido): {$entryName}");
                $failedEntries[] = $entryName . ' (protegido)';
                continue;
            }
            
            // Valida segurança do caminho
            $validation = Security::validatePathSafety($entryName);
            if (!$validation['safe']) {
                Logger::warning("Entrada ZIP rejeitada (validação de segurança): {$entryName} - {$validation['reason']}");
                $failedEntries[] = $entryName . ' (' . $validation['reason'] . ')';
                continue;
            }
            
            // Verifica se é um diretório
            $stats = $zip->statIndex($i);
            if ($stats === false || ($stats['size'] === 0 && substr($entryName, -1) === '/')) {
                // É um diretório, permite mas não conta como arquivo extraído
                continue;
            }
            
            // Valida extensão e conteúdo do arquivo antes de extrair
            $fileValidation = Security::validateFile($entryName);
            if (!$fileValidation['safe']) {
                Logger::warning("Entrada ZIP rejeitada (arquivo perigoso): {$entryName} - {$fileValidation['reason']}");
                $failedEntries[] = $entryName . ' (' . $fileValidation['reason'] . ')';
                continue;
            }
            
            // Valida conteúdo do arquivo (lê do ZIP para verificar)
            try {
                $fileContent = $zip->getFromIndex($i);
                if ($fileContent !== false && !empty($fileContent)) {
                    // Limita tamanho da validação para performance (primeiros 50KB)
                    $contentSample = substr($fileContent, 0, 51200);
                    $contentValidation = Security::validateFileContent($contentSample, $entryName);
                    
                    if (!$contentValidation['safe']) {
                        Logger::warning("Entrada ZIP rejeitada (conteúdo malicioso): {$entryName} - {$contentValidation['reason']}");
                        $failedEntries[] = $entryName . ' (' . $contentValidation['reason'] . ')';
                        continue;
                    }
                }
            } catch (Exception $e) {
                // Se não conseguiu ler o conteúdo, registra mas continua (pode ser arquivo muito grande)
                Logger::debug("Não foi possível validar conteúdo do arquivo: {$entryName}");
            }
            
            $validEntries[] = $entryName;
        }
        
        // Se modo for 'delete', apaga pastas raiz existentes antes de extrair
        if ($overwriteMode === 'delete' && !empty($rootFolders)) {
            $protectedFolders = [];
            foreach ($rootFolders as $folder) {
                // Verifica se a pasta está protegida
                if (Security::isProtectedPath($folder)) {
                    $protectedFolders[] = $folder;
                    Logger::warning("Tentativa de apagar pasta protegida bloqueada: {$folder}");
                    continue;
                }
                
                $folderPath = EXTRACT_DIR . DIRECTORY_SEPARATOR . $folder;
                
                // Verifica se a pasta existe e está dentro do diretório de extração
                if (file_exists($folderPath) && is_dir($folderPath)) {
                    // Valida segurança antes de apagar
                    $validation = Security::validatePathSafety($folder);
                    if (!$validation['safe']) {
                        $protectedFolders[] = $folder;
                        Logger::warning("Tentativa de apagar pasta protegida bloqueada: {$folder} - {$validation['reason']}");
                        continue;
                    }
                    
                    if ($this->deleteDirectory($folderPath)) {
                        Logger::info("Pasta deletada antes de extrair: {$folder}");
                    } else {
                        Logger::warning("Falha ao deletar pasta: {$folder}");
                    }
                }
            }
            
            // Se tentou apagar pastas protegidas, bloqueia a operação
            if (!empty($protectedFolders)) {
                $zip->close();
                @unlink($zipPath);
                throw new Exception('Operação bloqueada: não é permitido apagar pastas ou arquivos do sistema: ' . implode(', ', $protectedFolders));
            }
        }
        
        // Extrai todos os arquivos válidos de uma vez
        if (!empty($validEntries)) {
            $extractResult = $zip->extractTo(EXTRACT_DIR, $validEntries);
            
            if ($extractResult === false) {
                // Se a extração em massa falhou, tenta arquivo por arquivo
                foreach ($validEntries as $entryName) {
                    $extractPath = Security::sanitizeExtractPath($entryName);
                    if ($extractPath === null) continue;
                    
                    $entryDir = dirname($extractPath);
                    if (!is_dir($entryDir)) {
                        @mkdir($entryDir, 0755, true);
                    }
                    
                    $extracted = $zip->extractTo(EXTRACT_DIR, [$entryName]);
                    
                    if ($extracted && file_exists($extractPath)) {
                        $relativePath = str_replace(EXTRACT_DIR . DIRECTORY_SEPARATOR, '', $extractPath);
                        $relativePath = str_replace('\\', '/', $relativePath);
                        $extractedFiles[] = $relativePath;
                        $totalExtractedSize += filesize($extractPath);
                        @chmod($extractPath, 0644);
                    } else {
                        $failedEntries[] = $entryName;
                    }
                }
            } else {
                // Extração em massa bem-sucedida, lista arquivos extraídos
                foreach ($validEntries as $entryName) {
                    $extractPath = Security::sanitizeExtractPath($entryName);
                    if ($extractPath === null) {
                        continue;
                    }
                    
                    // Verifica se o arquivo existe no caminho esperado
                    if (!file_exists($extractPath)) {
                        // Tenta também com o caminho relativo ao EXTRACT_DIR
                        $relativePath = $entryName;
                        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                        $relativePath = ltrim($relativePath, '/\\');
                        $alternativePath = EXTRACT_DIR . DIRECTORY_SEPARATOR . $relativePath;
                        
                        if (file_exists($alternativePath)) {
                            $extractPath = $alternativePath;
                        } else {
                            continue;
                        }
                    }
                    
                    // Calcula o caminho relativo para exibição
                    $relativePath = str_replace(EXTRACT_DIR . DIRECTORY_SEPARATOR, '', $extractPath);
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $extractedFiles[] = $relativePath;
                    $totalExtractedSize += filesize($extractPath);
                    @chmod($extractPath, 0644);
                }
            }
        }
        
        $zip->close();
        $extractTime = round(microtime(true) - $startTime, 2);
        
        // Remove ZIP após extração
        @unlink($zipPath);
        
        if (!empty($failedEntries)) {
            Logger::warning("Algumas entradas do ZIP falharam ao extrair", ['entries' => $failedEntries]);
        }
        
        $result = [
            'extracted' => true,
            'extractTime' => $extractTime,
            'filesCount' => count($extractedFiles),
            'totalExtractedSize' => $totalExtractedSize,
            'totalExtractedSizeMB' => round($totalExtractedSize / 1024 / 1024, 2),
            'filesList' => $extractedFiles,
            'failedEntries' => $failedEntries
        ];
        
        // Adiciona informações sobre modo de overwrite usado
        $result['overwriteMode'] = $overwriteMode;
        if ($overwriteMode === 'delete' && !empty($rootFolders)) {
            $result['rootFolders'] = $rootFolders;
        }
        
        return $result;
    }
    
    /**
     * Deleta diretório recursivamente
     */
    private function deleteDirectory(string $dir): bool {
        if (!file_exists($dir) || !is_dir($dir)) {
            return false;
        }
        
        $realPath = realpath($dir);
        $realExtractDir = realpath(EXTRACT_DIR);
        
        // Validação de segurança: só apaga se estiver dentro do EXTRACT_DIR
        if (!$realPath || !$realExtractDir || strpos($realPath, $realExtractDir) !== 0) {
            return false;
        }
        
        // Verifica se o diretório está protegido
        $relativePath = str_replace($realExtractDir . DIRECTORY_SEPARATOR, '', $realPath);
        if (Security::isProtectedPath($relativePath)) {
            Logger::warning("Tentativa de apagar diretório protegido bloqueada: {$relativePath}");
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            
            // Verifica se o arquivo/pasta dentro também está protegido
            $fileRelativePath = str_replace($realExtractDir . DIRECTORY_SEPARATOR, '', $filePath);
            if (Security::isProtectedPath($fileRelativePath)) {
                Logger::warning("Pulando arquivo/pasta protegido ao deletar diretório: {$fileRelativePath}");
                continue;
            }
            
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                @unlink($filePath);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * Retorna mensagem de erro de upload
     */
    private function getUploadErrorMessage(int $errorCode): string {
        $errors = [
            UPLOAD_ERR_OK => 'Nenhum erro',
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE do formulário',
            UPLOAD_ERR_PARTIAL => 'Upload parcial',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        
        return $errors[$errorCode] ?? 'Erro desconhecido';
    }
}


