# Padrões de Design - QR Combustível

## Visão Geral
Este documento descreve os padrões de design, princípios de arquitetura e convenções de código utilizados no sistema QR Combustível.

## Padrões Arquiteturais

### 1. MVC Simplificado (Model-View-Controller)

#### Model
Representado pelas classes de configuração e acesso a dados:
```php
// config/database.php - Acesso a dados
class Database {
    public function connect() { ... }
    public function query($sql) { ... }
    public function prepare($sql) { ... }
}

// config/security.php - Lógica de negócio
class Security {
    public static function generateToken($userId, $userRole) { ... }
    public static function validateToken($token) { ... }
    public static function hashPassword($password) { ... }
}
```

**Responsabilidades**:
- Acesso e manipulação de dados
- Regras de negócio
- Validações
- Criptografia e segurança

#### View
Arquivos PHP que renderizam HTML:
```php
// pages/veiculo.php
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="main-content">
    <!-- Conteúdo da página -->
</div>
<?php include '../includes/footer.php'; ?>
```

**Características**:
- Templates PHP com mínimo de lógica
- Componentes reutilizáveis (includes/)
- Separação de layout (header, sidebar, footer)
- JavaScript para interatividade

#### Controller
APIs REST que processam requisições:
```php
// api/veiculo.php
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list': /* listar veículos */ break;
    case 'get': /* buscar um veículo */ break;
    case 'create': /* criar veículo */ break;
    case 'update': /* atualizar veículo */ break;
    case 'delete': /* excluir veículo */ break;
}
```

**Responsabilidades**:
- Processar requisições HTTP
- Validar entrada
- Chamar Model
- Retornar resposta JSON

### 2. Front Controller Pattern

Cada endpoint API atua como mini front controller:

```php
// Estrutura padrão de API
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

$response = ['success' => false, 'message' => '', 'data' => null];

// Validação de autenticação
if (empty($_SESSION['token'])) {
    $response['message'] = 'Não autenticado';
    echo json_encode($response);
    exit;
}

// Roteamento baseado em action
$action = $_GET['action'] ?? $_POST['action'] ?? '';
switch ($action) {
    // ... handlers
}

echo json_encode($response);
```

**Benefícios**:
- Centralização de autenticação
- Formato de resposta consistente
- Fácil manutenção

### 3. Repository Pattern (Implícito)

A classe Database funciona como repository base:

```php
// Abstração de acesso a dados
$db = new Database();
$db->connect();

// Query direta
$result = $db->query("SELECT * FROM veiculos");

// Prepared statement
$stmt = $db->prepare("SELECT * FROM veiculos WHERE id = ?");
$stmt->bind_param("i", $id);
```

**Vantagens**:
- Encapsulamento de lógica de BD
- Facilita testes (pode ser mockado)
- Centraliza tratamento de erros

### 4. Factory Pattern

Componentes do dashboard usam factory functions:

```php
// components/dashboard-components.php

function renderStatCard($title, $value, $icon, $footer, $color) {
    // Cria HTML do card baseado nos parâmetros
    ?>
    <div class="card">
        <div class="card-icon" style="background: <?php echo $colorClass; ?>">
            <i class="fas <?php echo $icon; ?>"></i>
        </div>
        <!-- ... -->
    </div>
    <?php
}

// Uso:
renderStatCard('Veículos', '24', 'fa-car', 'Cadastrados', 'primary');
renderStatCard('Abastecimentos', '156', 'fa-gas-pump', 'Este mês', 'orange');
```

**Benefícios**:
- Reutilização de código
- Interface consistente
- Manutenção centralizada

### 5. Strategy Pattern (JWT)

Diferentes estratégias de geração/validação de tokens:

```php
class Security {
    // Strategy: Geração de token
    public static function generateToken($userId, $userRole) {
        $header = ['alg' => JWT_ALGORITHM, 'typ' => 'JWT'];
        $payload = [
            'userId' => $userId,
            'userRole' => $userRole,
            'iat' => time(),
            'exp' => time() + TOKEN_EXPIRY
        ];
        // Implementação específica de JWT
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    // Strategy: Validação de token
    public static function validateToken($token) {
        // Implementação de validação JWT
        // Pode ser trocada por OAuth, API Key, etc.
    }
}
```

### 6. Template Method Pattern

Estrutura padrão de páginas:

```php
// Template base de página
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

// Hook: Validação de autenticação
if (empty($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <!-- Hook: Meta tags e CSS -->
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Hook: Conteúdo específico da página -->
    <div class="main-content">
        <!-- Implementação específica -->
    </div>
    
    <?php include '../includes/footer.php'; ?>
    <!-- Hook: Scripts específicos -->
</body>
</html>
```

## Padrões de Código

### Nomenclatura

#### PHP
```php
// Classes: PascalCase
class Database { }
class Security { }

// Funções: camelCase
function renderStatCard() { }
function validateEmail() { }

// Variáveis: snake_case (banco) / camelCase (código)
$user_id = 1;
$userName = "João";
$db_connection = new Database();

// Constantes: UPPER_SNAKE_CASE
define('DB_HOST', 'localhost');
define('JWT_SECRET', 'secret');
```

