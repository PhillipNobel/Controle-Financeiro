#!/bin/bash

# Native Staging Deployment Script
# This script deploys the application to the staging environment using native PHP/MySQL
# No Docker dependencies - optimized for VPS with limited hardware resources
# 
# Usage: ./scripts/deploy-staging.sh [--auto-install]
#
# The --auto-install flag will automatically install missing dependencies

set -e

# Configuration (can be overridden by environment variables)
APP_URL="${APP_URL:-https://staging.controle-financeiro.com}"
APP_PATH="${APP_PATH:-/var/www/html/controle-financeiro}"
BACKUP_DIR="/var/backups/controle-financeiro"
LOG_FILE="/var/log/controle-financeiro-deploy.log"
PHP_USER="${PHP_USER:-www-data}"
MYSQL_USER="${MYSQL_USER:-staging_user}"
MYSQL_DB="${MYSQL_DB:-controle_financeiro_staging}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
AUTO_INSTALL="${AUTO_INSTALL:-false}"
TIMEOUT=300

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    local message="[$(date +'%Y-%m-%d %H:%M:%S')] $1"
    echo -e "${BLUE}${message}${NC}"
    echo "$message" >> "$LOG_FILE"
}

success() {
    local message="[✓] $1"
    echo -e "${GREEN}${message}${NC}"
    echo "$message" >> "$LOG_FILE"
}

error() {
    local message="[✗] $1"
    echo -e "${RED}${message}${NC}"
    echo "$message" >> "$LOG_FILE"
}

warning() {
    local message="[!] $1"
    echo -e "${YELLOW}${message}${NC}"
    echo "$message" >> "$LOG_FILE"
}

# Cleanup function for graceful exit
cleanup() {
    local exit_code=$?
    if [[ $exit_code -ne 0 ]]; then
        error "Deployment failed with exit code $exit_code"
        log "Starting rollback procedure..."
        rollback_deployment
    fi
    exit $exit_code
}

# Set trap for cleanup
trap cleanup EXIT

# Auto-install missing dependencies (AAPanel compatible)
auto_install_dependencies() {
    log "Checking for missing dependencies (AAPanel environment detected)..."
    
    # Install Composer if missing (AAPanel doesn't include it by default)
    if ! command -v composer >/dev/null 2>&1; then
        log "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        success "Composer installed"
    fi
    
    # Install Node.js if missing (for asset compilation)
    if ! command -v node >/dev/null 2>&1; then
        log "Installing Node.js..."
        # Detect OS for Node.js installation
        if [[ -f /etc/debian_version ]]; then
            curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
            sudo apt install -y nodejs
        elif [[ -f /etc/redhat-release ]]; then
            curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
            sudo yum install -y nodejs
        fi
        success "Node.js installed"
    fi
    
    success "Dependencies check completed (AAPanel environment)"
}

