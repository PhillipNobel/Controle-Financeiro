# Requirements Document

## Introduction

Este documento define os requisitos para um aplicativo simples de controle financeiro destinado a pequenas empresas com poucas despesas. O sistema será desenvolvido usando Laravel como framework backend e Filament PHP para a interface administrativa. O foco é na simplicidade e facilidade de uso, permitindo o registro e controle básico de transações financeiras organizadas por categorias (carteiras).

## Requirements

### Requirement 1

**User Story:** Como usuário de uma pequena empresa, eu quero registrar transações financeiras com informações básicas, para que eu possa manter um controle simples das minhas finanças.

#### Acceptance Criteria

1. WHEN o usuário acessa o sistema THEN o sistema SHALL apresentar uma interface para cadastro de transações
2. WHEN o usuário preenche os campos obrigatórios (Item, Data, Quantidade, Valor, Carteira) THEN o sistema SHALL validar e salvar a transação
3. WHEN o usuário não preenche campos obrigatórios THEN o sistema SHALL exibir mensagens de erro específicas
4. WHEN o usuário insere valores numéricos THEN o sistema SHALL aceitar apenas números válidos para Quantidade e Valor

### Requirement 2

**User Story:** Como usuário, eu quero organizar minhas transações por categorias (carteiras), para que eu possa separar diferentes tipos de despesas e receitas.

#### Acceptance Criteria

1. WHEN o usuário cria uma nova transação THEN o sistema SHALL permitir selecionar ou criar uma carteira
2. WHEN o usuário visualiza transações THEN o sistema SHALL agrupar e filtrar por carteira
3. WHEN o usuário cria uma nova carteira THEN o sistema SHALL validar que o nome é único
4. IF uma carteira possui transações THEN o sistema SHALL impedir sua exclusão

### Requirement 3

**User Story:** Como usuário, eu quero visualizar uma lista de todas as transações registradas, para que eu possa acompanhar o histórico financeiro da empresa.

#### Acceptance Criteria

1. WHEN o usuário acessa a lista de transações THEN o sistema SHALL exibir todas as transações ordenadas por data
2. WHEN o usuário visualiza a lista THEN o sistema SHALL mostrar Item, Data, Quantidade, Valor e Carteira para cada transação
3. WHEN o usuário clica em uma transação THEN o sistema SHALL permitir visualizar detalhes completos
4. WHEN a lista possui muitas transações THEN o sistema SHALL implementar paginação

### Requirement 4

**User Story:** Como usuário, eu quero editar e excluir transações existentes, para que eu possa corrigir erros ou remover registros desnecessários.

#### Acceptance Criteria

1. WHEN o usuário seleciona uma transação THEN o sistema SHALL permitir edição de todos os campos
2. WHEN o usuário salva alterações THEN o sistema SHALL validar os dados e atualizar a transação
3. WHEN o usuário solicita exclusão THEN o sistema SHALL pedir confirmação antes de remover
4. WHEN uma transação é excluída THEN o sistema SHALL remover permanentemente do banco de dados

### Requirement 5

**User Story:** Como usuário, eu quero ter uma interface administrativa intuitiva usando Filament PHP, para que eu possa gerenciar o sistema de forma eficiente.

#### Acceptance Criteria

1. WHEN o usuário acessa o painel administrativo THEN o sistema SHALL apresentar interface Filament com navegação clara
2. WHEN o usuário navega entre seções THEN o sistema SHALL manter consistência visual e funcional
3. WHEN o usuário realiza operações THEN o sistema SHALL fornecer feedback visual adequado
4. WHEN o sistema carrega dados THEN o sistema SHALL otimizar performance para pequenos volumes de dados

### Requirement 6

**User Story:** Como usuário, eu quero ver resumos básicos das minhas finanças através de widgets no dashboard, para que eu possa ter uma visão geral rápida da situação financeira.

#### Acceptance Criteria

1. WHEN o usuário acessa o dashboard THEN o sistema SHALL exibir widgets com total de receitas e despesas
2. WHEN o usuário visualiza o dashboard THEN o sistema SHALL mostrar widget comparativo de despesas vs receitas
3. WHEN o usuário visualiza resumos THEN o sistema SHALL exibir widget com a despesa mais custosa
4. WHEN o usuário filtra por período THEN o sistema SHALL recalcular os widgets automaticamente
5. WHEN não há transações THEN o sistema SHALL exibir mensagem informativa nos widgets

### Requirement 7

**User Story:** Como administrador do sistema, eu quero gerenciar usuários com diferentes níveis de permissão, para que eu possa controlar o acesso às funcionalidades do sistema.

#### Acceptance Criteria

1. WHEN o sistema é instalado THEN o sistema SHALL criar três tipos de usuário: super_admin, admin, editor
2. WHEN um super_admin acessa o sistema THEN o sistema SHALL permitir acesso total a todas as funcionalidades
3. WHEN um admin acessa o sistema THEN o sistema SHALL permitir gerenciar transações, carteiras e visualizar relatórios
4. WHEN um editor acessa o sistema THEN o sistema SHALL permitir apenas criar e editar transações
5. WHEN um usuário tenta acessar funcionalidade sem permissão THEN o sistema SHALL negar acesso e exibir mensagem apropriada
6. WHEN um super_admin gerencia usuários THEN o sistema SHALL permitir criar, editar e excluir usuários
7. WHEN um usuário é criado THEN o sistema SHALL validar email único e senha segura

