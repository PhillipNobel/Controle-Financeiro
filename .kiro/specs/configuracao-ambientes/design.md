# Design Document

## Overview

Este documento descreve o design da arquitetura de ambientes para o sistema de Controle Financeiro, estabelecendo uma estrutura clara para desenvolvimento local nativo e staging no VPS também nativo (sem Docker), devido às limitações de hardware do VPS.

## Architecture

### Environment Structure

```
┌─────────────────┐    ┌─────────────────┐
│   DEVELOPMENT   │    │     STAGING     │
│  (Local Native) │───▶│  (VPS Native)   │
│   NO DOCKER!    │    │   NO DOCKER!    │
└─────────────────┘    └─────────────────┘
│                      │
│ • PHP 8.3+ Native    │ • PHP 8.3+ Native
│ • MySQL Native       │ • MySQL Native
│ • File Cache         │ • File Cache
│ • Local Files        │ • VPS Files
│ • Debug ON           │ • Debug Limited
│ • Hot Reload         │ • Production Build
│ • Artisan Serve      │ • OpenLiteSpeed
│ • MAMP/XAMPP/Herd    │ • Pre-installed Stack
│ • Composer Local     │ • Composer VPS
│ • SQLite Tests       │ • MySQL Tests
│ • Xdebug Native      │ • Logs Estruturados
│ • MailHog Native     │ • SSL/HTTPS
│ • Zero Containers    │ • Health Checks
└──────────────────────┴──────────────────────

IMPORTANTE: Docker foi REMOVIDO completamente!
Desenvolvimento = 100% nativo, Staging = 100% nativo
Hardware limitado do VPS não suporta Docker
```

### Configuration Management

```mermaid
graph TD
    A[Environment Detection] --> B{Environment Type}
    B -->|local| C[Development Config]
    B -->|staging| D[Staging Config]
    
    C --> F[MySQL Native + File Cache]
    D --> G[MySQL Native + File Cache]
    
    F --> I[Local Development]
    G --> J[VPS Staging]
```

## Components and Interfaces

### 1. Environment Configuration Manager

**Purpose**: Detect and configure environment-specific settings

**Interface**:
```php
interface EnvironmentConfigInterface
{
    public function detectEnvironment(): string;
    public function loadConfiguration(string $environment): array;
    public function validateConfiguration(): bool;
}
```

**Implementation**:
- Environment detection via `APP_ENV` variable
- Fallback detection via hostname/domain patterns
- Configuration validation and error handling

### 2. Database Configuration Handler

**Purpose**: Manage database connections per environment

**Configurations**:

**Development (Local Native)**:
```php
'default' => env('DB_CONNECTION', 'mysql'),
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'controle_financeiro_local'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        // Native MySQL via MAMP/XAMPP/Homebrew
    ],
    'sqlite_testing' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        // For fast testing only
    ]
]
```

**Staging (VPS Native)**:
```php
'default' => 'mysql',
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'controle_financeiro_staging'),
        'username' => env('DB_USERNAME', 'staging_user'),
        'password' => env('DB_PASSWORD', ''),
        // Native MySQL installed on VPS
    ]
]
```

### 3. Cache and Session Configuration

**Development**:
```php
'cache' => [
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ]
    ]
],
'session' => [
    'driver' => 'file',
]
```

**Staging**:
```php
'cache' => [
    'default' => 'file',
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ]
    ]
],
'session' => [
    'driver' => 'file',
]
```

### 4. Native Configuration Manager

**Purpose**: Provide native setup for both development and staging environments

**Local Development** (100% NATIVO):
- Native PHP 8.3+ via `php artisan serve` (porta 8000) ou Laravel Herd
- Native MySQL via MAMP/XAMPP/Homebrew/Laravel Herd (porta 3306)
- File-based cache and sessions para máxima velocidade de desenvolvimento
- Direct file system access para hot reload instantâneo sem overhead
- SQLite em memória APENAS para testes automatizados (PHPUnit)
- Composer install local para vendor/ nativo
- Xdebug nativo para debugging com IDEs
- Ferramentas auxiliares nativas opcionais (MailHog local, Redis local)
- ZERO containers, ZERO Docker
- Configuração via .env.local para ambiente nativo
- Performance máxima sem overhead de virtualização

