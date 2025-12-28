# Guia de Deploy - QR Combustível

## Visão Geral
Este documento fornece instruções detalhadas para deploy do sistema QR Combustível em ambientes de produção.

## 📋 Checklist Pré-Deploy

### Ambiente
- [ ] Servidor com PHP 7.4+ instalado
- [ ] MySQL 5.7+ ou MariaDB 10.3+
- [ ] Composer instalado
- [ ] Certificado SSL configurado (HTTPS)
- [ ] Firewall configurado (portas 80/443)
- [ ] Backup do banco de dados atual

### Código
- [ ] Testes passando
- [ ] Dependências atualizadas
- [ ] Logs de debug desabilitados
- [ ] Credenciais em variáveis de ambiente
- [ ] Arquivos de configuração revisados

### Segurança
- [ ] JWT_SECRET alterado para valor aleatório
- [ ] Senhas do banco alteradas
- [ ] HTTPS habilitado
- [ ] Permissões de arquivo corretas
- [ ] CORS configurado adequadamente

## 🚀 Deploy em Servidor Próprio (VPS)

### 1. Preparar o Servidor

#### Atualizar Sistema
```bash
sudo apt update && sudo apt upgrade -y
```

#### Instalar Dependências
```bash
# Apache, PHP e extensões
sudo apt install -y apache2 php7.4 php7.4-mysql php7.4-mbstring \
    php7.4-xml php7.4-curl php7.4-zip php7.4-gd php7.4-bcmath \
    libapache2-mod-php7.4

# MySQL
sudo apt install -y mysql-server

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Configurar MySQL
```bash
sudo mysql_secure_installation

# Criar banco de dados
sudo mysql -u root -p <<EOF
CREATE DATABASE conceit1_combustivel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'SENHA_FORTE_AQUI';
GRANT SELECT, INSERT, UPDATE, DELETE ON conceit1_combustivel.* TO 'app_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
```

### 2. Configurar Apache

#### Habilitar Módulos
```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo systemctl restart apache2
```

#### Criar VirtualHost
```bash
sudo nano /etc/apache2/sites-available/qrcombustivel.conf
```

```apache
<VirtualHost *:80>
    ServerName qrcombustivel.com.br
    ServerAlias www.qrcombustivel.com.br
    
    DocumentRoot /var/www/html/admin_abastecimento
    
    <Directory /var/www/html/admin_abastecimento>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Segurança adicional
        <Files "config.php">
            Require all denied
        </Files>
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/qrcombustivel_error.log
    CustomLog ${APACHE_LOG_DIR}/qrcombustivel_access.log combined
    
    # Redirect para HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName qrcombustivel.com.br
    ServerAlias www.qrcombustivel.com.br
    
    DocumentRoot /var/www/html/admin_abastecimento
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/qrcombustivel.com.br/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/qrcombustivel.com.br/privkey.pem
    
    <Directory /var/www/html/admin_abastecimento>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Headers de segurança
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    ErrorLog ${APACHE_LOG_DIR}/qrcombustivel_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/qrcombustivel_ssl_access.log combined
</VirtualHost>
```

#### Habilitar Site
```bash
sudo a2ensite qrcombustivel.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

### 3. Obter Certificado SSL (Let's Encrypt)

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-apache

# Obter certificado
sudo certbot --apache -d qrcombustivel.com.br -d www.qrcombustivel.com.br

# Renovação automática (já configurada por padrão)
sudo certbot renew --dry-run
```

### 4. Deploy do Código

#### Via Git (Recomendado)
```bash
# Clonar repositório
cd /var/www/html
sudo git clone https://github.com/seu-usuario/admin_abastecimento.git
cd admin_abastecimento

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Configurar permissões
sudo chown -R www-data:www-data /var/www/html/admin_abastecimento
sudo find /var/www/html/admin_abastecimento -type d -exec chmod 755 {} \;
sudo find /var/www/html/admin_abastecimento -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage/

# Proteger config
sudo chmod 600 config/config.php
```

#### Via FTP/SFTP
```bash
# Upload dos arquivos via SFTP
# Depois ajustar permissões:
sudo chown -R www-data:www-data /var/www/html/admin_abastecimento
sudo chmod -R 755 /var/www/html/admin_abastecimento
sudo chmod -R 775 storage/
sudo chmod 600 config/config.php
```

### 5. Configurar Variáveis de Produção

```bash
sudo nano /var/www/html/admin_abastecimento/config/config.php
```

```php
<?php
// PRODUÇÃO - Configurações
define('DB_HOST', 'localhost');
define('DB_USER', 'app_user');
define('DB_PASS', getenv('DB_PASSWORD')); // Usar variável de ambiente
define('DB_NAME', 'conceit1_combustivel');

