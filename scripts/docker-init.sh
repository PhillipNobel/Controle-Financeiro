#!/bin/bash

# Docker initialization script for Laravel application
set -e

echo "🚀 Starting Docker initialization..."

# Function to wait for service
wait_for_service() {
    local service=$1
    local max_attempts=30
    local attempt=1
    
    echo "⏳ Waiting for $service to be ready..."
    
    while [ $attempt -le $max_attempts ]; do
        if docker-compose exec -T $service echo "Service is ready" > /dev/null 2>&1; then
            echo "✅ $service is ready!"
            return 0
        fi
        
        echo "   Attempt $attempt/$max_attempts - $service not ready yet..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    echo "❌ $service failed to start after $max_attempts attempts"
    return 1
}

# Function to run Laravel setup
setup_laravel() {
    echo "🔧 Setting up Laravel application..."
    
    # Copy environment file if it doesn't exist
    if [ ! -f .env ]; then
        echo "📋 Copying .env.example to .env..."
        cp .env.example .env
        
        # Update database configuration for Docker
        sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
        sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=mysql/' .env
        sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env
        sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=controle_financeiro/' .env
        sed -i 's/# DB_USERNAME=root/DB_USERNAME=root/' .env
        sed -i 's/# DB_PASSWORD=/DB_PASSWORD=secret/' .env
    fi
    
    # Update Redis/Cache configuration based on user choice
    update_env_for_redis $USE_REDIS
    
    # Generate application key
    echo "🔑 Generating application key..."
    docker-compose exec -T app php artisan key:generate --force
    
    # Wait for database
    echo "⏳ Waiting for database connection..."
    docker-compose exec -T app php artisan tinker --execute="DB::connection()->getPdo();" || {
        echo "❌ Database connection failed"
        return 1
    }
    
    # Run migrations
    echo "🗄️  Running database migrations..."
    docker-compose exec -T app php artisan migrate --force
    
    # Seed database
    echo "🌱 Seeding database..."
    docker-compose exec -T app php artisan db:seed --force
    
    # Clear and cache configurations
    echo "🧹 Clearing and caching configurations..."
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan cache:clear
    docker-compose exec -T app php artisan route:clear
    docker-compose exec -T app php artisan view:clear
    
    # Create storage link
    echo "🔗 Creating storage link..."
    docker-compose exec -T app php artisan storage:link
    
    # Set permissions
    echo "🔒 Setting permissions..."
    docker-compose exec -T app chown -R www-data:www-data /var/www/html/storage
    docker-compose exec -T app chown -R www-data:www-data /var/www/html/bootstrap/cache
    docker-compose exec -T app chmod -R 775 /var/www/html/storage
    docker-compose exec -T app chmod -R 775 /var/www/html/bootstrap/cache
}

# Function to prompt user for Redis choice
prompt_redis_choice() {
    echo ""
    echo "🔴 Redis Configuration"
    echo "Redis provides caching, session storage, and queue management for better performance."
    echo ""
    echo "Choose your setup:"
    echo "1) Full setup with Redis (recommended for production)"
    echo "2) Simple setup without Redis (lighter, good for development)"
    echo ""
    
    while true; do
        read -p "Enter your choice (1 or 2): " choice
        case $choice in
            1)
                echo "✅ Selected: Full setup with Redis"
                return 0
                ;;
            2)
                echo "✅ Selected: Simple setup without Redis"
                return 1
                ;;
            *)
                echo "❌ Invalid choice. Please enter 1 or 2."
                ;;
        esac
    done
}

# Function to update .env for Redis configuration
update_env_for_redis() {
    local use_redis=$1
    
    if [ "$use_redis" = true ]; then
        # Configure for Redis
        sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
        sed -i 's/REDIS_PASSWORD=null/REDIS_PASSWORD=secret/' .env
        sed -i 's/CACHE_STORE=database/CACHE_STORE=redis/' .env
        sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/' .env
        sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/' .env
        echo "🔴 Redis configuration applied to .env"
    else
        # Configure for database-based caching
        sed -i 's/CACHE_STORE=redis/CACHE_STORE=database/' .env
        sed -i 's/SESSION_DRIVER=redis/SESSION_DRIVER=database/' .env
        sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=database/' .env
        echo "💾 Database-based configuration applied to .env"
    fi
}

# Main execution
main() {
    # Check if Docker and Docker Compose are installed
    if ! command -v docker &> /dev/null; then
        echo "❌ Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        echo "❌ Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Prompt user for Redis choice
    if prompt_redis_choice; then
        USE_REDIS=true
        COMPOSE_FILE="docker-compose.yml"
        echo "🏗️  Building Docker containers with Redis support..."
    else
        USE_REDIS=false
        COMPOSE_FILE="docker-compose.simple.yml"
        echo "🏗️  Building Docker containers without Redis..."
    fi
    
    # Build containers
    if docker-compose -f $COMPOSE_FILE build --no-cache; then
        echo "✅ Build successful!"
    else
        echo "❌ Build failed. Please check Docker installation and logs."
        exit 1
    fi
    
    echo "🚀 Starting Docker containers..."
    docker-compose -f $COMPOSE_FILE up -d
    
    # Wait for services
    wait_for_service mysql
    if [ "$USE_REDIS" = true ]; then
        wait_for_service redis
    fi
    wait_for_service app
    
    # Setup Laravel
    setup_laravel
    
    echo ""
    echo "🎉 Docker initialization completed successfully!"
    echo ""
    echo "📋 Service URLs:"
    echo "   🌐 Application: http://localhost:8080"
    echo "   🗄️  MySQL: localhost:3306"
    if [ "$USE_REDIS" = true ]; then
        echo "   🔴 Redis: localhost:6379"
    fi
    echo ""
    echo "📋 Configuration:"
    if [ "$USE_REDIS" = true ]; then
        echo "   ✅ Redis enabled for caching, sessions, and queues"
    else
        echo "   💾 Database-based caching, sessions, and queues"
    fi
    echo ""
    echo "📋 Useful commands:"
    echo "   docker-compose -f $COMPOSE_FILE logs -f          # View logs"
    echo "   docker-compose -f $COMPOSE_FILE exec app bash    # Access app container"
    echo "   docker-compose -f $COMPOSE_FILE down             # Stop containers"
    echo "   docker-compose -f $COMPOSE_FILE up -d            # Start containers"
    echo ""
}

# Run main function
main "$@"