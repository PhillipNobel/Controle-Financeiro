#!/bin/bash

# SSL Setup Script for Staging Environment
# This script sets up SSL certificates using Let's Encrypt for the staging environment

set -e

# Configuration
DOMAIN="${NGINX_HOST:-staging.controle-financeiro.com}"
EMAIL="${SSL_EMAIL:-admin@controle-financeiro.com}"
COMPOSE_FILE="docker-compose.yml"
CERTBOT_DIR="./docker/certbot"
SSL_DIR="./docker/ssl"

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

# Check prerequisites
check_prerequisites() {
    log "Checking SSL setup prerequisites..."
    
    # Check if Docker is running
    if ! docker info >/dev/null 2>&1; then
        error "Docker is not running or not accessible"
        exit 1
    fi
    
    # Check if domain is provided
    if [[ -z "$DOMAIN" ]]; then
        error "Domain not specified. Set NGINX_HOST environment variable."
        exit 1
    fi
    
    # Check if email is provided
    if [[ -z "$EMAIL" ]]; then
        error "Email not specified. Set SSL_EMAIL environment variable."
        exit 1
    fi
    
    # Check if docker-compose file exists
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        error "docker-compose.yml not found. Are you in the project root?"
        exit 1
    fi
    
    success "Prerequisites check passed"
}

# Create necessary directories
create_directories() {
    log "Creating SSL directories..."
    
    mkdir -p "${CERTBOT_DIR}/www"
    mkdir -p "${CERTBOT_DIR}/conf"
    mkdir -p "${SSL_DIR}"
    
    success "SSL directories created"
}

# Check if domain is accessible
check_domain_accessibility() {
    log "Checking domain accessibility..."
    
    # Start nginx temporarily for domain verification
    log "Starting nginx for domain verification..."
    docker-compose -f "$COMPOSE_FILE" up -d nginx
    
    # Wait for nginx to be ready
    sleep 10
    
    # Test if domain is accessible
    local max_attempts=5
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s -o /dev/null --max-time 10 "http://${DOMAIN}/.well-known/acme-challenge/test" 2>/dev/null; then
            success "Domain is accessible for ACME challenge"
            return 0
        fi
        
        log "Testing domain accessibility (attempt $attempt/$max_attempts)..."
        sleep 5
        ((attempt++))
    done
    
    warning "Domain accessibility test failed, but continuing with certificate request"
    return 0
}

# Generate self-signed certificate as fallback
generate_self_signed_cert() {
    log "Generating self-signed certificate as fallback..."
    
    # Create self-signed certificate
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "${SSL_DIR}/${DOMAIN}.key" \
        -out "${SSL_DIR}/${DOMAIN}.crt" \
        -subj "/C=BR/ST=SP/L=SaoPaulo/O=ControleFinanceiro/CN=${DOMAIN}"
    
    # Set proper permissions
    chmod 600 "${SSL_DIR}/${DOMAIN}.key"
    chmod 644 "${SSL_DIR}/${DOMAIN}.crt"
    
    success "Self-signed certificate generated"
}

# Request Let's Encrypt certificate
request_letsencrypt_cert() {
    log "Requesting Let's Encrypt certificate for ${DOMAIN}..."
    
    # Run certbot to obtain certificate
    docker-compose -f "$COMPOSE_FILE" --profile ssl-setup run --rm certbot \
        certonly \
        --webroot \
        --webroot-path=/var/www/certbot \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email \
        --force-renewal \
        -d "$DOMAIN"
    
    if [[ $? -eq 0 ]]; then
        success "Let's Encrypt certificate obtained successfully"
        return 0
    else
        error "Failed to obtain Let's Encrypt certificate"
        return 1
    fi
}

# Setup certificate renewal
setup_certificate_renewal() {
    log "Setting up certificate renewal..."
    
    # Start the renewal service
    docker-compose -f "$COMPOSE_FILE" --profile ssl-renew up -d certbot-renew
    
    success "Certificate renewal service started"
}

