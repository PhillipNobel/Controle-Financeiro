# Customização de Cores do Filament PHP

Este guia explica como personalizar as cores e estilos do Filament PHP na sua aplicação Laravel.

## Arquivos Criados

### 1. `resources/css/filament-custom.css`
Arquivo principal com todas as customizações de cores e estilos do Filament.

### 2. Modificações em `resources/css/app.css`
Adicionado import do arquivo de customização.

### 3. Modificações em `vite.config.js`
Incluído o arquivo CSS personalizado no processo de build.

## Como Usar

### 1. Compilar os Assets
Após fazer qualquer alteração nos arquivos CSS, execute:

```bash
npm run dev
# ou para produção
npm run build
```

### 2. Personalizar Cores

No arquivo `resources/css/filament-custom.css`, você pode modificar as variáveis CSS no início do arquivo:

```css
:root {
    /* Cores primárias - substitua pelos valores hexadecimais das suas cores preferidas */
    --primary-500: #3b82f6;  /* Azul padrão - mude para sua cor preferida */
    --primary-600: #2563eb;
    --primary-700: #1d4ed8;
    
    /* Cores de sucesso */
    --success-500: #22c55e;  /* Verde padrão */
    
    /* Cores de perigo */
    --danger-500: #ef4444;   /* Vermelho padrão */
    
    /* Cores de aviso */
    --warning-500: #f59e0b;  /* Amarelo padrão */
}
```

### 3. Exemplos de Paletas de Cores

#### Paleta Azul Corporativo
```css
--primary-500: #1e40af;
--primary-600: #1d4ed8;
--primary-700: #1e3a8a;
```

#### Paleta Verde Natureza
```css
--primary-500: #059669;
--primary-600: #047857;
--primary-700: #065f46;
```

#### Paleta Roxo Moderno
```css
--primary-500: #7c3aed;
--primary-600: #6d28d9;
--primary-700: #5b21b6;
```

#### Paleta Laranja Vibrante
```css
--primary-500: #ea580c;
--primary-600: #dc2626;
--primary-700: #b91c1c;
```

## Componentes Customizados

O arquivo inclui customizações para:

- **Sidebar/Navegação**: Cores de fundo e hover
- **Botões**: Primários, sucesso, perigo e aviso
- **Formulários**: Campos de input e foco
- **Tabelas**: Linhas e hover
- **Cards/Painéis**: Fundo e bordas
- **Notificações**: Cores por tipo
- **Widgets**: Dashboard e estatísticas
- **Badges**: Cores por categoria

## Modo Escuro

O arquivo inclui suporte automático para modo escuro baseado na preferência do sistema:

```css
@media (prefers-color-scheme: dark) {
    /* Customizações para modo escuro */
}
```

## Responsividade

Incluídas customizações para dispositivos móveis:

```css
@media (max-width: 768px) {
    /* Ajustes para mobile */
}
```

## Dicas de Customização

### 1. Testando Cores
- Use ferramentas como [Coolors.co](https://coolors.co) para gerar paletas
- Teste a acessibilidade com [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

### 2. Mantendo Consistência
- Use sempre a mesma paleta de cores em toda a aplicação
- Mantenha pelo menos 3 tons de cada cor (claro, médio, escuro)

### 3. Backup
- Sempre faça backup do arquivo antes de grandes mudanças
- Use controle de versão (Git) para rastrear alterações

## Comandos Úteis

```bash
# Compilar assets em modo desenvolvimento (com watch)
npm run dev

# Compilar assets para produção
npm run build

# Limpar cache do Filament
php artisan filament:cache-components

# Publicar assets do Filament (se necessário)
php artisan filament:assets
```

## Troubleshooting

### Cores não aparecem
1. Verifique se executou `npm run dev` ou `npm run build`
2. Limpe o cache do navegador (Ctrl+F5)
3. Verifique se não há erros no console do navegador

### Conflitos de CSS
1. Use `!important` apenas quando necessário
2. Verifique a especificidade dos seletores CSS
3. Use as classes específicas do Filament quando possível

### Performance
1. Evite usar muitos `!important`
2. Minimize o número de customizações desnecessárias
3. Use variáveis CSS para manter consistência

## Recursos Adicionais

- [Documentação oficial do Filament](https://filamentphp.com/docs)
- [Tailwind CSS Colors](https://tailwindcss.com/docs/customizing-colors)
- [CSS Custom Properties (Variables)](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)

---

**Nota**: Lembre-se de sempre testar suas customizações em diferentes navegadores e dispositivos para garantir uma experiência consistente para todos os usuários.