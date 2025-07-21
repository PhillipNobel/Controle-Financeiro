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
        
        # Update Redis configuration
        sed -i 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
        sed -i 's/REDIS_PASSWORD=null/REDIS_PASSWORD=secret/' .env
        sed -i 's/CACHE_STORE=database/CACHE_STORE=redis/' .env
        sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/' .env
        sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/' .env
    fi
    
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
    
    # Build and start containers
    echo "🏗️  Building Docker containers..."
    
    # Try building with Redis first
    if docker-compose build --no-cache; then
        echo "✅ Build successful with Redis support"
        COMPOSE_FILE="docker-compose.yml"
        USE_REDIS=true
    else
        echo "⚠️  Build failed with Redis, trying simplified version..."
        if docker-compose -f docker-compose.simple.yml build --no-cache; then
            echo "✅ Build successful with simplified version (no Redis)"
            COMPOSE_FILE="docker-compose.simple.yml"
            USE_REDIS=false
        else
            echo "❌ Both builds failed. Please check Docker installation and logs."
            exit 1
        fi
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
    echo "   🔴 Redis: localhost:6379"
    echo ""
    echo "📋 Useful commands:"
    echo "   docker-compose logs -f          # View logs"
    echo "   docker-compose exec app bash    # Access app container"
    echo "   docker-compose down             # Stop containers"
    echo "   docker-compose up -d            # Start containers"
    echo ""
}

# Run main function
main "$@"