#### JavaScript
```javascript
// Variáveis: camelCase
let currentPage = 1;
let searchTerm = '';

// Funções: camelCase
function loadVeiculos() { }
function renderTable(veiculos) { }

// Constantes: UPPER_CASE
const API_URL = '/api';
const DEFAULT_LIMIT = 10;
```

#### CSS
```css
/* Classes: kebab-case */
.header-content { }
.sidebar-menu { }
.btn-primary { }

/* IDs: camelCase */
#veiculosTable { }
#searchInput { }

/* Variáveis CSS: kebab-case com -- */
--primary-dark: #1b5175;
--secondary-orange: #f07a28;
```

### Estrutura de Resposta JSON

Todas as APIs retornam formato consistente:

```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "code": "success",
    "data": {
        "id": 1,
        "campo": "valor"
    },
    "errors": []
}
```

**Campos**:
- `success` (bool): Indica sucesso/falha
- `message` (string): Mensagem para o usuário
- `code` (string): Código de erro/sucesso para lógica frontend
- `data` (mixed): Dados retornados
- `errors` (array): Lista de erros de validação

### Tratamento de Erros

#### Backend
```php
// Estrutura try-catch com log
try {
    $db = new Database();
    $db->connect();
    // operação
} catch (Throwable $e) {
    _log_error('Erro: ' . $e->getMessage());
    $response['message'] = 'Erro interno';
    $response['code'] = 'internal_error';
    echo json_encode($response);
    exit;
}
```

#### Frontend
```javascript
$.ajax({
    url: 'api/veiculo.php',
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        if (data.success) {
            // sucesso
        } else {
            alert(data.message);
        }
    },
    error: function(xhr, status, error) {
        alert('Erro na comunicação com o servidor');
    }
});
```

### Validação de Dados

#### Camadas de Validação

**1. Frontend (JavaScript)**
```javascript
// Validação básica de campos
if (!placa || !modelo || !marca) {
    alert('Por favor, preencha todos os campos');
    return;
}

// Validação de formato
if (!/^[A-Z]{3}-\d{4}$/.test(placa)) {
    alert('Placa inválida');
    return;
}
```

**2. Backend (PHP)**
```php
// Sanitização
$usuario = Security::sanitize($_POST['usuario'] ?? '');

// Validação de obrigatoriedade
if (empty($usuario)) {
    $response['message'] = 'Usuário é obrigatório';
    $response['field'] = 'usuario';
    exit;
}

// Validação de formato
if (!Security::validateEmail($email)) {
    $response['message'] = 'E-mail inválido';
    exit;
}
```

**3. Banco de Dados (Constraints)**
```sql
-- NOT NULL, UNIQUE, etc.
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) UNIQUE,
    senha VARCHAR(255) NOT NULL
);
```

### Segurança por Design

#### 1. Defesa em Profundidade

```php
// Layer 1: Sanitização
$input = Security::sanitize($_POST['data']);

// Layer 2: Prepared Statements
$stmt = $db->prepare("SELECT * FROM table WHERE field = ?");
$stmt->bind_param("s", $input);

// Layer 3: Validação de saída
echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
```

#### 2. Princípio do Menor Privilégio

```php
// Menu baseado em role
$menus = [
    'admin' => [/* todos os menus */],
    'user' => [/* menus limitados */]
];

$userRole = $_SESSION['userRole'] ?? 'user';
$userMenus = $menus[$userRole] ?? $menus['user'];
```

#### 3. Fail-Safe Defaults

```php
// Sempre assume não autenticado
if (empty($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

// Sempre valida token
$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}
```

## Design System

### Paleta de Cores

```css
:root {
    /* Primárias */
    --primary-dark: #1b5175;      /* Azul escuro */
    --primary-darker: #0d2a42;    /* Azul marinho */
    --primary-light: #2e6fa1;     /* Azul claro */
    
    /* Secundárias */
    --secondary-orange: #f07a28;  /* Laranja */
    --secondary-green: #1f5734;   /* Verde */
    
    /* Neutras */
    --gray-light: #f5f5f5;
    --gray-lighter: #f9f9f9;
    --gray-medium: #c1c3c7;
    --gray-border: #e0e0e0;
    
    /* Texto */
    --text-primary: #333;
    --text-secondary: #666;
    
    /* Sistema */
    --white: #ffffff;
}
```

### Componentes Visuais

#### 1. Cards
```php
renderStatCard('Título', 'Valor', 'ícone', 'rodapé', 'cor')
```
Cores: `primary`, `success`, `danger`, `orange`

#### 2. Badges
```php
renderBadge('Texto', 'tipo')
```
Tipos: `primary`, `success`, `danger`, `warning`

#### 3. Botões
```html
<button class="btn-primary">Primário</button>
<button class="btn-secondary">Secundário</button>
<button class="btn-danger">Perigo</button>
<button class="btn-success">Sucesso</button>
```

#### 4. Tabelas
```html
<div class="table-container">
    <table class="table table-hover">
        <thead><!-- cabeçalho --></thead>
        <tbody><!-- dados --></tbody>
    </table>
</div>
```

