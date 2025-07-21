# Docker Setup Guide

Este guia explica como configurar e usar o ambiente Docker para o sistema de Controle Financeiro.

## 📋 Pré-requisitos

- Docker 20.10+
- Docker Compose 2.0+
- Git

## 🚀 Início Rápido

### 1. Configuração Inicial

```bash
# Clone o repositório
git clone <repository-url>
cd controle-financeiro

# Execute o script de inicialização
./scripts/docker-init.sh
```

O script de inicialização irá:
- Construir as imagens Docker
- Iniciar todos os containers
- Configurar o banco de dados
- Executar as migrations
- Popular com dados de exemplo

### 2. Acessar a Aplicação

- **Aplicação Web**: http://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## 🏗️ Arquitetura dos Containers

### Containers de Desenvolvimento

| Container | Serviço | Porta | Descrição |
|-----------|---------|-------|-----------|
| `app` | PHP-FPM | 9000 | Aplicação Laravel |
| `nginx` | NGINX | 8080 | Servidor web |
| `mysql` | MySQL 8.0 | 3306 | Banco de dados |
| `redis` | Redis 7 | 6379 | Cache e filas |
| `queue` | Laravel Queue | - | Processamento de filas |
| `scheduler` | Laravel Scheduler | - | Tarefas agendadas |

### Volumes

- `mysql_data`: Dados persistentes do MySQL
- `redis_data`: Dados persistentes do Redis
- `./`: Código fonte (bind mount para desenvolvimento)

## 🛠️ Comandos Úteis

### Gerenciamento de Containers

```bash
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Reiniciar um serviço específico
docker-compose restart app

# Ver logs
docker-compose logs -f app

# Ver status dos containers
docker-compose ps
```

### Acesso aos Containers

```bash
# Acessar container da aplicação
docker-compose exec app bash

# Executar comandos Artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker

# Acessar MySQL
docker-compose exec mysql mysql -u root -psecret controle_financeiro

# Acessar Redis
docker-compose exec redis redis-cli -a secret
```

### Desenvolvimento

```bash
# Instalar dependências
docker-compose exec app composer install

# Executar testes
docker-compose exec app php artisan test

# Limpar caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Gerar chave da aplicação
docker-compose exec app php artisan key:generate
```

## 🔧 Scripts Utilitários

### Health Check

```bash
# Verificar saúde dos containers
./scripts/health-check.sh

# Verificar com logs detalhados
./scripts/health-check.sh --logs
```

### Backup do Banco de Dados

```bash
# Criar backup
./scripts/backup-database.sh

# Listar backups
./scripts/backup-database.sh list

# Restaurar backup
./scripts/backup-database.sh restore backups/backup_file.sql.gz

# Limpar backups antigos (mais de 7 dias)
./scripts/backup-database.sh clean
```

## 🚀 Deploy em Produção

### 1. Configuração do Ambiente

```bash
# Copiar e configurar arquivo de produção
cp .env.production .env.prod
# Editar .env.prod com suas configurações

# Configurar variáveis sensíveis
export DB_USERNAME=your_db_user
export DB_PASSWORD=your_strong_password
export REDIS_PASSWORD=your_redis_password
```

### 2. Deploy

```bash
# Deploy com zero downtime
./scripts/deploy-production.sh

# Verificar status do deploy
./scripts/deploy-production.sh status

# Verificar saúde da aplicação
./scripts/deploy-production.sh health

# Rollback se necessário
./scripts/deploy-production.sh rollback
```

### 3. Monitoramento

```bash
# Ver logs de produção
docker-compose -f docker-compose.prod.yml logs -f

# Monitorar recursos
docker stats

# Verificar saúde dos containers
docker-compose -f docker-compose.prod.yml ps
```

## 🔒 Segurança

### Configurações de Produção

- Containers executam com usuário não-root
- Secrets são gerenciados via variáveis de ambiente
- Logs são limitados em tamanho
- Recursos são limitados por container
- Health checks implementados

### Backup e Recuperação

- Backups automáticos do banco de dados
- Volumes persistentes para dados críticos
- Scripts de restauração automatizados

## 🐛 Troubleshooting

### Problemas Comuns

#### Container não inicia

```bash
# Verificar logs
docker-compose logs container_name

# Reconstruir imagem
docker-compose build --no-cache container_name
```

#### Erro de permissão

```bash
# Corrigir permissões
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

#### Banco de dados não conecta

```bash
# Verificar se MySQL está rodando
docker-compose exec mysql mysqladmin ping -h localhost -u root -psecret

# Recriar container do banco
docker-compose down mysql
docker volume rm controle-financeiro_mysql_data
docker-compose up -d mysql
```

#### Cache/Session não funciona

```bash
# Verificar Redis
docker-compose exec redis redis-cli -a secret ping

# Limpar cache
docker-compose exec app php artisan cache:clear
```

### Logs e Debugging

```bash
# Ver todos os logs
docker-compose logs

# Logs específicos com timestamp
docker-compose logs -f -t app

# Logs do PHP
docker-compose exec app tail -f /var/log/php_errors.log

# Logs do NGINX
docker-compose exec nginx tail -f /var/log/nginx/error.log
```

## 📊 Monitoramento e Performance

### Métricas dos Containers

```bash
# Uso de recursos em tempo real
docker stats

# Informações detalhadas de um container
docker inspect container_name

# Processos rodando em um container
docker-compose exec app ps aux
```

### Otimização

- Use multi-stage builds para imagens menores
- Configure limites de recursos apropriados
- Implemente health checks eficientes
- Use cache layers do Docker adequadamente

## 🔄 CI/CD

O projeto inclui pipeline GitHub Actions que:

1. **Testa** o código automaticamente
2. **Constrói** imagens Docker otimizadas
3. **Faz deploy** automático para staging/produção
4. **Notifica** sobre status do pipeline

### Configuração do CI/CD

1. Configure secrets no GitHub:
   - `DOCKER_USERNAME`
   - `DOCKER_PASSWORD`
   - Variáveis de ambiente de produção

2. Ajuste os workflows em `.github/workflows/`

## 📚 Referências

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)
- [NGINX Configuration](https://nginx.org/en/docs/)

## 🆘 Suporte

Para problemas específicos:

1. Verifique os logs: `./scripts/health-check.sh --logs`
2. Execute diagnósticos: `./scripts/health-check.sh`
3. Consulte a documentação do Laravel
4. Abra uma issue no repositório