# Staging Docker Setup Guide

Este documento descreve como configurar e gerenciar o ambiente de staging usando Docker.

## ⚠️ IMPORTANTE: Docker é APENAS para Staging e Produção

**NUNCA use Docker para desenvolvimento local!**

- **Desenvolvimento Local**: 100% nativo (PHP, MySQL, Composer nativos)
- **Staging**: Docker no VPS
- **Produção**: Docker em ambiente de produção

## Visão Geral da Arquitetura

O ambiente de staging utiliza uma arquitetura containerizada com os seguintes serviços:

```
┌─────────────────────────────────────────────────────────────┐
│                    STAGING ENVIRONMENT                      │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │    NGINX    │  │  PHP-FPM    │  │   QUEUE     │        │
│  │   (SSL/80)  │  │    (9000)   │  │  WORKER     │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
│         │                 │                 │              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │    MYSQL    │  │    REDIS    │  │ SCHEDULER   │        │
│  │   (3306)    │  │   (6379)    │  │   (CRON)    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
│                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   CERTBOT   │  │  LOGSPOUT   │  │ MONITORING  │        │
│  │ (SSL CERTS) │  │   (LOGS)    │  │ (OPTIONAL)  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

## Pré-requisitos

### Sistema
- Ubuntu 20.04+ ou CentOS 8+
- Docker 20.10+
- Docker Compose 2.0+
- Git
- Curl
- OpenSSL

### Recursos Mínimos
- **CPU**: 2 cores
- **RAM**: 4GB
- **Disco**: 20GB livres
- **Rede**: Conexão estável com internet

### Domínio e DNS
- Domínio configurado (ex: `staging.controle-financeiro.com`)
- DNS apontando para o IP do servidor
- Portas 80 e 443 abertas no firewall

## Configuração Inicial

### 1. Preparar o Servidor

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependências
sudo apt install -y git curl openssl

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Reiniciar sessão para aplicar mudanças do grupo docker
```

### 2. Clonar o Repositório

```bash
# Clonar repositório
git clone https://github.com/seu-usuario/controle-financeiro.git
cd controle-financeiro

# Configurar branch de staging (se necessário)
git checkout staging  # ou main/master
```

### 3. Configurar Variáveis de Ambiente

```bash
# Copiar arquivo de ambiente para staging
cp .env.staging .env

# Editar variáveis de ambiente
nano .env
```

**Variáveis importantes para configurar:**

```bash
# Aplicação
APP_NAME="Controle Financeiro - Staging"
APP_ENV=staging
APP_KEY=base64:SUA_CHAVE_AQUI
APP_URL=https://staging.controle-financeiro.com

# Banco de dados
DB_ROOT_PASSWORD=senha_root_super_segura
DB_PASSWORD=senha_usuario_segura

# Redis
REDIS_PASSWORD=senha_redis_segura

# SSL
SSL_EMAIL=seu-email@dominio.com
NGINX_HOST=staging.controle-financeiro.com
```

### 4. Gerar Chave da Aplicação

```bash
# Gerar chave da aplicação
docker run --rm -v $(pwd):/app -w /app php:8.3-cli php artisan key:generate --show
```

## Deployment

### Deploy Automático (Recomendado)

```bash
# Executar script de deploy
./scripts/deploy-staging.sh

# Ou fazer dry-run primeiro
./scripts/deploy-staging.sh --dry-run
```

### Deploy Manual

```bash
# 1. Construir e iniciar containers
docker-compose up -d --build

# 2. Aguardar containers ficarem prontos
sleep 30

# 3. Executar migrations
docker exec controle-financeiro-app-staging php artisan migrate --force

# 4. Otimizar aplicação
docker exec controle-financeiro-app-staging php artisan config:cache
docker exec controle-financeiro-app-staging php artisan route:cache
docker exec controle-financeiro-app-staging php artisan view:cache

# 5. Verificar saúde
./scripts/health-check-staging.sh
```

## Configuração SSL

### Automática (Let's Encrypt)

```bash
# Configurar SSL automaticamente
./scripts/setup-ssl-staging.sh

# Ou apenas certificado auto-assinado para testes
./scripts/setup-ssl-staging.sh --self-signed
```

### Manual

```bash
# Obter certificado Let's Encrypt
docker-compose --profile ssl-setup run --rm certbot

# Iniciar renovação automática
docker-compose --profile ssl-renew up -d certbot-renew
```

## Monitoramento e Logs

### Health Checks

```bash
# Verificar saúde geral
./scripts/health-check-staging.sh

# Verificar apenas aplicação
docker exec controle-financeiro-app-staging php artisan health:check --detailed

# Verificar containers
docker-compose ps
```

### Logs

```bash
# Ver logs de todos os serviços
docker-compose logs -f

# Ver logs de serviço específico
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql

# Ver logs estruturados (JSON)
docker-compose logs -f nginx | jq '.'
```

### Monitoramento Avançado (Opcional)

```bash
# Iniciar stack de monitoramento
docker-compose -f docker-compose.yml -f docker/monitoring/docker-compose.monitoring.yml --profile monitoring up -d

# Acessar interfaces
# Grafana: http://seu-servidor:3000 (admin/admin123)
# Prometheus: http://seu-servidor:9090
```

