#!/bin/bash

# VPS Setup Script for Controle Financeiro
# This script sets up the VPS environment and deploys the application
# Run this script after cloning the repository on your VPS

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Get user input
get_user_input() {
    echo "=== VPS Setup for Controle Financeiro ==="
    echo ""
    
    # Domain configuration
    read -p "Enter your domain (e.g., staging.controle-financeiro.com): " DOMAIN
    if [[ -z "$DOMAIN" ]]; then
        error "Domain is required"
        exit 1
    fi
    
    # Database configuration
    read -p "Enter MySQL database name [controle_financeiro_staging]: " DB_NAME
    DB_NAME=${DB_NAME:-controle_financeiro_staging}
    
    read -p "Enter MySQL username [staging_user]: " DB_USER
    DB_USER=${DB_USER:-staging_user}
    
    read -s -p "Enter MySQL password (will be hidden): " DB_PASSWORD
    echo ""
    if [[ -z "$DB_PASSWORD" ]]; then
        error "Database password is required"
        exit 1
    fi
    
    # Email for SSL certificate
    read -p "Enter email for SSL certificate: " SSL_EMAIL
    if [[ -z "$SSL_EMAIL" ]]; then
        error "Email is required for SSL certificate"
        exit 1
    fi
    
    # Application path
    read -p "Enter application path [/var/www/html/controle-financeiro]: " APP_PATH
    APP_PATH=${APP_PATH:-/var/www/html/controle-financeiro}
    
    echo ""
    log "Configuration:"
    log "Domain: $DOMAIN"
    log "Database: $DB_NAME"
    log "Database User: $DB_USER"
    log "SSL Email: $SSL_EMAIL"
    log "App Path: $APP_PATH"
    echo ""
    
    read -p "Continue with this configuration? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        log "Setup cancelled"
        exit 0
    fi
}

# Setup database
setup_database() {
    log "Setting up MySQL database..."
    
    # Check if MySQL is running
    if ! systemctl is-active --quiet mysql && ! systemctl is-active --quiet mysqld; then
        log "Starting MySQL..."
        sudo systemctl start mysql || sudo systemctl start mysqld
        sudo systemctl enable mysql || sudo systemctl enable mysqld
    fi
    
    # Create database and user
    log "Creating database and user..."
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
    sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
    success "Database setup completed"
}

# Configure environment
configure_environment() {
    log "Configuring environment..."
    
    # Create .env.staging if it doesn't exist
    if [[ ! -f ".env.staging" ]]; then
        log "Creating .env.staging file..."
        cp .env.example .env.staging
    fi
    
    # Update .env.staging with user input
    log "Updating environment configuration..."
    
    # Update basic app settings
    sed -i "s|APP_ENV=.*|APP_ENV=staging|" .env.staging
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" .env.staging
    sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env.staging
    
    # Update database settings
    sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env.staging
    sed -i "s|DB_HOST=.*|DB_HOST=127.0.0.1|" .env.staging
    sed -i "s|DB_PORT=.*|DB_PORT=3306|" .env.staging
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" .env.staging
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env.staging
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env.staging
    
    # Update cache and session settings for production
    sed -i "s|CACHE_STORE=.*|CACHE_STORE=file|" .env.staging
    sed -i "s|SESSION_DRIVER=.*|SESSION_DRIVER=file|" .env.staging
    sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env.staging
    
    success "Environment configured"
}

# Configure Nginx
configure_nginx() {
    log "Configuring Nginx..."
    
    # Create Nginx configuration
    sudo tee /etc/nginx/sites-available/controle-financeiro > /dev/null << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $APP_PATH/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Handle Laravel routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
    }

    # Deny access to sensitive files
    location ~ /\.ht {
        deny all;
    }
    
    location ~ /\.env {
        deny all;
    }

    # Optimize static files
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
EOF

    # Enable site
    sudo ln -sf /etc/nginx/sites-available/controle-financeiro /etc/nginx/sites-enabled/
    
    # Remove default site if it exists
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test Nginx configuration
    if sudo nginx -t; then
        sudo systemctl reload nginx
        success "Nginx configured successfully"
    else
        error "Nginx configuration test failed"
        exit 1
    fi
}

# Setup SSL certificate
setup_ssl() {
    log "Setting up SSL certificate..."
    
    # Install Certbot if not present
    if ! command -v certbot >/dev/null 2>&1; then
        log "Installing Certbot..."
        if [[ -f /etc/debian_version ]]; then
            sudo apt install -y certbot python3-certbot-nginx
        else
            sudo yum install -y certbot python3-certbot-nginx
        fi
    fi
    
    # Obtain SSL certificate
    log "Obtaining SSL certificate for $DOMAIN..."
    sudo certbot --nginx -d "$DOMAIN" -d "www.$DOMAIN" --email "$SSL_EMAIL" --agree-tos --non-interactive
    
    # Setup auto-renewal
    log "Setting up SSL certificate auto-renewal..."
    (sudo crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | sudo crontab -
    
    success "SSL certificate configured"
}

# Run deployment
run_deployment() {
    log "Running application deployment..."
    
    # Set environment variables for deployment
    export APP_URL="https://$DOMAIN"
    export APP_PATH="$APP_PATH"
    export MYSQL_USER="$DB_USER"
    export MYSQL_DB="$DB_NAME"
    export MYSQL_PASSWORD="$DB_PASSWORD"
    export AUTO_INSTALL="true"
    
    # Run deployment script
    chmod +x scripts/deploy-staging.sh
    ./scripts/deploy-staging.sh --auto-install
    
    success "Deployment completed"
}

# Final verification
verify_setup() {
    log "Verifying setup..."
    
    # Test web server response
    log "Testing web server response..."
    if curl -f -s -o /dev/null --max-time 30 "https://$DOMAIN"; then
        success "Web server is responding correctly"
    else
        warning "Web server test failed, but this might be normal if DNS is not propagated yet"
    fi
    
    # Test database connection
    log "Testing database connection..."
    cd "$APP_PATH"
    if sudo -u www-data php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" 2>/dev/null; then
        success "Database connection is working"
    else
        warning "Database connection test failed"
    fi
    
    success "Setup verification completed"
}

# Main function
main() {
    echo "=== VPS Setup Script for Controle Financeiro ==="
    echo "This script will:"
    echo "1. Install missing dependencies"
    echo "2. Configure database"
    echo "3. Configure environment"
    echo "4. Configure Nginx"
    echo "5. Setup SSL certificate"
    echo "6. Deploy the application"
    echo ""
    
    # Check if running as root
    if [[ $EUID -eq 0 ]]; then
        error "This script should not be run as root"
        exit 1
    fi
    
    # Check if we're in the project directory
    if [[ ! -f "composer.json" ]]; then
        error "Please run this script from the project root directory"
        exit 1
    fi
    
    get_user_input
    setup_database
    configure_environment
    configure_nginx
    setup_ssl
    run_deployment
    verify_setup
    
    echo ""
    success "=== Setup completed successfully! ==="
    echo ""
    log "Your application is now available at: https://$DOMAIN"
    log "Admin panel: https://$DOMAIN/admin"
    echo ""
    log "Default credentials:"
    log "- Admin: admin@admin.com / password"
    log "- Demo: demo@demo.com / password"
    echo ""
    warning "Remember to change the default passwords after first login!"
    echo ""
    log "Logs are available at: /var/log/controle-financeiro-deploy.log"
    log "Application files are at: $APP_PATH"
    echo ""
}

# Run main function
main "$@"