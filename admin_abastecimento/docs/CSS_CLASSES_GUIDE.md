# Guia de Classes CSS - Sistema QR Combustível

## Cores do Sistema

### Variáveis CSS (`:root`)
```css
--primary-dark: #2f6b8f        /* Azul principal */
--primary-darker: #255a7a      /* Azul escuro (hover) */
--primary-light: #4a8ab5       /* Azul claro */
--secondary-orange: #f59b4c    /* Laranja secundário */
--secondary-green: #1f5734     /* Verde secundário */
--gray-light: #f5f5f5          /* Cinza claro */
--gray-lighter: #f9f9f9        /* Cinza mais claro */
--gray-medium: #c1c3c7         /* Cinza médio */
--gray-border: #e0e0e0         /* Cinza para bordas */
--text-primary: #333           /* Texto principal */
--text-secondary: #666         /* Texto secundário */
--white: #ffffff               /* Branco */
```

### Alinhamento com config.php
```php
COLOR_PRIMARY_2 => #2f6b8f (--primary-dark)
COLOR_SECONDARY_2 => #f59b4c (--secondary-orange)
COLOR_SECONDARY_3 => #1f5734 (--secondary-green)
```

## Classes Reutilizáveis

### Tabelas Modernas

#### `.table-modern`
Tabela padrão com visual moderno e responsivo

**Uso:**
```html
<div class="table-container">
    <table class="table-modern">
        <thead>
            <tr>
                <th>Coluna 1</th>
                <th>Coluna 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dado 1</td>
                <td>Dado 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

**Características:**
- Cabeçalho azul (#2f6b8f) com texto branco
- Linhas zebradas (pares com fundo #f8f9fa)
- Hover cinza claro (#f1f3f5)
- Primeira coluna alinhada à esquerda
- Separadores verticais entre colunas do cabeçalho
- Bordas sutis (#dee2e6)

### Container de Busca

#### `.search-container`
Container flexível para campo de busca e botões de ação

**Uso:**
```html
<div class="search-container">
    <div class="input-group search-input-group">
        <input type="text" class="form-control form-control-sm" placeholder="Buscar...">
        <button class="btn btn-secondary btn-sm" type="button">
            <i class="fas fa-search"></i>
        </button>
    </div>
    <button class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Novo
    </button>
</div>
```

**Características:**
- Display flex com gap de 10px
- Alinhamento central de itens
- Campo de busca limitado a 480px (`.search-input-group`)
- Margin-bottom de 12px

### Status Coloridos

#### Classes de Status
Use para indicar estados de validade, situação, etc.

```html
<span class="status-ativo">Ativo</span>
<span class="status-alerta">Em alerta</span>
<span class="status-vencido">Vencido</span>
```

**Cores:**
- `.status-ativo`: Verde (#1f5734)
- `.status-alerta`: Laranja (#f59b4c)
- `.status-vencido`: Vermelho (#dc3545)

### Botões de Ação

#### `.btn-action`
Botões pequenos para ações em linhas de tabela

**Uso:**
```html
<button class="btn-action btn-edit" title="Editar">
    <i class="fas fa-edit"></i>
</button>
<button class="btn-action btn-view" title="Visualizar">
    <i class="fas fa-eye"></i>
</button>
<button class="btn-action btn-delete" title="Excluir">
    <i class="fas fa-trash"></i>
