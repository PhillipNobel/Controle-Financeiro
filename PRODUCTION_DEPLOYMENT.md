# Production Deployment Guide

Este guia fornece instruções detalhadas para configurar e implantar o sistema de Controle Financeiro em ambiente de produção usando Docker.

## 🐳 Deploy com Docker (Recomendado)

### Pré-requisitos
- Docker 20.10+
- Docker Compose 2.0+
- Git

### Deploy Rápido

```bash
# Clone o repositório
git clone <repository-url>
cd controle-financeiro

# Configure o ambiente de produção
cp .env.production .env.prod
# Edite .env.prod com suas configurações específicas

# Execute o deploy
./scripts/deploy-production.sh
```

### Configuração Detalhada

1. **Configurar variáveis de ambiente**:
```bash
# Edite .env.production com suas configurações
nano .env.production

# Variáveis importantes:
# - APP_URL=https://seu-dominio.com
# - DB_PASSWORD=senha_forte_mysql
# - REDIS_PASSWORD=senha_forte_redis
# - MAIL_* (configurações de email)
```

2. **Executar deploy**:
```bash
# Deploy com zero downtime
./scripts/deploy-production.sh deploy

# Verificar status
./scripts/deploy-production.sh status

# Verificar saúde da aplicação
./scripts/deploy-production.sh health
```

3. **Configurar SSL/HTTPS**:
```bash
# Adicionar certificados SSL
mkdir -p docker/nginx/ssl
# Copie seus certificados para docker/nginx/ssl/

# Atualizar configuração do NGINX
# Edite docker/nginx/default.conf para incluir SSL
```

### Monitoramento e Manutenção

```bash
# Ver logs em tempo real
docker-compose -f docker-compose.prod.yml logs -f

# Backup do banco de dados
./scripts/backup-database.sh

# Verificar saúde dos containers
./scripts/health-check.sh

# Rollback se necessário
./scripts/deploy-production.sh rollback
```

## 🖥️ Deploy Tradicional (Sem Docker)

### Pré-requisitos

### Sistema Operacional
- Ubuntu 20.04+ ou CentOS 8+ (recomendado)
- Acesso root ou sudo

### Software Necessário
- PHP 8.2+ com extensões: mbstring, xml, curl, zip, gd, mysql, redis
- MySQL 8.0+
- NGINX ou Apache
- Composer 2.x
- Node.js 18+ e NPM (para assets)
- Redis (para cache e filas)

## Configuração do Servidor

### 1. Instalação do PHP e Extensões

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mbstring php8.2-redis

# CentOS/RHEL
sudo dnf install php php-fpm php-mysql php-xml php-curl php-zip php-gd php-mbstring php-redis
```

### 2. Instalação do MySQL

```bash
# Ubuntu/Debian
sudo apt install mysql-server

# CentOS/RHEL
sudo dnf install mysql-server
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

### 3. Configuração do Banco de Dados

```sql
CREATE DATABASE controle_financeiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'controle_user'@'localhost' IDENTIFIED BY 'senha_segura_aqui';
GRANT ALL PRIVILEGES ON controle_financeiro.* TO 'controle_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Instalação do NGINX

```bash
sudo apt install nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

## Configuração da Aplicação

### 1. Clone do Repositório

```bash
cd /var/www
sudo git clone https://github.com/seu-usuario/controle-financeiro.git
sudo chown -R www-data:www-data controle-financeiro
cd controle-financeiro
```

### 2. Instalação das Dependências

```bash
# Instalar dependências PHP
composer install --no-dev --optimize-autoloader

# Instalar dependências Node.js
npm ci --production
npm run build
```

### 3. Configuração do Ambiente

```bash
# Copiar arquivo de configuração de produção
cp .env.production .env

# Editar configurações específicas
nano .env
```

Ajuste as seguintes variáveis no arquivo `.env`:

```env
APP_NAME="Controle Financeiro"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://seu-dominio.com

DB_HOST=127.0.0.1
DB_DATABASE=controle_financeiro
DB_USERNAME=controle_user
DB_PASSWORD=sua_senha_segura

MAIL_HOST=seu-smtp.com
MAIL_USERNAME=seu-email@dominio.com
MAIL_PASSWORD=sua_senha_email
```

### 4. Geração da Chave da Aplicação

```bash
php artisan key:generate
```

### 5. Execução das Migrações

```bash
php artisan migrate --force
```

### 6. Criação de Dados Iniciais

```bash
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=DemoUsersSeeder
```

## Configuração do NGINX

Crie o arquivo de configuração do NGINX:

```bash
sudo nano /etc/nginx/sites-available/controle-financeiro
```

```nginx
server {
    listen 80;
    server_name seu-dominio.com www.seu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seu-dominio.com www.seu-dominio.com;
    root /var/www/controle-financeiro/public;

    index index.php index.html index.htm;

    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Ative o site:

```bash
sudo ln -s /etc/nginx/sites-available/controle-financeiro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Otimizações de Produção

### 1. Executar Script de Deploy

```bash
bash scripts/deploy-production.sh
```

### 2. Configurar Cron Jobs

```bash
bash scripts/setup-cron.sh
```

### 3. Configurar Backup Automático

O backup automático já está configurado via cron. Para testar manualmente:

```bash
bash scripts/backup-database.sh backup
```

### 4. Configurar Monitoramento

O sistema inclui endpoints de health check:

- `/health` - Verificação completa do sistema
- `/health/simple` - Verificação simples

Configure seu sistema de monitoramento para verificar estes endpoints.

## Configurações de Segurança

### 1. Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable

# Firewalld (CentOS)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 2. Fail2Ban (Opcional)

```bash
sudo apt install fail2ban
sudo systemctl start fail2ban
sudo systemctl enable fail2ban
```

### 3. Permissões de Arquivos

```bash
sudo chown -R www-data:www-data /var/www/controle-financeiro
sudo find /var/www/controle-financeiro -type f -exec chmod 644 {} \;
sudo find /var/www/controle-financeiro -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/controle-financeiro/storage
sudo chmod -R 775 /var/www/controle-financeiro/bootstrap/cache
```

## Manutenção

### Backup Manual

```bash
bash scripts/backup-database.sh backup
```

### Restaurar Backup

```bash
bash scripts/backup-database.sh restore /path/to/backup.sql.gz
```

### Atualização da Aplicação

```bash
bash scripts/deploy-production.sh
```

### Verificação de Saúde

```bash
bash scripts/deploy-production.sh health
```

### Logs

Os logs estão localizados em:

- Aplicação: `storage/logs/laravel.log`
- Backup: `storage/logs/backup.log`
- Deploy: `storage/logs/deployment.log`
- Monitoramento: `storage/logs/monitoring.log`

## Solução de Problemas

### Verificar Status dos Serviços

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
```

### Verificar Logs de Erro

```bash
# NGINX
sudo tail -f /var/log/nginx/error.log

# PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# Aplicação
tail -f storage/logs/laravel.log
```

### Limpar Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Reotimizar para Produção

```bash
bash scripts/deploy-production.sh optimize
```

## Contato e Suporte

Para suporte técnico ou dúvidas sobre a implantação, consulte a documentação do projeto ou entre em contato com a equipe de desenvolvimento.