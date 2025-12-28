# QR Combustível - Sistema Administrativo de Abastecimento

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/license-MIT-green)

Sistema web para gestão e controle de abastecimento de veículos, desenvolvido em PHP com arquitetura MVC simplificada.

## 📋 Índice

- [Sobre o Projeto](#sobre-o-projeto)
- [Funcionalidades](#funcionalidades)
- [Tecnologias](#tecnologias)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Uso](#uso)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [API](#api)
- [Segurança](#segurança)
- [Contribuição](#contribuição)
- [Troubleshooting](#troubleshooting)
- [Licença](#licença)

## 🎯 Sobre o Projeto

O **QR Combustível** é um sistema administrativo desenvolvido para facilitar o gerenciamento de frotas de veículos e seus respectivos abastecimentos. Com interface moderna e responsiva, permite controle completo sobre:

- Cadastro e gestão de veículos
- Registro de abastecimentos
- Controle de usuários com diferentes níveis de acesso
- Dashboard com estatísticas e relatórios
- Sistema de recuperação de senha

### 🎨 Capturas de Tela

**Tela de Login**
- Design moderno com gradientes
- Animações suaves
- Recuperação de senha integrada

**Dashboard**
- Cards de estatísticas
- Tabelas interativas
- Navegação intuitiva

**Gestão de Veículos**
- CRUD completo
- Busca e paginação
- Modais responsivos

## ✨ Funcionalidades

### Autenticação e Autorização
- ✅ Login com usuário e senha
- ✅ Autenticação JWT com expiração
- ✅ Recuperação de senha por e-mail
- ✅ Controle de acesso baseado em roles (Admin/User)
- ✅ Sessões seguras

### Gestão de Veículos
- ✅ Cadastro de veículos (placa, modelo, marca)
- ✅ Listagem com busca e paginação
- ✅ Edição e exclusão
- ✅ Visualização detalhada

### Dashboard
- ✅ Estatísticas de veículos
- ✅ Abastecimentos recentes
- ✅ Veículos ativos
- ✅ Gastos totais
- ✅ Consumo médio

### Segurança
- ✅ Senhas criptografadas com BCrypt
- ✅ Tokens JWT com expiração
- ✅ Sanitização de inputs
- ✅ Proteção contra SQL Injection
- ✅ Proteção contra XSS
- ✅ HTTPS recomendado

## 🚀 Tecnologias

### Backend
- **PHP 7.4+**: Linguagem principal
- **MySQL 5.7+**: Banco de dados relacional
- **Composer**: Gerenciador de dependências
- **PHPMailer 7.0**: Biblioteca para envio de e-mails

### Frontend
- **HTML5**: Estrutura semântica
- **CSS3**: Estilos modernos com variáveis CSS
- **JavaScript ES6+**: Interatividade
- **jQuery 3.6**: Requisições AJAX e manipulação DOM
- **Bootstrap 5.3**: Framework CSS responsivo
- **Font Awesome 6.4**: Biblioteca de ícones

### Servidor
- **Apache 2.4+** ou **Nginx 1.18+**
- **PHP-FPM** (recomendado para Nginx)

## 📦 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

```bash
# Verificar versão do PHP
php -v  # Necessário: PHP >= 7.4

# Verificar MySQL
mysql --version  # Necessário: MySQL >= 5.7 ou MariaDB >= 10.3

# Verificar Composer
composer --version  # Necessário para instalar dependências
```

### Requisitos do Sistema
- **PHP**: 7.4 ou superior
- **MySQL**: 5.7+ ou MariaDB 10.3+
- **Apache**: 2.4+ ou Nginx 1.18+
- **Composer**: 2.0+

### Extensões PHP Necessárias
```ini
extension=mysqli
extension=mbstring
extension=openssl
extension=json
extension=session
```

## 🔧 Instalação

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/admin_abastecimento.git
cd admin_abastecimento
```

### 2. Instale as Dependências

```bash
composer install
```

### 3. Configure o Banco de Dados

```bash
# Acesse o MySQL
mysql -u root -p

# Crie o banco de dados
CREATE DATABASE conceit1_combustivel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Importe a estrutura (se houver arquivo SQL)
mysql -u root -p conceit1_combustivel < database/schema.sql
```

#### Estrutura das Tabelas

```sql
-- Tabela de usuários
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    perfil ENUM('admin', 'user') DEFAULT 'user',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de veículos
CREATE TABLE veiculos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(10) NOT NULL UNIQUE,
    modelo VARCHAR(100) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_placa (placa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de recuperação de senha
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de abastecimentos (estrutura futura)
CREATE TABLE abastecimentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT NOT NULL,
    user_id INT NOT NULL,
    data_abastecimento DATETIME NOT NULL,
    litros DECIMAL(10,2) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    tipo_combustivel ENUM('gasolina', 'etanol', 'diesel', 'gnv') NOT NULL,
    km_atual INT,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES usuarios(id),
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_data (data_abastecimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Criar Usuário Admin Inicial

```sql
-- Senha: admin123 (trocar após primeiro login)
INSERT INTO usuarios (usuario, senha, email, perfil, ativo) 
VALUES (
    'admin', 
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5erg7kx3H6Qy6',
    'admin@qrcombustivel.com.br',
    'admin',
    1
);
```

### 4. Configure o Apache/Nginx

#### Apache (.htaccess já incluído)

```apache
<VirtualHost *:80>
    ServerName qrcombustivel.local
    DocumentRoot /var/www/html/admin_abastecimento
    
    <Directory /var/www/html/admin_abastecimento>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/qrcombustivel_error.log
    CustomLog ${APACHE_LOG_DIR}/qrcombustivel_access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name qrcombustivel.local;
    root /var/www/html/admin_abastecimento;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### 5. Configure Permissões

```bash
# Dê permissões de escrita para o diretório storage
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Proteja o arquivo de configuração
chmod 600 config/config.php
```

## ⚙️ Configuração

### Arquivo config/config.php

Edite o arquivo `config/config.php` com suas configurações:

```php
<?php
// Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'conceit1_combustivel');

// Identidade Visual
define('COMPANY_NAME', 'QR Combustível');
define('COMPANY_LOGO', 'assets/QR_Combustivel.png');

// Cores
define('COLOR_PRIMARY_1', '#ced4da');
define('COLOR_PRIMARY_2', '#1b5175');
define('COLOR_SECONDARY_1', '#c1c3c7');
define('COLOR_SECONDARY_2', '#f07a28');

// Segurança
define('JWT_SECRET', 'sua_chave_secreta_aleatoria_aqui');
define('JWT_ALGORITHM', 'HS256');
define('TOKEN_EXPIRY', 3600); // 1 hora

// URL Base
define('BASE_URL', 'http://localhost/admin_abastecimento/');

// Configurações de E-mail
define('MAIL_FROM', 'noreply@qrcombustivel.com.br');
define('MAIL_FROM_NAME', 'QR Combustível');
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hora

// SMTP (opcional, mas recomendado)
define('MAIL_USE_SMTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@gmail.com');
define('SMTP_PASS', 'sua_senha_app');
define('SMTP_SECURE', 'tls');

// Debug (false em produção)
define('DEBUG', false);
```

### Configuração de E-mail (PHPMailer)

Para usar Gmail como SMTP:

1. Ative "Verificação em duas etapas" na sua conta Google
2. Gere uma "Senha de app" em: https://myaccount.google.com/apppasswords
3. Use essa senha no `SMTP_PASS`

### Variáveis de Ambiente (Recomendado)

Para maior segurança, use arquivo `.env`:

```bash
# Crie arquivo .env
cp .env.example .env

# Edite com suas configurações
nano .env
```

## 📖 Uso

### Acessar o Sistema

1. Abra o navegador em: `http://localhost/admin_abastecimento/`
2. Faça login com:
   - **Usuário**: admin
   - **Senha**: admin123

### Fluxo de Uso

1. **Login**: Autentique-se no sistema
2. **Dashboard**: Visualize estatísticas gerais
3. **Veículos**: Gerencie a frota
   - Clique em "Novo Veículo"
   - Preencha placa, modelo e marca
   - Busque veículos usando o campo de pesquisa
4. **Recuperar Senha**:
   - Clique em "Esqueci a senha"
   - Informe usuário ou e-mail
   - Verifique o e-mail recebido

### Atalhos de Teclado

- `Ctrl + K`: Foco no campo de busca
- `Esc`: Fechar modais
- `Enter`: Confirmar formulários

## 📁 Estrutura do Projeto

```
admin_abastecimento/
├── api/                          # Endpoints REST
│   ├── login.php                 # Autenticação
│   ├── logout.php                # Encerrar sessão
│   ├── veiculo.php               # CRUD de veículos
│   ├── password_reset_request.php # Solicitar reset
│   └── password_reset_confirm.php # Confirmar reset
├── assets/                       # Recursos estáticos
│   └── QR_Combustivel.png       # Logo
├── components/                   # Componentes reutilizáveis
│   └── dashboard-components.php  # Factory de componentes
├── config/                       # Configurações
│   ├── config.php               # Configurações gerais
│   ├── database.php             # Classe Database
│   └── security.php             # Classe Security (JWT, BCrypt)
├── css/                          # Estilos
│   └── style.css                # Estilos globais
├── includes/                     # Fragmentos de páginas
│   ├── header.php               # Cabeçalho
│   ├── sidebar.php              # Menu lateral
│   └── footer.php               # Rodapé
├── info/                         # Documentação
│   ├── ARCHITECTURE.md          # Arquitetura do sistema
│   ├── DESIGN_PATTERNS.md       # Padrões de design
│   └── README.md                # Este arquivo
├── js/                           # JavaScript
│   ├── app.js                   # Utilitários gerais
│   ├── auth.js                  # Lógica de login
│   ├── veiculo.js               # CRUD de veículos
│   ├── password_reset.js        # Recuperação de senha
│   └── reset_password.js        # Confirmação de reset
├── pages/                        # Páginas da aplicação
│   ├── dashboard.php            # Dashboard principal
│   └── veiculo.php              # Gestão de veículos
├── storage/                      # Logs e arquivos temporários
│   ├── password_reset.log       # Log de resets
│   └── password_reset_errors.log # Log de erros
├── tests/                        # Testes (estrutura futura)
├── vendor/                       # Dependências Composer
├── composer.json                 # Dependências PHP
├── index.php                    # Página de entrada (login)
└── reset_password.php           # Página de reset
```

## 🔌 API

### Endpoints Disponíveis

#### Autenticação

**POST** `/api/login.php`
```json
// Request
{
  "usuario": "admin",
  "senha": "admin123"
}

// Response
{
  "success": true,
  "message": "Login realizado com sucesso",
  "token": "eyJ0eXAiOiJKV1QiLCJhb...",
  "code": "success"
}
```

**GET** `/api/logout.php`
```json
// Response: Redireciona para index.php
```

#### Veículos

**GET** `/api/veiculo.php?action=list&search=ABC&page=1`
```json
// Response
{
  "success": true,
  "veiculos": [
    {
      "id": 1,
      "placa": "ABC-1234",
      "modelo": "Gol",
      "marca": "Volkswagen"
    }
  ],
  "totalPages": 5,
  "currentPage": 1
}
```

**GET** `/api/veiculo.php?action=get&id=1`
```json
// Response
{
  "success": true,
  "veiculo": {
    "id": 1,
    "placa": "ABC-1234",
    "modelo": "Gol",
    "marca": "Volkswagen"
  }
}
```

**POST** `/api/veiculo.php`
```json
// Request
{
  "action": "create",
  "placa": "ABC-1234",
  "modelo": "Gol",
  "marca": "Volkswagen"
}

// Response
{
  "success": true,
  "message": "Veículo criado com sucesso"
}
```

**POST** `/api/veiculo.php`
```json
// Request
{
  "action": "update",
  "id": 1,
  "placa": "ABC-1234",
  "modelo": "Gol G7",
  "marca": "Volkswagen"
}

// Response
{
  "success": true,
  "message": "Veículo atualizado com sucesso"
}
```

**POST** `/api/veiculo.php`
```json
// Request
{
  "action": "delete",
  "id": 1
}

// Response
{
  "success": true,
  "message": "Veículo excluído com sucesso"
}
```

#### Recuperação de Senha

**POST** `/api/password_reset_request.php`
```json
// Request
{
  "usuario": "admin"
}

// Response
{
  "success": true,
  "message": "Se o usuário/e-mail existir, você receberá instruções",
  "code": "request_queued"
}
```

**POST** `/api/password_reset_confirm.php`
```json
// Request
{
  "token": "abc123...",
  "senha": "novaSenha123",
  "confirmar_senha": "novaSenha123"
}

// Response
{
  "success": true,
  "message": "Senha redefinida com sucesso",
  "code": "password_reset_success"
}
```

## 🔒 Segurança

### Implementações de Segurança

#### 1. Autenticação JWT
```php
// Token com expiração de 1 hora
$token = Security::generateToken($userId, $userRole);

// Validação em cada requisição
$payload = Security::validateToken($_SESSION['token']);
if (!$payload) {
    // Redireciona para login
}
```

#### 2. Senhas Criptografadas
```php
// Hash com BCrypt (cost factor 12)
$hash = Security::hashPassword($senha);

// Verificação segura
$valid = Security::verifyPassword($senha, $hash);
```

#### 3. Sanitização de Inputs
```php
// Proteção contra SQL Injection e XSS
$input = Security::sanitize($_POST['campo']);

// htmlspecialchars + mysqli::real_escape_string
```

#### 4. Prepared Statements
```php
// Proteção contra SQL Injection
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
```

### Recomendações de Segurança

✅ **Implementar**:
- HTTPS em produção
- CSRF tokens em formulários
- Rate limiting em APIs
- Logs de auditoria
- Backup automático do banco

⚠️ **Não fazer**:
- Expor credenciais no código
- Usar `SELECT *` em produção
- Logar senhas ou tokens
- Desabilitar CORS sem necessidade

## 🤝 Contribuição

Contribuições são bem-vindas! Para contribuir:

1. Fork o projeto
2. Crie uma branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'Adiciona nova funcionalidade'`
4. Push para a branch: `git push origin feature/nova-funcionalidade`
5. Abra um Pull Request

### Padrões de Código

- Use PSR-12 para PHP
- Comente código complexo
- Escreva testes para novas funcionalidades
- Mantenha a documentação atualizada

## 🐛 Troubleshooting

### Erro: "Não foi possível conectar ao banco de dados"

**Solução**:
```bash
# Verifique se o MySQL está rodando
sudo systemctl status mysql

# Verifique credenciais em config/config.php
# Teste conexão manual:
mysql -u seu_usuario -p -h localhost
```

### Erro: "Token inválido ou expirado"

**Solução**:
- Limpe o cache do navegador
- Verifique se a constante `JWT_SECRET` está configurada
- Token expira em 1 hora (ajuste `TOKEN_EXPIRY`)

### E-mails não estão sendo enviados

**Solução**:
```php
// 1. Verifique se PHPMailer está instalado
composer require phpmailer/phpmailer

// 2. Configure SMTP em config/config.php
define('MAIL_USE_SMTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);

// 3. Verifique logs em storage/password_reset_errors.log
```

### Erro 500 após instalação

**Solução**:
```bash
# Verifique logs do Apache/Nginx
sudo tail -f /var/log/apache2/error.log

# Verifique permissões
chmod -R 755 /var/www/html/admin_abastecimento
chown -R www-data:www-data storage/

# Ative display_errors temporariamente
# Em config/config.php:
define('DEBUG', true);
ini_set('display_errors', 1);
```

### Problema com acentuação

**Solução**:
```sql
-- Verifique charset do banco
SHOW VARIABLES LIKE 'character_set%';

-- Altere charset se necessário
ALTER DATABASE conceit1_combustivel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 📚 Documentação Adicional

- [ARCHITECTURE.md](ARCHITECTURE.md): Arquitetura detalhada do sistema
- [DESIGN_PATTERNS.md](DESIGN_PATTERNS.md): Padrões de design utilizados
- [API_DOCS.md](API_DOCS.md): Documentação completa da API (a criar)

## 🗺️ Roadmap

### Versão 2.0 (Planejado)
- [ ] Módulo de Abastecimentos completo
- [ ] Relatórios em PDF
- [ ] Gráficos de consumo
- [ ] Export para Excel
- [ ] API RESTful completa

### Versão 3.0 (Futuro)
- [ ] App mobile (React Native)
- [ ] Integração com QR Code
- [ ] Dashboard em tempo real
- [ ] Notificações push
- [ ] Multi-idioma

## 📝 Changelog

### [1.0.0] - 2024-11-16
- ✨ Release inicial
- ✅ Sistema de autenticação JWT
- ✅ CRUD de veículos
- ✅ Dashboard com estatísticas
- ✅ Recuperação de senha
- ✅ Design responsivo

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

```
MIT License

Copyright (c) 2024 QR Combustível

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## 👥 Autores

- **Equipe QR Combustível** - *Desenvolvimento inicial*

## 🙏 Agradecimentos

- Bootstrap pela framework CSS
- Font Awesome pelos ícones
- PHPMailer pela biblioteca de e-mail
- Comunidade PHP pelo suporte

---

**Desenvolvido com ❤️ pela equipe QR Combustível**

Para suporte: support@qrcombustivel.com.br