# Update nginx configuration for SSL
update_nginx_config() {
    log "Updating nginx configuration for SSL..."
    
    # Restart nginx with SSL configuration
    docker-compose -f "$COMPOSE_FILE" restart nginx
    
    # Wait for nginx to be ready
    sleep 10
    
    success "Nginx configuration updated"
}

# Test SSL certificate
test_ssl_certificate() {
    log "Testing SSL certificate..."
    
    local max_attempts=5
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        if curl -f -s -o /dev/null --max-time 10 "https://${DOMAIN}/health"; then
            success "SSL certificate is working correctly"
            return 0
        fi
        
        log "Testing SSL certificate (attempt $attempt/$max_attempts)..."
        sleep 10
        ((attempt++))
    done
    
    warning "SSL certificate test failed, but certificate may still be valid"
    return 0
}

# Display certificate information
display_certificate_info() {
    log "Certificate information:"
    
    if [[ -f "${CERTBOT_DIR}/conf/live/${DOMAIN}/fullchain.pem" ]]; then
        echo "Let's Encrypt certificate details:"
        openssl x509 -in "${CERTBOT_DIR}/conf/live/${DOMAIN}/fullchain.pem" -text -noout | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"
    elif [[ -f "${SSL_DIR}/${DOMAIN}.crt" ]]; then
        echo "Self-signed certificate details:"
        openssl x509 -in "${SSL_DIR}/${DOMAIN}.crt" -text -noout | grep -E "(Subject:|Issuer:|Not Before:|Not After:)"
    else
        warning "No certificate found"
    fi
}

# Main SSL setup function
main() {
    echo "=== SSL Setup for Staging Environment ==="
    echo "Timestamp: $(date)"
    echo "Domain: $DOMAIN"
    echo "Email: $EMAIL"
    echo ""
    
    check_prerequisites
    create_directories
    
    # Generate self-signed certificate first as fallback
    generate_self_signed_cert
    
    # Check domain accessibility
    check_domain_accessibility
    
    # Try to get Let's Encrypt certificate
    if request_letsencrypt_cert; then
        log "Using Let's Encrypt certificate"
        setup_certificate_renewal
    else
        warning "Using self-signed certificate as fallback"
    fi
    
    # Update nginx configuration
    update_nginx_config
    
    # Test the certificate
    test_ssl_certificate
    
    # Display certificate information
    display_certificate_info
    
    echo ""
    success "SSL setup completed!"
    log "Your staging site should now be accessible at: https://${DOMAIN}"
    
    # Show renewal information
    echo ""
    log "Certificate renewal information:"
    log "- Let's Encrypt certificates are automatically renewed"
    log "- Self-signed certificates need manual renewal after 365 days"
    log "- Check certificate status: openssl x509 -in /path/to/cert -text -noout"
}

# Handle command line arguments
case "${1:-}" in
    --renew)
        log "Manual certificate renewal requested"
        docker-compose -f "$COMPOSE_FILE" --profile ssl-setup run --rm certbot renew
        docker-compose -f "$COMPOSE_FILE" restart nginx
        success "Certificate renewal completed"
        exit 0
        ;;
    --test)
        log "Testing current SSL configuration"
        test_ssl_certificate
        display_certificate_info
        exit 0
        ;;
    --self-signed)
        log "Generating self-signed certificate only"
        check_prerequisites
        create_directories
        generate_self_signed_cert
        update_nginx_config
        test_ssl_certificate
        display_certificate_info
        success "Self-signed certificate setup completed"
        exit 0
        ;;
    --help)
        echo "Usage: $0 [OPTIONS]"
        echo ""
        echo "Options:"
        echo "  --renew       Renew existing certificates"
        echo "  --test        Test current SSL configuration"
        echo "  --self-signed Generate self-signed certificate only"
        echo "  --help        Show this help message"
        echo ""
        echo "Environment variables:"
        echo "  NGINX_HOST    Domain name (default: staging.controle-financeiro.com)"
        echo "  SSL_EMAIL     Email for Let's Encrypt (default: admin@controle-financeiro.com)"
        echo ""
        exit 0
        ;;
esac

# Run main SSL setup
main "$@"