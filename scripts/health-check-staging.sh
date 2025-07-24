#!/bin/bash

# Native Staging Health Check Script
# This script performs comprehensive health checks for the native staging environment

set -e

# Configuration
APP_URL="${APP_URL:-https://staging.controle-financeiro.com}"
APP_PATH="${APP_PATH:-/var/www/html/controle-financeiro}"
MYSQL_USER="${MYSQL_USER:-staging_user}"
MYSQL_DB="${MYSQL_DB:-controle_financeiro_staging}"
PHP_USER="${PHP_USER:-www-data}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

error() {
    echo -e "${RED}[✗] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[!] $1${NC}"
}

# Check system services
check_system_services() {
    log "Checking system services..."
    
    # Check OpenLiteSpeed
    if systemctl is-active --quiet lshttpd; then
        success "OpenLiteSpeed is running"
    else
        error "OpenLiteSpeed is not running"
        return 1
    fi
    
    # Check MySQL
    if systemctl is-active --quiet mysql; then
        success "MySQL is running"
    else
        error "MySQL is not running"
        return 1
    fi
    
    return 0
}

# Check PHP configuration
check_php() {
    log "Checking PHP configuration..."
    
    # Check PHP version
    local php_version=$(php -r "echo PHP_VERSION;")
    log "PHP version: $php_version"
    
    # Check required PHP extensions
    local required_extensions=("pdo" "pdo_mysql" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath")
    local missing_extensions=()
    
    for ext in "${required_extensions[@]}"; do
        if php -m | grep -q "^$ext$"; then
            success "PHP extension '$ext' is loaded"
        else
            error "PHP extension '$ext' is missing"
            missing_extensions+=("$ext")
        fi
    done
    
    if [[ ${#missing_extensions[@]} -gt 0 ]]; then
        error "Missing PHP extensions: ${missing_extensions[*]}"
        return 1
    fi
    
    return 0
}

# Check database connectivity
check_database() {
    log "Checking database connectivity..."
    
    cd "$APP_PATH"
    
    # Test database connection
    if sudo -u "$PHP_USER" php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';" 2>/dev/null | grep -q "Database connection OK"; then
        success "Database connection is working"
    else
        error "Database connection failed"
        return 1
    fi
    
    # Check database tables
    local table_count=$(sudo -u "$PHP_USER" php artisan tinker --execute="echo DB::select('SHOW TABLES')[0] ? count(DB::select('SHOW TABLES')) : 0;" 2>/dev/null | tail -1)
    if [[ "$table_count" -gt 0 ]]; then
        success "Database has $table_count tables"
    else
        warning "Database appears to be empty"
    fi
    
    return 0
}

# Check application files and permissions
check_application() {
    log "Checking application files and permissions..."
    
    # Check if application directory exists
    if [[ -d "$APP_PATH" ]]; then
        success "Application directory exists: $APP_PATH"
    else
        error "Application directory not found: $APP_PATH"
        return 1
    fi
    
    # Check key Laravel files
    local key_files=("artisan" "composer.json" ".env" "app/Http/Kernel.php")
    for file in "${key_files[@]}"; do
        if [[ -f "$APP_PATH/$file" ]]; then
            success "Key file exists: $file"
        else
            error "Key file missing: $file"
            return 1
        fi
    done
    
    # Check writable directories
    local writable_dirs=("storage" "bootstrap/cache")
    for dir in "${writable_dirs[@]}"; do
        if [[ -w "$APP_PATH/$dir" ]]; then
            success "Directory is writable: $dir"
        else
            error "Directory is not writable: $dir"
            return 1
        fi
    done
    
    # Check vendor directory
    if [[ -d "$APP_PATH/vendor" ]]; then
        success "Composer dependencies are installed"
    else
        error "Composer dependencies not found"
        return 1
    fi
    
    return 0
}

# Check Laravel application
check_laravel() {
    log "Checking Laravel application..."
    
    cd "$APP_PATH"
    
    # Check if Laravel can boot
    if sudo -u "$PHP_USER" php artisan --version >/dev/null 2>&1; then
        local laravel_version=$(sudo -u "$PHP_USER" php artisan --version)
        success "Laravel is working: $laravel_version"
    else
        error "Laravel cannot boot properly"
        return 1
    fi
    
    # Check environment
    local app_env=$(sudo -u "$PHP_USER" php artisan tinker --execute="echo config('app.env');" 2>/dev/null | tail -1)
    if [[ "$app_env" == "staging" ]]; then
        success "Application environment: $app_env"
    else
        warning "Application environment: $app_env (expected: staging)"
    fi
    
    # Check application key
    if sudo -u "$PHP_USER" php artisan tinker --execute="echo config('app.key') ? 'SET' : 'NOT SET';" 2>/dev/null | grep -q "SET"; then
        success "Application key is set"
    else
        error "Application key is not set"
        return 1
    fi
    
    return 0
}

# Check web server response
check_web_response() {
    log "Checking web server response..."
    
    # Test main URL
    if curl -f -s -o /dev/null --max-time 30 "$APP_URL"; then
        success "Main URL is responding: $APP_URL"
    else
        error "Main URL is not responding: $APP_URL"
        return 1
    fi
    
    # Test health endpoint if it exists
    if curl -f -s -o /dev/null --max-time 30 "$APP_URL/health" 2>/dev/null; then
        success "Health endpoint is responding: $APP_URL/health"
    else
        warning "Health endpoint not available: $APP_URL/health"
    fi
    
    # Check response time
    local response_time=$(curl -o /dev/null -s -w '%{time_total}' --max-time 30 "$APP_URL" 2>/dev/null || echo "timeout")
    if [[ "$response_time" != "timeout" ]]; then
        local response_ms=$(echo "$response_time * 1000" | bc 2>/dev/null || echo "unknown")
        success "Response time: ${response_ms}ms"
    else
        warning "Could not measure response time"
    fi
    
    return 0
}

# Check disk space
check_disk_space() {
    log "Checking disk space..."
    
    # Check application directory disk usage
    local app_usage=$(du -sh "$APP_PATH" 2>/dev/null | cut -f1)
    log "Application directory size: $app_usage"
    
    # Check available disk space
    local available_space=$(df -h "$APP_PATH" | awk 'NR==2 {print $4}')
    local used_percent=$(df -h "$APP_PATH" | awk 'NR==2 {print $5}' | sed 's/%//')
    
    log "Available disk space: $available_space"
    log "Disk usage: $used_percent%"
    
    if [[ "$used_percent" -lt 90 ]]; then
        success "Disk space is adequate"
    else
        warning "Disk space is running low ($used_percent% used)"
    fi
    
    return 0
}

# Check logs for errors
check_logs() {
    log "Checking application logs..."
    
    local log_file="$APP_PATH/storage/logs/laravel.log"
    
    if [[ -f "$log_file" ]]; then
        # Check for recent errors (last 100 lines)
        local error_count=$(tail -100 "$log_file" | grep -c "ERROR" || echo "0")
        local warning_count=$(tail -100 "$log_file" | grep -c "WARNING" || echo "0")
        
        if [[ "$error_count" -eq 0 ]]; then
            success "No recent errors in application log"
        else
            warning "Found $error_count recent errors in application log"
        fi
        
        if [[ "$warning_count" -eq 0 ]]; then
            success "No recent warnings in application log"
        else
            log "Found $warning_count recent warnings in application log"
        fi
    else
        warning "Application log file not found: $log_file"
    fi
    
    return 0
}

# Main health check function
main() {
    echo "=== Native Staging Health Check ==="
    echo "Timestamp: $(date)"
    echo "Target URL: $APP_URL"
    echo "Application Path: $APP_PATH"
    echo ""
    
    local failed_checks=0
    
    # Run all health checks
    check_system_services || ((failed_checks++))
    echo ""
    
    check_php || ((failed_checks++))
    echo ""
    
    check_database || ((failed_checks++))
    echo ""
    
    check_application || ((failed_checks++))
    echo ""
    
    check_laravel || ((failed_checks++))
    echo ""
    
    check_web_response || ((failed_checks++))
    echo ""
    
    check_disk_space || ((failed_checks++))
    echo ""
    
    check_logs || ((failed_checks++))
    echo ""
    
    # Summary
    if [[ $failed_checks -eq 0 ]]; then
        success "All health checks passed!"
        echo ""
        log "System is healthy and ready for production traffic"
        exit 0
    else
        error "$failed_checks health check(s) failed"
        echo ""
        log "Please review the failed checks above"
        exit 1
    fi
}

# Handle command line arguments
case "${1:-}" in
    --quick)
        log "Running quick health check (web response only)"
        check_web_response
        exit $?
        ;;
    --database)
        log "Running database check only"
        check_database
        exit $?
        ;;
    --services)
        log "Running system services check only"
        check_system_services
        exit $?
        ;;
    --help)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --quick      Quick health check (web response only)"
        echo "  --database   Database connectivity check only"
        echo "  --services   System services check only"
        echo "  --help       Show this help message"
        echo ""
        echo "Default: Run all health checks"
        echo ""
        exit 0
        ;;
esac

# Run main health check
main "$@"