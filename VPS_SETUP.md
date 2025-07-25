# 🚀 Setup no VPS com AAPanel

Deploy super simples para VPS com AAPanel já instalado.

## ⚡ Setup em 2 comandos

### 1. Clone o repositório
```bash
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro
```

### 2. Execute o script
```bash
./scripts/setup-aapanel.sh
```

**Pronto!** 🎉 O script vai:
- ✅ Usar PHP/MySQL já instalados no AAPanel
- ✅ Instalar apenas Composer (se necessário)
- ✅ Configurar banco de dados
- ✅ Fazer deploy da aplicação
- ✅ Executar migrações
- ✅ Otimizar para produção

### Durante o setup você informa:
- **Domínio**: ex: `staging.meusite.com`
- **Banco de dados**: nome, usuário e senha
- **Senha root MySQL**: para criar o banco

### Após o script, configure no AAPanel:
1. **Criar website** apontando para `/www/wwwroot/seu-dominio.com/public`
2. **Configurar SSL** para seu domínio
3. **Definir PHP 8.2+** como versão

## 🌐 Após o Setup

### Acesse sua aplicação:
- **Site**: `https://seu-dominio.com`
- **Admin**: `https://seu-dominio.com/admin`

### Credenciais padrão:
- **Admin**: `admin@admin.com` / `password`
- **Demo**: `demo@demo.com` / `password`

⚠️ **IMPORTANTE**: Altere essas senhas após o primeiro login!

## 🔄 Atualizações Futuras

Para atualizar:

```bash
cd /www/wwwroot/seu-dominio.com
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
```

## 📋 Requisitos

- **VPS** com AAPanel instalado
- **PHP 8.2+** configurado no AAPanel
- **MySQL** configurado no AAPanel
- **SSH** com acesso sudo

---

**Dúvidas?** Abra uma [issue](https://github.com/PhillipNobel/Controle-Financeiro/issues) no GitHub!