### Responsividade

#### Breakpoints
```css
/* Mobile first */
/* Base: 0-767px */

@media (min-width: 768px) {
    /* Tablet */
}

@media (min-width: 1024px) {
    /* Desktop */
}

@media (min-width: 1440px) {
    /* Large desktop */
}
```

#### Grid System
Utiliza Bootstrap 5 grid:
```html
<div class="container">
    <div class="row">
        <div class="col-12 col-md-6 col-lg-4">
            <!-- Conteúdo -->
        </div>
    </div>
</div>
```

## Padrões de Banco de Dados

### Convenções de Nomenclatura

```sql
-- Tabelas: plural, snake_case
CREATE TABLE veiculos (...);
CREATE TABLE abastecimentos (...);
CREATE TABLE password_resets (...);

-- Colunas: singular, snake_case
id, user_id, created_at, updated_at

-- Primary Keys: id
id INT PRIMARY KEY AUTO_INCREMENT

-- Foreign Keys: [tabela_singular]_id
user_id INT REFERENCES usuarios(id)
veiculo_id INT REFERENCES veiculos(id)

-- Timestamps
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Índices

```sql
-- Primary key automático
PRIMARY KEY (id)

-- Índices únicos
UNIQUE KEY (usuario)
UNIQUE KEY (email)

-- Índices de busca
INDEX idx_placa (placa)
INDEX idx_user_email (email)

-- Índices compostos
INDEX idx_user_active (usuario, ativo)
```

### Relacionamentos

```sql
-- One-to-Many: usuário → abastecimentos
CREATE TABLE abastecimentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE
);
```

## Padrões de Performance

### 1. Query Optimization

```php
// ✅ BOM: SELECT específico com LIMIT
$sql = "SELECT id, placa, modelo, marca 
        FROM veiculos 
        WHERE placa LIKE ? 
        LIMIT 10 OFFSET ?";

// ❌ RUIM: SELECT * sem limites
$sql = "SELECT * FROM veiculos";
```

### 2. Paginação

```php
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Total de páginas
$sqlCount = "SELECT COUNT(*) as total FROM veiculos WHERE $where";
$totalPages = ceil($total / $limit);

// Dados paginados
$sql = "SELECT * FROM veiculos WHERE $where LIMIT $limit OFFSET $offset";
```

### 3. Lazy Loading (Frontend)

```javascript
// Carrega dados apenas quando necessário
function loadVeiculos() {
    $.ajax({
        url: '../api/veiculo.php',
        data: { action: 'list', page: currentPage },
        success: function(data) {
            renderTable(data.veiculos);
        }
    });
}

// Trigger apenas em eventos específicos
$('#searchInput').on('keyup', debounce(loadVeiculos, 300));
```

## Padrões de Testes

### Estrutura Recomendada

```
tests/
├── unit/
│   ├── SecurityTest.php
│   └── DatabaseTest.php
├── integration/
│   ├── LoginApiTest.php
│   └── VeiculoApiTest.php
└── e2e/
    ├── LoginFlowTest.php
    └── VeiculoCrudTest.php
```

### Exemplo de Teste Unitário

```php
class SecurityTest extends PHPUnit\Framework\TestCase {
    
    public function testHashPassword() {
        $password = 'senha123';
        $hash = Security::hashPassword($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(Security::verifyPassword($password, $hash));
    }
    
    public function testGenerateToken() {
        $token = Security::generateToken(1, 'admin');
        
        $this->assertNotNull($token);
        $this->assertIsString($token);
        
        $payload = Security::validateToken($token);
        $this->assertEquals(1, $payload['userId']);
        $this->assertEquals('admin', $payload['userRole']);
    }
}
```

## Anti-Patterns a Evitar

### ❌ 1. God Object
```php
// EVITAR: Classe que faz tudo
class System {
    public function login() { }
    public function createVeiculo() { }
    public function sendEmail() { }
    public function generateReport() { }
}
```

### ❌ 2. Magic Numbers
```php
// EVITAR
if ($status == 1) { }

// USAR
define('STATUS_ACTIVE', 1);
if ($status == STATUS_ACTIVE) { }
```

### ❌ 3. Hardcoded Credentials
```php
// EVITAR
$db = new mysqli('localhost', 'root', 'senha123', 'db');

// USAR
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
```

### ❌ 4. SQL Injection
```php
// EVITAR
$sql = "SELECT * FROM users WHERE id = $_GET[id]";

// USAR
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
```

## Conclusão

O sistema segue padrões consolidados de desenvolvimento web com PHP, priorizando:
- **Simplicidade**: Código direto e fácil de entender
- **Segurança**: Múltiplas camadas de proteção
- **Manutenibilidade**: Separação de responsabilidades
- **Escalabilidade**: Estrutura preparada para crescimento

Para evoluções futuras, recomenda-se:
1. Migrar para framework moderno (Laravel/Symfony)
2. Implementar testes automatizados
3. Adicionar CI/CD
4. Containerizar com Docker
5. Implementar API RESTful completa
