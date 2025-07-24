# Deployment em Subdiretório

Este guia explica como fazer deploy da aplicação para rodar em um subdiretório de um domínio existente.

**Target URL:** `https://dev.nexxtecnologia.com.br/Controle-Financeiro`

## 🎯 Visão Geral

A aplicação será deployada usando Docker com mapeamento de porta, permitindo que rode em um subdiretório do seu domínio principal através de reverse proxy.

### Arquitetura

```
Internet → Nginx Principal (dev.nexxtecnologia.com.br)
                ↓
         Reverse Proxy (/Controle-Financeiro)
                ↓
         Docker Nginx (localhost:8080)
                ↓
         Laravel Application
```

## 🚀 Deploy Rápido

### 1. Preparar o Servidor

```bash
# Clonar o repositório
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro

# Configurar ambiente
cp .env.subdirectory .env
nano .env  # Configurar variáveis sensíveis
```

### 2. Configurar Variáveis de Ambiente

Edite o arquivo `.env` com as seguintes variáveis obrigatórias:

```bash
# Aplicação
APP_KEY=                                    # Gerar com: docker run --rm -v $(pwd):/app -w /app php:8.3-cli php artisan key:generate --show
APP_URL=https://dev.nexxtecnologia.com.br/Controle-Financeiro

# Banco de dados
DB_DATABASE=controle_financeiro_staging
DB_USERNAME=staging_user
DB_PASSWORD=SuaSenhaSuperSegura123
DB_ROOT_PASSWORD=SenhaRootSuperSegura456

# Redis
REDIS_PASSWORD=SenhaRedisSuperSegura789

# Sessões (IMPORTANTE para subdiretório)
SESSION_PATH=/Controle-Financeiro
SESSION_DOMAIN=dev.nexxtecnologia.com.br
```

### 3. Executar Deploy

```bash
# Deploy automático
./scripts/deploy-subdirectory.sh

# Verificar saúde
./scripts/deploy-subdirectory.sh --health-check
```

## 🔧 Configuração do Reverse Proxy

Após o deploy, configure seu servidor web principal para fazer proxy para a aplicação:

### Nginx Principal

Adicione ao seu arquivo de configuração do Nginx principal:

```nginx
# No arquivo de configuração do dev.nexxtecnologia.com.br
server {
    listen 443 ssl http2;
    server_name dev.nexxtecnologia.com.br;
    
    # ... suas configurações SSL existentes ...
    
    # Proxy para a aplicação Laravel
    location /Controle-Financeiro/ {
        proxy_pass http://localhost:8080/Controle-Financeiro/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Prefix /Controle-Financeiro;
        
        # Timeouts
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
        proxy_read_timeout 300;
        
        # Buffers
        proxy_buffer_size 128k;
        proxy_buffers 4 256k;
        proxy_busy_buffers_size 256k;
    }
    
    # Proxy para arquivos estáticos
    location ~* ^/Controle-Financeiro/.*\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache Principal

Se usar Apache, adicione ao VirtualHost:

```apache
<VirtualHost *:443>
    ServerName dev.nexxtecnologia.com.br
    
    # ... suas configurações SSL existentes ...
    
    # Proxy para a aplicação Laravel
    ProxyPass /Controle-Financeiro/ http://localhost:8080/Controle-Financeiro/
    ProxyPassReverse /Controle-Financeiro/ http://localhost:8080/Controle-Financeiro/
    ProxyPreserveHost On
    
    # Headers para Laravel
    ProxyPassReverse /Controle-Financeiro/ http://localhost:8080/Controle-Financeiro/
    ProxyPassReverseRewrite /Controle-Financeiro/ http://localhost:8080/Controle-Financeiro/
</VirtualHost>
```

### Recarregar Configuração

```bash
# Nginx
sudo nginx -t
sudo systemctl reload nginx

# Apache
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## 🔍 Verificação

### 1. Testar Containers

```bash
# Ver status dos containers
docker-compose -f docker-compose.subdirectory.yml ps

# Ver logs
docker-compose -f docker-compose.subdirectory.yml logs -f
```

### 2. Testar Aplicação

```bash
# Teste direto (sem reverse proxy)
curl -I http://localhost:8080/Controle-Financeiro/health

# Teste através do reverse proxy
curl -I https://dev.nexxtecnologia.com.br/Controle-Financeiro/health
```