### Requirement 8

**User Story:** Como usuário, eu quero cadastrar e gerenciar informações da empresa, para que eu possa manter dados organizacionais atualizados no sistema.

#### Acceptance Criteria

1. WHEN o usuário acessa configurações da empresa THEN o sistema SHALL permitir cadastrar Nome, CNPJ, Razão Social
2. WHEN o usuário preenche dados da empresa THEN o sistema SHALL validar formato do CNPJ e campos obrigatórios
3. WHEN o usuário cadastra contatos THEN o sistema SHALL permitir inserir Telefone, E-mail e Website
4. WHEN o usuário cadastra endereço THEN o sistema SHALL permitir inserir endereço completo da empresa
5. WHEN o usuário define responsável THEN o sistema SHALL permitir cadastrar pessoa responsável
6. WHEN dados da empresa são salvos THEN o sistema SHALL validar formato de email e telefone
7. IF usuário não tem permissão THEN o sistema SHALL restringir acesso às configurações da empresa

### Requirement 9

**User Story:** Como desenvolvedor ou sistema externo, eu quero acessar uma API REST para gerenciar transações, para que eu possa integrar o sistema com outras aplicações.

#### Acceptance Criteria

1. WHEN uma requisição POST é feita para /api/transactions THEN o sistema SHALL criar uma nova transação
2. WHEN uma requisição GET é feita para /api/transactions THEN o sistema SHALL retornar lista de todas as transações
3. WHEN uma requisição GET é feita para /api/transactions/{id} THEN o sistema SHALL retornar detalhes de uma transação específica
4. WHEN uma requisição PUT é feita para /api/transactions/{id} THEN o sistema SHALL atualizar a transação especificada
5. WHEN uma requisição DELETE é feita para /api/transactions/{id} THEN o sistema SHALL remover a transação especificada
6. WHEN requisições são feitas à API THEN o sistema SHALL retornar respostas em formato JSON
7. WHEN dados inválidos são enviados THEN o sistema SHALL retornar erros de validação apropriados
8. WHEN a API é acessada THEN o sistema SHALL implementar autenticação via token

### Requirement 10

**User Story:** Como administrador do sistema, eu quero que o sistema utilize MySQL como banco de dados, para que eu possa ter uma solução robusta e confiável para armazenamento de dados.

#### Acceptance Criteria

1. WHEN o sistema é configurado THEN o sistema SHALL utilizar MySQL como banco de dados principal
2. WHEN o sistema é instalado THEN o sistema SHALL criar as tabelas necessárias via migrations do Laravel
3. WHEN dados são persistidos THEN o sistema SHALL utilizar conexão MySQL configurada
4. WHEN o sistema realiza consultas THEN o sistema SHALL otimizar queries para performance no MySQL
5. WHEN backup é necessário THEN o sistema SHALL ser compatível com ferramentas padrão do MySQL

### Requirement 11

**User Story:** Como desenvolvedor, eu quero que o sistema seja containerizado com Docker, para que eu possa garantir consistência entre ambientes e facilitar o deploy em produção.

#### Acceptance Criteria

1. WHEN o sistema é configurado THEN o sistema SHALL incluir arquivos Docker (Dockerfile e docker-compose.yml)
2. WHEN o ambiente é iniciado THEN o sistema SHALL criar containers separados para aplicação, banco de dados e outros serviços
3. WHEN o sistema é executado em produção THEN o sistema SHALL utilizar configurações otimizadas para ambiente produtivo
4. WHEN novos deploys são realizados THEN o sistema SHALL permitir atualizações com mínimo downtime
5. WHEN o ambiente é configurado THEN o sistema SHALL incluir scripts de inicialização e healthchecks
6. WHEN containers são criados THEN o sistema SHALL seguir boas práticas de segurança e otimização
7. WHEN variáveis de ambiente são necessárias THEN o sistema SHALL fornecer exemplos de configuração

### Requirement 12

**User Story:** Como usuário, eu quero imprimir um extrato consolidado das transações mensais, para que eu possa ter uma visão detalhada dos movimentos financeiros e saldos do período.

#### Acceptance Criteria

1. WHEN o usuário solicita impressão de extrato THEN o sistema SHALL permitir selecionar o mês e ano desejado
2. WHEN o extrato é gerado THEN o sistema SHALL listar todas as transações do período ordenadas por data
3. WHEN o extrato é exibido THEN o sistema SHALL mostrar saldo inicial, todas as movimentações e saldo final
4. WHEN existem múltiplas carteiras THEN o sistema SHALL permitir filtrar o extrato por carteira específica
5. WHEN o extrato é gerado THEN o sistema SHALL calcular e exibir subtotais por tipo de transação (receitas/despesas)
6. WHEN o usuário solicita THEN o sistema SHALL permitir exportar o extrato em formato PDF
7. WHEN não há transações no período THEN o sistema SHALL exibir mensagem informativa apropriada