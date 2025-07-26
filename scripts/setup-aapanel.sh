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

# Environment file management functions

# Create backup of existing .env file
backup_existing_env() {
    if [[ -f ".env" ]]; then
        local backup_name=".env.backup.$(date +%Y%m%d_%H%M%S)"
        cp .env "$backup_name"
        log "Backup created: $backup_name"
        return 0
    else
        log "No existing .env file to backup"
        return 1
    fi
}

# Copy .env.example to .env
copy_env_example() {
    if [[ ! -f ".env.example" ]]; then
        error "File .env.example not found"
        error "Make sure you're running the script from the Laravel project root"
        exit 1
    fi
    
    # Create backup of existing .env if it exists
    backup_existing_env
    
    # Copy .env.example to .env
    cp .env.example .env
    success ".env created from .env.example"
}

# Update specific variable in .env file
update_env_variable() {
    local key="$1"
    local value="$2"
    
    if [[ -z "$key" ]]; then
        error "Variable key cannot be empty"
        return 1
    fi
    
    if [[ ! -f ".env" ]]; then
        error ".env file not found"
        return 1
    fi
    
    # Create a temporary file for safer replacement
    local temp_file="/tmp/.env.temp.$$"
    
    # Check if variable exists in .env
    if grep -q "^${key}=" .env; then
        # Update existing variable by replacing the line
        # Use a more robust approach that preserves escaped characters
        while IFS= read -r line; do
            if [[ "$line" =~ ^${key}= ]]; then
                echo "${key}=\"${value}\""
            else
                echo "$line"
            fi
        done < .env > "$temp_file"
        
        mv "$temp_file" .env
        log "Updated ${key} in .env"
    else
        # Add new variable
        echo "${key}=\"${value}\"" >> .env
        log "Added ${key} to .env"
    fi
}

# Update database variables in .env file
update_database_variables() {
    local db_name="$1"
    local db_user="$2"
    local db_password="$3"
    local db_host="${4:-127.0.0.1}"
    local db_port="${5:-3306}"
    
    if [[ -z "$db_name" || -z "$db_user" || -z "$db_password" ]]; then
        error "Database name, user, and password are required"
        return 1
    fi
    
    log "Updating database variables in .env..."
    
    # Update all database-related variables
    update_env_variable "DB_CONNECTION" "mysql"
    update_env_variable "DB_HOST" "$db_host"
    update_env_variable "DB_PORT" "$db_port"
    update_env_variable "DB_DATABASE" "$db_name"
    update_env_variable "DB_USERNAME" "$db_user"
    update_env_variable "DB_PASSWORD" "$db_password"
    
    success "Database variables updated in .env"
}

# Update APP_URL with HTTPS
update_app_url() {
    local domain="$1"
    
    if [[ -z "$domain" ]]; then
        error "Domain is required for APP_URL"
        return 1
    fi
    
    # Remove any existing protocol from domain
    domain=$(echo "$domain" | sed 's|^https*://||')
    
    # Remove trailing slash if present
    domain=$(echo "$domain" | sed 's|/$||')
    
    # Construct HTTPS URL
    local app_url="https://$domain"
    
    log "Updating APP_URL to: $app_url"
    update_env_variable "APP_URL" "$app_url"
    
    success "APP_URL updated with HTTPS"
}

