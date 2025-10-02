#!/bin/bash

# Setup cron jobs for Controle Financeiro production environment
# This script configures automated tasks for production

set -e

# Configuration
APP_DIR=$(pwd)
CRON_USER="${CRON_USER:-www-data}"
BACKUP_SCHEDULE="${BACKUP_SCHEDULE:-0 2 * * *}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Function to setup Laravel scheduler
setup_laravel_scheduler() {
    print_status $BLUE "Setting up Laravel scheduler..."
    
    # Create cron entry for Laravel scheduler
    CRON_ENTRY="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
    
    # Check if cron entry already exists
    if crontab -u $CRON_USER -l 2>/dev/null | grep -q "artisan schedule:run"; then
        print_status $YELLOW "Laravel scheduler cron job already exists"
    else
        # Add cron entry
        (crontab -u $CRON_USER -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -u $CRON_USER -
        print_status $GREEN "Laravel scheduler cron job added"
    fi
}

# Function to setup database backup cron
setup_backup_cron() {
    print_status $BLUE "Setting up database backup cron job..."
    
    # Create cron entry for database backup
    BACKUP_CRON_ENTRY="$BACKUP_SCHEDULE cd $APP_DIR && bash scripts/backup-database.sh backup >> storage/logs/backup.log 2>&1"
    
    # Check if backup cron entry already exists
    if crontab -u $CRON_USER -l 2>/dev/null | grep -q "backup-database.sh"; then
        print_status $YELLOW "Database backup cron job already exists"
    else
        # Add backup cron entry
        (crontab -u $CRON_USER -l 2>/dev/null; echo "$BACKUP_CRON_ENTRY") | crontab -u $CRON_USER -
        print_status $GREEN "Database backup cron job added (schedule: $BACKUP_SCHEDULE)"
    fi
}

# Function to setup log rotation
setup_log_rotation() {
    print_status $BLUE "Setting up log rotation..."
    
    # Create logrotate configuration
    LOGROTATE_CONFIG="/etc/logrotate.d/controle-financeiro"
    
    if [ ! -f "$LOGROTATE_CONFIG" ]; then
        sudo tee "$LOGROTATE_CONFIG" > /dev/null <<EOF
$APP_DIR/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 $CRON_USER $CRON_USER
    postrotate
        # Restart PHP-FPM to release log file handles
        # systemctl reload php8.2-fpm
    endscript
}

$APP_DIR/storage/logs/backup.log {
    weekly
    missingok
    rotate 12
    compress
    delaycompress
    notifempty
    create 644 $CRON_USER $CRON_USER
}

$APP_DIR/storage/logs/deployment.log {
    monthly
    missingok
    rotate 6
    compress
    delaycompress
    notifempty
    create 644 $CRON_USER $CRON_USER
}
EOF
        print_status $GREEN "Log rotation configuration created"
    else
        print_status $YELLOW "Log rotation configuration already exists"
    fi
}

# Function to setup system monitoring
setup_monitoring_cron() {
    print_status $BLUE "Setting up system monitoring..."
    
    # Create monitoring script
    MONITOR_SCRIPT="$APP_DIR/scripts/monitor-system.sh"
    
    cat > "$MONITOR_SCRIPT" <<'EOF'
#!/bin/bash

# System monitoring script for Controle Financeiro
APP_DIR=$(dirname $(dirname $(realpath $0)))
LOG_FILE="$APP_DIR/storage/logs/monitoring.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Check disk space
DISK_USAGE=$(df $APP_DIR | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    log_message "WARNING: Disk usage is ${DISK_USAGE}%"
fi

# Check memory usage
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')
if [ $MEMORY_USAGE -gt 85 ]; then
    log_message "WARNING: Memory usage is ${MEMORY_USAGE}%"
fi

# Check if application is responding
if command -v curl >/dev/null 2>&1; then
    APP_URL=$(cd $APP_DIR && php artisan tinker --execute="echo config('app.url');" 2>/dev/null | tail -n1)
    if ! curl -f -s "$APP_URL/health/simple" > /dev/null; then
        log_message "ERROR: Application health check failed"
    fi
fi

# Rotate monitoring log if it gets too large (>10MB)
if [ -f "$LOG_FILE" ] && [ $(stat -f%z "$LOG_FILE" 2>/dev/null || stat -c%s "$LOG_FILE" 2>/dev/null || echo 0) -gt 10485760 ]; then
    mv "$LOG_FILE" "${LOG_FILE}.old"
    touch "$LOG_FILE"
    log_message "Monitoring log rotated"
fi
EOF

    chmod +x "$MONITOR_SCRIPT"
    
    # Create cron entry for monitoring
    MONITOR_CRON_ENTRY="*/5 * * * * bash $MONITOR_SCRIPT"
    
    # Check if monitoring cron entry already exists
    if crontab -u $CRON_USER -l 2>/dev/null | grep -q "monitor-system.sh"; then
        print_status $YELLOW "System monitoring cron job already exists"
    else
        # Add monitoring cron entry
        (crontab -u $CRON_USER -l 2>/dev/null; echo "$MONITOR_CRON_ENTRY") | crontab -u $CRON_USER -
        print_status $GREEN "System monitoring cron job added (every 5 minutes)"
    fi
}

# Function to display current cron jobs
show_cron_jobs() {
    print_status $BLUE "Current cron jobs for user $CRON_USER:"
    crontab -u $CRON_USER -l 2>/dev/null || echo "No cron jobs found"
}

# Function to remove all cron jobs
remove_cron_jobs() {
    print_status $BLUE "Removing Controle Financeiro cron jobs..."
    
    # Get current crontab and remove our entries
    crontab -u $CRON_USER -l 2>/dev/null | grep -v "artisan schedule:run" | grep -v "backup-database.sh" | grep -v "monitor-system.sh" | crontab -u $CRON_USER -
    
    print_status $GREEN "Cron jobs removed"
}

# Main script logic
case "${1:-setup}" in
    "setup")
        print_status $BLUE "Setting up production cron jobs..."
        setup_laravel_scheduler
        setup_backup_cron
        setup_log_rotation
        setup_monitoring_cron
        show_cron_jobs
        print_status $GREEN "Production cron jobs setup completed!"
        ;;
    "show")
        show_cron_jobs
        ;;
    "remove")
        remove_cron_jobs
        ;;
    "scheduler")
        setup_laravel_scheduler
        ;;
    "backup")
        setup_backup_cron
        ;;
    "monitoring")
        setup_monitoring_cron
        ;;
    "logrotate")
        setup_log_rotation
        ;;
    *)
        echo "Usage: $0 {setup|show|remove|scheduler|backup|monitoring|logrotate}"
        echo "  setup      - Setup all production cron jobs"
        echo "  show       - Show current cron jobs"
        echo "  remove     - Remove all Controle Financeiro cron jobs"
        echo "  scheduler  - Setup Laravel scheduler only"
        echo "  backup     - Setup database backup cron only"
        echo "  monitoring - Setup system monitoring cron only"
        echo "  logrotate  - Setup log rotation only"
        exit 1
        ;;
esac