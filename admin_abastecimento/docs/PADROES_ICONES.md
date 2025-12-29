# 🎨 Padrões de Ícones - QR Combustível

## 📌 Objetivo

Este documento define os padrões de tamanho, espaçamento e uso de ícones no sistema QR Combustível, garantindo consistência visual em toda a aplicação.

---

## 🎯 Biblioteca de Ícones

- **Biblioteca**: FontAwesome 6.4.0
- **Estilo**: Solid (`fas`)
- **CDN**: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css

---

## 📏 Tamanhos Padronizados

### 1. Page Titles (Títulos de Página)

**Contexto**: Títulos principais de páginas (h1)

```html
<div class="page-title">
    <h1>
        <i class="fas fa-key"></i>Gerenciamento de Licenças
    </h1>
</div>
```

**CSS Aplicado**:
```css
.page-title h1 i {
    font-size: var(--font-xl); /* 1.25rem / 20px */
}
```

**Regras**:
- ✅ Ícone diretamente adjacente ao texto (sem espaço)
- ✅ Tamanho: `var(--font-xl)` = 1.25rem
- ❌ NÃO usar classes de margem (me-2, me-3)

---

### 2. Modal Titles (Títulos de Modais)

**Contexto**: Títulos de modais (h5)

```html
<h5 class="modal-title">
    <i class="fas fa-key me-2"></i>Gerar Nova Licença
</h5>
```

**CSS Aplicado**:
```css
.modal-title i {
    margin-right: 0.5rem; /* me-2 do Bootstrap */
    font-size: var(--font-xl); /* 1.25rem / 20px */
}
```

**Regras**:
- ✅ Usar classe `me-2` para espaçamento
- ✅ Tamanho: `var(--font-xl)` = 1.25rem
- ✅ Sempre incluir ícone nos títulos de modal

---

### 3. Botões Padrão (btn-primary, btn-secondary)

**Contexto**: Botões de ação principais

```html
<button class="btn btn-primary">
    <i class="fas fa-plus"></i> Gerar Nova Licença
</button>
```

**CSS Aplicado**:
```css
.btn-primary i,
.btn-secondary i {
    font-size: var(--font-sm); /* 0.875rem / 14px */
}
```

**Regras**:
- ✅ Tamanho: `var(--font-sm)` = 0.875rem
- ✅ Espaço entre ícone e texto já definido por `gap: 0.5rem` no botão
- ❌ NÃO usar classes de margem adicionais

---

### 4. Botões Pequenos (btn-sm)

**Contexto**: Botões compactos

```html
<button class="btn btn-primary btn-sm">
    <i class="fas fa-plus"></i> Novo
</button>
```

**CSS Aplicado**:
```css
.btn-sm i {
    font-size: var(--font-xs); /* 0.75rem / 12px */
}
```

**Regras**:
- ✅ Tamanho: `var(--font-xs)` = 0.75rem
- ✅ Automático quando `btn-sm` é usado
- ✅ Aplicado a botões de ação em tabelas

---

### 5. Botões de Ação em Tabelas

**Contexto**: Botões info, warning, danger

```html
<button class="btn btn-sm btn-info" title="Ver Detalhes">
    <i class="fas fa-eye"></i>
</button>
```

**CSS Aplicado**:
```css
.btn-info i,
.btn-warning i,
.btn-danger i {
    font-size: inherit; /* Herda do pai */
}
```

**Regras**:
- ✅ Tamanho herdado do botão pai
- ✅ Usar apenas ícone (sem texto) em botões de ação
- ✅ Sempre adicionar `title` para acessibilidade

---

## 🎨 Classes de Espaçamento

### Quando usar `me-2`

**✅ USAR em**:
- Títulos de modais (`.modal-title`)
- Botões com `me-2` explícito no HTML (casos específicos)

**❌ NÃO USAR em**:
- Page titles (h1)
- Botões padrão (já possuem `gap`)
- Botões btn-sm em tabelas

---

## 📋 Checklist de Implementação

Ao adicionar ícones em novos componentes, verificar:

- [ ] Biblioteca FontAwesome 6.4.0 está carregada?
- [ ] Classe `fas` está presente?
- [ ] Tamanho correto para o contexto?
- [ ] Espaçamento apropriado (me-2 quando necessário)?
- [ ] Ícone semanticamente apropriado?
- [ ] `title` adicionado em botões só com ícone?

---

## 🔍 Variáveis CSS de Tamanho

```css
/* Definidas em style.css - Linha 30+ */
--font-xs: 0.75rem;   /* 12px - Botões pequenos */
--font-sm: 0.875rem;  /* 14px - Botões padrão */
--font-base: 1rem;    /* 16px - Texto base */
--font-lg: 1.125rem;  /* 18px - Texto grande */
--font-xl: 1.25rem;   /* 20px - Títulos e ícones destacados */
--font-2xl: 1.5rem;   /* 24px - Títulos maiores */
--font-3xl: 1.875rem; /* 30px - Títulos principais */
```

---

## 🛠️ Exemplos de Uso

### Exemplo 1: Página Completa

```html
<!-- Page Title -->
<div class="page-title">
    <h1>
        <i class="fas fa-key"></i>Gerenciamento de Licenças
    </h1>
</div>

<!-- Botões de Ação -->
<button class="btn btn-primary btn-sm">
    <i class="fas fa-plus"></i> Gerar Nova Licença
</button>

<button class="btn btn-secondary btn-sm">
    <i class="fas fa-sync"></i> Atualizar
</button>

<!-- Modal -->
<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-key me-2"></i>Gerar Nova Licença
    </h5>
</div>

<div class="modal-footer">
    <button class="btn btn-secondary">
        <i class="fas fa-times me-2"></i>Cancelar
    </button>
    <button class="btn btn-primary">
        <i class="fas fa-key me-2"></i>Gerar Licença
    </button>
</div>
```

### Exemplo 2: Tabela com Ações

```javascript
// Botões de ação em JavaScript
function getAcoesButtons(item) {
    return `
        <button class="btn btn-sm btn-info" onclick="ver(${item.id})" title="Ver Detalhes">
            <i class="fas fa-eye"></i>
        </button>
        <button class="btn btn-sm btn-warning" onclick="editar(${item.id})" title="Editar">
            <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-sm btn-danger" onclick="excluir(${item.id})" title="Excluir">
            <i class="fas fa-trash"></i>
        </button>
    `;
}
```

---

## ✅ Vantagens da Padronização

1. **Consistência Visual**: Todos os ícones seguem o mesmo padrão de tamanho
2. **Manutenibilidade**: Fácil identificar e corrigir inconsistências
3. **Performance**: CSS centralizado, sem duplicação de estilos
4. **Acessibilidade**: Tamanhos adequados para leitura
5. **Escalabilidade**: Novos componentes seguem padrões estabelecidos

---

## 📝 Notas Importantes

- **Não usar classes de tamanho do FontAwesome** (fa-2x, fa-3x, fa-lg, etc.) - usar variáveis CSS
- **Sempre testar em diferentes resoluções** para garantir legibilidade
- **Manter consistência** entre páginas similares
- **Documentar exceções** quando necessário usar tamanhos diferentes

---

**Última atualização**: 2024
**Responsável**: Equipe de Desenvolvimento QR Combustível