**Staging Native (VPS)**:
- Native PHP 8.3+ pré-instalado no VPS
- Native MySQL pré-instalado no VPS
- OpenLiteSpeed configurado com SSL
- File-based cache and sessions for simplicity
- Health checks via scripts nativos
- Backup via scripts nativos
- Configuração via .env.staging
- ZERO Docker devido às limitações de hardware do VPS
- REMOÇÃO COMPLETA de todos os arquivos Docker do projeto
- REMOÇÃO COMPLETA de docker-compose files
- REMOÇÃO COMPLETA de scripts Docker
- REMOÇÃO COMPLETA de configurações Docker



## Data Models

### Environment Configuration Model

```php
class EnvironmentConfig
{
    public string $environment;
    public array $database;
    public array $cache;
    public array $session;
    public array $queue;
    public array $logging;
    public array $security;
    public bool $debug;
    
    public function isLocal(): bool;
    public function isStaging(): bool;


}
```

### Deployment Configuration

```php
class DeploymentConfig
{
    public string $sourceEnvironment;
    public string $targetEnvironment;
    public array $backupSettings;
    public array $migrationSettings;
    public array $rollbackSettings;
    
    public function createBackup(): bool;
    public function deploy(): bool;
    public function rollback(): bool;
    public function runSmokeTests(): bool;
}
```

## Error Handling

### Environment Detection Errors
- Fallback to safe defaults (staging-like settings)
- Log environment detection issues
- Provide clear error messages for configuration problems

### Database Connection Errors
- Retry logic for temporary connection issues
- Fallback database configurations
- Clear error messages for setup issues

### Native Configuration Errors
- Automatic fallback to safe native configurations
- Resource requirement validation for VPS
- Clear troubleshooting guidance for native setups

## Testing Strategy

### Local Development Testing
```bash
# Unit tests with SQLite in-memory
php artisan test --env=testing

# Feature tests with local database
php artisan test --env=local

# Native integration tests
./scripts/test-native-integration.sh
```

### Staging Testing
```bash
# Automated deployment tests
./scripts/deploy-staging.sh --test

# Smoke tests after deployment
./scripts/smoke-tests-staging.sh

# Performance tests
./scripts/performance-tests.sh staging
```

### Environment Configuration Tests
```php
class EnvironmentConfigTest extends TestCase
{
    public function test_detects_local_environment();
    public function test_detects_staging_environment();
    public function test_loads_correct_database_config();
    public function test_validates_configuration();
    public function test_fallback_to_safe_defaults();
}
```

## Deployment Workflow

### Local to Staging Deployment

```mermaid
sequenceDiagram
    participant Dev as Developer
    participant Git as Git Repository
    participant VPS as VPS Server
    participant App as Application
    
    Dev->>Git: git push origin main
    Git->>VPS: Webhook/Manual Pull
    VPS->>VPS: Backup Current State
    VPS->>VPS: Update Native Application
    VPS->>VPS: Run Database Migrations
    VPS->>App: Deploy New Version
    App->>VPS: Health Check
    VPS->>Dev: Deployment Status
```

### Rollback Process

```mermaid
sequenceDiagram
    participant Admin as Administrator
    participant VPS as VPS Server
    participant Backup as Backup System
    participant App as Application
    
    Admin->>VPS: Trigger Rollback
    VPS->>Backup: Restore Previous Version
    VPS->>VPS: Restore Database Backup
    VPS->>App: Start Previous Version
    App->>VPS: Health Check
    VPS->>Admin: Rollback Complete
```

## Security Considerations

### Environment Isolation
- Separate environment variables per environment
- No staging secrets in development
- Encrypted secrets in staging

### Database Security
- Different database users per environment
- SSL connections in staging
- Regular security updates

### Native Security
- Non-root user execution
- Proper file permissions
- Regular system updates
- Environment variable management

## Performance Optimizations

### Development
- Fast database (SQLite)
- File-based caching
- Hot reload enabled
- Minimal logging

### Staging
- Native performance optimization
- File-based caching for simplicity
- Optimized queries
- Performance monitoring via native tools
- CDN integration
- OpenLiteSpeed optimization for limited hardware

## Monitoring and Logging

### Development
```php
'logging' => [
    'default' => 'stack',
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'stderr'],
        ],
    ],
],
```

### Staging
```php
'logging' => [
    'default' => 'stack',
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],
    ],
],
```

## Documentation Structure

```
docs/
├── environments/
│   ├── local-setup.md
│   ├── staging-setup.md
│   └── troubleshooting.md
├── deployment/
│   ├── staging-deployment.md
│   ├── rollback-procedures.md
│   └── backup-restore.md
└── native/
    ├── vps-setup.md
    ├── openlitespeed-configuration.md
    └── native-troubleshooting.md
```