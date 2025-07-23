# 💰 Controle Financeiro Simples

Sistema completo de controle financeiro pessoal desenvolvido em Laravel com Filament.

## ⚠️ IMPORTANTE: Ambientes de Desenvolvimento

Este projeto usa uma abordagem híbrida para ambientes:

- **🏠 Desenvolvimento Local**: 100% NATIVO (sem Docker)
- **🚀 Staging/Produção**: 100% Docker

**Docker é PROIBIDO para desenvolvimento local!**

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

## 🐳 Staging/Produção (Docker)

### Pré-requisitos

- Docker 20.10+
- Docker Compose 2.0+
- Git

### Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/PhillipNobel/Controle-Financeiro.git
cd Controle-Financeiro

# 2. Execute o script de inicialização
./scripts/docker-init.sh

# 3. Acesse a aplicação
# http://localhost:8080
```

### Solução de Problemas

Se houver problemas na compilação do Redis, o script automaticamente tentará usar uma versão simplificada:

```bash
# Se o build falhar, tente manualmente:
docker-compose -f docker-compose.simple.yml up -d

# Ou use o Dockerfile simplificado:
cp Dockerfile.simple Dockerfile
docker-compose build --no-cache
```

### Usuários Padrão

Após a inicialização, você pode fazer login com:

- **Admin**: admin@admin.com / password
- **Demo**: demo@demo.com / password

## 🛠️ Desenvolvimento

### Comandos Úteis

```bash
# Ver status dos containers
docker-compose ps

# Acessar container da aplicação
docker-compose exec app bash

# Executar testes
docker-compose exec app php artisan test

# Ver logs
docker-compose logs -f

# Backup do banco
./scripts/backup-database.sh
```

### Estrutura do Projeto

```
├── app/                    # Código da aplicação Laravel
├── docker/                 # Configurações Docker
├── scripts/                # Scripts utilitários
├── docker-compose.yml      # Ambiente de desenvolvimento
├── docker-compose.prod.yml # Ambiente de produção
├── Dockerfile             # Imagem da aplicação
└── DOCKER.md              # Documentação Docker detalhada
```

## 🚀 Deploy em Produção

### Deploy Automático

```bash
# Configure o ambiente de produção
cp .env.production .env.prod
# Edite .env.prod com suas configurações

# Execute o deploy
./scripts/deploy-production.sh
```

### Deploy Manual

Consulte o arquivo [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md) para instruções detalhadas.

## 📚 Documentação

- [📖 Guia Docker](DOCKER.md) - Configuração e uso do Docker
- [🚀 Deploy em Produção](PRODUCTION_DEPLOYMENT.md) - Guia de deploy
- [🔧 Especificações](/.kiro/specs/controle-financeiro-simples/) - Documentação técnica

## 🧪 Testes

```bash
# Executar todos os testes
docker-compose exec app php artisan test

# Testes com coverage
docker-compose exec app php artisan test --coverage
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
# Criar backup
./scripts/backup-database.sh

# Restaurar backup
./scripts/backup-database.sh restore backup_file.sql.gz

# Listar backups
./scripts/backup-database.sh list
```

## 📊 Monitoramento

```bash
# Verificar saúde da aplicação
./scripts/health-check.sh

# Ver métricas dos containers
docker stats
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

- 📖 [Documentação](DOCKER.md)
- 🐛 [Issues](https://github.com/seu-usuario/controle-financeiro/issues)
- 💬 [Discussões](https://github.com/seu-usuario/controle-financeiro/discussions)

## 🏗️ Tecnologias

- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Filament 3, Tailwind CSS
- **Banco**: MySQL 8.0
- **Cache**: Redis 7
- **Containerização**: Docker, Docker Compose
- **CI/CD**: GitHub Actions

---

Desenvolvido com ❤️ para ajudar no controle das suas finanças pessoais.