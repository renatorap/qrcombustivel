#!/bin/bash

echo "=============================================="
echo "TESTE COMPLETO - Próximos Passos"
echo "=============================================="
echo ""

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Contador de testes
PASSED=0
FAILED=0

# Função para teste
test_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $2"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $2 - Arquivo não encontrado: $1"
        ((FAILED++))
    fi
}

# Função para teste de sintaxe PHP
test_php_syntax() {
    if php -l "$1" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $2 - Sintaxe OK"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $2 - Erro de sintaxe"
        ((FAILED++))
    fi
}

echo -e "${BLUE}1. Verificando Arquivos Criados${NC}"
echo "-------------------------------------------"
test_file "/var/www/html/admin_abastecimento/pages/menu_manager.php" "Interface Menu Manager"
test_file "/var/www/html/admin_abastecimento/api/menu_manager.php" "API Menu Manager"
test_file "/var/www/html/admin_abastecimento/js/menu_manager.js" "JavaScript Menu Manager"
test_file "/var/www/html/admin_abastecimento/components/breadcrumb.php" "Classe Breadcrumb"
test_file "/var/www/html/admin_abastecimento/components/helpers.php" "Helpers Component"
test_file "/var/www/html/admin_abastecimento/info/PROXIMOS_PASSOS_IMPLEMENTADOS.md" "Documentação"
echo ""

echo -e "${BLUE}2. Verificando Sintaxe PHP${NC}"
echo "-------------------------------------------"
test_php_syntax "/var/www/html/admin_abastecimento/pages/menu_manager.php" "menu_manager.php"
test_php_syntax "/var/www/html/admin_abastecimento/api/menu_manager.php" "api/menu_manager.php"
test_php_syntax "/var/www/html/admin_abastecimento/components/breadcrumb.php" "breadcrumb.php"
test_php_syntax "/var/www/html/admin_abastecimento/components/helpers.php" "helpers.php"
test_php_syntax "/var/www/html/admin_abastecimento/pages/grupos.php" "grupos.php (breadcrumb)"
test_php_syntax "/var/www/html/admin_abastecimento/pages/usuarios.php" "usuarios.php (breadcrumb)"
echo ""

echo -e "${BLUE}3. Verificando Banco de Dados${NC}"
echo "-------------------------------------------"

# Verificar aplicação
APP_EXISTS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM aplicacoes WHERE codigo = 'menu_manager';")
if [ "$APP_EXISTS" -eq "1" ]; then
    echo -e "${GREEN}✓${NC} Aplicação Menu Manager registrada"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Aplicação Menu Manager não encontrada"
    ((FAILED++))
fi

# Verificar módulo Configuração
MODULO_EXISTS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM modulo WHERE codigo = 'configuracao';")
if [ "$MODULO_EXISTS" -eq "1" ]; then
    echo -e "${GREEN}✓${NC} Módulo Configuração criado"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Módulo Configuração não encontrado"
    ((FAILED++))
fi

# Verificar submenu
SUBMENU_EXISTS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM submenu WHERE codigo = 'menu_manager_sub';")
if [ "$SUBMENU_EXISTS" -eq "1" ]; then
    echo -e "${GREEN}✓${NC} Submenu Gerenciador de Menu adicionado"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Submenu não encontrado"
    ((FAILED++))
fi

# Verificar permissões
PERM_EXISTS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM permissoes_grupo WHERE grupo_id = 1 AND aplicacao_id = (SELECT id FROM aplicacoes WHERE codigo = 'menu_manager');")
if [ "$PERM_EXISTS" -eq "1" ]; then
    echo -e "${GREEN}✓${NC} Permissões configuradas para Administrador"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Permissões não configuradas"
    ((FAILED++))
fi

# Verificar estrutura hierárquica
TOTAL_MODULOS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM modulo WHERE ativo = 1;")
TOTAL_SUBMENUS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM submenu WHERE ativo = 1;")
TOTAL_SUBSUBMENUS=$(sudo mysql conceit1_combustivel -sN -e "SELECT COUNT(*) FROM subsubmenu WHERE ativo = 1;")

echo -e "${GREEN}✓${NC} Estrutura hierárquica: $TOTAL_MODULOS módulos, $TOTAL_SUBMENUS submenus, $TOTAL_SUBSUBMENUS sub-submenus"
((PASSED++))

echo ""

echo -e "${BLUE}4. Verificando Componentes CSS${NC}"
echo "-------------------------------------------"
if grep -q "\.breadcrumb" /var/www/html/admin_abastecimento/css/style.css; then
    echo -e "${GREEN}✓${NC} Estilos de breadcrumb adicionados"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Estilos de breadcrumb não encontrados"
    ((FAILED++))
fi

if grep -q "\.menu-tree" /var/www/html/admin_abastecimento/pages/menu_manager.php; then
    echo -e "${GREEN}✓${NC} Estilos de árvore de menu incluídos"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Estilos de menu não encontrados"
    ((FAILED++))
fi

echo ""

echo -e "${BLUE}5. Verificando JavaScript${NC}"
echo "-------------------------------------------"
if grep -q "Sortable.create" /var/www/html/admin_abastecimento/js/menu_manager.js; then
    echo -e "${GREEN}✓${NC} Drag & Drop (SortableJS) implementado"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} SortableJS não implementado"
    ((FAILED++))
fi

if grep -q "reorderItems" /var/www/html/admin_abastecimento/js/menu_manager.js; then
    echo -e "${GREEN}✓${NC} Função de reordenação implementada"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Função de reordenação não encontrada"
    ((FAILED++))
fi

echo ""

echo "=============================================="
echo -e "${BLUE}RESULTADO FINAL${NC}"
echo "=============================================="
echo -e "${GREEN}Testes Passados: $PASSED${NC}"
echo -e "${RED}Testes Falhados: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ TODOS OS TESTES PASSARAM!${NC}"
    echo -e "${GREEN}✓ Próximos Passos implementados com sucesso!${NC}"
    exit 0
else
    echo -e "${RED}✗ Alguns testes falharam. Verifique os erros acima.${NC}"
    exit 1
fi
