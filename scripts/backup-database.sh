#!/bin/bash

# Database backup script for Docker MySQL container
set -e

# Configuration
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="controle_financeiro"
DB_USER="root"
DB_PASS="secret"
CONTAINER_NAME="controle-financeiro-mysql"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Function to create database backup
create_backup() {
    local backup_file="$BACKUP_DIR/backup_${DB_NAME}_${DATE}.sql"
    
    echo -e "${YELLOW}🗄️  Creating database backup...${NC}"
    echo "📁 Backup file: $backup_file"
    
    # Create backup using mysqldump
    if docker-compose exec -T mysql mysqldump \
        -u $DB_USER \
        -p$DB_PASS \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --add-locks \
        --extended-insert \
        $DB_NAME > "$backup_file"; then
        
        echo -e "${GREEN}✅ Backup created successfully!${NC}"
        echo "📊 Backup size: $(du -h "$backup_file" | cut -f1)"
        
        # Compress backup
        echo -e "${YELLOW}🗜️  Compressing backup...${NC}"
        gzip "$backup_file"
        echo -e "${GREEN}✅ Backup compressed: ${backup_file}.gz${NC}"
        echo "📊 Compressed size: $(du -h "${backup_file}.gz" | cut -f1)"
        
        return 0
    else
        echo -e "${RED}❌ Backup failed!${NC}"
        return 1
    fi
}

# Function to restore database from backup
restore_backup() {
    local backup_file=$1
    
    if [ -z "$backup_file" ]; then
        echo -e "${RED}❌ Please specify backup file to restore${NC}"
        echo "Usage: $0 restore <backup_file>"
        return 1
    fi
    
    if [ ! -f "$backup_file" ]; then
        echo -e "${RED}❌ Backup file not found: $backup_file${NC}"
        return 1
    fi
    
    echo -e "${YELLOW}⚠️  WARNING: This will replace the current database!${NC}"
    read -p "Are you sure you want to continue? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Restore cancelled."
        return 0
    fi
    
    echo -e "${YELLOW}🔄 Restoring database from backup...${NC}"
    
    # Check if file is compressed
    if [[ "$backup_file" == *.gz ]]; then
        echo "📦 Decompressing backup..."
        if zcat "$backup_file" | docker-compose exec -T mysql mysql -u $DB_USER -p$DB_PASS $DB_NAME; then
            echo -e "${GREEN}✅ Database restored successfully!${NC}"
            return 0
        else
            echo -e "${RED}❌ Restore failed!${NC}"
            return 1
        fi
    else
        if docker-compose exec -T mysql mysql -u $DB_USER -p$DB_PASS $DB_NAME < "$backup_file"; then
            echo -e "${GREEN}✅ Database restored successfully!${NC}"
            return 0
        else
            echo -e "${RED}❌ Restore failed!${NC}"
            return 1
        fi
    fi
}

# Function to list available backups
list_backups() {
    echo -e "${YELLOW}📋 Available backups:${NC}"
    echo ""
    
    if [ -d "$BACKUP_DIR" ] && [ "$(ls -A $BACKUP_DIR)" ]; then
        ls -lah $BACKUP_DIR/backup_*.sql* 2>/dev/null | while read line; do
            echo "  $line"
        done
    else
        echo "  No backups found in $BACKUP_DIR"
    fi
    echo ""
}

# Function to clean old backups
clean_old_backups() {
    local days=${1:-7}
    
    echo -e "${YELLOW}🧹 Cleaning backups older than $days days...${NC}"
    
    if [ -d "$BACKUP_DIR" ]; then
        find "$BACKUP_DIR" -name "backup_*.sql*" -type f -mtime +$days -delete
        echo -e "${GREEN}✅ Old backups cleaned!${NC}"
    else
        echo "No backup directory found."
    fi
}

# Function to check database connection
check_connection() {
    echo -e "${YELLOW}🔍 Checking database connection...${NC}"
    
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u $DB_USER -p$DB_PASS > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Database connection successful!${NC}"
        return 0
    else
        echo -e "${RED}❌ Database connection failed!${NC}"
        return 1
    fi
}

# Main function
main() {
    case "${1:-backup}" in
        "backup")
            check_connection && create_backup
            ;;
        "restore")
            check_connection && restore_backup "$2"
            ;;
        "list")
            list_backups
            ;;
        "clean")
            clean_old_backups "$2"
            ;;
        "help"|"-h"|"--help")
            echo "Database Backup Script"
            echo "====================="
            echo ""
            echo "Usage: $0 [command] [options]"
            echo ""
            echo "Commands:"
            echo "  backup          Create a new database backup (default)"
            echo "  restore <file>  Restore database from backup file"
            echo "  list            List available backup files"
            echo "  clean [days]    Clean backups older than N days (default: 7)"
            echo "  help            Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                                    # Create backup"
            echo "  $0 backup                             # Create backup"
            echo "  $0 restore backups/backup_db_20240120_143022.sql.gz"
            echo "  $0 list                               # List backups"
            echo "  $0 clean 30                          # Clean backups older than 30 days"
            ;;
        *)
            echo -e "${RED}❌ Unknown command: $1${NC}"
            echo "Use '$0 help' for usage information."
            exit 1
            ;;
    esac
}

# Run main function
main "$@"