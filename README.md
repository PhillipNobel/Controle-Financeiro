# 💰 Controle Financeiro Simples

Sistema completo de controle financeiro pessoal desenvolvido em Laravel com Filament.

## ⚠️ IMPORTANTE: Política de Ambientes

Este projeto utiliza ambientes 100% nativos:

### 🏠 Desenvolvimento e Staging: 100% NATIVO

- **🏠 Desenvolvimento Local**: 100% NATIVO (PHP + MySQL + Composer nativos)
- **🚀 Staging no VPS**: 100% NATIVO (sem Docker devido às limitações de hardware)

### Por que ambientes nativos?

- **Performance Máxima**: Sem overhead de virtualização
- **Simplicidade**: Configuração direta e debugging nativo
- **Compatibilidade**: Funciona em VPS com recursos limitados
- **Velocidade**: Hot reload instantâneo e máxima responsividade

## ✨ Funcionalidades

- 📊 **Dashboard** com visão geral das finanças
- 💳 **Gestão de Contas** bancárias e carteiras
- 📝 **Controle de Transações** (receitas e despesas)
- 🏷️ **Categorização** de transações
- 📈 **Relatórios** financeiros detalhados
- 🎯 **Metas** de economia e gastos
- 🔔 **Lembretes** de pagamentos
- 👥 **Multi-usuário** com controle de acesso
- 📱 **Interface responsiva** para mobile

## 🏠 Desenvolvimento Local (NATIVO)

### Pré-requisitos

- PHP 8.3+
- MySQL (via MAMP/XAMPP/Homebrew/Laravel Herd)
- Composer
- Node.js 18+

### Instalação Rápida

```bash
# 1. Clone o repositório
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro

# 2. Instale dependências
composer install
npm install

# 3. Configure ambiente
cp .env.example .env
php artisan key:generate

# 4. Configure MySQL nativo no .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=controle_financeiro_local

# 5. Execute migrações
php artisan migrate --seed

# 6. Inicie servidor nativo
php artisan serve
# Acesse: http://localhost:8000
```

📖 **Guia completo**: [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md)

## 🚀 Staging (Nativo no VPS)

### Staging no VPS

```bash
# 1. Clone o repositório no VPS
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro

# 2. Configure ambiente de staging
cp .env.staging .env
# Edite as variáveis necessárias

# 3. Execute deploy para staging
./scripts/deploy-staging.sh

# 4. Configure SSL nativo
# Configure SSL diretamente no servidor web (Apache/Nginx)
```

### Usuários Padrão

Após a inicialização, você pode fazer login com:

- **Admin**: admin@admin.com / password
- **Demo**: demo@demo.com / password

## 🛠️ Desenvolvimento

### Comandos Úteis

```bash
# Executar testes
php artisan test

# Ver logs da aplicação
tail -f storage/logs/laravel.log

# Executar migrações
php artisan migrate

# Limpar cache
php artisan cache:clear
```

### Estrutura do Projeto

```
├── app/                    # Código da aplicação Laravel
├── scripts/                # Scripts utilitários
├── config/                 # Configurações Laravel
├── database/               # Migrações e seeders
├── resources/              # Views e assets
└── storage/                # Logs e cache
```

## � Docume ntação

- [🏠 Setup de Desenvolvimento](DEVELOPMENT_SETUP.md) - Guia de configuração local
- [🚀 Deploy para Staging](scripts/deploy-staging.sh) - Script de deploy
- [🔧 Especificações](/.kiro/specs/controle-financeiro-simples/) - Documentação técnica

## 🧪 Testes

```bash
# Executar todos os testes
php artisan test

# Testes com coverage
php artisan test --coverage
```

## 🔒 Segurança

- Autenticação via Laravel Sanctum
- Controle de acesso baseado em roles
- Validação de dados em todas as camadas
- Proteção CSRF
- Headers de segurança configurados
- Logs de auditoria

## 🛡️ Backup e Recuperação

```bash
# Criar backup do banco MySQL nativo
mysqldump -u username -p database_name > backup.sql

# Restaurar backup
mysql -u username -p database_name < backup.sql
```

## 📊 Monitoramento

```bash
# Verificar saúde da aplicação
curl http://localhost:8000/health

# Verificar logs
tail -f storage/logs/laravel.log

# Verificar status do MySQL
mysqladmin ping -u username -p
```

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🆘 Suporte

- 📖 [Setup de Desenvolvimento](DEVELOPMENT_SETUP.md)
- 🐛 [Issues](https://github.com/seu-usuario/controle-financeiro/issues)
- 💬 [Discussões](https://github.com/seu-usuario/controle-financeiro/discussions)

## 🏗️ Tecnologias

- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Filament 3, Tailwind CSS
- **Banco**: MySQL 8.0 (nativo)
- **Cache**: Redis 7 (nativo)
- **Servidor**: Apache/Nginx (nativo)
- **CI/CD**: GitHub Actions

---

Desenvolvido com ❤️ para ajudar no controle das suas finanças pessoais.