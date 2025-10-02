#!/bin/bash

# Health check script for Docker containers
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to check service health
check_service() {
    local service=$1
    local url=$2
    local expected_response=$3
    
    echo -n "🔍 Checking $service... "
    
    if [ -n "$url" ]; then
        # HTTP health check
        response=$(curl -s -o /dev/null -w "%{http_code}" "$url" 2>/dev/null || echo "000")
        if [ "$response" = "$expected_response" ]; then
            echo -e "${GREEN}✅ Healthy${NC}"
            return 0
        else
            echo -e "${RED}❌ Unhealthy (HTTP $response)${NC}"
            return 1
        fi
    else
        # Docker health check
        health=$(docker-compose ps -q $service | xargs docker inspect --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
        if [ "$health" = "healthy" ]; then
            echo -e "${GREEN}✅ Healthy${NC}"
            return 0
        else
            echo -e "${RED}❌ Unhealthy ($health)${NC}"
            return 1
        fi
    fi
}

# Function to check container status
check_container_status() {
    local service=$1
    
    echo -n "📦 Checking $service container... "
    
    status=$(docker-compose ps -q $service | xargs docker inspect --format='{{.State.Status}}' 2>/dev/null || echo "not found")
    
    case $status in
        "running")
            echo -e "${GREEN}✅ Running${NC}"
            return 0
            ;;
        "exited")
            echo -e "${RED}❌ Exited${NC}"
            return 1
            ;;
        "not found")
            echo -e "${RED}❌ Not found${NC}"
            return 1
            ;;
        *)
            echo -e "${YELLOW}⚠️  $status${NC}"
            return 1
            ;;
    esac
}

# Function to check database connectivity
check_database() {
    echo -n "🗄️  Checking database connectivity... "
    
    if docker-compose exec -T mysql mysqladmin ping -h localhost -u root -psecret > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Connected${NC}"
        return 0
    else
        echo -e "${RED}❌ Connection failed${NC}"
        return 1
    fi
}

# Function to check Redis connectivity
check_redis() {
    echo -n "🔴 Checking Redis connectivity... "
    
    if docker-compose exec -T redis redis-cli -a secret ping > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Connected${NC}"
        return 0
    else
        echo -e "${RED}❌ Connection failed${NC}"
        return 1
    fi
}

# Function to check Laravel application
check_laravel() {
    echo -n "🐘 Checking Laravel application... "
    
    if docker-compose exec -T app php artisan tinker --execute="echo 'OK';" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Working${NC}"
        return 0
    else
        echo -e "${RED}❌ Not working${NC}"
        return 1
    fi
}

# Function to show resource usage
show_resource_usage() {
    echo ""
    echo -e "${BLUE}📊 Resource Usage:${NC}"
    echo ""
    
    # Get container stats
    docker-compose ps --format "table {{.Name}}\t{{.Status}}" | head -1
    docker-compose ps --format "table {{.Name}}\t{{.Status}}" | tail -n +2 | while read line; do
        container_name=$(echo $line | awk '{print $1}')
        if [ -n "$container_name" ]; then
            stats=$(docker stats --no-stream --format "table {{.CPUPerc}}\t{{.MemUsage}}" $container_name 2>/dev/null | tail -1)
            echo "$line	$stats"
        fi
    done
}

# Function to show logs summary
show_logs_summary() {
    echo ""
    echo -e "${BLUE}📋 Recent Logs Summary:${NC}"
    echo ""
    
    services=("app" "nginx" "mysql" "redis" "queue")
    
    for service in "${services[@]}"; do
        echo -e "${YELLOW}--- $service ---${NC}"
        docker-compose logs --tail=3 $service 2>/dev/null | tail -3 || echo "No logs available"
        echo ""
    done
}

# Main health check function
main() {
    echo -e "${BLUE}🏥 Docker Health Check Report${NC}"
    echo "=================================="
    echo ""
    
    # Check if Docker Compose is running
    if ! docker-compose ps > /dev/null 2>&1; then
        echo -e "${RED}❌ Docker Compose is not running${NC}"
        exit 1
    fi
    
    # Container status checks
    echo -e "${BLUE}📦 Container Status:${NC}"
    check_container_status "app"
    check_container_status "nginx"
    check_container_status "mysql"
    check_container_status "redis"
    check_container_status "queue"
    check_container_status "scheduler"
    echo ""
    
    # Service health checks
    echo -e "${BLUE}🔍 Service Health:${NC}"
    check_service "nginx" "http://localhost:8080/health" "200"
    check_database
    check_redis
    check_laravel
    echo ""
    
    # Application-specific checks
    echo -e "${BLUE}🐘 Application Checks:${NC}"
    echo -n "📋 Database migrations... "
    if docker-compose exec -T app php artisan migrate:status > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Up to date${NC}"
    else
        echo -e "${RED}❌ Issues found${NC}"
    fi
    
    echo -n "🔗 Storage link... "
    if docker-compose exec -T app test -L public/storage; then
        echo -e "${GREEN}✅ Linked${NC}"
    else
        echo -e "${YELLOW}⚠️  Not linked${NC}"
    fi
    
    echo -n "🔒 File permissions... "
    if docker-compose exec -T app test -w storage && docker-compose exec -T app test -w bootstrap/cache; then
        echo -e "${GREEN}✅ Correct${NC}"
    else
        echo -e "${RED}❌ Issues found${NC}"
    fi
    
    # Show resource usage
    show_resource_usage
    
    # Show logs summary if requested
    if [ "$1" = "--logs" ]; then
        show_logs_summary
    fi
    
    echo ""
    echo -e "${GREEN}🎉 Health check completed!${NC}"
    echo ""
    echo "💡 Tips:"
    echo "   • Run with --logs to see recent log entries"
    echo "   • Use 'docker-compose logs -f [service]' for live logs"
    echo "   • Use 'docker-compose restart [service]' to restart a service"
}

# Run main function
main "$@"