### 3. Verificar URLs

Acesse no navegador:
- **Health Check:** `https://dev.nexxtecnologia.com.br/Controle-Financeiro/health`
- **Aplicação:** `https://dev.nexxtecnologia.com.br/Controle-Financeiro`

## 📊 Monitoramento

### Logs

```bash
# Logs da aplicação
docker logs controle-financeiro-app-subdirectory -f

# Logs do nginx
docker logs controle-financeiro-nginx-subdirectory -f

# Logs do banco
docker logs controle-financeiro-mysql-subdirectory -f
```

### Health Checks

```bash
# Health check completo
./scripts/deploy-subdirectory.sh --health-check

# Health check da aplicação
docker exec controle-financeiro-app-subdirectory php artisan health:check --detailed
```

### Recursos

```bash
# Status dos containers
docker stats

# Uso de disco
docker system df

# Informações dos volumes
docker volume ls
```

## 🔧 Manutenção

### Backup

```bash
# Backup manual do banco
docker exec controle-financeiro-mysql-subdirectory mysqladump \
  -u root -p"${DB_ROOT_PASSWORD}" \
  --single-transaction \
  controle_financeiro_staging > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Atualização

```bash
# Atualizar código e fazer redeploy
git pull origin main
./scripts/deploy-subdirectory.sh
```

### Rollback

```bash
# Rollback automático
./scripts/deploy-subdirectory.sh --rollback
```

## 🚨 Troubleshooting

### Problema: Aplicação não carrega

1. **Verificar containers:**
   ```bash
   docker-compose -f docker-compose.subdirectory.yml ps
   ```

2. **Verificar logs:**
   ```bash
   docker-compose -f docker-compose.subdirectory.yml logs
   ```

3. **Verificar reverse proxy:**
   ```bash
   curl -I http://localhost:8080/Controle-Financeiro/health
   ```

### Problema: Assets não carregam

1. **Verificar configuração do Laravel:**
   ```bash
   docker exec controle-financeiro-app-subdirectory php artisan config:show app
   ```

2. **Limpar cache:**
   ```bash
   docker exec controle-financeiro-app-subdirectory php artisan cache:clear
   docker exec controle-financeiro-app-subdirectory php artisan config:cache
   ```

### Problema: Sessões não funcionam

1. **Verificar configuração de sessão:**
   ```bash
   # Deve mostrar SESSION_PATH=/Controle-Financeiro
   docker exec controle-financeiro-app-subdirectory php artisan config:show session
   ```

2. **Verificar Redis:**
   ```bash
   docker exec controle-financeiro-redis-subdirectory redis-cli ping
   ```

### Problema: Banco não conecta

1. **Verificar MySQL:**
   ```bash
   docker exec controle-financeiro-mysql-subdirectory mysqladmin ping
   ```

2. **Verificar credenciais:**
   ```bash
   docker exec controle-financeiro-app-subdirectory php artisan tinker --execute="DB::connection()->getPdo();"
   ```

## 📋 Checklist de Deploy

- [ ] Servidor com Docker e Docker Compose instalados
- [ ] Repositório clonado
- [ ] Arquivo `.env` configurado com variáveis corretas
- [ ] `APP_KEY` gerada
- [ ] Senhas de banco e Redis definidas
- [ ] Deploy executado com sucesso
- [ ] Containers rodando (verificar com `docker ps`)
- [ ] Health checks passando
- [ ] Reverse proxy configurado no servidor principal
- [ ] Configuração do servidor principal recarregada
- [ ] Aplicação acessível via URL final
- [ ] Assets carregando corretamente
- [ ] Sessões funcionando
- [ ] Banco de dados conectando

## 🔗 URLs Importantes

- **Aplicação:** `https://dev.nexxtecnologia.com.br/Controle-Financeiro`
- **Health Check:** `https://dev.nexxtecnologia.com.br/Controle-Financeiro/health`
- **Acesso Direto:** `http://localhost:8080/Controle-Financeiro` (apenas no servidor)

## 📞 Suporte

Para problemas específicos:

1. Verificar logs dos containers
2. Executar health checks
3. Verificar configuração do reverse proxy
4. Consultar este guia de troubleshooting

---

**Importante:** Esta configuração é específica para rodar em subdiretório. Para deploy em domínio próprio, use o `docker-compose.yml` padrão.