#!/bin/bash

# AAPanel VPS Setup Script for Controle Financeiro
# This script sets up the application on a VPS with AAPanel already installed
# AAPanel should already have PHP, MySQL, and Nginx/Apache configured

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Get user input
get_user_input() {
    echo "=== AAPanel VPS Setup for Controle Financeiro ==="
    echo ""
    log "This script assumes AAPanel is already installed with PHP, MySQL, and web server"
    echo ""
    
    # Domain configuration
    read -p "Enter your domain (e.g., staging.controle-financeiro.com): " DOMAIN
    if [[ -z "$DOMAIN" ]]; then
        error "Domain is required"
        exit 1
    fi
    
    # Database configuration
    read -p "Enter MySQL database name [controle_financeiro_staging]: " DB_NAME
    DB_NAME=${DB_NAME:-controle_financeiro_staging}
    
    read -p "Enter MySQL username [staging_user]: " DB_USER
    DB_USER=${DB_USER:-staging_user}
    
    read -s -p "Enter MySQL password (will be hidden): " DB_PASSWORD
    echo ""
    if [[ -z "$DB_PASSWORD" ]]; then
        error "Database password is required"
        exit 1
    fi
    
    read -s -p "Enter MySQL root password (for database creation): " MYSQL_ROOT_PASSWORD
    echo ""
    if [[ -z "$MYSQL_ROOT_PASSWORD" ]]; then
        error "MySQL root password is required"
        exit 1
    fi
    
    # Application path (AAPanel typically uses /www/wwwroot/)
    read -p "Enter application path [/www/wwwroot/$DOMAIN]: " APP_PATH
    APP_PATH=${APP_PATH:-/www/wwwroot/$DOMAIN}
    
    # PHP version (AAPanel may have multiple versions)
    read -p "Enter PHP version [8.2]: " PHP_VERSION
    PHP_VERSION=${PHP_VERSION:-8.2}
    
    echo ""
    log "Configuration:"
    log "Domain: $DOMAIN"
    log "Database: $DB_NAME"
    log "Database User: $DB_USER"
    log "App Path: $APP_PATH"
    log "PHP Version: $PHP_VERSION"
    echo ""
    
    read -p "Continue with this configuration? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        log "Setup cancelled"
        exit 0
    fi
}

# Check AAPanel environment
check_aapanel_environment() {
    log "Checking AAPanel environment..."
    
    # Check if AAPanel is installed
    if [[ -d "/www/server" ]] || [[ -d "/www/wwwroot" ]]; then
        success "AAPanel environment detected"
    else
        warning "AAPanel directory structure not found, but continuing..."
    fi
    
    # Check PHP
    local php_cmd="php$PHP_VERSION"
    if command -v $php_cmd >/dev/null 2>&1; then
        local php_version=$($php_cmd -r "echo PHP_VERSION;")
        success "PHP $php_version detected"
        PHP_CMD=$php_cmd
    elif command -v php >/dev/null 2>&1; then
        local php_version=$(php -r "echo PHP_VERSION;")
        success "PHP $php_version detected"
        PHP_CMD=php
    else
        error "PHP not found. Please install PHP through AAPanel first."
        exit 1
    fi
    
    # Check MySQL
    if command -v mysql >/dev/null 2>&1; then
        success "MySQL detected"
    else
        error "MySQL not found. Please install MySQL through AAPanel first."
        exit 1
    fi
    
    # Check web server
    if systemctl is-active --quiet nginx >/dev/null 2>&1; then
        success "Nginx detected and running"
        WEB_SERVER="nginx"
    elif systemctl is-active --quiet apache2 >/dev/null 2>&1 || systemctl is-active --quiet httpd >/dev/null 2>&1; then
        success "Apache detected and running"
        WEB_SERVER="apache"
    else
        warning "Web server status unknown - will configure anyway"
        WEB_SERVER="nginx"
    fi
}

# Install missing dependencies
install_missing_dependencies() {
    log "Installing missing dependencies..."
    
    # Install Composer if missing
    if ! command -v composer >/dev/null 2>&1; then
        log "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        success "Composer installed"
    else
        success "Composer already available"
    fi
    
    # Install Node.js if missing (for asset compilation)
    if ! command -v node >/dev/null 2>&1; then
        log "Installing Node.js..."
        if [[ -f /etc/debian_version ]]; then
            curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
            sudo apt install -y nodejs
        elif [[ -f /etc/redhat-release ]]; then
            curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
            sudo yum install -y nodejs
        fi
        success "Node.js installed"
    else
        success "Node.js already available"
    fi
    
    # Install Git if missing
    if ! command -v git >/dev/null 2>&1; then
        log "Installing Git..."
        if [[ -f /etc/debian_version ]]; then
            sudo apt install -y git
        elif [[ -f /etc/redhat-release ]]; then
            sudo yum install -y git
        fi
        success "Git installed"
    else
        success "Git already available"
    fi
}

# Setup database
setup_database() {
    log "Setting up MySQL database..."
    
    # Create database and user
    log "Creating database and user..."
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;" 2>/dev/null || {
        error "Failed to create database. Please check MySQL root password."
        exit 1
    }
    
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';" 2>/dev/null
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';" 2>/dev/null
    mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "FLUSH PRIVILEGES;" 2>/dev/null
    
    success "Database setup completed"
}

