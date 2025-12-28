# Icon Picker - Seletor Visual de Ícones FontAwesome

## Descrição
Componente JavaScript reutilizável que permite selecionar ícones FontAwesome de forma visual e interativa através de um modal com grid de ícones.

## Características

### 🎨 Visual
- Grid responsivo de ícones organizados em categorias
- Preview em tempo real do ícone selecionado
- Interface Bootstrap 5 moderna e intuitiva
- Efeitos hover e animações suaves
- Busca instantânea com filtro

### ⚡ Funcionalidades
- **Seleção Visual**: Clique no ícone ou campo para abrir o seletor
- **Busca em Tempo Real**: Filtra ícones conforme você digita
- **Preview Dinâmico**: Atualização automática do ícone no campo
- **Múltiplos Campos**: Suporta vários campos na mesma página
- **Categorização**: Ícones organizados por categoria (navegação, usuários, veículos, etc.)
- **Contador**: Exibe quantidade de ícones disponíveis/filtrados

### 📦 Coleção de Ícones (100+ ícones)
- **Navegação**: home, dashboard, tachometer-alt, chart-line, chart-bar
- **Usuários**: user, users, user-circle, user-cog, id-card
- **Arquivos**: file, folder, copy, clipboard
- **Comunicação**: envelope, comment, phone
- **Configurações**: cog, wrench, tools, sliders-h
- **Segurança**: shield-alt, lock, key
- **Negócios**: building, briefcase, store, warehouse
- **Transporte**: car, truck, bus, motorcycle, bicycle, plane
- **Combustível**: gas-pump, oil-can, fire, battery-full
- **Finanças**: dollar-sign, coins, credit-card, receipt
- **Ações**: plus, edit, trash, save, download, search
- **Status**: check, times, exclamation-triangle, info-circle
- **Menu**: bars, list, sitemap, layer-group
- E muito mais...

## Instalação

### 1. Incluir o script
```html
<script src="../js/icon_picker.js"></script>
```

### 2. Estrutura HTML do campo
```html
<div class="mb-3">
    <label for="meuIcone" class="form-label">Ícone</label>
    <div class="input-group">
        <span class="input-group-text" id="meuIconePreview">
            <i class="fas fa-folder"></i>
        </span>
        <input type="text" class="form-control" id="meuIcone" 
               placeholder="Clique para escolher">
    </div>
</div>
```

### 3. Inicializar o campo
```javascript
// Esperar DOM carregar
document.addEventListener('DOMContentLoaded', function() {
    // Tornar o campo clicável
    makeIconInputClickable('meuIcone', 'meuIconePreview');
    
    // Opcional: Atualizar preview quando o campo mudar
    document.getElementById('meuIcone').addEventListener('input', function() {
        const iconClass = this.value || 'fa-folder';
        document.querySelector('#meuIconePreview i').className = 'fas ' + iconClass;
    });
});
```

## API

### Funções Principais

#### `initIconPicker()`
Inicializa o componente e cria o modal. Chamada automaticamente ao carregar a página.

#### `openIconPicker(inputId, previewId)`
Abre o modal de seleção de ícones.

**Parâmetros:**
- `inputId` (string): ID do input que receberá o valor do ícone
- `previewId` (string, opcional): ID do elemento de preview

**Exemplo:**
```javascript
openIconPicker('campoIcone', 'previewIcone');
```

#### `makeIconInputClickable(inputId, previewId)`
Torna um campo de ícone clicável para abrir o seletor.

**Parâmetros:**
- `inputId` (string): ID do input
- `previewId` (string, opcional): ID do elemento de preview

**Exemplo:**
```javascript
makeIconInputClickable('aplicacaoIcone', 'aplicacaoIconePreview');
```

#### `filterIcons(searchTerm)`
Filtra os ícones exibidos no grid.

**Parâmetros:**
- `searchTerm` (string): Termo de busca

**Exemplo:**
```javascript
filterIcons('car'); // Mostra apenas ícones relacionados a carro
```

#### `selectIcon(icon, element)`
Seleciona um ícone e fecha o modal.

**Parâmetros:**
- `icon` (string): Classe do ícone (ex: 'fa-home')
- `element` (HTMLElement): Elemento clicado no grid

## Exemplos de Uso

### Exemplo 1: Campo Simples
```html
<div class="input-group">
    <span class="input-group-text" id="iconPreview">
        <i class="fas fa-home"></i>
    </span>
    <input type="text" id="iconInput" class="form-control" value="fa-home">
</div>

<script>
makeIconInputClickable('iconInput', 'iconPreview');
</script>
```

