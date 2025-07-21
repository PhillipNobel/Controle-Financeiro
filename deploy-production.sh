#!/bin/bash

# Production Deployment Script for Controle Financeiro Simples
# This script optimizes Laravel for production environment

echo "🚀 Starting production deployment..."

# Check if we're in production environment
if [ "$APP_ENV" != "production" ]; then
    echo "⚠️  Warning: APP_ENV is not set to 'production'"
    echo "Please ensure your .env file has APP_ENV=production"
fi

# Clear and optimize caches
echo "📦 Clearing and optimizing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize Composer autoloader
echo "🔧 Optimizing Composer autoloader..."
composer install --optimize-autoloader --no-dev

# Set proper permissions
echo "🔐 Setting proper file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Create symbolic link for storage (if not exists)
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage symbolic link..."
    php artisan storage:link
fi

# Run database migrations (with confirmation)
echo "🗄️  Database migrations..."
read -p "Do you want to run database migrations? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
fi

# Seed database (with confirmation)
read -p "Do you want to seed the database with demo data? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
fi

# Queue work setup reminder
echo "📋 Production checklist:"
echo "✅ Caches optimized"
echo "✅ Autoloader optimized"
echo "✅ File permissions set"
echo ""
echo "🔔 Don't forget to:"
echo "   - Set up queue workers: php artisan queue:work"
echo "   - Configure cron job for scheduled tasks"
echo "   - Set up SSL certificate"
echo "   - Configure web server (Nginx/Apache)"
echo "   - Set up monitoring and logging"
echo "   - Configure backup system"
echo ""
echo "🎉 Production deployment completed!"