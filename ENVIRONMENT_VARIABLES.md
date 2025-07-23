# Environment Variables Documentation

This document describes the environment-specific variables used in the Controle Financeiro application across different environments.

## Environment Files Overview

| File | Purpose | Usage |
|------|---------|-------|
| `.env` | Current active environment | Used by Laravel application |
| `.env.local` | Local development | Copy to `.env` for local development |
| `.env.staging` | VPS staging environment | Copy to `.env` on staging server |
| `.env.production` | Production environment | Copy to `.env` on production server |
| `.env.production.example` | Production template | Template for production setup |
| `.env.example` | Laravel default template | Laravel's default template |

## Environment-Specific Variables

### Application Configuration

| Variable | Local | Staging | Production | Description |
|----------|-------|---------|------------|-------------|
| `APP_ENV` | `local` | `staging` | `production` | Environment identifier |
| `APP_DEBUG` | `true` | `false` | `false` | Debug mode |
| `APP_URL` | `http://localhost:8000` | `https://staging.domain.com` | `https://domain.com` | Application URL |
| `LOG_LEVEL` | `debug` | `info` | `error` | Logging level |

### Database Configuration

#### Local Development
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889                    # MAMP default port
DB_DATABASE=controle_financeiro_local
DB_USERNAME=root
DB_PASSWORD=root
```

**Alternative SQLite for Local:**
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

#### Staging Environment
```env
DB_CONNECTION=mysql
DB_HOST=mysql                   # Docker container name
DB_PORT=3306
DB_DATABASE=controle_financeiro_staging
DB_USERNAME=staging_user
DB_PASSWORD=CHANGE_THIS_STAGING_DB_PASSWORD
DB_SSL_MODE=REQUIRED
```

#### Production Environment
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=controle_financeiro
DB_USERNAME=laravel_user
DB_PASSWORD=CHANGE_THIS_STRONG_DB_PASSWORD
DB_SSL_MODE=REQUIRED
DB_SSL_VERIFY_SERVER_CERT=true
DB_POOL_MIN=5
DB_POOL_MAX=20
```

### Cache Configuration

#### Local Development
```env
CACHE_STORE=file                # Fast file-based cache
CACHE_PREFIX=
```

#### Staging Environment
```env
CACHE_STORE=redis              # Production-like Redis cache
CACHE_PREFIX=cf_staging_
```

#### Production Environment
```env
CACHE_STORE=redis              # Optimized Redis cache
CACHE_PREFIX=cf_prod_
CACHE_TTL=3600
```

### Session Configuration

#### Local Development
```env
SESSION_DRIVER=file            # Simple file-based sessions
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
```

#### Staging Environment
```env
SESSION_DRIVER=redis           # Redis for scalability testing
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=staging.domain.com
```

#### Production Environment
```env
SESSION_DRIVER=redis           # Redis with security
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=domain.com
SESSION_SAME_SITE=strict
```

### Queue Configuration

#### Local Development
```env
QUEUE_CONNECTION=database      # Database queue for testing
```

#### Staging Environment
```env
QUEUE_CONNECTION=redis         # Redis queue for production-like testing
```

#### Production Environment
```env
QUEUE_CONNECTION=redis         # Redis queue with failover
QUEUE_FAILED_DRIVER=database
```

### Redis Configuration

