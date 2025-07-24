# Requirements Document

## Introduction

Este documento define os requisitos para configurar adequadamente os ambientes do sistema de Controle Financeiro, estabelecendo uma estrutura clara para desenvolvimento local nativo e staging no VPS usando Docker.

## Requirements

### Requirement 1

**User Story:** Como desenvolvedor, eu quero ter um ambiente de desenvolvimento local 100% nativo (sem Docker), para que eu possa desenvolver e testar funcionalidades com máxima velocidade e simplicidade.

#### Acceptance Criteria

1. WHEN o desenvolvedor executa `composer install` THEN o sistema SHALL instalar todas as dependências localmente sem qualquer uso de Docker
2. WHEN o desenvolvedor executa `php artisan serve` THEN o sistema SHALL iniciar o servidor de desenvolvimento local nativo na porta 8000
3. WHEN o desenvolvedor configura o banco de dados local THEN o sistema SHALL usar MySQL nativo instalado via MAMP/XAMPP/Homebrew/Laravel Herd
4. WHEN o desenvolvedor executa testes THEN o sistema SHALL usar SQLite em memória para velocidade máxima
5. WHEN o desenvolvedor trabalha no código THEN o sistema SHALL ter hot reload automático instantâneo sem containers
6. WHEN o desenvolvedor precisa de ferramentas auxiliares THEN o sistema SHALL usar ferramentas nativas locais (MailHog nativo, Redis local opcional)
7. WHEN o desenvolvedor faz debugging THEN o sistema SHALL usar Xdebug nativo configurado para IDEs locais
8. WHEN o desenvolvedor inicia o projeto THEN o sistema SHALL NUNCA usar Docker para desenvolvimento local
9. WHEN há arquivos Docker no projeto THEN o sistema SHALL garantir que são APENAS para staging, nunca para desenvolvimento local
10. WHEN há docker-compose.local.yml ou scripts Docker para desenvolvimento THEN o sistema SHALL removê-los completamente do projeto

### Requirement 2

**User Story:** Como desenvolvedor, eu quero configurar facilmente o ambiente de staging no VPS usando Docker exclusivamente, para que eu possa testar a aplicação em um ambiente otimizado e controlado.

#### Acceptance Criteria

1. WHEN o sistema é deployado no VPS THEN o sistema SHALL usar Docker exclusivamente para staging (NUNCA para desenvolvimento local)
2. WHEN o Docker é executado no VPS THEN o sistema SHALL usar MySQL containerizado para performance de staging
3. WHEN o ambiente de staging é configurado THEN o sistema SHALL ter logs detalhados habilitados e estruturados
4. WHEN o sistema roda em staging THEN o sistema SHALL usar HTTPS com certificado válido (Let's Encrypt ou similar)
5. WHEN o sistema roda em staging THEN o sistema SHALL usar configurações simplificadas de cache e sessões baseadas em arquivo
6. WHEN o desenvolvedor faz deploy THEN o sistema SHALL usar Docker apenas para staging, NUNCA local
7. WHEN o ambiente de staging é iniciado THEN o sistema SHALL ter health checks robustos para monitoramento
8. WHEN há configurações Docker THEN o sistema SHALL garantir que são APENAS para staging, NUNCA desenvolvimento
9. WHEN há docker-compose files THEN o sistema SHALL ter APENAS docker-compose.yml (staging)
10. WHEN há arquivos docker-compose.local.yml THEN o sistema SHALL removê-los completamente pois desenvolvimento é 100% nativo

### Requirement 3

**User Story:** Como administrador do sistema, eu quero ter configurações de ambiente bem definidas, para que eu possa facilmente distinguir entre desenvolvimento local e staging.

#### Acceptance Criteria

1. WHEN o sistema inicia THEN o sistema SHALL detectar automaticamente o ambiente baseado em variáveis
2. WHEN está em desenvolvimento THEN o sistema SHALL mostrar debug detalhado e usar configurações otimizadas para velocidade
3. WHEN está em staging THEN o sistema SHALL usar configurações otimizadas para performance com logs verbosos
4. IF o ambiente não for detectado THEN o sistema SHALL usar configuração de desenvolvimento por padrão

### Requirement 4

**User Story:** Como desenvolvedor, eu quero scripts de deploy automatizados, para que eu possa facilmente fazer deploy do código local para o ambiente de staging.

#### Acceptance Criteria

1. WHEN o desenvolvedor executa script de deploy THEN o sistema SHALL fazer push do código para o repositório
2. WHEN o código é enviado para o repositório THEN o sistema SHALL permitir deploy automático no VPS
3. WHEN o deploy é executado THEN o sistema SHALL fazer backup do ambiente atual antes de atualizar
4. IF o deploy falhar THEN o sistema SHALL permitir rollback automático para versão anterior
5. WHEN o deploy é concluído THEN o sistema SHALL executar testes de smoke no ambiente de staging

### Requirement 5

**User Story:** Como desenvolvedor, eu quero configurações de banco de dados flexíveis, para que eu possa usar diferentes bancos em diferentes ambientes sem conflitos.

#### Acceptance Criteria

1. WHEN está em desenvolvimento local THEN o sistema SHALL usar MySQL nativo instalado localmente
2. WHEN o desenvolvedor executa testes THEN o sistema SHALL usar SQLite em memória para velocidade
3. WHEN está em staging THEN o sistema SHALL usar MySQL via Docker com configurações de performance
4. IF houver migração entre ambientes THEN o sistema SHALL manter compatibilidade de dados

### Requirement 6

**User Story:** Como desenvolvedor, eu quero documentação clara dos ambientes, para que eu e outros desenvolvedores possam configurar e usar os ambientes corretamente.

#### Acceptance Criteria

1. WHEN um novo desenvolvedor acessa o projeto THEN o sistema SHALL ter documentação clara de setup local
2. WHEN é necessário fazer deploy THEN o sistema SHALL ter guia passo-a-passo para staging
3. WHEN há problemas THEN o sistema SHALL ter seção de troubleshooting para cada ambiente
4. WHEN há mudanças de configuração THEN o sistema SHALL ter changelog documentado
5. IF há dependências específicas THEN o sistema SHALL listar todos os pré-requisitos claramente