// Gerar novo JWT_SECRET
define('JWT_SECRET', getenv('JWT_SECRET')); // Usar variável de ambiente
define('JWT_ALGORITHM', 'HS256');
define('TOKEN_EXPIRY', 3600);

define('BASE_URL', 'https://qrcombustivel.com.br/');

// E-mail SMTP
define('MAIL_USE_SMTP', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SMTP_SECURE', 'tls');

// IMPORTANTE: Desabilitar debug em produção
define('DEBUG', false);
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
```

#### Configurar Variáveis de Ambiente

```bash
# Adicionar ao /etc/environment
sudo nano /etc/environment
```

```bash
DB_PASSWORD="senha_super_segura_aqui"
JWT_SECRET="chave_aleatoria_64_caracteres_min"
SMTP_USER="seu_email@gmail.com"
SMTP_PASS="senha_app_google"
```

```bash
# Recarregar
source /etc/environment
```

### 6. Importar Banco de Dados

```bash
# Importar estrutura
mysql -u app_user -p conceit1_combustivel < database/schema.sql

# Criar usuário admin
mysql -u app_user -p conceit1_combustivel <<EOF
INSERT INTO usuarios (usuario, senha, email, perfil, ativo) 
VALUES (
    'admin', 
    '\$2y\$12\$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5erg7kx3H6Qy6',
    'admin@qrcombustivel.com.br',
    'admin',
    1
);
EOF
```

### 7. Configurar Logs

```bash
# Criar diretório de logs
sudo mkdir -p /var/log/qrcombustivel
sudo chown www-data:www-data /var/log/qrcombustivel

# Rotação de logs
sudo nano /etc/logrotate.d/qrcombustivel
```

```
/var/log/qrcombustivel/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
```

### 8. Configurar Cron Jobs

```bash
sudo crontab -e
```

```cron
# Limpar tokens expirados (diariamente às 2h)
0 2 * * * mysql -u app_user -p'senha' conceit1_combustivel -e "DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1;" >> /var/log/qrcombustivel/cron.log 2>&1

# Backup diário (3h da manhã)
0 3 * * * /var/www/html/admin_abastecimento/scripts/backup.sh >> /var/log/qrcombustivel/backup.log 2>&1

# Otimização semanal (domingo 4h)
0 4 * * 0 mysql -u app_user -p'senha' conceit1_combustivel -e "OPTIMIZE TABLE usuarios, veiculos, abastecimentos;" >> /var/log/qrcombustivel/optimize.log 2>&1
```

## 🐳 Deploy com Docker

### Dockerfile

```dockerfile
FROM php:7.4-apache

# Instalar extensões PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar código
COPY . /var/www/html/
WORKDIR /var/www/html

# Instalar dependências
RUN composer install --no-dev --optimize-autoloader

# Permissões
RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 /var/www/html/storage

EXPOSE 80
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: qrcombustivel_app
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
      - ./storage:/var/www/html/storage
    environment:
      - DB_HOST=db
      - DB_USER=app_user
      - DB_PASS=senha_segura
      - DB_NAME=conceit1_combustivel
      - JWT_SECRET=${JWT_SECRET}
    depends_on:
      - db
    networks:
      - qrcombustivel_network

  db:
    image: mysql:5.7
    container_name: qrcombustivel_db
    environment:
      - MYSQL_ROOT_PASSWORD=root_password
      - MYSQL_DATABASE=conceit1_combustivel
      - MYSQL_USER=app_user
      - MYSQL_PASSWORD=senha_segura
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql
    networks:
      - qrcombustivel_network

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: qrcombustivel_phpmyadmin
    ports:
      - "8080:80"
    environment:
      - PMA_HOST=db
      - PMA_USER=root
      - PMA_PASSWORD=root_password
    depends_on:
      - db
    networks:
      - qrcombustivel_network

volumes:
  db_data:

networks:
  qrcombustivel_network:
    driver: bridge
```

### Deploy com Docker

```bash
# Build e iniciar
docker-compose up -d

# Ver logs
docker-compose logs -f

# Parar
docker-compose down

# Backup do banco
docker exec qrcombustivel_db mysqldump -u app_user -psenha_segura conceit1_combustivel > backup.sql
```

## ☁️ Deploy em Serviços Cloud

### AWS (EC2 + RDS)

#### 1. Criar Instância EC2
```bash
# AMI: Ubuntu Server 20.04 LTS
# Tipo: t2.micro (free tier) ou superior
# Security Group: Portas 22, 80, 443
```

#### 2. Criar RDS MySQL
```bash
# Engine: MySQL 5.7
# Instância: db.t2.micro
# Multi-AZ: Sim (produção)
# Backup: Sim, retenção 7 dias
```

#### 3. Configurar S3 para Backups
```bash
# Instalar AWS CLI
sudo apt install awscli

# Configurar credenciais
aws configure

# Script de backup para S3
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -h rds-endpoint -u admin -p conceit1_combustivel > backup_$DATE.sql
gzip backup_$DATE.sql
aws s3 cp backup_$DATE.sql.gz s3://qrcombustivel-backups/
rm backup_$DATE.sql.gz
```

### Heroku

#### Preparar Projeto
```bash
# Criar Procfile
echo "web: vendor/bin/heroku-php-apache2" > Procfile

# Criar app.json
cat > app.json <<EOF
{
  "name": "QR Combustível",
  "description": "Sistema de gestão de abastecimento",
  "addons": ["cleardb:ignite"],
  "env": {
    "JWT_SECRET": {
      "description": "Secret key for JWT",
      "generator": "secret"
    }
  }
}
EOF
```

#### Deploy
```bash
# Login
heroku login

# Criar app
heroku create qrcombustivel

# Adicionar banco de dados
heroku addons:create cleardb:ignite

# Obter DATABASE_URL
heroku config:get CLEARDB_DATABASE_URL

# Configurar variáveis
heroku config:set JWT_SECRET="sua_chave"
heroku config:set DEBUG=false

# Deploy
git push heroku main

# Rodar migrations
heroku run php scripts/migrate.php
```

## 🔒 Hardening de Segurança

### PHP.ini
```ini
# /etc/php/7.4/apache2/php.ini

expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/qrcombustivel/php_errors.log
max_execution_time = 30
memory_limit = 128M
post_max_size = 8M
upload_max_filesize = 5M

# Desabilitar funções perigosas
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### Apache Security Headers
```apache
# /etc/apache2/conf-available/security.conf

ServerTokens Prod
ServerSignature Off
TraceEnable Off

<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;"
</IfModule>
```

### Firewall (UFW)
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw enable
```

### Fail2Ban
```bash
# Instalar
sudo apt install fail2ban

# Configurar
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 3
bantime = 3600

[apache-noscript]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 5
bantime = 3600
```

## 📊 Monitoramento

### New Relic
```bash
# Instalar agent PHP
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
echo "deb http://apt.newrelic.com/debian/ newrelic non-free" | sudo tee /etc/apt/sources.list.d/newrelic.list
sudo apt update
sudo apt install newrelic-php5
sudo newrelic-install install
```

### Logs Centralizados
```bash
# Instalar Logwatch
sudo apt install logwatch

# Configurar relatório diário
sudo logwatch --output mail --mailto admin@qrcombustivel.com.br --detail high
```

## 🔄 CI/CD com GitHub Actions

### .github/workflows/deploy.yml

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
    
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Run tests
      run: ./vendor/bin/phpunit
    
    - name: Deploy to server
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/html/admin_abastecimento
          git pull origin main
          composer install --no-dev --optimize-autoloader
          sudo systemctl reload apache2
```

## 📋 Checklist Pós-Deploy

- [ ] Site acessível via HTTPS
- [ ] Login funcionando corretamente
- [ ] Envio de e-mail testado
- [ ] CRUD de veículos funcionando
- [ ] Dashboard carregando
- [ ] Logs sendo gravados
- [ ] Backup automático configurado
- [ ] Monitoramento ativo
- [ ] SSL válido e renovável
- [ ] Performance satisfatória (< 2s load time)

## 🆘 Rollback

### Em caso de problemas:

```bash
# 1. Restaurar código anterior
cd /var/www/html/admin_abastecimento
git log --oneline
git reset --hard COMMIT_ANTERIOR
composer install --no-dev

# 2. Restaurar banco de dados
mysql -u app_user -p conceit1_combustivel < /backups/backup_anterior.sql

# 3. Recarregar Apache
sudo systemctl reload apache2

# 4. Verificar logs
tail -f /var/log/apache2/qrcombustivel_error.log
```

## 📞 Suporte

Para problemas de deploy, contate:
- E-mail: suporte@qrcombustivel.com.br
- Documentação: https://docs.qrcombustivel.com.br
