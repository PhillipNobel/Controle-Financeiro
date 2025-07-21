# Design Document

## Overview

O sistema de controle financeiro será desenvolvido como uma aplicação web usando Laravel 10+ como framework backend e Filament PHP 3.x para a interface administrativa. O sistema utilizará MySQL como banco de dados e fornecerá uma API REST para integração externa. A arquitetura seguirá os padrões MVC do Laravel com Repository Pattern para acesso aos dados.

## Architecture

### Tecnologias Principais
- **Backend**: Laravel 10+
- **Interface Admin**: Filament PHP 3.x
- **Banco de Dados**: MySQL 8.0+
- **API**: Laravel Sanctum para autenticação
- **Frontend**: Blade Templates + Livewire (via Filament)

### Estrutura de Diretórios
```
app/
├── Models/
│   ├── User.php
│   ├── Transaction.php
│   ├── Wallet.php
│   └── Company.php
├── Http/
│   ├── Controllers/Api/
│   │   └── TransactionController.php
│   └── Resources/
├── Filament/
│   ├── Resources/
│   │   ├── TransactionResource.php
│   │   ├── WalletResource.php
│   │   ├── UserResource.php
│   │   └── CompanyResource.php
│   ├── Widgets/
│   │   ├── ExpenseVsRevenueWidget.php
│   │   ├── MostExpensiveWidget.php
│   │   └── FinancialSummaryWidget.php
│   └── Pages/
│       └── Dashboard.php
├── Policies/
└── Enums/
    └── UserRole.php
```

## Components and Interfaces

### Models

#### User Model
```php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'role'
    ];
    
    protected $casts = [
        'role' => UserRole::class
    ];
}
```

#### Transaction Model
```php
class Transaction extends Model
{
    protected $fillable = [
        'item', 'date', 'quantity', 'value', 'wallet_id'
    ];
    
    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'value' => 'decimal:2'
    ];
    
    public function wallet(): BelongsTo;
}
```

#### Wallet Model
```php
class Wallet extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function transactions(): HasMany;
    public function getTotalValue(): float;
}
```

#### Company Model
```php
class Company extends Model
{
    protected $fillable = [
        'name', 'cnpj', 'razao_social', 'inscricao_estadual',
        'telefone', 'endereco', 'email', 'pessoa_responsavel', 'website'
    ];
}
```

### Filament Resources

#### TransactionResource
- Gerenciamento completo de transações
- Filtros por carteira e período
- Validação de campos obrigatórios
- Formatação de valores monetários
- Geração de extrato consolidado mensal
- Exportação de extrato em PDF

#### WalletResource
- CRUD de carteiras
- Prevenção de exclusão com transações
- Exibição de totais por carteira

#### UserResource
- Gerenciamento de usuários
- Controle de roles/permissões
- Validação de email único

#### CompanyResource
- Configurações da empresa
- Validação de CNPJ
- Campos de contato e endereço

### Widgets do Dashboard

#### MonthlyStatementWidget
- Seletor de mês/ano para extrato
- Exibição de saldo inicial e final
- Cálculo de subtotais por tipo
- Filtro por carteira
- Exportação para PDF

#### ExpenseVsRevenueWidget
- Gráfico comparativo receitas vs despesas
- Filtro por período
- Atualização em tempo real

#### MostExpensiveWidget
- Lista das transações mais custosas
- Agrupamento por categoria
- Links para edição rápida

#### FinancialSummaryWidget
- Cards com totais gerais
- Indicadores de crescimento
- Resumo por carteira

## Data Models

### Database Schema

#### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'editor',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### wallets
```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### transactions
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    value DECIMAL(15,2) NOT NULL,
    wallet_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE RESTRICT
);
```

#### companies
```sql
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) UNIQUE NULL,
    razao_social VARCHAR(255) NULL,
    inscricao_estadual VARCHAR(50) NULL,
    telefone VARCHAR(20) NULL,
    endereco TEXT NULL,
    email VARCHAR(255) NULL,
    pessoa_responsavel VARCHAR(255) NULL,
    website VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Relationships
- User: Não possui relacionamentos diretos (sistema simples)
- Wallet: hasMany(Transaction)
- Transaction: belongsTo(Wallet)
- Company: Singleton (apenas um registro)

## Error Handling

### Validation Rules
- **Transaction**: item (required|string), date (required|date), quantity (required|numeric|min:0), value (required|numeric), wallet_id (required|exists:wallets,id)
- **Wallet**: name (required|string|unique), description (nullable|string)
- **User**: name (required|string), email (required|email|unique), password (required|min:8), role (required|in:super_admin,admin,editor)
- **Company**: cnpj (nullable|string|formato_cnpj), email (nullable|email)

### API Error Responses
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": ["Error message"]
    }
}
```

### Permission Handling
- Middleware para verificação de roles
- Policies para controle granular
- Redirecionamento para páginas de erro apropriadas

## Testing Strategy

### Unit Tests
- Model validations e relationships
- Business logic methods
- Helper functions e formatters

### Feature Tests
- API endpoints completos
- Filament resource operations
- Authentication e authorization

### Integration Tests
- Dashboard widgets com dados reais
- Fluxo completo de transações
- Backup e restore de dados

### Test Data
- Factories para todos os models
- Seeders para dados de demonstração
- Estados específicos para edge cases

## Security Considerations

### Authentication
- Laravel Sanctum para API tokens
- Filament authentication integrado
- Password hashing com bcrypt

### Authorization
- Role-based access control
- Filament policies integration
- API route protection

### Data Validation
- Server-side validation em todas as entradas
- CSRF protection habilitado
- SQL injection prevention via Eloquent

## Performance Optimization

### Database
- Índices em campos de busca frequente
- Eager loading para relacionamentos
- Query optimization para widgets

### Caching
- Cache de configurações da empresa
- Cache de totais calculados
- Session-based caching para widgets

### Frontend
- Lazy loading de componentes Filament
- Otimização de assets
- Compressão de responses

## Docker Architecture

### Container Structure
- **Application**: Container PHP-FPM com Laravel e Filament
- **Web Server**: NGINX para servir a aplicação
- **Database**: MySQL 8.0+ em container separado
- **Redis**: Cache e filas em container dedicado

### Development Environment
- Docker Compose para orquestração local
- Volumes para código fonte e persistência
- Hot-reload para desenvolvimento
- Xdebug para debugging

### Production Environment
- Containers otimizados e minimalistas
- Configurações de segurança reforçadas
- Healthchecks para monitoramento
- Scripts de backup automatizado

### Deployment Strategy
- Pipeline CI/CD com build de imagens
- Zero-downtime deployment
- Rollback automatizado
- Logs centralizados