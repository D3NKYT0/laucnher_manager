<?php
/**
 * Página de Upload
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Verifica autenticação
Auth::requireAuth();

// Processa upload se houver POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Configurações adicionais para uploads grandes
    set_time_limit(0); // Remove limite de execução
    ignore_user_abort(false); // Para se o usuário cancelar
    
    // Headers para uploads grandes
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: keep-alive');
    header('Keep-Alive: timeout=3600, max=1000');
    
    // Desabilita buffering de saída para feedback em tempo real
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $uploader = new FileUploader();
    $result = $uploader->processUpload();
    
    echo json_encode($result);
    exit;
}

// Bloquear cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Gera CSRF token
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Upload de Arquivos - Sistema Seguro</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            background: #eef1f5;
            color: #111;
        }
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: brightness(55%);
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f1f1f;
        }
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            display: inline-block;
        }
        .btn-admin {
            background: #28a745;
            color: white;
        }
        .btn-admin:hover {
            background: #218838;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .row {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        input[type=file] {
            background: #fafafa;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ccc;
            cursor: pointer;
            flex: 1;
            min-width: 200px;
        }
        button {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            background: #1a73e8;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
        }
        button:hover:not(:disabled) {
            background: #155ab6;
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        #btnClear {
            background: #888;
        }
        #btnClear:hover:not(:disabled) {
            background: #666;
        }
        .progress-wrap {
            margin-top: 15px;
            background: #e5e5e5;
            border-radius: 14px;
            overflow: hidden;
            height: 26px;
        }
        .bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg,#007cf0,#00dfd8);
            transition: width 0.15s linear;
        }
        .info {
            margin-top: 12px;
            font-size: 15px;
            text-align: center;
            line-height: 1.6em;
        }
        .small {
            font-size: 13px;
            color: #444;
        }
        .result-box {
            margin-top: 25px;
            background: #fafafa;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #ddd;
        }
        .result-box h3 {
            margin-top: 0;
        }
        details summary {
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #ddd;
            max-height: 350px;
            overflow: auto;
            font-size: 13px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="bg-video">
        <source src="/video/mp4/video-bg.mp4" type="video/mp4">
    </video>
    
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Upload de Arquivos ZIP</h1>
                <div class="header-actions">
                    <?php if (Auth::isAdmin()): ?>
                        <a href="admin.php" class="btn btn-admin">👥 Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn logout-btn">Sair</a>
                </div>
            </div>

            <div class="row">
                <input id="file" type="file" accept=".zip" />
                <button id="btnUpload">Enviar ZIP</button>
                <button id="btnClear">Limpar</button>
            </div>

            <div class="row" style="margin-top: 10px; justify-content: flex-start;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #495057;">
                    <input type="checkbox" id="overwriteDelete" style="width: auto; cursor: pointer;">
                    <span>Apagar pasta existente antes de extrair (sobrescreve completamente)</span>
                </label>
            </div>

            <div class="progress-wrap">
                <div id="bar" class="bar"></div>
            </div>

            <div class="info">
                <div>
                    <strong id="percent">0%</strong> —
                    <span id="size">0 MB</span>
                </div>
                <div class="small">
                    Velocidade: <span id="speed">0 MB/s</span> •
                    Tempo restante: <span id="eta">--:--</span>
                </div>
                <div class="small">
                    Status: <span id="status">Pronto</span>
                </div>
            </div>

            <div class="result-box">
                <h3>Resultado</h3>
                <div id="result"></div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        const fileInput = document.getElementById('file');
        const btnUpload = document.getElementById('btnUpload');
        const btnClear = document.getElementById('btnClear');
        const bar = document.getElementById('bar');
        const percent = document.getElementById('percent');
        const sizeText = document.getElementById('size');
        const speedText = document.getElementById('speed');
        const etaText = document.getElementById('eta');
        const statusText = document.getElementById('status');
        const resultDiv = document.getElementById('result');

        let lastLoaded = 0;
        let lastTime = 0;
        let lastProgressTime = 0; // Timestamp do último progresso
        let connectionCheckInterval = null; // Intervalo de verificação de conexão

        function formatBytes(bytes){
            if(bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B','KB','MB','GB','TB'];
            const i = Math.floor(Math.log(bytes)/Math.log(k));
            return parseFloat((bytes/Math.pow(k,i)).toFixed(2)) + ' ' + sizes[i];
        }

        function formatTime(sec){
            if(!isFinite(sec) || sec < 0) return '--:--';
            sec = Math.round(sec);
            const h = Math.floor(sec/3600); sec %= 3600;
            const m = Math.floor(sec/60); const s = sec%60;
            if(h>0) return `${h}h ${String(m).padStart(2,'0')}m`;
            return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }

        fileInput.addEventListener("change", function () {
            const f = fileInput.files[0];
            if (!f) return;
            sizeText.innerText = (f.size / 1024 / 1024).toFixed(2) + " MB";
            etaText.innerText = "calculando...";
            statusText.innerText = "Arquivo selecionado — pronto para enviar";
        });

        btnUpload.addEventListener('click', () => {
            const f = fileInput.files[0];
            if(!f) {
                resultDiv.innerHTML = '<div class="error">Selecione um arquivo .zip</div>';
                return;
            }
            if(!f.name.toLowerCase().endsWith('.zip')) {
                resultDiv.innerHTML = '<div class="error">Envie apenas arquivos ZIP</div>';
                return;
            }

            btnUpload.disabled = true;
            statusText.innerText = "Iniciando upload...";
            resultDiv.innerHTML = '';

            const form = new FormData();
            form.append('file', f);
            form.append('<?= CSRF_TOKEN_NAME ?>', '<?= $csrfToken ?>');
            
            // Adiciona modo de overwrite
            const overwriteMode = document.getElementById('overwriteDelete').checked ? 'delete' : 'merge';
            form.append('overwrite_mode', overwriteMode);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload.php', true);
            xhr.setRequestHeader('X-CSRF-Token', '<?= $csrfToken ?>');
            
            // Configurações para uploads grandes
            xhr.timeout = 0; // Sem timeout (ilimitado)
            
            // Variável para rastrear último progresso (para detectar conexão perdida)
            let lastProgressTime = Date.now();
            let connectionCheckInterval = null;
            
            // Monitora conexão e mantém ativa durante uploads longos
            connectionCheckInterval = setInterval(() => {
                const now = Date.now();
                // Se não houve progresso em 2 minutos e ainda está carregando, pode ter perdido conexão
                if (now - lastProgressTime > 120000 && xhr.readyState < 4) {
                    console.warn('Possível perda de conexão detectada - sem progresso há 2 minutos');
                    statusText.innerText = "Verificando conexão...";
                }
            }, 30000); // Verifica a cada 30 segundos

            const startTime = performance.now();
            lastLoaded = 0;
            lastTime = startTime;

            xhr.upload.addEventListener('progress', (e) => {
                if(e.lengthComputable){
                    const now = performance.now();
                    const loaded = e.loaded;
                    const total = e.total;
                    
                    // Atualiza timestamp do último progresso
                    lastProgressTime = Date.now();

                    const percentVal = Math.round((loaded/total) * 100);
                    bar.style.width = percentVal + '%';
                    percent.innerText = percentVal + '%';

                    const dt = (now - lastTime) / 1000;
                    const dBytes = loaded - lastLoaded;
                    let speed = dt > 0 ? dBytes / dt : 0;

                    lastLoaded = loaded;
                    lastTime = now;

                    speedText.innerText = (speed / (1024*1024)).toFixed(2) + " MB/s";

                    const remaining = total - loaded;
                    const eta = speed > 0 ? remaining / speed : Infinity;
                    etaText.innerText = formatTime(eta);

                    statusText.innerText = `Enviando... (${formatBytes(loaded)} / ${formatBytes(total)})`;
                    
                    // Atualiza título da aba para mostrar progresso
                    document.title = `Upload: ${percentVal}% - Launcher Manager`;
                }
            });

            xhr.onload = () => {
                clearInterval(connectionCheckInterval); // Limpa intervalo de verificação
                document.title = 'Upload de Arquivos - Sistema Seguro'; // Restaura título
                btnUpload.disabled = false;

                let json;
                try {
                    json = JSON.parse(xhr.responseText);
                } catch (e){
                    resultDiv.innerHTML = `<div class="error">Resposta inválida do servidor</div>`;
                    statusText.innerText = "Erro ao processar resposta";
                    return;
                }

                if(!json.success){
                    resultDiv.innerHTML = `<div class="error">${escapeHtml(json.error)}</div>`;
                    statusText.innerText = "Erro";
                    bar.style.width = '0%';
                    percent.innerText = '0%';
                    return;
                }

                statusText.innerText = "Concluído";
                bar.style.width = "100%";
                percent.innerText = "100%";

                const data = json.data || json;
                let html = `<div class="success">`;
                html += `<p><b>Arquivo enviado:</b> ${escapeHtml(data.originalName)} (${data.sizeMB || data.fileSizeMB} MB)</p>`;
                
                if(data.extracted) {
                    html += `<p><b>Tempo de extração:</b> ${data.extractTime} s</p>`;
                    html += `<p><b>Arquivos extraídos:</b> ${data.filesCount}</p>`;
                    html += `<p><b>Total extraído:</b> ${data.totalExtractedSizeMB || 0} MB</p>`;
                    
                    // Mostra informação sobre modo de overwrite
                    if(data.overwriteMode === 'delete' && data.rootFolders && data.rootFolders.length > 0) {
                        html += `<p><b>Modo:</b> <span style="color: #dc3545;">Pastas existentes foram apagadas antes de extrair</span></p>`;
                        html += `<p><b>Pastas processadas:</b> ${escapeHtml(data.rootFolders.join(', '))}</p>`;
                    } else if(data.overwriteMode) {
                        html += `<p><b>Modo:</b> <span style="color: #28a745;">Apenas sobrescreveu arquivos existentes</span></p>`;
                    }
                    
                    if(data.filesList && data.filesList.length > 0) {
                        html += `<details><summary>Mostrar lista de arquivos</summary>`;
                        html += `<pre>${escapeHtml(data.filesList.join("\n"))}</pre>`;
                        html += `</details>`;
                    }
                    
                    if(data.failedEntries && data.failedEntries.length > 0) {
                        html += `<details><summary>Avisos (arquivos não extraídos)</summary>`;
                        html += `<pre>${escapeHtml(data.failedEntries.join("\n"))}</pre>`;
                        html += `</details>`;
                    }
                }
                
                html += `</div>`;
                resultDiv.innerHTML = html;
            };

            xhr.onerror = () => {
                clearInterval(connectionCheckInterval); // Limpa intervalo de verificação
                document.title = 'Upload de Arquivos - Sistema Seguro'; // Restaura título
                btnUpload.disabled = false;
                resultDiv.innerHTML = `<div class="error">Erro de comunicação com o servidor. Verifique sua conexão e tente novamente.</div>`;
                statusText.innerText = "Erro de Conexão";
                bar.style.width = '0%';
                percent.innerText = '0%';
            };
            
            xhr.onabort = () => {
                clearInterval(connectionCheckInterval);
                document.title = 'Upload de Arquivos - Sistema Seguro';
                btnUpload.disabled = false;
                statusText.innerText = "Upload cancelado pelo usuário";
            };

            xhr.send(form);
        });

        btnClear.addEventListener('click', () => {
            fileInput.value = '';
            bar.style.width = '0%';
            percent.innerText = '0%';
            sizeText.innerText = '0 MB';
            speedText.innerText = '0 MB/s';
            etaText.innerText = '--:--';
            statusText.innerText = 'Pronto';
            resultDiv.innerHTML = '';
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    })();
    </script>
</body>
</html>
