<?php
/**
 * Página de Administração de Usuários
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Verifica autenticação e permissão de admin
Auth::requireAdmin();

// Gera CSRF token
$csrfToken = Security::generateCSRFToken();
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Administração de Usuários - Sistema Seguro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            margin: 0;
            background: #eef1f5;
            color: #111;
            min-height: 100vh;
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            color: #1f1f1f;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-admin {
            background: #ffc107;
            color: #000;
        }
        .badge-user {
            background: #17a2b8;
            color: white;
        }
        .badge-active {
            background: #28a745;
            color: white;
        }
        .badge-inactive {
            background: #6c757d;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 14px;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
        }
        .close:hover {
            color: #000;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            .card {
                padding: 20px;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
            }
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
                <h1>👥 Administração de Usuários</h1>
                <div class="btn-group">
                    <a href="upload.php" class="btn btn-secondary">← Voltar</a>
                    <button onclick="openModal('create')" class="btn btn-success">+ Novo Usuário</button>
                    <a href="logout.php" class="btn btn-danger">Sair</a>
                </div>
            </div>

            <div id="alert"></div>

            <div class="table-container">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="empty-state">Carregando usuários...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Criar/Editar Usuário -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Usuário</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="form-group">
                    <label for="username">Usuário *</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50">
                </div>
                
                <div class="form-group">
                    <label for="password">Senha *</label>
                    <input type="password" id="password" name="password" minlength="6">
                    <small style="color: #6c757d;">Deixe em branco para não alterar (ao editar)</small>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="user">Usuário</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="active" name="active" checked>
                        Usuário ativo
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = '<?= htmlspecialchars($csrfToken) ?>';
        let currentEditId = null;

        // Carrega lista de usuários
        function loadUsers() {
            const formData = new FormData();
            formData.append('action', 'list');
            formData.append('<?= CSRF_TOKEN_NAME ?>', csrfToken);

            fetch('admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderUsers(data.data);
                } else {
                    showAlert('Erro ao carregar usuários: ' + data.error, 'error');
                }
            })
            .catch(e => {
                showAlert('Erro ao carregar usuários', 'error');
                console.error(e);
            });
        }

        // Renderiza tabela de usuários
        function renderUsers(users) {
            const tbody = document.querySelector('#usersTable tbody');
            
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Nenhum usuário encontrado</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => {
                const roleBadge = user.role === 'admin' 
                    ? '<span class="badge badge-admin">Admin</span>'
                    : '<span class="badge badge-user">Usuário</span>';
                
                const statusBadge = user.active 
                    ? '<span class="badge badge-active">Ativo</span>'
                    : '<span class="badge badge-inactive">Inativo</span>';
                
                const createdDate = new Date(user.created_at).toLocaleString('pt-BR');
                
                const editBtn = `<button onclick="editUser(${user.id})" class="btn btn-primary btn-sm">Editar</button>`;
                const deleteBtn = user.id === <?= $currentUser['id'] ?? 0 ?> 
                    ? '<button disabled class="btn btn-danger btn-sm">Você</button>'
                    : `<button onclick="deleteUser(${user.id}, '${user.username}')" class="btn btn-danger btn-sm">Deletar</button>`;
                
                return `
                    <tr>
                        <td>${user.id}</td>
                        <td>${escapeHtml(user.username)}</td>
                        <td>${roleBadge}</td>
                        <td>${statusBadge}</td>
                        <td>${createdDate}</td>
                        <td>
                            <div class="actions">
                                ${editBtn}
                                ${deleteBtn}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Abre modal para criar usuário
        function openModal(mode = 'create') {
            const modal = document.getElementById('userModal');
            const form = document.getElementById('userForm');
            const title = document.getElementById('modalTitle');
            const actionField = document.getElementById('formAction');
            
            if (mode === 'create') {
                title.textContent = 'Novo Usuário';
                actionField.value = 'create';
                form.reset();
                document.getElementById('userId').value = '';
                document.getElementById('password').required = true;
            }
            
            modal.style.display = 'block';
        }

        // Fecha modal
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
            document.getElementById('userForm').reset();
            currentEditId = null;
        }

        // Edita usuário
        function editUser(id) {
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);
            formData.append('<?= CSRF_TOKEN_NAME ?>', csrfToken);

            fetch('admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const user = data.data;
                    document.getElementById('modalTitle').textContent = 'Editar Usuário';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('userId').value = user.id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('role').value = user.role;
                    document.getElementById('active').checked = user.active == 1;
                    document.getElementById('password').required = false;
                    currentEditId = id;
                    
                    document.getElementById('userModal').style.display = 'block';
                } else {
                    showAlert('Erro ao carregar usuário: ' + data.error, 'error');
                }
            });
        }

        // Salva usuário
        function saveUser(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            const action = formData.get('action');
            if (action === 'update') {
                formData.append('id', document.getElementById('userId').value);
            }
            
            fetch('admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert(action === 'create' ? 'Usuário criado com sucesso!' : 'Usuário atualizado com sucesso!', 'success');
                    closeModal();
                    loadUsers();
                } else {
                    showAlert('Erro: ' + data.error, 'error');
                }
            })
            .catch(e => {
                showAlert('Erro ao salvar usuário', 'error');
                console.error(e);
            });
        }

        // Deleta usuário
        function deleteUser(id, username) {
            if (!confirm(`Tem certeza que deseja deletar o usuário "${username}"?`)) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('<?= CSRF_TOKEN_NAME ?>', csrfToken);

            fetch('admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert('Usuário deletado com sucesso!', 'success');
                    loadUsers();
                } else {
                    showAlert('Erro: ' + data.error, 'error');
                }
            })
            .catch(e => {
                showAlert('Erro ao deletar usuário', 'error');
                console.error(e);
            });
        }

        // Exibe alerta
        function showAlert(message, type = 'success') {
            const alertDiv = document.getElementById('alert');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            setTimeout(() => {
                alertDiv.style.display = 'none';
            }, 5000);
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Fecha modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Carrega usuários ao carregar a página
        loadUsers();
    </script>
</body>
</html>

