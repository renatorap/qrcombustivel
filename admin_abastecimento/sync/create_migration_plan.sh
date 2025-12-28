#!/bin/bash

# Script para criar lista de migração de CSS inline para style.css

BASE_DIR="/var/www/html/admin_abastecimento"
MIGRATION_GUIDE="/var/www/html/admin_abastecimento/sync/CSS_MIGRATION_PLAN.md"

cat > "$MIGRATION_GUIDE" << 'EOF'
# Plano de Migração de CSS Inline

## Status Atual
Após atualização automática de cores, 13 arquivos ainda contêm CSS inline.

## Arquivos Analisados

### ✅ Páginas Standalone (Manter CSS Inline)
Estas páginas são acessadas sem o sistema principal e precisam de estilos próprios:

1. **index.php** - Página de login
   - Status: ✅ Cores atualizadas
   - Ação: Manter CSS inline (página standalone)

2. **reset_password.php** - Reset de senha
   - Status: ✅ Cores atualizadas
   - Ação: Manter CSS inline (página standalone)

### 🔄 Páginas do Sistema (Migrar para style.css)

#### Alta Prioridade - Usar Classes Reutilizáveis

3. **pages/relatorio_condutores.php**
   - CSS: ~50 linhas
   - Ação: Remover - usar `.table-modern`, `.search-container`
   - Classes já disponíveis em style.css

4. **pages/relatorio_veiculos.php**
   - CSS: ~50 linhas
   - Ação: Remover - usar `.table-modern`, `.search-container`
   - Classes já disponíveis em style.css

5. **pages/relatorio_consumo.php**
   - CSS: ~50 linhas
   - Ação: Remover - usar `.table-modern`, `.search-container`
   - Classes já disponíveis em style.css

6. **pages/relatorio_extrato_abastecimento.php**
   - CSS: ~80 linhas (filtros específicos)
   - Ação: Mover estilos de filtro para style.css como `.filter-grid-4cols`
   - Usar `.table-modern` para tabela

7. **pages/relatorio_cracha.php**
   - CSS: ~60 linhas (cards de seleção)
   - Ação: Criar classe `.selection-card` em style.css
   - Remover CSS inline

8. **pages/relatorio_veiculo_qrcode.php**
   - CSS: ~60 linhas (cards de seleção)
   - Ação: Usar mesma classe `.selection-card`
   - Remover CSS inline

#### Média Prioridade - CSS Específico

9. **pages/menu_manager.php**
   - CSS: ~40 linhas (drag & drop)
   - Ação: Mover para style.css como `.menu-manager-*`
   - Funcionalidade específica

10. **pages/permissoes.php**
    - CSS: ~30 linhas (matriz de permissões)
    - Ação: Mover para style.css como `.permissions-matrix`
    - Funcionalidade específica

11. **pages/ativar_licenca.php**
    - CSS: ~100 linhas (form de licença)
    - Ação: Mover para style.css como `.license-form`
    - Página específica

#### Baixa Prioridade - Componentes

12. **includes/header.php**
    - CSS: ~25 linhas (seletor de cliente)
    - Ação: Já usa variáveis CSS - OK manter
    - Componente reutilizável

13. **api/licenca.php**
    - CSS: Página de retorno JSON/HTML
    - Ação: Revisar necessidade

## Novas Classes a Criar em style.css

### 1. Cards de Seleção
```css
.selection-card {
    /* Para relatório_cracha.php e relatorio_veiculo_qrcode.php */
}
```

### 2. Grid de Filtros 4 Colunas
```css
.filter-grid-4cols {
    /* Para relatorio_extrato_abastecimento.php */
}
```

### 3. Matriz de Permissões
```css
.permissions-matrix {
    /* Para permissoes.php */
}
```

### 4. Gerenciador de Menu
```css
.menu-manager-container {
    /* Para menu_manager.php */
}
```

### 5. Formulário de Licença
```css
.license-activation-form {
    /* Para ativar_licenca.php */
}
```

## Ordem de Execução

### Fase 1 - Relatórios Simples (Usar Classes Existentes)
1. ✅ relatorio_condutores.php - usar `.table-modern`
2. relatorio_veiculos.php - usar `.table-modern`
3. relatorio_consumo.php - usar `.table-modern`

### Fase 2 - Criar Classes Novas
4. Criar `.selection-card` em style.css
5. Migrar relatorio_cracha.php
6. Migrar relatorio_veiculo_qrcode.php

### Fase 3 - Filtros e Grid
7. Criar `.filter-grid-4cols` em style.css
8. Migrar relatorio_extrato_abastecimento.php

### Fase 4 - Páginas Específicas
9. Criar `.permissions-matrix` em style.css
10. Migrar permissoes.php
11. Criar `.menu-manager-*` em style.css
12. Migrar menu_manager.php
13. Criar `.license-activation-form` em style.css
14. Migrar ativar_licenca.php

## Comandos Úteis

### Verificar cores atualizadas
```bash
grep -r "#2f6b8f\|#f59b4c\|#255a7a" pages/*.php
```

### Contar linhas de CSS inline
```bash
find pages -name "*.php" -exec sh -c 'echo "$1: $(sed -n "/<style>/,/<\/style>/p" "$1" | wc -l) linhas"' _ {} \;
```

### Remover backups de cores
```bash
find /var/www/html/admin_abastecimento -name "*.bak_colors" -delete
```

## Benefícios Esperados

- ✅ Redução de ~500 linhas de CSS duplicado
- ✅ Manutenção centralizada em 1 arquivo
- ✅ Consistência visual total
- ✅ Performance melhorada (cache)
- ✅ Facilita futuras atualizações de design

## Progresso

- [x] Cores atualizadas (13 arquivos)
- [x] condutor.php migrado
- [x] veiculo.php migrado
- [ ] relatorio_veiculos.php
- [ ] relatorio_consumo.php
- [ ] relatorio_cracha.php
- [ ] relatorio_veiculo_qrcode.php
- [ ] relatorio_extrato_abastecimento.php
- [ ] permissoes.php
- [ ] menu_manager.php
- [ ] ativar_licenca.php

**Total: 2/11 páginas migradas (18%)**

EOF

echo "=================================================="
echo "  PLANO DE MIGRAÇÃO CRIADO"
echo "=================================================="
echo ""
echo "Arquivo criado: $MIGRATION_GUIDE"
echo ""
echo "Para visualizar:"
echo "  cat $MIGRATION_GUIDE"
echo ""
echo "Resumo:"
echo "  - 13 arquivos analisados"
echo "  - 2 páginas standalone (manter CSS inline)"
echo "  - 11 páginas para migrar"
echo "  - 2 já migradas (condutor.php, veiculo.php)"
echo "  - 9 restantes"
echo ""
echo "Próximo passo: Revisar o plano e executar migração fase por fase"
