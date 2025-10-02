#!/bin/bash

# Production deployment script with zero-downtime deployment
set -e

# Configuration
PROJECT_NAME="controle-financeiro"
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Function to log messages
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

# Function to check prerequisites
check_prerequisites() {
    log "🔍 Checking prerequisites..."
    
    # Check if required files exist
    required_files=(".env.production" "docker-compose.prod.yml")
    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            echo -e "${RED}❌ Required file not found: $file${NC}"
            exit 1
        fi
    done
    
    # Check if Docker is running
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}❌ Docker is not running${NC}"
        exit 1
    fi
    
    # Check if production environment variables are set
    if [ ! -f ".env.production" ]; then
        echo -e "${RED}❌ Production environment file (.env.production) not found${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Prerequisites check passed${NC}"
}

# Function to create backup before deployment
create_backup() {
    log "🗄️  Creating backup before deployment..."
    
    mkdir -p $BACKUP_DIR
    
    # Check if production containers are running
    if docker-compose -f docker-compose.prod.yml ps mysql > /dev/null 2>&1; then
        # Create database backup
        backup_file="$BACKUP_DIR/pre_deploy_backup_${DATE}.sql"
        
        if docker-compose -f docker-compose.prod.yml exec -T mysql mysqladump \
            -u ${DB_USERNAME} \
            -p${DB_PASSWORD} \
            --single-transaction \
            --routines \
            --triggers \
            controle_financeiro > "$backup_file"; then
            
            gzip "$backup_file"
            echo -e "${GREEN}✅ Backup created: ${backup_file}.gz${NC}"
        else
            echo -e "${YELLOW}⚠️  Could not create backup (database might not be running)${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️  Production containers not running, skipping backup${NC}"
    fi
}

# Function to build new images
build_images() {
    log "🏗️  Building production images..."
    
    # Build with no cache to ensure latest code
    docker-compose -f docker-compose.prod.yml build --no-cache --parallel
    
    echo -e "${GREEN}✅ Images built successfully${NC}"
}

# Function to perform zero-downtime deployment
deploy() {
    log "🚀 Starting zero-downtime deployment..."
    
    # Copy production environment
    cp .env.production .env
    
    # Start new containers with temporary names
    log "📦 Starting new containers..."
    
    # Scale up new containers alongside old ones
    docker-compose -f docker-compose.prod.yml up -d --scale app=2 --scale queue=2
    
    # Wait for new containers to be healthy
    log "⏳ Waiting for new containers to be healthy..."
    sleep 30
    
    # Check health of new containers
    max_attempts=30
    attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker-compose -f docker-compose.prod.yml exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
            echo -e "${GREEN}✅ New containers are healthy${NC}"
            break
        fi
        
        echo "   Attempt $attempt/$max_attempts - waiting for containers..."
        sleep 10
        attempt=$((attempt + 1))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        echo -e "${RED}❌ New containers failed to become healthy${NC}"
        rollback
        exit 1
    fi
    
    # Run database migrations
    log "🗄️  Running database migrations..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
    
    # Clear caches
    log "🧹 Clearing application caches..."
    docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache
    
    # Scale down to normal number of containers
    log "📉 Scaling down to normal container count..."
    docker-compose -f docker-compose.prod.yml up -d --scale app=1 --scale queue=1
    
    # Remove old containers and images
    log "🧹 Cleaning up old containers and images..."
    docker system prune -f
    
    echo -e "${GREEN}✅ Deployment completed successfully${NC}"
}

# Function to rollback deployment
rollback() {
    log "🔄 Rolling back deployment..."
    
    # Find the most recent backup
    latest_backup=$(ls -t $BACKUP_DIR/pre_deploy_backup_*.sql.gz 2>/dev/null | head -1)
    
    if [ -n "$latest_backup" ]; then
        echo -e "${YELLOW}📦 Restoring from backup: $latest_backup${NC}"
        
        # Restore database
        zcat "$latest_backup" | docker-compose -f docker-compose.prod.yml exec -T mysql mysql \
            -u ${DB_USERNAME} \
            -p${DB_PASSWORD} \
            controle_financeiro
        
        echo -e "${GREEN}✅ Database restored from backup${NC}"
    else
        echo -e "${YELLOW}⚠️  No backup found for rollback${NC}"
    fi
    
    # Restart containers with previous image
    docker-compose -f docker-compose.prod.yml down
    docker-compose -f docker-compose.prod.yml up -d
    
    echo -e "${GREEN}✅ Rollback completed${NC}"
}

# Function to check deployment health
check_health() {
    log "🏥 Checking deployment health..."
    
    # Wait a bit for services to stabilize
    sleep 10
    
    # Check application health
    if curl -f http://localhost/health > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Application is responding${NC}"
    else
        echo -e "${RED}❌ Application health check failed${NC}"
        return 1
    fi
    
    # Check database connectivity
    if docker-compose -f docker-compose.prod.yml exec -T mysql mysqladmin ping -h localhost -u ${DB_USERNAME} -p${DB_PASSWORD} > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Database is accessible${NC}"
    else
        echo -e "${RED}❌ Database health check failed${NC}"
        return 1
    fi
    
    # Check Redis connectivity
    if docker-compose -f docker-compose.prod.yml exec -T redis redis-cli -a ${REDIS_PASSWORD} ping > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Redis is accessible${NC}"
    else
        echo -e "${RED}❌ Redis health check failed${NC}"
        return 1
    fi
    
    echo -e "${GREEN}🎉 All health checks passed!${NC}"
    return 0
}

# Function to show deployment status
show_status() {
    log "📊 Deployment Status:"
    echo ""
    
    # Show container status
    docker-compose -f docker-compose.prod.yml ps
    echo ""
    
    # Show resource usage
    echo -e "${BLUE}Resource Usage:${NC}"
    docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" $(docker-compose -f docker-compose.prod.yml ps -q)
    echo ""
    
    # Show recent logs
    echo -e "${BLUE}Recent Logs (last 5 lines):${NC}"
    docker-compose -f docker-compose.prod.yml logs --tail=5
}

# Main function
main() {
    # Load environment variables
    if [ -f ".env.production" ]; then
        export $(cat .env.production | grep -v '^#' | xargs)
    fi
    
    case "${1:-deploy}" in
        "deploy")
            check_prerequisites
            create_backup
            build_images
            deploy
            if check_health; then
                log "🎉 Deployment completed successfully!"
                show_status
            else
                log "❌ Health checks failed, consider rollback"
                exit 1
            fi
            ;;
        "rollback")
            rollback
            ;;
        "status")
            show_status
            ;;
        "health")
            check_health
            ;;
        "backup")
            create_backup
            ;;
        "help"|"-h"|"--help")
            echo "Production Deployment Script"
            echo "==========================="
            echo ""
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  deploy     Perform zero-downtime deployment (default)"
            echo "  rollback   Rollback to previous version"
            echo "  status     Show deployment status"
            echo "  health     Check deployment health"
            echo "  backup     Create backup only"
            echo "  help       Show this help message"
            echo ""
            echo "Prerequisites:"
            echo "  • .env.production file with production settings"
            echo "  • docker-compose.prod.yml file"
            echo "  • Docker and Docker Compose installed"
            echo ""
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