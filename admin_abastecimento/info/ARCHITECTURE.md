# Arquitetura do Sistema - QR Combustível

## Visão Geral
O **QR Combustível** é um sistema administrativo de gestão de abastecimento desenvolvido em PHP com arquitetura MVC simplificada. O sistema permite o gerenciamento de veículos, abastecimentos e usuários através de uma interface web responsiva.

## Arquitetura em Camadas

### 1. Camada de Apresentação (Frontend)
#### Páginas (Views)
- **index.php**: Página de login principal
- **pages/dashboard.php**: Dashboard com estatísticas e visão geral
- **pages/veiculo.php**: Gestão de veículos (CRUD completo)
- **reset_password.php**: Página de redefinição de senha

#### Componentes de Interface
- **includes/header.php**: Cabeçalho da aplicação com logo e informações do usuário
- **includes/sidebar.php**: Menu lateral com navegação contextual baseada em permissões
- **includes/footer.php**: Rodapé da aplicação
- **components/dashboard-components.php**: Componentes reutilizáveis (cards, tabelas, badges, modals)

#### Assets Estáticos
- **css/style.css**: Estilos globais com variáveis CSS e design system
- **js/auth.js**: Lógica de autenticação no frontend
- **js/veiculo.js**: Lógica de CRUD de veículos
- **js/password_reset.js**: Lógica de recuperação de senha
- **js/app.js**: Utilitários gerais do frontend

### 2. Camada de Lógica de Negócio (Backend)
#### APIs RESTful
Todas as APIs seguem o padrão REST e retornam JSON:

##### api/login.php
- **Método**: POST
- **Responsabilidade**: Autenticação de usuários
- **Retorna**: Token JWT, dados do usuário
- **Validações**: 
  - Campos obrigatórios
  - Usuário ativo
  - Senha correta

##### api/logout.php
- **Método**: GET/POST
- **Responsabilidade**: Encerrar sessão do usuário
- **Ação**: Destroi sessão e redireciona para login

##### api/veiculo.php
- **Métodos**: GET, POST
- **Actions**: list, get, create, update, delete
- **Responsabilidade**: CRUD de veículos
- **Recursos**:
  - Paginação (10 registros por página)
  - Busca por placa, modelo ou marca
  - Validação de autenticação via token

##### api/password_reset_request.php
- **Método**: POST
- **Responsabilidade**: Solicitar recuperação de senha
- **Recursos**:
  - Geração de token seguro
  - Envio de e-mail via PHPMailer ou mail()
  - Log de erros e requisições

##### api/password_reset_confirm.php
- **Método**: POST
- **Responsabilidade**: Confirmar nova senha
- **Validações**: Token válido, senhas coincidentes

### 3. Camada de Configuração
#### config/config.php
Configurações centralizadas do sistema:
- **Banco de Dados**: Host, usuário, senha, database
- **Identidade Visual**: Nome, logo, cores primárias e secundárias
- **Segurança**: Secret JWT, algoritmo, expiração de token
- **E-mail**: Configurações SMTP e PHPMailer
- **URLs**: Base URL do sistema

#### config/database.php
Classe **Database** com:
- Conexão mysqli
- Tratamento de erros
- Métodos: connect(), query(), prepare(), escape(), close()
- Charset UTF-8MB4

#### config/security.php
Classe **Security** com métodos estáticos:
- **generateToken()**: Gera JWT com userId e userRole
- **validateToken()**: Valida JWT e verifica expiração
- **encrypt()** / **decrypt()**: Criptografia AES-256-CBC
- **sanitize()**: Sanitização contra SQL Injection e XSS
- **validateEmail()**: Validação de formato de e-mail
- **hashPassword()** / **verifyPassword()**: BCrypt com cost 12

### 4. Camada de Dados
#### Banco de Dados MySQL
Tabelas identificadas no projeto:

