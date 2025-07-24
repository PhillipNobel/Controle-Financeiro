#!/bin/bash

# Subdirectory Deployment Script
# This script deploys the application to run in a subdirectory with port mapping
# Target: https://dev.nexxtecnologia.com.br/Controle-Financeiro

set -e

# Configuration
COMPOSE_FILE="docker-compose.subdirectory.yml"
APP_URL="${APP_URL:-https://dev.nexxtecnologia.com.br/Controle-Financeiro}"
BACKUP_DIR="/var/backups/controle-financeiro-subdirectory"
LOG_FILE="/var/log/controle-financeiro-subdirectory-deploy.log"
TIMEOUT=300
NGINX_PORT=8080

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

# Check prerequisites
check_prerequisites() {
    log "Checking deployment prerequisites for subdirectory setup..."
    
    # Check if Docker is running
    if ! docker info >/dev/null 2>&1; then
        error "Docker is not running or not accessible"
        exit 1
    fi
    
    # Check if docker-compose is available
    if ! command -v docker-compose >/dev/null 2>&1; then
        error "docker-compose is not installed"
        exit 1
    fi
    
    # Check if we're in the right directory
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        error "$COMPOSE_FILE not found. Are you in the project root?"
        exit 1
    fi
    
    # Check if .env.subdirectory exists
    if [[ ! -f ".env.subdirectory" ]]; then
        error ".env.subdirectory file not found"
        exit 1
    fi
    
    # Check if port 8080 is available
    if netstat -tuln | grep -q ":$NGINX_PORT "; then
        warning "Port $NGINX_PORT is already in use. This might cause conflicts."
        log "You may need to stop other services using port $NGINX_PORT"
    fi
    
    success "Prerequisites check passed"
}

