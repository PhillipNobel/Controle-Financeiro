#!/bin/bash

# AAPanel Setup Script for Controle Financeiro
# Simple deployment script for VPS with AAPanel
# Assumes PHP, MySQL, and web server are already configured

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }
warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }

# Get user input
get_user_input() {
    echo "=== AAPanel Setup for Controle Financeiro ==="
    echo ""
    
    read -p "Domain (ex: staging.meusite.com): " DOMAIN
    [[ -z "$DOMAIN" ]] && { error "Domain required"; exit 1; }
    
    read -p "Database name [controle_financeiro]: " DB_NAME
    DB_NAME=${DB_NAME:-controle_financeiro}
    
    read -p "Database user [laravel_user]: " DB_USER
    DB_USER=${DB_USER:-laravel_user}
    
    read -s -p "Database password: " DB_PASSWORD
    echo ""
    [[ -z "$DB_PASSWORD" ]] && { error "Password required"; exit 1; }
    
    read -s -p "MySQL root password: " MYSQL_ROOT_PASSWORD
    echo ""
    [[ -z "$MYSQL_ROOT_PASSWORD" ]] && { error "Root password required"; exit 1; }
    
    APP_PATH="/www/wwwroot/$DOMAIN"
    
    echo ""
    log "Domain: $DOMAIN"
    log "Database: $DB_NAME"
    log "Path: $APP_PATH"
    echo ""
    
    read -p "Continue? (y/N): " CONFIRM
    [[ ! "$CONFIRM" =~ ^[Yy]$ ]] && { log "Cancelled"; exit 0; }
}

# Check environment
check_environment() {
    log "Checking environment..."
    
    # Check AAPanel
    [[ -d "/www/wwwroot" ]] && success "AAPanel detected" || warning "AAPanel not detected"
    
    # Check PHP
    if command -v php >/dev/null 2>&1; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        success "PHP $PHP_VERSION found"
    else
        error "PHP not found. Install through AAPanel first."
        exit 1
    fi
    
    # Check MySQL
    command -v mysql >/dev/null 2>&1 && success "MySQL found" || { error "MySQL not found"; exit 1; }
}

# Install dependencies
install_dependencies() {
    log "Installing dependencies..."
    
    # Composer
    if ! command -v composer >/dev/null 2>&1; then
        log "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        success "Composer installed"
    fi
    
    # Node.js (if needed for assets)
    if ! command -v node >/dev/null 2>&1; then
        log "Installing Node.js..."
        curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash - 2>/dev/null || true
        sudo apt install -y nodejs 2>/dev/null || sudo yum install -y nodejs 2>/dev/null || true
        success "Node.js installed"
    fi
}

# Setup database
setup_database() {
    log "Setting up database..."
    
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" << EOF 2>/dev/null || { error "Database setup failed"; exit 1; }
CREATE DATABASE IF NOT EXISTS $DB_NAME;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    success "Database created"
}

# Configure environment
configure_environment() {
    log "Configuring environment..."
    
    # Create .env file
    cat > .env << EOF
APP_NAME="Controle Financeiro"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
EOF
    
    success "Environment configured"
}

# Deploy application
deploy_application() {
    log "Deploying to $APP_PATH..."
    
    # Create and copy files
    sudo mkdir -p "$APP_PATH"
    sudo cp -r . "$APP_PATH/"
    
    # Set permissions
    sudo chown -R www:www "$APP_PATH" 2>/dev/null || sudo chown -R www-data:www-data "$APP_PATH"
    sudo chmod -R 755 "$APP_PATH"
    sudo chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
    
    cd "$APP_PATH"
    
    # Install dependencies
    log "Installing dependencies..."
    sudo -u www composer install --no-dev --optimize-autoloader 2>/dev/null || \
    sudo -u www-data composer install --no-dev --optimize-autoloader
    
    success "Application deployed"
}

# Configure Laravel
configure_laravel() {
    log "Configuring Laravel..."
    
    cd "$APP_PATH"
    
    # Generate key and run migrations
    sudo -u www php artisan key:generate --force 2>/dev/null || sudo -u www-data php artisan key:generate --force
    sudo -u www php artisan migrate --force 2>/dev/null || sudo -u www-data php artisan migrate --force
    sudo -u www php artisan db:seed --force 2>/dev/null || sudo -u www-data php artisan db:seed --force || true
    
    # Optimize
    sudo -u www php artisan config:cache 2>/dev/null || sudo -u www-data php artisan config:cache
    sudo -u www php artisan route:cache 2>/dev/null || sudo -u www-data php artisan route:cache
    sudo -u www php artisan view:cache 2>/dev/null || sudo -u www-data php artisan view:cache
    
    success "Laravel configured"
}

# Verify setup
verify_setup() {
    log "Verifying setup..."
    
    cd "$APP_PATH"
    if sudo -u www php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null || \
       sudo -u www-data php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null; then
        success "Database connection OK"
    else
        warning "Database test failed"
    fi
}

# Main function
main() {
    echo "=== AAPanel Setup for Controle Financeiro ==="
    echo ""
    
    [[ $EUID -eq 0 ]] && { error "Don't run as root"; exit 1; }
    [[ ! -f "composer.json" ]] && { error "Run from project root"; exit 1; }
    
    get_user_input
    check_environment
    install_dependencies
    setup_database
    configure_environment
    deploy_application
    configure_laravel
    verify_setup
    
    echo ""
    success "=== Setup Complete! ==="
    echo ""
    log "Files deployed to: $APP_PATH"
    echo ""
    log "Next steps in AAPanel:"
    log "1. Create website pointing to: $APP_PATH/public"
    log "2. Configure SSL for: $DOMAIN"
    log "3. Set PHP 8.2+"
    echo ""
    log "Then access:"
    log "- Site: https://$DOMAIN"
    log "- Admin: https://$DOMAIN/admin"
    echo ""
    log "Default login: admin@admin.com / password"
    warning "Change password after first login!"
}

main "$@"