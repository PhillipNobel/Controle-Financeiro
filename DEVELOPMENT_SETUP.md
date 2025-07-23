# Configuração de Ambiente de Desenvolvimento

## ⚠️ IMPORTANTE: Desenvolvimento Local é 100% NATIVO

**Docker é PROIBIDO para desenvolvimento local!**

Este projeto usa uma abordagem híbrida:
- **Desenvolvimento Local**: 100% nativo (sem Docker)
- **Staging/Produção**: 100% Docker

## 🚀 Setup de Desenvolvimento Local (NATIVO)

### Pré-requisitos

1. **PHP 8.3+** instalado nativamente
2. **MySQL** nativo via uma das opções:
   - MAMP/XAMPP
   - Homebrew (`brew install mysql`)
   - Laravel Herd (recomendado)
3. **Composer** instalado globalmente
4. **Node.js 18+** para assets

### Instalação Passo-a-Passo

#### 1. Clone e Instale Dependências

```bash
# Clone o repositório
git clone [url-do-repositorio]
cd controle-financeiro

# Instale dependências PHP (NATIVO)
composer install

# Instale dependências Node.js
npm install
```

#### 2. Configure o Ambiente

```bash
# Copie o arquivo de ambiente
cp .env.example .env.local
cp .env.local .env

# Gere a chave da aplicação
php artisan key:generate
```

#### 3. Configure o Banco de Dados MySQL Nativo

**Opção A: MAMP/XAMPP**
```bash
# Configure no .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=controle_financeiro_local
DB_USERNAME=root
DB_PASSWORD=root
```

**Opção B: Homebrew MySQL**
```bash
# Inicie o MySQL
brew services start mysql

# Configure no .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=controle_financeiro_local
DB_USERNAME=root
DB_PASSWORD=
```

**Opção C: Laravel Herd (Recomendado)**
```bash
# Herd gerencia tudo automaticamente
# Configure no .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=controle_financeiro_local
DB_USERNAME=root
DB_PASSWORD=
```

#### 4. Configure Cache e Sessões (Nativo)

```bash
# No .env, use configurações nativas:
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

#### 5. Execute Migrações e Seeds

```bash
# Crie o banco de dados
mysql -u root -p -e "CREATE DATABASE controle_financeiro_local;"

# Execute migrações
php artisan migrate

# Execute seeds
php artisan db:seed
```

#### 6. Inicie o Servidor de Desenvolvimento

```bash
# Servidor Laravel nativo (porta 8000)
php artisan serve

# OU use Laravel Herd para melhor experiência
# Herd gerencia automaticamente
```

#### 7. Compile Assets (Opcional)

```bash
# Para desenvolvimento
npm run dev

# Para produção
npm run build
```

### 🧪 Testes

```bash
# Testes unitários (SQLite em memória)
php artisan test

# Testes específicos
php artisan test --filter=UserTest
```

### 🐛 Debugging

#### Xdebug Nativo

1. Instale Xdebug nativo:
```bash
# macOS com Homebrew
brew install php@8.3-xdebug

# Ou via PECL
pecl install xdebug
```

2. Configure no php.ini:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

3. Configure seu IDE (VS Code, PhpStorm) para porta 9003

### 📧 Email Testing (Opcional)

```bash
# Instale MailHog nativo
brew install mailhog

# Inicie MailHog
mailhog

# Configure no .env:
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

### 🔄 Hot Reload

O desenvolvimento nativo oferece hot reload instantâneo:
- Mudanças em PHP são refletidas imediatamente
- Assets são recompilados automaticamente com `npm run dev`
- Sem overhead de containers

### ⚡ Performance

Desenvolvimento nativo é significativamente mais rápido que Docker:

**Comparação de Performance:**
| Aspecto | Nativo | Docker |
|---------|--------|--------|
| Boot time | Instantâneo | 30-60s |
| Hot reload | Imediato | 2-5s delay |
| File watching | Direto | Overhead de sync |
| Memory usage | Mínimo | 500MB-2GB+ |
| CPU usage | Baixo | Médio-Alto |
| Debugging | Direto | Complexo |

**Benefícios do Desenvolvimento Nativo:**
- ✅ Boot time instantâneo (vs 30-60s Docker)
- ✅ Hot reload sem delay (vs 2-5s Docker)
- ✅ Acesso direto ao filesystem (vs volumes Docker)
- ✅ Debugging nativo com Xdebug (vs configuração complexa)
- ✅ Sem overhead de virtualização (economia de RAM/CPU)
- ✅ Integração direta com IDEs
- ✅ Comandos artisan executam instantaneamente

### 🚫 O que NÃO fazer

- ❌ **NUNCA** use `docker-compose up` para desenvolvimento
- ❌ **NUNCA** use containers para desenvolvimento local
- ❌ **NUNCA** instale Docker para desenvolvimento
- ❌ **NUNCA** use `docker-compose.local.yml` (não existe mais)

### 🔍 Verificação do Ambiente

Execute o script de verificação para confirmar que tudo está funcionando:

```bash
./scripts/verify-native-dev.sh
```

Este script verifica:
- ✅ Docker não está sendo usado (correto para desenvolvimento)
- ✅ PHP e Composer nativos funcionando
- ✅ Cache e sessões configurados para arquivo
- ✅ SQLite em memória para testes
- ✅ Configuração geral do ambiente

### 🆘 Troubleshooting

#### Problema: "Connection refused" no MySQL
```bash
# Verifique se MySQL está rodando
brew services list | grep mysql
# ou
sudo lsof -i :3306

# Para MAMP/XAMPP, inicie via interface gráfica
# Para Homebrew MySQL:
brew services start mysql

# Para Laravel Herd, o MySQL é gerenciado automaticamente
```

#### Problema: Extensões PHP faltando
```bash
# Instale extensões necessárias (Homebrew)
brew install php@8.3-mysql php@8.3-gd php@8.3-zip php@8.3-mbstring

# Para MAMP/XAMPP, as extensões já vêm incluídas
# Para Laravel Herd, as extensões são gerenciadas automaticamente
```

#### Problema: Permissões
```bash
# Ajuste permissões
chmod -R 775 storage bootstrap/cache

# Se ainda houver problemas:
sudo chown -R $(whoami) storage bootstrap/cache
```

#### Problema: Porta 8000 ocupada
```bash
# Verifique o que está usando a porta
lsof -i :8000

# Use uma porta diferente
php artisan serve --port=8001
```

### 📚 Comandos Úteis

```bash
# Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar banco
php artisan migrate:fresh --seed

# Verificar status
php artisan about

# Listar rotas
php artisan route:list

# Tinker (REPL)
php artisan tinker
```

## 🐳 Docker (APENAS para Staging/Produção)

Docker é usado EXCLUSIVAMENTE para:
- ✅ Ambiente de staging no VPS
- ✅ Ambiente de produção
- ❌ **NUNCA** para desenvolvimento local

Para staging/produção, consulte:
- `DOCKER.md` - Configuração Docker
- `PRODUCTION_DEPLOYMENT.md` - Deploy de produção

## 📖 Documentação Adicional

- `ENVIRONMENT_VARIABLES.md` - Variáveis de ambiente
- `TROUBLESHOOTING.md` - Solução de problemas
- `FILAMENT_CUSTOMIZATION.md` - Customizações do Filament

---

**Lembre-se: Desenvolvimento = NATIVO, Staging/Produção = DOCKER**