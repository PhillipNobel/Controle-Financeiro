#!/bin/bash

# Health Check Script for Staging Environment
# This script performs comprehensive health checks on the staging environment

set -e

# Configuration
COMPOSE_FILE="docker-compose.yml"
APP_URL="${APP_URL:-https://staging.controle-financeiro.com}"
TIMEOUT=30

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Check if container is running
check_container() {
    local container_name=$1
    local service_name=$2
    
    if docker ps --format "table {{.Names}}" | grep -q "^$container_name$"; then
        success "$service_name container is running"
        return 0
    else
        error "$service_name container is not running"
        return 1
    fi
}

# Check container health status
check_container_health() {
    local container_name=$1
    local service_name=$2
    
    local health_status=$(docker inspect --format='{{.State.Health.Status}}' "$container_name" 2>/dev/null || echo "no-healthcheck")
    
    case $health_status in
        "healthy")
            success "$service_name container is healthy"
            return 0
            ;;
        "unhealthy")
            error "$service_name container is unhealthy"
            return 1
            ;;
        "starting")
            warning "$service_name container is still starting"
            return 1
            ;;
        "no-healthcheck")
            warning "$service_name container has no health check configured"
            return 0
            ;;
        *)
            error "$service_name container health status unknown: $health_status"
            return 1
            ;;
    esac
}

# Check application health
check_application_health() {
    log "Checking application health..."
    
    if docker exec controle-financeiro-app-staging php artisan health:check --detailed --no-interaction; then
        success "Application health check passed"
        return 0
    else
        error "Application health check failed"
        return 1
    fi
}

# Check database connectivity
check_database() {
    log "Checking database connectivity..."
    
    # Check if MySQL container is responding
    if docker exec controle-financeiro-mysql-staging mysqladmin ping -h localhost --silent; then
        success "MySQL is responding to ping"
    else
        error "MySQL is not responding to ping"
        return 1
    fi
    
    # Check database connection through application
    if docker exec controle-financeiro-app-staging php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';" 2>/dev/null | grep -q "Database connection OK"; then
        success "Application can connect to database"
        return 0
    else
        error "Application cannot connect to database"
        return 1
    fi
}

# Check Redis connectivity
check_redis() {
    log "Checking Redis connectivity..."
    
    # Check if Redis container is responding
    if docker exec controle-financeiro-redis-staging redis-cli ping 2>/dev/null | grep -q "PONG"; then
        success "Redis is responding to ping"
    else
        error "Redis is not responding to ping"
        return 1
    fi
    
    # Check Redis connection through application
    if docker exec controle-financeiro-app-staging php artisan tinker --execute="Redis::ping(); echo 'Redis connection OK';" 2>/dev/null | grep -q "Redis connection OK"; then
        success "Application can connect to Redis"
        return 0
    else
        error "Application cannot connect to Redis"
        return 1
    fi
}

