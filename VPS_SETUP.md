# 🚀 Setup Rápido no VPS

Este guia mostra como fazer o deploy do projeto no VPS de forma **super simples** usando apenas **2 comandos**.

## ⚡ Setup Automático (Recomendado)

### 1. Clone o repositório no VPS
```bash
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro
```

### 2. Execute o script de setup
```bash
./scripts/setup-vps.sh
```

**Pronto!** 🎉 O script vai:
- ✅ Instalar todas as dependências automaticamente (PHP, MySQL, Nginx, etc.)
- ✅ Configurar o banco de dados
- ✅ Configurar o Nginx
- ✅ Configurar SSL (HTTPS)
- ✅ Fazer o deploy da aplicação
- ✅ Executar todas as migrações
- ✅ Otimizar a aplicação para produção

### Durante o setup, você será perguntado sobre:
- **Domínio**: ex: `staging.meusite.com`
- **Banco de dados**: nome, usuário e senha
- **Email**: para o certificado SSL
- **Caminho da aplicação**: onde instalar (padrão: `/var/www/html/controle-financeiro`)

## 🔧 Setup Manual (Se preferir)

Se você quiser fazer o setup passo a passo manualmente:

### 1. Clone e configure dependências
```bash
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro
./scripts/deploy-staging.sh --auto-install
```

### 2. Configure manualmente
- Configure o banco de dados MySQL
- Configure o Nginx
- Configure o SSL
- Edite o arquivo `.env.staging`

## 🌐 Após o Setup

### Acesse sua aplicação:
- **Site**: `https://seu-dominio.com`
- **Admin**: `https://seu-dominio.com/admin`

### Credenciais padrão:
- **Admin**: `admin@admin.com` / `password`
- **Demo**: `demo@demo.com` / `password`

⚠️ **IMPORTANTE**: Altere essas senhas após o primeiro login!

## 🔄 Atualizações Futuras

Para atualizar a aplicação no futuro:

```bash
cd /var/www/html/controle-financeiro
git pull origin main
./scripts/deploy-staging.sh
```

## 🆘 Solução de Problemas

### Se algo der errado:
```bash
# Ver logs do deploy
tail -f /var/log/controle-financeiro-deploy.log

# Verificar status dos serviços
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status php8.2-fpm

# Fazer rollback se necessário
./scripts/deploy-staging.sh --rollback
```

### Verificar se tudo está funcionando:
```bash
./scripts/deploy-staging.sh --health-check
```

## 📋 Requisitos do VPS

### Mínimos:
- **RAM**: 1GB (recomendado 2GB+)
- **Disco**: 10GB livres
- **OS**: Ubuntu 20.04+ ou CentOS 7+
- **Acesso**: SSH com sudo

### O script instala automaticamente:
- PHP 8.2+ com extensões necessárias
- MySQL 8.0+
- Nginx
- Composer
- Node.js 18+
- Certbot (para SSL)

## 🎯 Vantagens do Setup Automático

- ⚡ **Rápido**: 2 comandos e pronto
- 🔒 **Seguro**: SSL automático, headers de segurança
- 🚀 **Otimizado**: Cache, compressão, otimizações de produção
- 🔄 **Backup**: Backup automático antes de cada deploy
- 📊 **Monitoramento**: Health checks e logs estruturados
- 🛡️ **Rollback**: Volta para versão anterior se algo der errado

---

**Dúvidas?** Abra uma [issue](https://github.com/PhillipNobel/Controle-Financeiro/issues) no GitHub!