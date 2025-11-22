# 🚀 Launcher Manager em PHP

Sistema completo de gerenciamento de uploads e administração de usuários desenvolvido em PHP com SQLite. Permite upload seguro de arquivos ZIP, extração automática e controle total sobre arquivos e usuários do sistema.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3.0+-003B57?style=flat&logo=sqlite&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Uso](#-uso)
- [Segurança](#-segurança)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Documentação da API](#-documentação-da-api)
- [Troubleshooting](#-troubleshooting)
- [Contribuindo](#-contribuindo)
- [Licença](#-licença)

## ✨ Características

### 🔐 Sistema de Autenticação
- **Autenticação segura** com hash bcrypt
- **Gerenciamento de usuários** via painel administrativo
- **Sistema de roles** (Admin/Usuário)
- **Sessões seguras** com regeneração de ID
- **Proteção CSRF** em todas as operações
- **Rate limiting** para prevenir ataques de força bruta

### 📤 Upload e Extração de Arquivos
- **Upload de arquivos ZIP** com progresso em tempo real
- **Extração automática** de arquivos ZIP
- **Validação de segurança** contra ZIP bombs
- **Modos de sobrescrita**:
  - **Merge**: Apenas sobrescreve arquivos existentes
  - **Delete**: Apaga pastas existentes antes de extrair
- **Proteção de arquivos do sistema** (não permite sobrescrever arquivos críticos)
- **Validação de conteúdo** contra arquivos maliciosos

### 🛡️ Segurança
- **Proteção contra arquivos PHP maliciosos**
- **Bloqueio de executáveis e scripts perigosos**
- **Detecção de webshells e backdoors**
- **Proteção contra path traversal**
- **Validação de extensões de arquivos**
- **Sanitização de nomes de arquivos**
- **Proteção de pastas e arquivos do sistema**

### 👥 Administração
- **Painel administrativo completo**
- **CRUD de usuários** (Create, Read, Update, Delete)
- **Gerenciamento de roles** (Admin/Usuário)
- **Ativação/Desativação de usuários**
- **Logs detalhados** de todas as operações

### 🎨 Interface
- **Design moderno e responsivo**
- **Vídeo de fundo** (opcional)
- **Progresso de upload em tempo real**
- **Feedback visual** de todas as operações
- **Páginas de erro personalizadas** (400, 401, 403, 404, 500, 503)

## 📦 Requisitos

### Servidor
- **PHP 7.4+** (recomendado PHP 8.0+)
- **Extensões PHP necessárias**:
  - `zip` - Para extração de arquivos ZIP
  - `pdo_sqlite` - Para banco de dados SQLite
  - `fileinfo` - Para validação de MIME types
  - `session` - Para gerenciamento de sessões
  - `hash` - Para hashing de senhas
- **Servidor web** (Apache/Nginx)
- **Mod_rewrite** habilitado (Apache) ou configuração equivalente (Nginx)

### Permissões
- Permissão de escrita no diretório `database/`
- Permissão de escrita no diretório `logs/`
- Permissão de escrita no diretório `uploads/`
- Permissão de escrita no diretório de extração (configurável)

## 🚀 Instalação

### 1. Clone ou baixe o projeto

```bash
git clone <url-do-repositorio> launcher-manager
cd launcher-manager
```

Ou baixe o ZIP e extraia no diretório desejado.

### 2. Configure o servidor web

#### Apache (.htaccess já configurado)
O arquivo `.htaccess` já está configurado. Certifique-se de que `mod_rewrite` está habilitado:

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

#### Nginx
Adicione a configuração equivalente no seu `nginx.conf`:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3. Configure as permissões

```bash
chmod 755 database/
chmod 755 logs/
chmod 755 uploads/
chmod 644 .htaccess
chmod 644 config.php
```

### 4. Configure o arquivo `config.php`

Edite o arquivo `config.php` e configure:

```php
// Credenciais do primeiro admin (opcional - usado apenas na primeira inicialização)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('sua_senha_segura', PASSWORD_BCRYPT));

// Diretório de extração (onde os arquivos ZIP serão extraídos)
define('EXTRACT_DIR', BASE_DIR); // Ou outro diretório de sua escolha
```

### 5. Gere um hash de senha seguro

Para gerar um hash de senha para o admin inicial, use:

```php
<?php
echo password_hash('sua_senha_segura', PASSWORD_BCRYPT);
?>
```

Cole o resultado em `ADMIN_PASSWORD_HASH` no `config.php`.

### 6. Acesse o sistema

Abra seu navegador e acesse:

```
http://localhost/launcher-manager/
```

Ou o domínio configurado no seu servidor.

## ⚙️ Configuração

### Configurações Principais (`config.php`)

#### Segurança
```php
// Nome do usuário admin padrão
define('ADMIN_USERNAME', 'admin');

// Hash da senha do admin padrão
define('ADMIN_PASSWORD_HASH', '$2y$10$...');

// Nome do token CSRF
define('CSRF_TOKEN_NAME', 'csrf_token');

// Nome da sessão
define('SESSION_NAME', 'upload_system');

// Tempo de vida da sessão (em segundos)
define('SESSION_LIFETIME', 3600); // 1 hora
```

#### Upload
```php
// Tamanho máximo de arquivo (em bytes)
define('MAX_FILE_SIZE', 500 * 1024 * 1024); // 500 MB

// Tamanho total máximo (em bytes)
define('MAX_TOTAL_SIZE', 1024 * 1024 * 1024); // 1 GB

// Extensões permitidas (apenas ZIP no upload)
define('ALLOWED_EXTENSIONS', ['zip']);

// MIME types permitidos
define('ALLOWED_MIME_TYPES', [
    'application/zip',
    'application/x-zip-compressed',
    'application/x-zip'
]);
```

#### Diretórios
```php
// Diretório base do projeto
define('BASE_DIR', __DIR__);

// Diretório de uploads (onde os ZIPs são salvos temporariamente)
define('UPLOAD_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'uploads');

// Diretório de extração (onde os arquivos são extraídos)
define('EXTRACT_DIR', BASE_DIR);

// Diretório de logs
define('LOG_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'logs');

// Diretório do banco de dados
define('DATABASE_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'database');
```

### Configurações PHP (`config.php`)

O sistema já configura automaticamente:

```php
// Limites de upload
ini_set('upload_max_filesize', '500M');
ini_set('post_max_size', '520M');
ini_set('max_execution_time', 300); // 5 minutos
ini_set('max_input_time', 300);
ini_set('memory_limit', '512M');
```

## 📖 Uso

### Primeiro Acesso

1. Acesse a página inicial: `http://localhost/launcher-manager/`
2. Faça login com as credenciais configuradas em `config.php`:
   - **Usuário**: `admin` (ou o que você configurou)
   - **Senha**: A senha que você configurou
3. Após o login, você será redirecionado para a página de upload

### Upload de Arquivos

1. Na página de upload, clique em "Escolher arquivo" e selecione um arquivo ZIP
2. **Opcional**: Marque a opção "Apagar pasta existente antes de extrair" se quiser sobrescrever completamente
3. Clique em "Enviar ZIP"
4. Acompanhe o progresso em tempo real
5. Após o upload, o arquivo será extraído automaticamente

### Modos de Extração

#### Modo Merge (Padrão)
- Apenas sobrescreve arquivos existentes
- Arquivos novos são adicionados
- Pastas existentes são mantidas

#### Modo Delete
- **Apaga completamente** pastas existentes antes de extrair
- Garante uma extração limpa
- **Não apaga** pastas protegidas do sistema

### Administração de Usuários

1. Acesse o painel admin clicando em "👥 Admin" (apenas para administradores)
2. **Criar usuário**:
   - Clique em "Novo Usuário"
   - Preencha os dados (usuário, senha, role)
   - Clique em "Salvar"
3. **Editar usuário**:
   - Clique em "Editar" ao lado do usuário
   - Modifique os dados desejados
   - Deixe a senha em branco para não alterar
   - Clique em "Salvar"
4. **Deletar usuário**:
   - Clique em "Deletar" ao lado do usuário
   - Confirme a exclusão
   - **Nota**: Não é possível deletar o último admin ou sua própria conta

### Roles (Papéis)

- **Admin**: Acesso completo ao sistema, incluindo painel administrativo
- **Usuário**: Acesso apenas à funcionalidade de upload

## 🛡️ Segurança

### Proteções Implementadas

#### Autenticação e Autorização
- ✅ Hash de senha com bcrypt (cost 10)
- ✅ Proteção CSRF em todas as operações
- ✅ Rate limiting (5 tentativas de login a cada 5 minutos)
- ✅ Regeneração de ID de sessão
- ✅ Validação de sessão com timeout
- ✅ Verificação de roles antes de operações sensíveis

#### Upload e Extração
- ✅ Validação de extensão de arquivo
- ✅ Validação de MIME type
- ✅ Verificação de assinatura de arquivo ZIP
- ✅ Proteção contra ZIP bombs
- ✅ Limite de tamanho de arquivo e total
- ✅ Sanitização de nomes de arquivos

#### Proteção de Arquivos
- ✅ Bloqueio de extensões perigosas (PHP, executáveis, scripts)
- ✅ Detecção de conteúdo malicioso
- ✅ Proteção contra webshells e backdoors
- ✅ Proteção contra path traversal
- ✅ Proteção de pastas e arquivos do sistema
- ✅ Validação de assinaturas de executáveis

#### Extensões Bloqueadas por Padrão
- Scripts: `.php`, `.php3`, `.php4`, `.php5`, `.phtml`
- Executáveis: `.bat`, `.cmd`, `.com`, `.scr`, `.vbs`
- Shell scripts: `.sh`, `.bash`, `.zsh`, `.csh`
- PowerShell: `.ps1`, `.psm1`, `.psd1`
- Outros: `.py`, `.pl`, `.cgi`, `.asp`, `.aspx`, `.jsp`, `.jar`

#### Extensões Permitidas (Explicitamente)
- `.exe`, `.dll`, `.bin`, `.dat`, `.ini`, `.txt`, `.xml`, `.zip`
- `.pak`, `.l2`, `.sys`, `.cfg`, `.log`, `.bak`, `.tmp`
- `.cache`, `.idx`, `.grp`, `.pck`, `.ukx`, `.ifr`, `.htm`
- `.unr`, `.ogg`, `.uax`, `.usx`, `.utx`, `.bmp`, `.ddf`
- `.des`, `.ffe`, `.gly`, `.vxd`, `.dmp`, `.xdat`, `.i64`, `.bm`, `.ugx`
- Arquivos de jogo Unreal Engine: `.u`, `.int`, `.ttf`, `.ugx`

### Arquivos e Pastas Protegidos

Os seguintes itens **não podem ser sobrescritos**:

**Pastas:**
- `database/` - Banco de dados SQLite
- `logs/` - Arquivos de log
- `uploads/` - Arquivos enviados
- `errors/` - Páginas de erro
- `includes/` - Arquivos do sistema
- `classes/` - Classes PHP
- `video/` - Vídeos do sistema

**Arquivos:**
- `config.php` - Configurações
- `index.php` - Página inicial
- `login.php` - API de login
- `logout.php` - Logout
- `upload.php` - Página de upload
- `admin.php` - Painel administrativo
- `admin_api.php` - API de admin
- `.htaccess` - Configuração do Apache

## 📁 Estrutura do Projeto

```
launcher-manager/
│
├── classes/                    # Classes PHP do sistema
│   ├── Auth.php               # Autenticação e sessões
│   ├── Database.php           # Gerenciamento SQLite
│   ├── ErrorHandler.php       # Manipulação de erros
│   ├── FileUploader.php       # Upload e extração de ZIPs
│   ├── Logger.php             # Sistema de logs
│   ├── Security.php           # Validações de segurança
│   └── User.php               # Gerenciamento de usuários
│
├── database/                 # Banco de dados SQLite
│   ├── .htaccess              # Proteção do diretório
│   └── system.db              # Banco SQLite (criado automaticamente)
│
├── errors/                     # Páginas de erro HTTP
│   ├── .htaccess              # Proteção do diretório
│   ├── 400.php                # Bad Request
│   ├── 401.php                # Unauthorized
│   ├── 403.php                # Forbidden
│   ├── 404.php                # Not Found
│   ├── 500.php                # Internal Server Error
│   └── 503.php                # Service Unavailable
│
├── includes/                   # Arquivos de inclusão
│   └── bootstrap.php          # Inicialização do sistema
│
├── logs/                       # Arquivos de log
│   ├── system_YYYY-MM-DD.log  # Logs do sistema
│   └── php_errors.log         # Erros do PHP
│
├── uploads/                    # Uploads temporários
│   ├── .htaccess              # Proteção do diretório
│   └── *.zip                  # Arquivos ZIP enviados (removidos após extração)
│
├── video/                      # Vídeos do sistema (opcional)
│   └── mp4/
│       └── video-bg.mp4       # Vídeo de fundo
│
├── .htaccess                   # Configuração Apache
├── admin.php                   # Painel administrativo
├── admin_api.php               # API de administração
├── config.php                  # Configurações do sistema
├── index.php                   # Página de login
├── login.php                   # API de login (AJAX)
├── logout.php                  # Logout
├── upload.php                  # Página de upload
└── README.md                   # Este arquivo
```

## 🔌 Documentação da API

### Endpoints Disponíveis

#### `POST /login.php`
API de login via AJAX.

**Parâmetros:**
```json
{
    "usuario": "admin",
    "senha": "password",
    "csrf_token": "token_gerado"
}
```

**Resposta (sucesso):**
```json
{
    "success": true
}
```

**Resposta (erro):**
```json
{
    "success": false,
    "error": "Mensagem de erro"
}
```

#### `POST /upload.php`
Upload e extração de arquivo ZIP.

**Parâmetros (FormData):**
- `file`: Arquivo ZIP
- `csrf_token`: Token CSRF
- `overwrite_mode`: `"merge"` ou `"delete"` (opcional)

**Resposta (sucesso):**
```json
{
    "success": true,
    "data": {
        "originalName": "arquivo.zip",
        "savedName": "1234567890_abc123_arquivo.zip",
        "path": "/path/to/uploads/arquivo.zip",
        "size": 1234567,
        "sizeMB": 1.18,
        "isZip": true,
        "extracted": true,
        "extractTime": 0.05,
        "filesCount": 10,
        "totalExtractedSize": 2345678,
        "totalExtractedSizeMB": 2.24,
        "filesList": ["arquivo1.txt", "pasta/arquivo2.txt"],
        "failedEntries": [],
        "overwriteMode": "merge",
        "rootFolders": ["pasta"]
    }
}
```

#### `POST /admin_api.php`
API de administração de usuários (requer autenticação admin).

**Parâmetros (FormData):**
- `action`: `"list"`, `"get"`, `"create"`, `"update"`, `"delete"`
- `id`: ID do usuário (para get, update, delete)
- `username`: Nome de usuário (para create, update)
- `password`: Senha (para create, update)
- `role`: `"admin"` ou `"user"` (para create, update)
- `active`: `true` ou `false` (para create, update)
- `csrf_token`: Token CSRF

**Ações disponíveis:**

1. **Listar usuários** (`action: "list"`):
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "username": "admin",
            "role": "admin",
            "active": 1,
            "created_at": "2024-01-01 12:00:00",
            "updated_at": "2024-01-01 12:00:00"
        }
    ]
}
```

2. **Criar usuário** (`action: "create"`):
```json
{
    "success": true,
    "id": 2
}
```

3. **Atualizar usuário** (`action: "update"`):
```json
{
    "success": true,
    "message": "Usuário atualizado com sucesso"
}
```

4. **Deletar usuário** (`action: "delete"`):
```json
{
    "success": true,
    "message": "Usuário deletado com sucesso"
}
```

## 🐛 Troubleshooting

### Problemas Comuns

#### Erro: "Falha ao salvar arquivo no servidor"
**Causa**: Permissões insuficientes no diretório `uploads/`
**Solução**:
```bash
chmod 755 uploads/
chown www-data:www-data uploads/
```

#### Erro: "Erro ao conectar ao banco de dados"
**Causa**: Permissões insuficientes no diretório `database/`
**Solução**:
```bash
chmod 755 database/
chown www-data:www-data database/
```

#### Erro 500 após upload
**Causa**: Limites de PHP muito baixos
**Solução**: Edite `php.ini` ou `.htaccess`:
```ini
upload_max_filesize = 500M
post_max_size = 520M
max_execution_time = 300
memory_limit = 512M
```

#### Upload muito lento
**Causa**: Configurações de timeout muito baixas
**Solução**: Aumente os valores em `config.php`:
```php
ini_set('max_execution_time', 600); // 10 minutos
ini_set('max_input_time', 600);
```

#### Arquivos não são extraídos
**Causa**: Pastas protegidas ou validação de segurança
**Solução**: Verifique os logs em `logs/system_YYYY-MM-DD.log`

#### Não consigo fazer login
**Causa**: Hash de senha incorreto ou banco não inicializado
**Solução**:
1. Verifique o hash em `config.php`
2. Delete `database/system.db` para recriar
3. Gere novo hash: `password_hash('senha', PASSWORD_BCRYPT)`

### Logs

Os logs estão disponíveis em:
- **Sistema**: `logs/system_YYYY-MM-DD.log`
- **PHP Errors**: `logs/php_errors.log`

Para habilitar logs de debug, altere em `config.php`:
```php
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Padrões de Código
- Use PSR-12 para estilo de código PHP
- Documente funções e classes
- Adicione logs para operações importantes
- Mantenha a segurança em mente

## 📝 Changelog

### Versão 1.0.0 (Atual)
- ✅ Sistema completo de autenticação
- ✅ Upload e extração de arquivos ZIP
- ✅ Painel administrativo de usuários
- ✅ Proteção contra arquivos maliciosos
- ✅ Sistema de logs
- ✅ Páginas de erro personalizadas
- ✅ Validações de segurança completas

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 👨‍💻 Autor

Desenvolvido com ❤️ para gerenciamento seguro de uploads e arquivos.

## 🙏 Agradecimentos

- Comunidade PHP
- Contribuidores do projeto
- Usuários que reportam bugs e sugerem melhorias

---

**⚠️ IMPORTANTE**: Este sistema é poderoso e permite upload de arquivos. Use com cuidado e sempre mantenha:
- Senhas fortes
- Configurações de segurança atualizadas
- Logs habilitados
- Backups regulares do banco de dados

**📧 Suporte**: Para questões e suporte, abra uma issue no repositório do projeto.