# Check web server
check_web_server() {
    log "Checking web server..."
    
    # Check if nginx container is responding
    if curl -f -s -o /dev/null --max-time $TIMEOUT "$APP_URL/health"; then
        success "Web server is responding"
    else
        error "Web server is not responding"
        return 1
    fi
    
    # Check SSL certificate (if HTTPS)
    if [[ $APP_URL == https://* ]]; then
        local domain=$(echo "$APP_URL" | sed 's|https://||' | sed 's|/.*||')
        if echo | openssl s_client -servername "$domain" -connect "$domain:443" 2>/dev/null | openssl x509 -noout -dates 2>/dev/null; then
            success "SSL certificate is valid"
        else
            warning "SSL certificate check failed or certificate is invalid"
        fi
    fi
    
    return 0
}

# Check disk space
check_disk_space() {
    log "Checking disk space..."
    
    # Check host disk space
    local disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
    if [[ $disk_usage -lt 80 ]]; then
        success "Host disk usage is acceptable ($disk_usage%)"
    elif [[ $disk_usage -lt 90 ]]; then
        warning "Host disk usage is high ($disk_usage%)"
    else
        error "Host disk usage is critical ($disk_usage%)"
        return 1
    fi
    
    # Check Docker volumes
    docker system df --format "table {{.Type}}\t{{.TotalCount}}\t{{.Size}}\t{{.Reclaimable}}" | while read line; do
        if [[ $line == *"Volumes"* ]]; then
            log "Docker volumes: $line"
        fi
    done
    
    return 0
}

# Check memory usage
check_memory_usage() {
    log "Checking memory usage..."
    
    # Check host memory
    local mem_info=$(free | grep Mem)
    local total_mem=$(echo $mem_info | awk '{print $2}')
    local used_mem=$(echo $mem_info | awk '{print $3}')
    local mem_percentage=$((used_mem * 100 / total_mem))
    
    if [[ $mem_percentage -lt 80 ]]; then
        success "Host memory usage is acceptable ($mem_percentage%)"
    elif [[ $mem_percentage -lt 90 ]]; then
        warning "Host memory usage is high ($mem_percentage%)"
    else
        error "Host memory usage is critical ($mem_percentage%)"
        return 1
    fi
    
    # Check container memory usage
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" | grep controle-financeiro | while read line; do
        log "Container stats: $line"
    done
    
    return 0
}

# Check logs for errors
check_logs() {
    log "Checking recent logs for errors..."
    
    # Check application logs
    local app_errors=$(docker logs controle-financeiro-app-staging --since="1h" 2>&1 | grep -i "error\|exception\|fatal" | wc -l)
    if [[ $app_errors -eq 0 ]]; then
        success "No recent errors in application logs"
    else
        warning "Found $app_errors recent errors in application logs"
    fi
    
    # Check nginx logs
    local nginx_errors=$(docker logs controle-financeiro-nginx-staging --since="1h" 2>&1 | grep -i "error" | wc -l)
    if [[ $nginx_errors -eq 0 ]]; then
        success "No recent errors in nginx logs"
    else
        warning "Found $nginx_errors recent errors in nginx logs"
    fi
    
    return 0
}

# Main health check function
main() {
    echo "=== Staging Environment Health Check ==="
    echo "Timestamp: $(date)"
    echo "Environment: staging"
    echo "Application URL: $APP_URL"
    echo ""
    
    local overall_status=0
    
    # Container status checks
    log "=== Container Status ==="
    check_container "controle-financeiro-app-staging" "Application" || overall_status=1
    check_container "controle-financeiro-nginx-staging" "Nginx" || overall_status=1
    check_container "controle-financeiro-mysql-staging" "MySQL" || overall_status=1
    check_container "controle-financeiro-redis-staging" "Redis" || overall_status=1
    echo ""
    
    # Container health checks
    log "=== Container Health ==="
    check_container_health "controle-financeiro-app-staging" "Application" || overall_status=1
    check_container_health "controle-financeiro-nginx-staging" "Nginx" || overall_status=1
    check_container_health "controle-financeiro-mysql-staging" "MySQL" || overall_status=1
    check_container_health "controle-financeiro-redis-staging" "Redis" || overall_status=1
    echo ""
    
    # Service connectivity checks
    log "=== Service Connectivity ==="
    check_application_health || overall_status=1
    check_database || overall_status=1
    check_redis || overall_status=1
    check_web_server || overall_status=1
    echo ""
    
    # System resource checks
    log "=== System Resources ==="
    check_disk_space || overall_status=1
    check_memory_usage || overall_status=1
    echo ""
    
    # Log analysis
    log "=== Log Analysis ==="
    check_logs || overall_status=1
    echo ""
    
    # Summary
    echo "=== Health Check Summary ==="
    if [[ $overall_status -eq 0 ]]; then
        success "All health checks passed - staging environment is healthy"
    else
        error "Some health checks failed - staging environment needs attention"
    fi
    
    exit $overall_status
}

# Handle command line arguments
case "${1:-}" in
    --json)
        # TODO: Implement JSON output format
        echo "JSON output not yet implemented"
        exit 1
        ;;
    --quiet)
        # Redirect output to suppress verbose logging
        exec > /dev/null 2>&1
        ;;
esac

# Run main function
main "$@"