## Manutenção

### Backup

```bash
# Backup automático (incluído no deploy)
# Backups ficam em: /var/backups/controle-financeiro/

# Backup manual do banco
docker exec controle-financeiro-mysql-staging mysqladump \
  -u root -p"${DB_ROOT_PASSWORD}" \
  --single-transaction \
  controle_financeiro_staging > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup manual dos volumes
docker run --rm -v controle-financeiro_mysql_data:/data -v $(pwd):/backup alpine \
  tar czf /backup/mysql_data_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .
```

### Atualizações

```bash
# Atualizar código e fazer deploy
git pull origin main
./scripts/deploy-staging.sh

# Atualizar apenas containers (sem código)
docker-compose pull
docker-compose up -d
```

### Rollback

```bash
# Rollback automático (se deploy falhar)
./scripts/deploy-staging.sh --rollback

# Rollback manual para backup específico
# (restaurar arquivos de backup manualmente)
```

### Limpeza

```bash
# Limpar containers parados
docker container prune -f

# Limpar imagens não utilizadas
docker image prune -f

# Limpar volumes órfãos
docker volume prune -f

# Limpeza completa (CUIDADO!)
docker system prune -af --volumes
```

## Troubleshooting

### Problemas Comuns

#### 1. Container não inicia
```bash
# Verificar logs
docker-compose logs nome-do-container

# Verificar recursos
docker stats

# Verificar configuração
docker-compose config
```

#### 2. Erro de SSL
```bash
# Verificar certificados
./scripts/setup-ssl-staging.sh --test

# Renovar certificados
./scripts/setup-ssl-staging.sh --renew

# Usar certificado auto-assinado temporariamente
./scripts/setup-ssl-staging.sh --self-signed
```

#### 3. Erro de banco de dados
```bash
# Verificar conectividade
docker exec controle-financeiro-mysql-staging mysqladmin ping

# Verificar logs do MySQL
docker-compose logs mysql

# Conectar ao banco manualmente
docker exec -it controle-financeiro-mysql-staging mysql -u root -p
```

#### 4. Erro de permissões
```bash
# Corrigir permissões do storage
docker exec controle-financeiro-app-staging chown -R www-data:www-data /var/www/html/storage
docker exec controle-financeiro-app-staging chmod -R 755 /var/www/html/storage
```

### Comandos Úteis

```bash
# Status dos containers
docker-compose ps

# Recursos utilizados
docker stats

# Informações do sistema
docker system df

# Logs em tempo real
docker-compose logs -f --tail=100

# Executar comandos na aplicação
docker exec -it controle-financeiro-app-staging bash
docker exec controle-financeiro-app-staging php artisan tinker

# Reiniciar serviço específico
docker-compose restart nginx
```

## Segurança

### Configurações Implementadas

- **SSL/TLS**: Certificados Let's Encrypt com renovação automática
- **Headers de Segurança**: HSTS, CSP, X-Frame-Options, etc.
- **Rate Limiting**: Proteção contra ataques de força bruta
- **Firewall**: Apenas portas 80, 443 e SSH abertas
- **Secrets**: Senhas em variáveis de ambiente
- **Non-root containers**: Containers executam sem privilégios root

### Recomendações Adicionais

```bash
# Configurar firewall
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443

# Configurar fail2ban
sudo apt install fail2ban
sudo systemctl enable fail2ban

# Atualizar sistema regularmente
sudo apt update && sudo apt upgrade -y

# Monitorar logs de segurança
sudo tail -f /var/log/auth.log
```

## Performance

### Otimizações Implementadas

- **OPcache**: Cache de bytecode PHP habilitado
- **Redis**: Cache de aplicação e sessões
- **Nginx**: Compressão gzip e cache de arquivos estáticos
- **MySQL**: Configurações otimizadas para staging
- **Docker**: Multi-stage builds e imagens otimizadas

### Monitoramento de Performance

```bash
# Métricas de containers
docker stats

# Métricas da aplicação
docker exec controle-financeiro-app-staging php artisan health:check --detailed

# Métricas do banco
docker exec controle-financeiro-mysql-staging mysqladmin status

# Métricas do Redis
docker exec controle-financeiro-redis-staging redis-cli info stats
```

## Suporte

### Logs Importantes

- **Aplicação**: `docker-compose logs app`
- **Nginx**: `docker-compose logs nginx`
- **MySQL**: `docker-compose logs mysql`
- **Deploy**: `/var/log/controle-financeiro-deploy.log`
- **Sistema**: `/var/log/syslog`

### Contatos

- **Documentação**: Este arquivo
- **Issues**: GitHub Issues do projeto
- **Logs**: Verificar logs antes de reportar problemas

---

## ⚠️ Lembrete Final

**Docker é APENAS para staging. Para desenvolvimento local, use ambiente 100% nativo!**

- ✅ **Staging**: Docker
- ❌ **Desenvolvimento**: NUNCA Docker
- ✅ **Desenvolvimento**: PHP nativo + MySQL nativo + Composer nativo