# Update APP_NAME with proper character escaping
update_app_name() {
    local app_name="$1"
    
    if [[ -z "$app_name" ]]; then
        error "Application name cannot be empty"
        return 1
    fi
    
    # Trim leading and trailing whitespace
    app_name=$(echo "$app_name" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
    
    if [[ -z "$app_name" ]]; then
        error "Application name cannot be empty after trimming whitespace"
        return 1
    fi
    
    log "Updating APP_NAME to: $app_name"
    
    # Escape double quotes in the app name for proper .env format
    local escaped_name=$(echo "$app_name" | sed 's/"/\\"/g')
    
    update_env_variable "APP_NAME" "$escaped_name"
    
    success "APP_NAME updated with proper character escaping"
}

# Preserve .env file structure while updating variables
preserve_env_structure() {
    local temp_file="/tmp/.env.temp.$$"
    
    if [[ ! -f ".env" ]]; then
        error ".env file not found"
        return 1
    fi
    
    log "Preserving .env file structure..."
    
    # Create a backup before any modifications
    cp .env "${temp_file}.backup"
    
    # The structure is already preserved by our update_env_variable function
    # which only modifies existing lines or appends new ones
    # This function serves as a validation step
    
    # Verify the file still has proper structure
    if ! validate_env_file_format; then
        warning "File structure may have been compromised, restoring backup"
        cp "${temp_file}.backup" .env
        rm -f "${temp_file}.backup"
        return 1
    fi
    
    # Clean up backup
    rm -f "${temp_file}.backup"
    
    success ".env file structure preserved"
}

# Validate .env file format (basic structure check)
validate_env_file_format() {
    if [[ ! -f ".env" ]]; then
        return 1
    fi
    
    # Check for basic .env format: KEY=VALUE or KEY="VALUE"
    # Allow empty lines and comments (lines starting with #)
    while IFS= read -r line; do
        # Skip empty lines
        [[ -z "$line" ]] && continue
        
        # Skip comments
        [[ "$line" =~ ^[[:space:]]*# ]] && continue
        
        # Check if line matches KEY=VALUE format
        if [[ ! "$line" =~ ^[A-Za-z_][A-Za-z0-9_]*= ]]; then
            warning "Invalid line format in .env: $line"
            return 1
        fi
    done < .env
    
    return 0
}

# Validate .env file structure
validate_env_file() {
    if [[ ! -f ".env" ]]; then
        error ".env file not found"
        return 1
    fi
    
    local validation_errors=0
    
    # Check for required Laravel variables
    local required_vars=("APP_NAME" "APP_ENV" "APP_KEY" "APP_URL" "DB_CONNECTION" "DB_HOST" "DB_PORT" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    
    log "Validating .env file structure..."
    
    for var in "${required_vars[@]}"; do
        if ! grep -q "^${var}=" .env; then
            warning "Missing required variable: ${var}"
            ((validation_errors++))
        fi
    done
    
    # Check for empty critical variables
    local critical_vars=("APP_KEY" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    
    for var in "${critical_vars[@]}"; do
        if grep -q "^${var}=$" .env || grep -q "^${var}=\"\"$" .env; then
            warning "Critical variable ${var} is empty"
            ((validation_errors++))
        fi
    done
    
    # Check file format (basic validation)
    if grep -q $'\r' .env; then
        warning ".env file contains Windows line endings (CRLF)"
        ((validation_errors++))
    fi
    
    # Check for duplicate variables
    local duplicates=$(cut -d'=' -f1 .env | sort | uniq -d)
    if [[ -n "$duplicates" ]]; then
        warning "Duplicate variables found: $duplicates"
        ((validation_errors++))
    fi
    
    if [[ $validation_errors -eq 0 ]]; then
        success ".env file validation passed"
        return 0
    else
        warning ".env file validation completed with $validation_errors warnings"
        return 1
    fi
}

# User information collection functions

# Collect database information
get_database_info() {
    echo "=== Configuração do Banco de Dados ==="
    echo ""
    
    # Database name
    read -p "Nome do banco de dados [controle_financeiro]: " DB_NAME
    DB_NAME=${DB_NAME:-controle_financeiro}
    
    # Validate database name (basic validation)
    if [[ ! "$DB_NAME" =~ ^[a-zA-Z0-9_]+$ ]]; then
        error "Nome do banco deve conter apenas letras, números e underscore"
        return 1
    fi
    
    # Database user
    read -p "Usuário do banco [laravel_user]: " DB_USER
    DB_USER=${DB_USER:-laravel_user}
    
    # Validate database user (basic validation)
    if [[ ! "$DB_USER" =~ ^[a-zA-Z0-9_]+$ ]]; then
        error "Nome do usuário deve conter apenas letras, números e underscore"
        return 1
    fi
    
    # Database password (required)
    while [[ -z "$DB_PASSWORD" ]]; do
        read -s -p "Senha do banco de dados: " DB_PASSWORD
        echo ""
        if [[ -z "$DB_PASSWORD" ]]; then
            error "Senha é obrigatória"
        elif [[ ${#DB_PASSWORD} -lt 6 ]]; then
            error "Senha deve ter pelo menos 6 caracteres"
            DB_PASSWORD=""
        fi
    done
    
    # MySQL root password (required for database creation)
    while [[ -z "$MYSQL_ROOT_PASSWORD" ]]; do
        read -s -p "Senha do root do MySQL: " MYSQL_ROOT_PASSWORD
        echo ""
        if [[ -z "$MYSQL_ROOT_PASSWORD" ]]; then
            error "Senha do root é obrigatória para criar o banco de dados"
        fi
    done
    
    success "Informações do banco de dados coletadas"
    return 0
}

# Collect domain information
get_domain_info() {
    echo ""
    echo "=== Configuração do Domínio ==="
    echo ""
    
    while [[ -z "$DOMAIN" ]]; do
        read -p "Domínio da aplicação (ex: app.exemplo.com): " DOMAIN
        
        if [[ -z "$DOMAIN" ]]; then
            error "Domínio é obrigatório"
            continue
        fi
        
        # Basic URL format validation
        if [[ ! "$DOMAIN" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9]\.[a-zA-Z]{2,}$ ]]; then
            error "Formato de domínio inválido"
            error "Use apenas letras, números, pontos e hífens (ex: app.exemplo.com)"
            DOMAIN=""
            continue
        fi
        
        # Check for common mistakes
        if [[ "$DOMAIN" =~ ^https?:// ]]; then
            error "Não inclua http:// ou https:// no domínio"
            DOMAIN=""
            continue
        fi
        
        if [[ "$DOMAIN" =~ /$ ]]; then
            error "Não inclua barra no final do domínio"
            DOMAIN=""
            continue
        fi
        
        # Convert to lowercase for consistency
        DOMAIN=$(echo "$DOMAIN" | tr '[:upper:]' '[:lower:]')
        
        # Confirm domain
        echo ""
        log "Domínio configurado: $DOMAIN"
        log "URL da aplicação será: https://$DOMAIN"
        echo ""
        read -p "Confirma o domínio? (Y/n): " CONFIRM_DOMAIN
        
        if [[ "$CONFIRM_DOMAIN" =~ ^[Nn]$ ]]; then
            DOMAIN=""
            continue
        fi
    done
    
    success "Informações do domínio coletadas"
    return 0
}

# Collect application information
get_app_info() {
    echo ""
    echo "=== Configuração da Aplicação ==="
    echo ""
    
    # Application name
    read -p "Nome da aplicação [Controle Financeiro]: " APP_NAME
    APP_NAME=${APP_NAME:-"Controle Financeiro"}
    
    # Validate application name (allow spaces and special characters, but not empty)
    if [[ -z "$APP_NAME" ]]; then
        error "Nome da aplicação não pode estar vazio"
        return 1
    fi
    
    # Trim whitespace
    APP_NAME=$(echo "$APP_NAME" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
    
    # Check for reasonable length
    if [[ ${#APP_NAME} -gt 50 ]]; then
        warning "Nome da aplicação muito longo (máximo recomendado: 50 caracteres)"
        read -p "Continuar mesmo assim? (y/N): " CONFIRM_LONG_NAME
        if [[ ! "$CONFIRM_LONG_NAME" =~ ^[Yy]$ ]]; then
            return 1
        fi
    fi
    
    success "Informações da aplicação coletadas"
    return 0
}

# Validate user input
validate_input() {
    local input_type="$1"
    local value="$2"
    
    case "$input_type" in
        "database_name")
            [[ "$value" =~ ^[a-zA-Z0-9_]+$ ]] && return 0 || return 1
            ;;
        "database_user")
            [[ "$value" =~ ^[a-zA-Z0-9_]+$ ]] && return 0 || return 1
            ;;
        "domain")
            [[ "$value" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9]\.[a-zA-Z]{2,}$ ]] && return 0 || return 1
            ;;
        "app_name")
            [[ -n "$value" ]] && [[ ${#value} -le 100 ]] && return 0 || return 1
            ;;
        *)
            return 1
            ;;
    esac
}

# Main user input collection function
get_user_input() {
    echo "=== AAPanel Setup for Controle Financeiro ==="
    echo ""
    
    # Collect all user information
    if ! get_database_info; then
        error "Falha na coleta de informações do banco de dados"
        exit 1
    fi
    
    if ! get_domain_info; then
        error "Falha na coleta de informações do domínio"
        exit 1
    fi
    
    if ! get_app_info; then
        error "Falha na coleta de informações da aplicação"
        exit 1
    fi
    
    # Set application path based on domain
    APP_PATH="/www/wwwroot/$DOMAIN"
    
    # Display summary
    echo ""
    echo "=== Resumo da Configuração ==="
    log "Nome da aplicação: $APP_NAME"
    log "Domínio: $DOMAIN"
    log "URL: https://$DOMAIN"
    log "Banco de dados: $DB_NAME"
    log "Usuário do banco: $DB_USER"
    log "Caminho de instalação: $APP_PATH"
    echo ""
    
    # Final confirmation
    read -p "Confirma a configuração e continua com a instalação? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        log "Instalação cancelada pelo usuário"
        exit 0
    fi
    
    success "Configuração confirmada, iniciando instalação..."
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
    
    # Copy .env.example to .env (with backup if exists)
    copy_env_example
    
    # Update application name with proper character escaping
    update_app_name "$APP_NAME"
    
    # Update APP_URL with HTTPS
    update_app_url "$DOMAIN"
    
    # Update database variables
    update_database_variables "$DB_NAME" "$DB_USER" "$DB_PASSWORD"
    
    # Update other environment variables
    update_env_variable "APP_ENV" "production"
    update_env_variable "APP_DEBUG" "false"
    
    # Additional configuration
    update_env_variable "CACHE_STORE" "file"
    update_env_variable "SESSION_DRIVER" "file"
    update_env_variable "QUEUE_CONNECTION" "database"
    
    # Preserve file structure and validate
    preserve_env_structure
    
    # Validate the .env file structure
    validate_env_file
    
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