### Exemplo 2: Múltiplos Campos
```javascript
// Inicializar vários campos
document.addEventListener('DOMContentLoaded', function() {
    makeIconInputClickable('iconeModulo', 'previewModulo');
    makeIconInputClickable('iconeSubmenu', 'previewSubmenu');
    makeIconInputClickable('iconeAplicacao', 'previewAplicacao');
});
```

### Exemplo 3: Com Validação
```javascript
document.getElementById('meuIcone').addEventListener('change', function() {
    const icon = this.value;
    if (!icon.startsWith('fa-')) {
        alert('Ícone deve começar com fa-');
        this.value = 'fa-folder';
    }
    // Atualizar preview
    document.querySelector('#previewIcone i').className = 'fas ' + this.value;
});
```

## Integração com Formulários

### Menu Manager (menu_manager.php)
```javascript
// Em menu_manager.js
$(document).ready(function() {
    setTimeout(() => {
        makeIconInputClickable('itemIcone', 'iconPreview');
    }, 100);
});
```

### Aplicações (aplicacoes.php)
```javascript
// Inicializar icon picker
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        makeIconInputClickable('aplicacaoIcone', 'aplicacaoIconePreview');
    }, 100);
    
    // Atualizar preview ao digitar
    document.getElementById('aplicacaoIcone').addEventListener('input', function() {
        const iconClass = this.value || 'fa-file';
        document.querySelector('#aplicacaoIconePreview i').className = 'fas ' + iconClass;
    });
});
```

## Customização

### Adicionar Novos Ícones
Edite o array `fontAwesomeIcons` em `icon_picker.js`:

```javascript
const fontAwesomeIcons = [
    'fa-home',
    'fa-user',
    // Adicione seus ícones aqui
    'fa-meu-novo-icone'
];
```

### Modificar Estilos
Os estilos são injetados automaticamente. Para customizar, edite a seção de estilos em `initIconPicker()`:

```javascript
const style = document.createElement('style');
style.textContent = `
    .icon-picker-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); /* Células maiores */
    }
    .icon-picker-item:hover {
        background: #your-color; /* Sua cor */
    }
`;
```

## Testes

### Testes Automatizados
Execute o script de teste:
```bash
/var/www/html/admin_abastecimento/tests/test_icon_picker.sh
```

### Testes Interativos
Acesse a página de testes:
```
http://localhost/admin_abastecimento/tests/test_icon_picker.php
```

### Validações Incluídas
✓ Modal criado corretamente  
✓ Campos clicáveis  
✓ Busca funcionando  
✓ Seleção atualizando input  
✓ Preview sincronizado  
✓ Múltiplos campos suportados  
✓ Estilos CSS aplicados  
✓ 100+ ícones disponíveis  

## Páginas Integradas

1. **Menu Manager** (`pages/menu_manager.php`)
   - Campo: `itemIcone`
   - Preview: `iconPreview`
   - Contexto: Cadastro de módulos, submenus e sub-submenus

2. **Aplicações** (`pages/aplicacoes.php`)
   - Campo: `aplicacaoIcone`
   - Preview: `aplicacaoIconePreview`
   - Contexto: Cadastro de aplicações do sistema

## Requisitos

- Bootstrap 5.3+
- FontAwesome 6.4+
- jQuery 3.6+ (opcional para menu_manager)

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Opera 76+

## Acessibilidade

- ✓ Navegação por teclado
- ✓ ARIA labels
- ✓ Alto contraste
- ✓ Títulos descritivos

## Performance

- **Modal lazy load**: Criado apenas quando necessário
- **Busca otimizada**: Filtro em tempo real sem delay
- **CSS Grid**: Layout responsivo nativo
- **Event delegation**: Listeners otimizados

## Licença

Componente interno do sistema de Abastecimento QR.

## Suporte

Para dúvidas ou problemas:
1. Consulte a página de testes interativos
2. Verifique o console do navegador para erros
3. Execute o script de testes automatizados

## Changelog

### v1.0.0 (2025-01-19)
- ✨ Lançamento inicial
- 📦 100+ ícones FontAwesome
- 🎨 Interface visual moderna
- 🔍 Busca em tempo real
- 📱 Design responsivo
- ✅ Integrado em 2 páginas (menu_manager, aplicacoes)
- 🧪 25 testes automatizados