#### Local Development
```env
# Redis not used in local development
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Staging Environment
```env
REDIS_CLIENT=phpredis
REDIS_HOST=redis               # Docker container
REDIS_PASSWORD=CHANGE_THIS_STAGING_REDIS_PASSWORD
REDIS_PORT=6379
```

#### Production Environment
```env
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=CHANGE_THIS_STRONG_REDIS_PASSWORD
REDIS_PORT=6379
REDIS_DB=0
# Optional cluster configuration
REDIS_CLUSTER_ENABLED=false
```

### Mail Configuration

#### Local Development
```env
MAIL_MAILER=log                # Log emails for testing
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
```

#### Staging Environment
```env
MAIL_MAILER=smtp               # Real SMTP for testing
MAIL_HOST=smtp.mailtrap.io     # Testing service
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
```

#### Production Environment
```env
MAIL_MAILER=smtp               # Production SMTP
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=CHANGE_THIS_EMAIL_PASSWORD
MAIL_ENCRYPTION=tls
```

### Security Configuration

#### Local Development
```env
SESSION_SECURE_COOKIE=false    # HTTP allowed for local
FORCE_HTTPS=false
```

#### Staging Environment
```env
SESSION_SECURE_COOKIE=true     # HTTPS required
FORCE_HTTPS=true
SANCTUM_STATEFUL_DOMAINS=staging.domain.com
```

#### Production Environment
```env
SESSION_SECURE_COOKIE=true     # HTTPS required
FORCE_HTTPS=true
HSTS_ENABLED=true
SECURE_HEADERS=true
SANCTUM_STATEFUL_DOMAINS=domain.com
TRUSTED_PROXIES=*
```

### Development Tools

#### Local Development
```env
TELESCOPE_ENABLED=false        # Optional debugging tool
DEBUGBAR_ENABLED=true          # Debug toolbar enabled
QUERY_LOG_ENABLED=true         # SQL query logging
FEATURE_TESTING_ENABLED=true   # Testing features
SEED_DEMO_DATA=true           # Demo data for development
```

#### Staging Environment
```env
TELESCOPE_ENABLED=true         # Debugging tool for staging
DEBUGBAR_ENABLED=false         # No debug toolbar
PERFORMANCE_MONITORING=true    # Performance monitoring
ERROR_REPORTING_ENABLED=true   # Error tracking
```

#### Production Environment
```env
TELESCOPE_ENABLED=false        # No debugging tools
DEBUGBAR_ENABLED=false         # No debug toolbar
OPCACHE_ENABLED=true          # PHP optimization
RESPONSE_CACHE_ENABLED=true   # Response caching
QUERY_CACHE_ENABLED=true      # Query caching
```

### Monitoring and Backup

#### Staging Environment
```env
BACKUP_ENABLED=true
BACKUP_RETENTION_DAYS=7
HEALTH_CHECK_ENABLED=true
RATE_LIMIT_PER_MINUTE=120
```

#### Production Environment
```env
BACKUP_ENABLED=true
BACKUP_RETENTION_DAYS=30
BACKUP_S3_BUCKET=your-backup-bucket
HEALTH_CHECK_ENABLED=true
RATE_LIMIT_PER_MINUTE=60
API_RATE_LIMIT_PER_MINUTE=1000
SENTRY_LARAVEL_DSN=your-sentry-dsn
LOG_SLACK_WEBHOOK_URL=your-slack-webhook
```

## Environment Setup Instructions

### Local Development Setup
1. Copy `.env.local` to `.env`
2. Update database credentials if not using MAMP
3. Run `php artisan key:generate`
4. Run `php artisan migrate --seed`

### Staging Environment Setup
1. Copy `.env.staging` to `.env` on staging server
2. Update all `CHANGE_THIS_*` values
3. Generate new APP_KEY: `php artisan key:generate`
4. Configure SSL certificates
5. Run migrations: `php artisan migrate`

### Production Environment Setup
1. Copy `.env.production.example` to `.env.production`
2. Update all `CHANGE_THIS_*` values with secure credentials
3. Generate new APP_KEY: `php artisan key:generate`
4. Configure SSL certificates and security headers
5. Set up monitoring and backup systems
6. Run migrations: `php artisan migrate --force`

## Security Notes

### Passwords and Keys
- Always use strong, unique passwords for each environment
- Generate new APP_KEY for each environment
- Use different database passwords for each environment
- Store production credentials securely (e.g., vault systems)

### SSL/HTTPS
- Local: HTTP is acceptable for development
- Staging: HTTPS required for realistic testing
- Production: HTTPS mandatory with HSTS enabled

### Database Security
- Local: Basic security acceptable
- Staging: SSL connections required
- Production: SSL with certificate verification, connection pooling

## Troubleshooting

### Common Issues
1. **Database Connection Failed**: Check host, port, and credentials
2. **Redis Connection Failed**: Ensure Redis is running and accessible
3. **Mail Not Sending**: Verify SMTP credentials and settings
4. **Cache Issues**: Clear cache with `php artisan cache:clear`
5. **Session Issues**: Clear sessions with `php artisan session:flush`

### Environment Detection
The application uses `EnvironmentDetector` service to automatically detect the environment based on:
1. `APP_ENV` variable
2. Hostname patterns
3. Fallback to production settings for security

### Performance Optimization
- **Local**: Optimized for development speed
- **Staging**: Balanced for testing and debugging
- **Production**: Optimized for performance and security