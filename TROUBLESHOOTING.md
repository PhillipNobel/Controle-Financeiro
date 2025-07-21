# 🛠️ Guia de Solução de Problemas

Este guia ajuda a resolver problemas comuns ao usar o sistema de Controle Financeiro com Docker.

## 🐳 Problemas de Docker

### Erro: "Cannot find autoconf"

**Problema**: Falha na compilação da extensão Redis do PHP.

**Solução**:
```bash
# Use a versão simplificada sem Redis
docker-compose -f docker-compose.simple.yml up -d

# Ou substitua o Dockerfile
cp Dockerfile.simple Dockerfile
docker-compose build --no-cache
```

### Erro: "version is obsolete"

**Problema**: Warning sobre versão obsoleta no docker-compose.yml.

**Solução**: Este é apenas um aviso e pode ser ignorado. Os arquivos já foram atualizados para remover a versão.

### Erro: "Build failed"

**Problema**: Falha geral na construção da imagem Docker.

**Soluções**:
```bash
# 1. Limpar cache do Docker
docker system prune -f

# 2. Tentar build sem cache
docker-compose build --no-cache

# 3. Usar versão simplificada
docker-compose -f docker-compose.simple.yml build --no-cache
```

## 🔧 Problemas de Configuração

### Redis não disponível

**Problema**: Aplicação não consegue conectar ao Redis.

**Solução**: Configure para usar cache de arquivo:
```bash
# Edite .env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### Banco de dados não conecta

**Problema**: Erro de conexão com MySQL.

**Soluções**:
```bash
# 1. Verificar se MySQL está rodando
docker-compose ps mysql

# 2. Verificar logs do MySQL
docker-compose logs mysql

# 3. Aguardar inicialização completa
sleep 30

# 4. Testar conexão manualmente
docker-compose exec mysql mysql -u root -psecret -e "SHOW DATABASES;"
```

### Permissões de arquivo

**Problema**: Erro de permissão em storage ou cache.

**Solução**:
```bash
# Corrigir permissões
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache
```

## 🚀 Problemas de Performance

### Build muito lento

**Problema**: Construção da imagem demora muito tempo.

**Soluções**:
```bash
# 1. Usar build paralelo
docker-compose build --parallel

# 2. Usar versão simplificada
docker-compose -f docker-compose.simple.yml build

# 3. Aumentar recursos do Docker
# Docker Desktop > Settings > Resources > Advanced
```

### Containers usando muita memória

**Problema**: Alto uso de memória pelos containers.

**Soluções**:
```bash
# 1. Verificar uso atual
docker stats

# 2. Usar versão de produção (mais otimizada)
docker-compose -f docker-compose.prod.yml up -d

# 3. Limitar recursos no docker-compose.yml
deploy:
  resources:
    limits:
      memory: 512M
```

## 🌐 Problemas de Rede

### Porta já em uso

**Problema**: Erro "port already in use" na porta 8080.

**Soluções**:
```bash
# 1. Verificar o que está usando a porta
lsof -i :8080

# 2. Mudar porta no docker-compose.yml
ports:
  - "8081:80"  # Muda para porta 8081

# 3. Parar processo que está usando a porta
sudo kill -9 <PID>
```

### Não consegue acessar aplicação

**Problema**: http://localhost:8080 não carrega.

**Soluções**:
```bash
# 1. Verificar se containers estão rodando
docker-compose ps

# 2. Verificar logs do NGINX
docker-compose logs nginx

# 3. Testar health check
curl http://localhost:8080/health

# 4. Verificar se porta está aberta
telnet localhost 8080
```

## 📱 Problemas Específicos do Sistema

### macOS: Docker muito lento

**Soluções**:
```bash
# 1. Usar volumes delegados
volumes:
  - ./:/var/www/html:delegated

# 2. Excluir node_modules do bind mount
volumes:
  - ./:/var/www/html
  - /var/www/html/node_modules
```

### Windows: Problemas de linha de comando

**Soluções**:
```bash
# 1. Usar Git Bash ou WSL2
# 2. Converter line endings
git config --global core.autocrlf false

# 3. Usar Docker Desktop com WSL2 backend
```

### Linux: Problemas de permissão

**Soluções**:
```bash
# 1. Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# 2. Reiniciar sessão
newgrp docker

# 3. Verificar permissões do socket
sudo chmod 666 /var/run/docker.sock
```

## 🔍 Comandos de Diagnóstico

### Verificação completa do sistema

```bash
# Script de health check
./scripts/health-check.sh --logs

# Verificar todas as configurações
docker-compose config

# Verificar recursos do sistema
docker system df
docker system info
```

### Logs detalhados

```bash
# Todos os logs
docker-compose logs

# Logs específicos com timestamp
docker-compose logs -f -t app

# Logs de erro apenas
docker-compose logs | grep -i error
```

### Limpeza completa

```bash
# Parar tudo e limpar
docker-compose down -v
docker system prune -a -f
docker volume prune -f

# Reconstruir do zero
docker-compose build --no-cache
docker-compose up -d
```

## 📞 Obtendo Ajuda

Se os problemas persistirem:

1. **Verifique os logs**: `./scripts/health-check.sh --logs`
2. **Documente o erro**: Copie a mensagem de erro completa
3. **Informe o ambiente**: SO, versão do Docker, etc.
4. **Abra uma issue**: https://github.com/PhillipNobel/Controle-Financeiro/issues

## 📚 Recursos Úteis

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Troubleshooting](https://docs.docker.com/compose/troubleshooting/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [PHP-FPM Configuration](https://www.php.net/manual/en/install.fpm.configuration.php)