**usuarios**
- id (INT, PK, AUTO_INCREMENT)
- usuario (VARCHAR)
- senha (VARCHAR, hash BCrypt)
- email (VARCHAR)
- perfil (VARCHAR: 'admin', 'user')
- ativo (TINYINT: 0 ou 1)
- created_at / updated_at (TIMESTAMP)

**veiculos**
- id (INT, PK, AUTO_INCREMENT)
- placa (VARCHAR)
- modelo (VARCHAR)
- marca (VARCHAR)
- created_at / updated_at (TIMESTAMP)

**password_resets**
- id (INT, PK, AUTO_INCREMENT)
- user_id (INT, FK usuarios)
- token (VARCHAR, 64 chars)
- expires_at (DATETIME)
- created_at (TIMESTAMP)

**abastecimentos** (estrutura não implementada completamente)
- Relacionamento com veículos e usuários
- Campos: data, litros, valor, tipo_combustivel

## Fluxo de Dados

### Autenticação
```
[Frontend] -> POST api/login.php
  ↓
[Security::sanitize()] -> Sanitiza entrada
  ↓
[Database] -> Busca usuário
  ↓
[Security::verifyPassword()] -> Valida senha BCrypt
  ↓
[Security::generateToken()] -> Gera JWT
  ↓
[$_SESSION] -> Armazena token e dados do usuário
  ↓
[Response JSON] -> { success: true, token: "..." }
  ↓
[Frontend] -> Redireciona para dashboard.php
```

### CRUD de Veículos
```
[Frontend] -> GET api/veiculo.php?action=list&search=ABC&page=1
  ↓
[Session Check] -> Valida $_SESSION['token']
  ↓
[Database::query()] -> SELECT com WHERE e LIMIT
  ↓
[Response JSON] -> { veiculos: [...], totalPages: 5, currentPage: 1 }
  ↓
[Frontend veiculo.js] -> renderTable() + renderPagination()
```

### Recuperação de Senha
```
[Frontend] -> POST api/password_reset_request.php (usuario/email)
  ↓
[Database] -> Busca usuário e verifica se está ativo
  ↓
[bin2hex(random_bytes(32))] -> Gera token seguro
  ↓
[Database INSERT] -> password_resets table
  ↓
[PHPMailer/mail()] -> Envia e-mail com link
  ↓
[Usuário clica link] -> reset_password.php?token=xxx
  ↓
[Frontend] -> POST api/password_reset_confirm.php
  ↓
[Security::hashPassword()] -> Nova senha hasheada
  ↓
[Database UPDATE] -> usuarios.senha
```

## Segurança

### Implementações de Segurança
1. **Autenticação JWT**: Token com expiração de 1 hora
2. **Senha BCrypt**: Hash com cost factor 12
3. **Sanitização**: htmlspecialchars + mysqli::real_escape_string
4. **CSRF**: Validação de sessão em todas as páginas protegidas
5. **Headers HTTP**: Content-Type correto, charset UTF-8
6. **Criptografia**: AES-256-CBC para dados sensíveis
7. **Prepared Statements**: Proteção contra SQL Injection (parcial)

### Pontos de Atenção
⚠️ **Vulnerabilidades a corrigir**:
- Algumas queries usam concatenação de strings ao invés de prepared statements
- Secret JWT está hardcoded no código
- Credenciais do banco de dados expostas no config.php
- Falta validação de CSRF tokens
- Logs de erro podem expor informações sensíveis

## Tecnologias Utilizadas

### Backend
- **PHP 7.4+**: Linguagem principal
- **MySQL/MySQLi**: Banco de dados
- **Composer**: Gerenciador de dependências
- **PHPMailer 7.0**: Envio de e-mails SMTP

### Frontend
- **HTML5 / CSS3**: Estrutura e estilos
- **Bootstrap 5.3**: Framework CSS
- **jQuery 3.6**: Biblioteca JavaScript
- **Font Awesome 6.4**: Ícones

### Design System
- **Cores Primárias**: #1b5175 (azul escuro), #0d2a42 (azul marinho)
- **Cores Secundárias**: #f07a28 (laranja), #1f5734 (verde)
- **Tipografia**: Segoe UI
- **Responsividade**: Mobile-first com breakpoints Bootstrap