# Configure environment
configure_environment() {
    log "Configuring environment..."
    
    # Create .env.staging if it doesn't exist
    if [[ ! -f ".env.staging" ]]; then
        log "Creating .env.staging file..."
        cp .env.example .env.staging
    fi
    
    # Update .env.staging with user input
    log "Updating environment configuration..."
    
    # Update basic app settings
    sed -i "s|APP_ENV=.*|APP_ENV=staging|" .env.staging
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env.staging
    sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env.staging
    
    # Update database settings
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env.staging
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env.staging
    sed -i "s|DB_PORT=.*|DB_PORT=3306|" .env.staging
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" .env.staging
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env.staging
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env.staging
    
    # Update cache and session settings for production
    sed -i "s|CACHE_STORE=.*|CACHE_STORE=file|" .env.staging
    sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env.staging
    sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env.staging
    
    success "Environment configured"
}

# Deploy application
deploy_application() {
    log "Deploying application to $APP_PATH..."
    
    # Create application directory
    sudo mkdir -p "$APP_PATH"
    
    # Copy application files
    log "Copying application files..."
    sudo rsync -av --delete \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        ./ "$APP_PATH/"
    
    # Set proper ownership (AAPanel typically uses www:www)
    log "Setting file permissions..."
    if id "www" &>/dev/null; then
        sudo chown -R www:www "$APP_PATH"
    else
        sudo chown -R www-data:www-data "$APP_PATH"
    fi
    
    sudo chmod -R 755 "$APP_PATH"
    sudo chmod -R 775 "$APP_PATH/storage"
    sudo chmod -R 775 "$APP_PATH/bootstrap/cache"
    
    # Install Composer dependencies
    log "Installing Composer dependencies..."
    cd "$APP_PATH"
    
    if id "www" &>/dev/null; then
        sudo -u www composer install --no-dev --optimize-autoloader --no-interaction
    else
        sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
    fi
    
    # Create necessary directories
    sudo mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
    
    # Copy environment file
    cp .env.staging .env
    
    success "Application deployed"
}

# Configure Laravel application
configure_laravel() {
    log "Configuring Laravel application..."
    
    cd "$APP_PATH"
    
    # Generate application key
    log "Generating application key..."
    if id "www" &>/dev/null; then
        sudo -u www $PHP_CMD artisan key:generate --force
    else
        sudo -u www-data $PHP_CMD artisan key:generate --force
    fi
    
    # Run database migrations
    log "Running database migrations..."
    if id "www" &>/dev/null; then
        sudo -u www $PHP_CMD artisan migrate --force --no-interaction
    else
        sudo -u www-data $PHP_CMD artisan migrate --force --no-interaction
    fi
    
    # Seed database (optional)
    log "Seeding database..."
    if id "www" &>/dev/null; then
        sudo -u www $PHP_CMD artisan db:seed --force --no-interaction || true
    else
        sudo -u www-data $PHP_CMD artisan db:seed --force --no-interaction || true
    fi
    
    # Optimize application
    log "Optimizing application..."
    if id "www" &>/dev/null; then
        sudo -u www $PHP_CMD artisan config:cache
        sudo -u www $PHP_CMD artisan route:cache
        sudo -u www $PHP_CMD artisan view:cache
        sudo -u www composer dump-autoload --optimize
    else
        sudo -u www-data $PHP_CMD artisan config:cache
        sudo -u www-data $PHP_CMD artisan route:cache
        sudo -u www-data $PHP_CMD artisan view:cache
        sudo -u www-data composer dump-autoload --optimize
    fi
    
    success "Laravel application configured"
}

# Final verification
verify_setup() {
    log "Verifying setup..."
    
    # Test database connection
    log "Testing database connection..."
    cd "$APP_PATH"
    if id "www" &>/dev/null; then
        if sudo -u www $PHP_CMD artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" 2>/dev/null; then
            success "Database connection is working"
        else
            warning "Database connection test failed"
        fi
    else
        if sudo -u www-data $PHP_CMD artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" 2>/dev/null; then
            success "Database connection is working"
        else
            warning "Database connection test failed"
        fi
    fi
    
    success "Setup verification completed"
}

# Main function
main() {
    echo "=== AAPanel VPS Setup for Controle Financeiro ==="
    echo ""
    echo "This script is optimized for VPS with AAPanel already installed."
    echo "It assumes PHP, MySQL, and web server are already configured through AAPanel."
    echo ""
    echo "The script will:"
    echo "1. Check AAPanel environment"
    echo "2. Install missing dependencies (Composer, Node.js)"
    echo "3. Configure database"
    echo "4. Configure environment"
    echo "5. Deploy the application"
    echo "6. Configure Laravel"
    echo ""
    
    # Check if running as root
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
        exit 1
    fi
    
    # Check if we're in the project directory
    if [[ ! -f "composer.json" ]]; then
        error "Please run this script from the project root directory"
        exit 1
    fi
    
    get_user_input
    check_aapanel_environment
    install_missing_dependencies
    setup_database
    configure_environment
    deploy_application
    configure_laravel
    verify_setup
    
    echo ""
    success "=== AAPanel Setup completed successfully! ==="
    echo ""
    log "Your application files are at: $APP_PATH"
    echo ""
    log "Next steps:"
    log "1. Create a website in AAPanel pointing to: $APP_PATH/public"
    log "2. Configure SSL certificate in AAPanel for: $DOMAIN"
    log "3. Set PHP version to $PHP_VERSION in AAPanel"
    echo ""
    log "After AAPanel configuration, your site will be available at:"
    log "- Site: https://$DOMAIN"
    log "- Admin: https://$DOMAIN/admin"
    echo ""
    log "Default credentials:"
    log "- Admin: admin@admin.com / password"
    log "- Demo: demo@demo.com / password"
    echo ""
    warning "Remember to change the default passwords after first login!"
    echo ""
}

# Run main function
main "$@"