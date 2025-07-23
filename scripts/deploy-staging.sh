#!/bin/bash

# Staging Deployment Script
# This script deploys the application to the staging environment with comprehensive checks

set -e

# Configuration
COMPOSE_FILE="docker-compose.yml"
APP_URL="${APP_URL:-https://staging.controle-financeiro.com}"
BACKUP_DIR="/var/backups/controle-financeiro"
LOG_FILE="/var/log/controle-financeiro-deploy.log"
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

# Check prerequisites
check_prerequisites() {
    log "Checking deployment prerequisites..."
    
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
    
    # Check if git is available
    if ! command -v git >/dev/null 2>&1; then
        error "git is not installed"
        exit 1
    fi
    
    # Check if we're in the right directory
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        error "docker-compose.yml not found. Are you in the project root?"
        exit 1
    fi
    
    # Check if .env.staging exists
    if [[ ! -f ".env.staging" ]]; then
        error ".env.staging file not found"
        exit 1
    fi
    
    success "Prerequisites check passed"
}

# Create backup
create_backup() {
    log "Creating backup before deployment..."
    
    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"
    
    local backup_timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="staging_backup_${backup_timestamp}"
    local backup_path="${BACKUP_DIR}/${backup_name}"
    
    mkdir -p "$backup_path"
    
    # Backup database
    if docker ps --format "table {{.Names}}" | grep -q "controle-financeiro-mysql-staging"; then
        log "Backing up database..."
        docker exec controle-financeiro-mysql-staging mysqldump \
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
    if docker ps --format "table {{.Names}}" | grep -q "controle-financeiro-app-staging"; then
        log "Backing up application storage..."
        docker cp controle-financeiro-app-staging:/var/www/html/storage "${backup_path}/storage"
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
    "environment": "staging",
    "backup_type": "pre_deployment"
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

# Build and deploy containers
deploy_containers() {
    log "Building and deploying containers..."
    
    # Copy staging environment file
    cp .env.staging .env
    
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
}

# Run database migrations
run_migrations() {
    log "Running database migrations..."
    
    # Wait for database to be ready
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if docker exec controle-financeiro-app-staging php artisan migrate --dry-run --no-interaction >/dev/null 2>&1; then
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
    docker exec controle-financeiro-app-staging php artisan migrate --force --no-interaction
    
    success "Database migrations completed"
}

# Clear and optimize caches
optimize_application() {
    log "Optimizing application..."
    
    # Clear caches
    docker exec controle-financeiro-app-staging php artisan cache:clear
    docker exec controle-financeiro-app-staging php artisan config:clear
    docker exec controle-financeiro-app-staging php artisan route:clear
    docker exec controle-financeiro-app-staging php artisan view:clear
    
    # Optimize for production
    docker exec controle-financeiro-app-staging php artisan config:cache
    docker exec controle-financeiro-app-staging php artisan route:cache
    docker exec controle-financeiro-app-staging php artisan view:cache
    
    # Generate optimized autoloader
    docker exec controle-financeiro-app-staging composer dump-autoload --optimize
    
    success "Application optimized"
}

# Run health checks
run_health_checks() {
    log "Running post-deployment health checks..."
    
    # Wait a bit for services to stabilize
    sleep 15
    
    # Run comprehensive health check
    if docker exec controle-financeiro-app-staging php artisan health:check --detailed --no-interaction; then
        success "Health checks passed"
    else
        error "Health checks failed"
        return 1
    fi
    
    # Test web server response
    local max_attempts=10
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s -o /dev/null --max-time 30 "$APP_URL/health"; then
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
            
            # Stop current containers
            docker-compose -f "$COMPOSE_FILE" down --timeout 30
            
            # Restore docker-compose file
            cp "${backup_path}/docker-compose.yml" ./ 2>/dev/null || true
            cp "${backup_path}/.env" ./ 2>/dev/null || true
            
            # Start containers with old configuration
            docker-compose -f "$COMPOSE_FILE" up -d
            
            # Wait for containers to be ready
            sleep 30
            
            # Restore database if backup exists
            if [[ -f "${backup_path}/database.sql" ]]; then
                log "Restoring database..."
                docker exec -i controle-financeiro-mysql-staging mysql \
                    -u root -p"${DB_ROOT_PASSWORD}" \
                    "${DB_DATABASE:-controle_financeiro_staging}" < "${backup_path}/database.sql"
            fi
            
            # Restore storage if backup exists
            if [[ -d "${backup_path}/storage" ]]; then
                log "Restoring application storage..."
                docker cp "${backup_path}/storage" controle-financeiro-app-staging:/var/www/html/
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
    deploy_containers
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
        exit 0
        ;;
esac

# Run main deployment
main "$@"