</button>
```

**Variantes:**
- `.btn-edit`: Azul (#2f6b8f)
- `.btn-view`: Azul (#2f6b8f)
- `.btn-delete`: Vermelho (#dc3545)

**Comportamento:**
- Fundo branco com borda sutil
- Hover: fundo colorido, texto branco
- Leve zoom (scale 1.05) ao passar mouse
- Sombra suave no hover

### Botões Principais

#### `.btn-primary`
Botão principal do sistema

**Características:**
- Cor: #2f6b8f
- Hover: #255a7a
- Padding: 10px 20px
- Border-radius: 6px
- Font-weight: 600

#### `.btn-secondary`
Botão secundário

**Características:**
- Cor: #f59b4c
- Hover: #e68a3d
- Mesmas dimensões do primary

### Header e Sidebar

#### `.header`
Cabeçalho do sistema

**Características:**
- Background: #2f6b8f
- Altura: 72px
- Sombra leve: 0 2px 4px rgba(0,0,0,0.05)
- Position sticky

#### `.sidebar`
Menu lateral

**Características:**
- Background: #ffffff
- Borda direita: 1px solid #dee2e6
- Largura: 240px
- Links com ícones #2f6b8f
- Item ativo com borda esquerda #f59b4c e fundo #f1f3f5

### Paginação

Paginação personalizada com cores do sistema

**Características:**
- Botões brancos com borda #dee2e6
- Texto azul (#2f6b8f)
- Ativo: fundo azul, texto branco
- Hover: fundo laranja (#f59b4c), texto branco
- Desabilitado: fundo cinza (#f8f9fa)

## Estrutura de Diretórios

```
admin_abastecimento/
├── css/
│   ├── style.css              # Arquivo principal - USE ESTE!
│   ├── style.css.backup       # Backup automático
│   └── dashboard-enhancements.css
├── pages/                     # Páginas SEM estilos inline
│   ├── condutor.php
│   ├── veiculo.php
│   └── ...
└── docs/
    └── CSS_CLASSES_GUIDE.md  # Este documento
```

## Boas Práticas

### ✅ FAÇA
1. Use classes reutilizáveis do `style.css`
2. Mantenha consistência visual entre páginas
3. Use variáveis CSS para cores
4. Aplique `.table-modern` em todas as tabelas de dados
5. Use `.search-container` para campos de busca padrão
6. Aplique classes de status para indicadores visuais

### ❌ NÃO FAÇA
1. Adicionar `<style>` tags em páginas PHP
2. Usar estilos inline exceto para casos muito específicos
3. Criar cores hardcoded - use variáveis CSS
4. Duplicar estilos entre arquivos
5. Misturar classes antigas com modernas

## Migração de Páginas

Para migrar uma página com estilos inline:

1. **Identifique** estilos específicos da página
2. **Verifique** se já existem classes equivalentes no `style.css`
3. **Crie** novas classes se necessário (no `style.css`)
4. **Remova** a tag `<style>` da página
5. **Aplique** as classes CSS no HTML
6. **Teste** a aparência visual

## Exemplos de Uso

### Página de Listagem Padrão

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Entidades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title">
            <span><i class="fas fa-list"></i> Gestão de Entidades</span>
        </div>

        <div class="search-container">
            <div class="input-group search-input-group">
                <input type="text" id="searchInput" class="form-control form-control-sm" 
                       placeholder="Buscar...">
                <button class="btn btn-secondary btn-sm" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovo">
                <i class="fas fa-plus"></i> Novo
            </button>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Exemplo</td>
                        <td><span class="status-ativo">Ativo</span></td>
                        <td>
                            <button class="btn-action btn-edit"><i class="fas fa-edit"></i></button>
                            <button class="btn-action btn-view"><i class="fas fa-eye"></i></button>
                            <button class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav aria-label="Paginação">
            <ul class="pagination pagination-sm justify-content-center">
                <!-- Paginação aqui -->
            </ul>
        </nav>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
```

## Atualizações Recentes

### v2.0 - Dezembro 2025
- ✅ Paleta de cores suavizada aplicada
- ✅ Variáveis CSS atualizadas e alinhadas com config.php
- ✅ Classes `.table-modern` criadas
- ✅ Classes `.search-container` padronizadas
- ✅ Estilos inline removidos de condutor.php e veiculo.php
- ✅ Botões de ação padronizados
- ✅ Paginação personalizada
- ✅ Sidebar e header atualizados

## Suporte

Para dúvidas ou sugestões sobre o sistema de estilos:
- Consulte este documento
- Revise o arquivo `css/style.css`
- Verifique exemplos em páginas já migradas (condutor.php, veiculo.php)