# Check prerequisites (AAPanel compatible)
check_prerequisites() {
    log "Checking deployment prerequisites (AAPanel environment)..."
    
    local missing_deps=()
    
    # Check if PHP is available (should be installed by AAPanel)
    if ! command -v php >/dev/null 2>&1; then
        missing_deps+=("PHP")
    else
        local php_version=$(php -r "echo PHP_VERSION;")
        log "PHP version: $php_version (AAPanel managed)"
    fi
    
    # Check if Composer is available
    if ! command -v composer >/dev/null 2>&1; then
        missing_deps+=("Composer")
    fi
    
    # Check if MySQL is available (should be installed by AAPanel)
    if ! command -v mysql >/dev/null 2>&1; then
        missing_deps+=("MySQL")
    else
        log "MySQL detected (AAPanel managed)"
    fi
    
    # Check if git is available
    if ! command -v git >/dev/null 2>&1; then
        missing_deps+=("Git")
    fi
    
    # Handle missing dependencies
    if [[ ${#missing_deps[@]} -gt 0 ]]; then
        warning "Missing dependencies: ${missing_deps[*]}"
        
        if [[ $AUTO_INSTALL == "true" ]]; then
            auto_install_dependencies
        else
            error "Missing dependencies. Run with --auto-install to install them automatically"
            log "Or install manually: ${missing_deps[*]}"
            exit 1
        fi
    fi
    
    # Check if we're in the right directory
    if [[ ! -f "composer.json" ]]; then
        error "composer.json not found. Are you in the project root?"
        exit 1
    fi
    
    # Check if .env.staging exists
    if [[ ! -f ".env.staging" ]]; then
        error ".env.staging file not found"
        exit 1
    fi
    
    # Check if application directory exists
    if [[ ! -d "$APP_PATH" ]]; then
        log "Creating application directory: $APP_PATH"
        sudo mkdir -p "$APP_PATH"
        sudo chown "$PHP_USER:$PHP_USER" "$APP_PATH"
    fi
    
    # Check web server status (AAPanel manages Nginx/Apache)
    if systemctl is-active --quiet nginx >/dev/null 2>&1; then
        success "Nginx is running (AAPanel managed)"
    elif systemctl is-active --quiet apache2 >/dev/null 2>&1; then
        success "Apache is running (AAPanel managed)"
    elif systemctl is-active --quiet httpd >/dev/null 2>&1; then
        success "Apache is running (AAPanel managed)"
    else
        warning "Web server status unknown - AAPanel may be managing it differently"
    fi
    
    success "Prerequisites check passed (AAPanel environment)"
}

# Create backup
create_backup() {
    log "Creating backup before deployment..."
    
    # Create backup directory if it doesn't exist
    sudo mkdir -p "$BACKUP_DIR"
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="staging_backup_${backup_timestamp}"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    sudo mkdir -p "$backup_path"
    
    # Backup database
    log "Backing up database..."
    if mysql -u "$MYSQL_USER" -p"${DB_PASSWORD}" -e "USE $MYSQL_DB;" 2>/dev/null; then
        mysqldump -u "$MYSQL_USER" -p"${DB_PASSWORD}" \
            --single-transaction \
            --routines \
            --triggers \
            "$MYSQL_DB" > "${backup_path}/database.sql"
        success "Database backup created"
    else
        warning "Could not connect to database, skipping database backup"
    fi
    
    # Backup application files (storage, uploads, etc.)
    if [[ -d "$APP_PATH" ]]; then
        log "Backing up application files..."
        sudo cp -r "$APP_PATH" "${backup_path}/application"
        success "Application files backup created"
    else
        warning "Application directory not found, skipping application backup"
    fi
    
    # Backup current environment file
    cp ".env" "${backup_path}/" 2>/dev/null || true
    cp ".env.staging" "${backup_path}/" 2>/dev/null || true
    
    # Create backup metadata
    sudo tee "${backup_path}/metadata.json" > /dev/null << EOF
{
    "timestamp": "$(date -Iseconds)",
    "git_commit": "$(git rev-parse HEAD)",
    "git_branch": "$(git rev-parse --abbrev-ref HEAD)",
    "environment": "staging",
    "backup_type": "pre_deployment",
    "app_path": "$APP_PATH",
    "mysql_db": "$MYSQL_DB"
}
EOF
    
    # Store backup path for potential rollback
    echo "$backup_path" > /tmp/last_backup_path
    
    success "Backup created at: $backup_path"
}

# Pull latest code
pull_code() {
    log "Pulling latest code from repository..."
    
    # Fetch latest changes
    git fetch origin
    
    # Get current branch
    local current_branch=$(git rev-parse --abbrev-ref HEAD)
    log "Current branch: $current_branch"
    
    # Pull latest changes
    git pull origin "$current_branch"
    
    # Show latest commit
    local latest_commit=$(git log -1 --pretty=format:"%h - %s (%an, %ar)")
    log "Latest commit: $latest_commit"
    
    success "Code updated successfully"
}

# Deploy application natively
deploy_application() {
    log "Deploying application natively..."
    
    # Copy staging environment file
    cp .env.staging .env
    
    # Sync application files to deployment directory
    log "Syncing application files..."
    sudo rsync -av --delete \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        ./ "$APP_PATH/"
    
    # Set proper ownership and permissions
    log "Setting file permissions..."
    sudo chown -R "$PHP_USER:$PHP_USER" "$APP_PATH"
    sudo chmod -R 755 "$APP_PATH"
    sudo chmod -R 775 "$APP_PATH/storage"
    sudo chmod -R 775 "$APP_PATH/bootstrap/cache"
    
    # Install/update Composer dependencies
    log "Installing Composer dependencies..."
    cd "$APP_PATH"
    sudo -u "$PHP_USER" composer install --no-dev --optimize-autoloader --no-interaction
    
    # Create necessary directories
    sudo -u "$PHP_USER" mkdir -p storage/logs
    sudo -u "$PHP_USER" mkdir -p storage/framework/cache
    sudo -u "$PHP_USER" mkdir -p storage/framework/sessions
    sudo -u "$PHP_USER" mkdir -p storage/framework/views
    
    success "Application deployed successfully"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    cd "$APP_PATH"
    
    # Test database connection
    local max_attempts=10
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if sudo -u "$PHP_USER" php artisan migrate --dry-run --no-interaction >/dev/null 2>&1; then
            break
        fi
        
        log "Waiting for database to be ready (attempt $attempt/$max_attempts)..."
        sleep 5
        ((attempt++))
    done
    
    if [[ $attempt -gt $max_attempts ]]; then
        error "Database is not ready after $max_attempts attempts"
        exit 1
    fi
    
    # Run migrations
    log "Executing database migrations..."
    sudo -u "$PHP_USER" php artisan migrate --force --no-interaction
    
    success "Database migrations completed"
}

# Clear and optimize caches
optimize_application() {
    log "Optimizing application..."
    
    cd "$APP_PATH"
    
    # Clear caches
    log "Clearing application caches..."
    sudo -u "$PHP_USER" php artisan cache:clear
    sudo -u "$PHP_USER" php artisan config:clear
    sudo -u "$PHP_USER" php artisan route:clear
    sudo -u "$PHP_USER" php artisan view:clear
    
    # Optimize for production
    log "Caching configuration for production..."
    sudo -u "$PHP_USER" php artisan config:cache
    sudo -u "$PHP_USER" php artisan route:cache
    sudo -u "$PHP_USER" php artisan view:cache
    
    # Generate optimized autoloader
    log "Optimizing Composer autoloader..."
    sudo -u "$PHP_USER" composer dump-autoload --optimize
    
    # Restart OpenLiteSpeed to ensure changes take effect
    log "Restarting OpenLiteSpeed..."
    if systemctl is-active --quiet lshttpd; then
        sudo systemctl reload lshttpd
        success "OpenLiteSpeed reloaded"
    else
        sudo systemctl restart lshttpd
        success "OpenLiteSpeed restarted"
    fi
    
    success "Application optimized"
}

# Run health checks
run_health_checks() {
    log "Running post-deployment health checks..."
    
    cd "$APP_PATH"
    
    # Wait a bit for services to stabilize
    sleep 10
    
    # Run comprehensive health check
    log "Running application health checks..."
    if sudo -u "$PHP_USER" php artisan health:check --detailed --no-interaction 2>/dev/null; then
        success "Application health checks passed"
    else
        warning "Application health check command not available or failed"
    fi
    
    # Test database connection
    log "Testing database connection..."
    if sudo -u "$PHP_USER" php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';" 2>/dev/null; then
        success "Database connection is working"
    else
        error "Database connection failed"
        return 1
    fi
    
    # Test OpenLiteSpeed status
    log "Checking OpenLiteSpeed status..."
    if systemctl is-active --quiet lshttpd; then
        success "OpenLiteSpeed is running"
    else
        error "OpenLiteSpeed is not running"
        return 1
    fi
    
    # Test web server response
    local max_attempts=10
    local attempt=1
    
    log "Testing web server response..."
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s -o /dev/null --max-time 30 "$APP_URL" || curl -f -s -o /dev/null --max-time 30 "$APP_URL/health" 2>/dev/null; then
            success "Web server is responding"
            break
        fi
        
        log "Testing web server response (attempt $attempt/$max_attempts)..."
        sleep 10
        ((attempt++))
    done
    
    if [[ $attempt -gt $max_attempts ]]; then
        error "Web server is not responding after $max_attempts attempts"
        return 1
    fi
    
    return 0
}

# Rollback deployment
rollback_deployment() {
    log "Rolling back deployment..."
    
    if [[ -f "/tmp/last_backup_path" ]]; then
        local backup_path=$(cat /tmp/last_backup_path)
        
        if [[ -d "$backup_path" ]]; then
            log "Restoring from backup: $backup_path"
            
            # Restore application files
            if [[ -d "${backup_path}/application" ]]; then
                log "Restoring application files..."
                sudo rm -rf "$APP_PATH"
                sudo cp -r "${backup_path}/application" "$APP_PATH"
                sudo chown -R "$PHP_USER:$PHP_USER" "$APP_PATH"
                success "Application files restored"
            fi
            
            # Restore environment files
            cp "${backup_path}/.env" ./ 2>/dev/null || true
            cp "${backup_path}/.env.staging" ./ 2>/dev/null || true
            
            # Restore database if backup exists
            if [[ -f "${backup_path}/database.sql" ]]; then
                log "Restoring database..."
                mysql -u "$MYSQL_USER" -p"${DB_PASSWORD}" "$MYSQL_DB" < "${backup_path}/database.sql"
                success "Database restored"
            fi
            
            # Restart OpenLiteSpeed
            log "Restarting OpenLiteSpeed..."
            sudo systemctl restart lshttpd
            
            success "Rollback completed"
        else
            error "Backup path not found: $backup_path"
        fi
    else
        error "No backup information found for rollback"
    fi
}

# Send deployment notification
send_notification() {
    local status=$1
    local message=$2
    
    # Log the deployment result
    if [[ $status == "success" ]]; then
        success "Deployment completed successfully"
        log "Application is available at: $APP_URL"
    else
        error "Deployment failed: $message"
    fi
    
    # TODO: Add webhook notification, Slack, email, etc.
    # Example:
    # curl -X POST -H 'Content-type: application/json' \
    #     --data "{\"text\":\"Staging deployment $status: $message\"}" \
    #     "$SLACK_WEBHOOK_URL"
}

# Main deployment function
main() {
    echo "=== Staging Deployment Started ==="
    echo "Timestamp: $(date)"
    echo "Environment: staging"
    echo "Target URL: $APP_URL"
    echo ""
    
    # Create log file
    mkdir -p "$(dirname "$LOG_FILE")"
    touch "$LOG_FILE"
    
    # Run deployment steps
    check_prerequisites
    create_backup
    pull_code
    deploy_application
    run_migrations
    optimize_application
    
    if run_health_checks; then
        send_notification "success" "Deployment completed successfully"
        
        # Clean up old backups (keep last 5)
        find "$BACKUP_DIR" -name "staging_backup_*" -type d | sort -r | tail -n +6 | xargs rm -rf 2>/dev/null || true
        
        success "Deployment completed successfully!"
        log "Application is available at: $APP_URL"
    else
        send_notification "failed" "Health checks failed"
        error "Deployment failed due to health check failures"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    --auto-install)
        AUTO_INSTALL="true"
        log "Auto-install mode enabled"
        ;;
    --dry-run)
        log "Dry run mode - no actual deployment will be performed"
        check_prerequisites
        log "Dry run completed successfully"
        exit 0
        ;;
    --rollback)
        log "Manual rollback requested"
        rollback_deployment
        exit 0
        ;;
    --health-check)
        log "Running health check only"
        run_health_checks
        exit $?
        ;;
    --help)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --auto-install Install missing dependencies automatically"
        echo "  --dry-run      Perform a dry run without actual deployment"
        echo "  --rollback     Rollback to the previous deployment"
        echo "  --health-check Run health checks only"
        echo "  --help         Show this help message"
        echo ""
        echo "Environment Variables:"
        echo "  APP_URL        Application URL (default: https://staging.controle-financeiro.com)"
        echo "  APP_PATH       Application path (default: /var/www/html/controle-financeiro)"
        echo "  MYSQL_USER     MySQL user (default: staging_user)"
        echo "  MYSQL_DB       MySQL database (default: controle_financeiro_staging)"
        echo "  MYSQL_PASSWORD MySQL password (required)"
        echo ""
        exit 0
        ;;
esac

# Run main deployment
main "$@"