# Create backup
create_backup() {
    log "Creating backup before deployment..."
    
    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="subdirectory_backup_${backup_timestamp}"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    mkdir -p "$backup_path"
    
    # Backup database
    if docker ps --format "table {{.Names}}" | grep -q "controle-financeiro-mysql-subdirectory"; then
        log "Backing up database..."
        docker exec controle-financeiro-mysql-subdirectory mysqldump \
            -u root -p"${DB_ROOT_PASSWORD}" \
            --single-transaction \
            --routines \
            --triggers \
            "${DB_DATABASE:-controle_financeiro_staging}" > "${backup_path}/database.sql"
        success "Database backup created"
    else
        warning "MySQL container not running, skipping database backup"
    fi
    
    # Backup application files (storage, uploads, etc.)
    if docker ps --format "table {{.Names}}" | grep -q "controle-financeiro-app-subdirectory"; then
        log "Backing up application storage..."
        docker cp controle-financeiro-app-subdirectory:/var/www/html/storage "${backup_path}/storage"
        success "Application storage backup created"
    else
        warning "Application container not running, skipping storage backup"
    fi
    
    # Backup current docker-compose and environment files
    cp "$COMPOSE_FILE" "${backup_path}/"
    cp ".env" "${backup_path}/" 2>/dev/null || true
    
    # Create backup metadata
    cat > "${backup_path}/metadata.json" << EOF
{
    "timestamp": "$(date -Iseconds)",
    "git_commit": "$(git rev-parse HEAD)",
    "git_branch": "$(git rev-parse --abbrev-ref HEAD)",
    "environment": "subdirectory",
    "backup_type": "pre_deployment",
    "nginx_port": "$NGINX_PORT",
    "app_url": "$APP_URL"
}
EOF
    
    # Store backup path for potential rollback
    echo "$backup_path" > /tmp/last_backup_path_subdirectory
    
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

# Build and deploy containers
deploy_containers() {
    log "Building and deploying containers for subdirectory setup..."
    
    # Copy subdirectory environment file
    cp .env.subdirectory .env
    
    # Build images
    log "Building Docker images..."
    docker-compose -f "$COMPOSE_FILE" build --no-cache
    
    # Stop existing containers gracefully
    log "Stopping existing containers..."
    docker-compose -f "$COMPOSE_FILE" down --timeout 30
    
    # Start new containers
    log "Starting new containers..."
    docker-compose -f "$COMPOSE_FILE" up -d
    
    # Wait for containers to be ready
    log "Waiting for containers to be ready..."
    sleep 30
    
    success "Containers deployed successfully"
    log "Application will be available at: $APP_URL"
    log "Direct nginx access at: http://localhost:$NGINX_PORT/Controle-Financeiro"
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    # Wait for database to be ready
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if docker exec controle-financeiro-app-subdirectory php artisan migrate --dry-run --no-interaction >/dev/null 2>&1; then
            break
        fi
        
        log "Waiting for database to be ready (attempt $attempt/$max_attempts)..."
        sleep 10
        ((attempt++))
    done
    
    if [[ $attempt -gt $max_attempts ]]; then
        error "Database is not ready after $max_attempts attempts"
        exit 1
    fi
    
    # Run migrations
    docker exec controle-financeiro-app-subdirectory php artisan migrate --force --no-interaction
    
    success "Database migrations completed"
}

# Clear and optimize caches
optimize_application() {
    log "Optimizing application..."
    
    # Clear caches
    docker exec controle-financeiro-app-subdirectory php artisan cache:clear
    docker exec controle-financeiro-app-subdirectory php artisan config:clear
    docker exec controle-financeiro-app-subdirectory php artisan route:clear
    docker exec controle-financeiro-app-subdirectory php artisan view:clear
    
    # Optimize for production
    docker exec controle-financeiro-app-subdirectory php artisan config:cache
    docker exec controle-financeiro-app-subdirectory php artisan route:cache
    docker exec controle-financeiro-app-subdirectory php artisan view:cache
    
    # Generate optimized autoloader
    docker exec controle-financeiro-app-subdirectory composer dump-autoload --optimize
    
    success "Application optimized"
}

# Run health checks
run_health_checks() {
    log "Running post-deployment health checks..."
    
    # Wait a bit for services to stabilize
    sleep 15
    
    # Run comprehensive health check
    if docker exec controle-financeiro-app-subdirectory php artisan health:check --detailed --no-interaction; then
        success "Health checks passed"
    else
        error "Health checks failed"
        return 1
    fi
    
    # Test nginx response on mapped port
    local max_attempts=10
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s -o /dev/null --max-time 30 "http://localhost:$NGINX_PORT/Controle-Financeiro/health"; then
            success "Nginx is responding on port $NGINX_PORT"
            break
        fi
        
        log "Testing nginx response (attempt $attempt/$max_attempts)..."
        sleep 10
        ((attempt++))
    done
    
    if [[ $attempt -gt $max_attempts ]]; then
        error "Nginx is not responding after $max_attempts attempts"
        return 1
    fi
    
    return 0
}

# Configure reverse proxy instructions
show_reverse_proxy_config() {
    log "=== REVERSE PROXY CONFIGURATION ==="
    echo ""
    echo "To make the application accessible at $APP_URL,"
    echo "configure your main web server (Apache/Nginx) with:"
    echo ""
    echo "For Nginx:"
    echo "location /Controle-Financeiro/ {"
    echo "    proxy_pass http://localhost:$NGINX_PORT/Controle-Financeiro/;"
    echo "    proxy_set_header Host \$host;"
    echo "    proxy_set_header X-Real-IP \$remote_addr;"
    echo "    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;"
    echo "    proxy_set_header X-Forwarded-Proto \$scheme;"
    echo "}"
    echo ""
    echo "For Apache:"
    echo "ProxyPass /Controle-Financeiro/ http://localhost:$NGINX_PORT/Controle-Financeiro/"
    echo "ProxyPassReverse /Controle-Financeiro/ http://localhost:$NGINX_PORT/Controle-Financeiro/"
    echo "ProxyPreserveHost On"
    echo ""
    success "Reverse proxy configuration displayed"
}

# Rollback deployment
rollback_deployment() {
    log "Rolling back deployment..."
    
    if [[ -f "/tmp/last_backup_path_subdirectory" ]]; then
        local backup_path=$(cat /tmp/last_backup_path_subdirectory)
        
        if [[ -d "$backup_path" ]]; then
            log "Restoring from backup: $backup_path"
            
            # Stop current containers
            docker-compose -f "$COMPOSE_FILE" down --timeout 30
            
            # Restore docker-compose file
            cp "${backup_path}/$COMPOSE_FILE" ./ 2>/dev/null || true
            cp "${backup_path}/.env" ./ 2>/dev/null || true
            
            # Start containers with old configuration
            docker-compose -f "$COMPOSE_FILE" up -d
            
            # Wait for containers to be ready
            sleep 30
            
            # Restore database if backup exists
            if [[ -f "${backup_path}/database.sql" ]]; then
                log "Restoring database..."
                docker exec -i controle-financeiro-mysql-subdirectory mysql \
                    -u root -p"${DB_ROOT_PASSWORD}" \
                    "${DB_DATABASE:-controle_financeiro_staging}" < "${backup_path}/database.sql"
            fi
            
            # Restore storage if backup exists
            if [[ -d "${backup_path}/storage" ]]; then
                log "Restoring application storage..."
                docker cp "${backup_path}/storage" controle-financeiro-app-subdirectory:/var/www/html/
            fi
            
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
        log "Direct access at: http://localhost:$NGINX_PORT/Controle-Financeiro"
    else
        error "Deployment failed: $message"
    fi
}

# Main deployment function
main() {
    echo "=== Subdirectory Deployment Started ==="
    echo "Timestamp: $(date)"
    echo "Environment: subdirectory"
    echo "Target URL: $APP_URL"
    echo "Nginx Port: $NGINX_PORT"
    echo ""
    
    # Create log file
    mkdir -p "$(dirname "$LOG_FILE")"
    touch "$LOG_FILE"
    
    # Run deployment steps
    check_prerequisites
    create_backup
    pull_code
    deploy_containers
    run_migrations
    optimize_application
    
    if run_health_checks; then
        show_reverse_proxy_config
        send_notification "success" "Deployment completed successfully"
        
        # Clean up old backups (keep last 5)
        find "$BACKUP_DIR" -name "subdirectory_backup_*" -type d | sort -r | tail -n +6 | xargs rm -rf 2>/dev/null || true
        
        success "Deployment completed successfully!"
        log "Application is available at: $APP_URL"
        log "Configure your reverse proxy as shown above"
    else
        send_notification "failed" "Health checks failed"
        error "Deployment failed due to health check failures"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
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
        echo "  --dry-run      Perform a dry run without actual deployment"
        echo "  --rollback     Rollback to the previous deployment"
        echo "  --health-check Run health checks only"
        echo "  --help         Show this help message"
        echo ""
        echo "This script deploys the application to run in a subdirectory"
        echo "Target: $APP_URL"
        echo "Nginx Port: $NGINX_PORT"
        echo ""
        exit 0
        ;;
esac

# Run main deployment
main "$@"