## Estrutura de Diretórios
```
admin_abastecimento/
├── api/                    # Endpoints REST
│   ├── login.php
│   ├── logout.php
│   ├── veiculo.php
│   ├── password_reset_request.php
│   └── password_reset_confirm.php
├── assets/                 # Recursos estáticos (imagens, logos)
├── components/             # Componentes PHP reutilizáveis
│   └── dashboard-components.php
├── config/                 # Configurações do sistema
│   ├── config.php
│   ├── database.php
│   └── security.php
├── css/                    # Estilos CSS
│   └── style.css
├── includes/               # Fragmentos de páginas
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── info/                   # Documentação do projeto
├── js/                     # Scripts JavaScript
│   ├── app.js
│   ├── auth.js
│   ├── veiculo.js
│   ├── password_reset.js
│   └── reset_password.js
├── pages/                  # Páginas da aplicação
│   ├── dashboard.php
│   └── veiculo.php
├── storage/                # Logs e arquivos temporários
├── vendor/                 # Dependências Composer
├── composer.json           # Dependências PHP
├── index.php              # Página de entrada (login)
└── reset_password.php     # Página de reset de senha
```

## Padrões de Projeto Utilizados

### 1. MVC Simplificado
- **Model**: Classes Database e Security
- **View**: Arquivos PHP em pages/ e includes/
- **Controller**: APIs em api/

### 2. Repository Pattern (Implícito)
- Classe Database atua como repository base
- APIs específicas encapsulam lógica de negócio

### 3. Singleton (Database Connection)
- Instância única de conexão MySQL por request

### 4. Factory (Componentes)
- Funções em dashboard-components.php geram componentes HTML

### 5. Front Controller (Parcial)
- Cada API funciona como mini front controller
- Switch/case para determinar ações

## Escalabilidade

### Capacidade Atual
- Sistema monolítico adequado para até ~1000 usuários simultâneos
- Banco de dados relacional com índices básicos
- Sessões em filesystem (limite de escalabilidade horizontal)

### Melhorias Recomendadas
1. **Cache**: Redis/Memcached para sessões e queries
2. **CDN**: Servir assets estáticos
3. **Load Balancer**: Nginx para múltiplas instâncias PHP
4. **Database**: Master-slave replication
5. **API Gateway**: Separar frontend e backend
6. **Microserviços**: Separar módulos (auth, veículos, abastecimentos)
7. **Containerização**: Docker para deploy consistente

## Manutenibilidade

### Boas Práticas
✅ Separação de concerns (config, api, pages)
✅ Componentes reutilizáveis
✅ Nomenclatura clara e consistente
✅ Código em português (contexto da equipe)

### Pontos de Melhoria
- Adicionar autoloader PSR-4
- Implementar namespaces
- Criar camada de Service/Business Logic
- Adicionar validações em camada de modelo
- Implementar testes unitários
- Documentação PHPDoc em todas as funções

## Performance

### Otimizações Implementadas
- Paginação de resultados (10 por página)
- Queries específicas (SELECT apenas campos necessários)
- Cache de navegador para assets estáticos

### Oportunidades de Melhoria
- Implementar lazy loading de imagens
- Minificar CSS/JS
- Comprimir resposta HTTP (gzip)
- Índices adicionais no banco de dados
- Query optimization (EXPLAIN)
- Asset bundling (Webpack/Vite)

## Monitoramento e Logs

### Sistema de Logs Atual
- **password_reset.log**: Requisições de reset de senha
- **password_reset_errors.log**: Erros de envio de e-mail
- Logs em: `storage/` directory

### Melhorias Recomendadas
- Integrar com Monolog ou similar
- Logs estruturados (JSON)
- Níveis de log (DEBUG, INFO, WARNING, ERROR)
- Rotação automática de logs
- Monitoramento em tempo real (Sentry, New Relic)
- Métricas de performance (tempo de resposta, queries lentas)
