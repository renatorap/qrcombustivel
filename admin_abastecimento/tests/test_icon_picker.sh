#!/bin/bash

# Teste de Implementação do Icon Picker
# Valida a integração do seletor visual de ícones FontAwesome

echo "=========================================="
echo "  TESTE: Icon Picker FontAwesome"
echo "=========================================="
echo ""

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para testar
test_check() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        PASSED_TESTS=$((PASSED_TESTS + 1))
    else
        echo -e "${RED}✗${NC} $2"
        FAILED_TESTS=$((FAILED_TESTS + 1))
    fi
}

echo -e "${BLUE}1. Verificando arquivos criados...${NC}"

# Teste 1: Arquivo icon_picker.js
if [ -f "/var/www/html/admin_abastecimento/js/icon_picker.js" ]; then
    test_check 0 "Arquivo icon_picker.js criado"
else
    test_check 1 "Arquivo icon_picker.js não encontrado"
fi

# Teste 2: Arquivo de teste
if [ -f "/var/www/html/admin_abastecimento/tests/test_icon_picker.php" ]; then
    test_check 0 "Arquivo de teste criado"
else
    test_check 1 "Arquivo de teste não encontrado"
fi

echo ""
echo -e "${BLUE}2. Verificando conteúdo do icon_picker.js...${NC}"

# Teste 3: Função initIconPicker
if grep -q "function initIconPicker()" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Função initIconPicker() definida"
else
    test_check 1 "Função initIconPicker() não encontrada"
fi

# Teste 4: Função openIconPicker
if grep -q "function openIconPicker(" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Função openIconPicker() definida"
else
    test_check 1 "Função openIconPicker() não encontrada"
fi

# Teste 5: Função makeIconInputClickable
if grep -q "function makeIconInputClickable(" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Função makeIconInputClickable() definida"
else
    test_check 1 "Função makeIconInputClickable() não encontrada"
fi

# Teste 6: Função filterIcons
if grep -q "function filterIcons(" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Função filterIcons() definida"
else
    test_check 1 "Função filterIcons() não encontrada"
fi

# Teste 7: Array de ícones
if grep -q "const fontAwesomeIcons = \[" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Array de ícones FontAwesome definido"
else
    test_check 1 "Array de ícones não encontrado"
fi

# Teste 8: Modal HTML
if grep -q "iconPickerModal" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Modal do Icon Picker configurado"
else
    test_check 1 "Modal do Icon Picker não encontrado"
fi

echo ""
echo -e "${BLUE}3. Verificando integração em menu_manager.php...${NC}"

# Teste 9: Script incluído em menu_manager.php
if grep -q "icon_picker.js" /var/www/html/admin_abastecimento/pages/menu_manager.php; then
    test_check 0 "Script icon_picker.js incluído em menu_manager.php"
else
    test_check 1 "Script não incluído em menu_manager.php"
fi

# Teste 10: Campo de ícone configurado
if grep -q "makeIconInputClickable" /var/www/html/admin_abastecimento/js/menu_manager.js; then
    test_check 0 "Campo de ícone configurado em menu_manager.js"
else
    test_check 1 "Campo de ícone não configurado em menu_manager.js"
fi

# Teste 11: Placeholder atualizado
if grep -q "Clique para escolher" /var/www/html/admin_abastecimento/pages/menu_manager.php; then
    test_check 0 "Placeholder do campo atualizado"
else
    test_check 1 "Placeholder não atualizado"
fi

echo ""
echo -e "${BLUE}4. Verificando integração em aplicacoes.php...${NC}"

# Teste 12: Script incluído em aplicacoes.php
if grep -q "icon_picker.js" /var/www/html/admin_abastecimento/pages/aplicacoes.php; then
    test_check 0 "Script icon_picker.js incluído em aplicacoes.php"
else
    test_check 1 "Script não incluído em aplicacoes.php"
fi

# Teste 13: Input group com preview
if grep -q "aplicacaoIconePreview" /var/www/html/admin_abastecimento/pages/aplicacoes.php; then
    test_check 0 "Input group com preview configurado"
else
    test_check 1 "Input group com preview não encontrado"
fi

# Teste 14: Inicialização do icon picker
if grep -q "makeIconInputClickable.*aplicacaoIcone" /var/www/html/admin_abastecimento/pages/aplicacoes.php; then
    test_check 0 "Icon picker inicializado em aplicacoes.php"
else
    test_check 1 "Icon picker não inicializado"
fi

# Teste 15: Preview atualizado ao editar
if grep -q "aplicacaoIconePreview.*className" /var/www/html/admin_abastecimento/pages/aplicacoes.php; then
    test_check 0 "Preview atualizado na função de edição"
else
    test_check 1 "Preview não atualizado ao editar"
fi

echo ""
echo -e "${BLUE}5. Verificando funcionalidades CSS...${NC}"

# Teste 16: Estilos do grid
if grep -q "icon-picker-grid" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Estilos CSS do grid definidos"
else
    test_check 1 "Estilos CSS do grid não encontrados"
fi

# Teste 17: Estilos dos itens
if grep -q "icon-picker-item" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Estilos CSS dos itens definidos"
else
    test_check 1 "Estilos CSS dos itens não encontrados"
fi

# Teste 18: Hover effects
if grep -q "icon-picker-item:hover" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Efeitos hover configurados"
else
    test_check 1 "Efeitos hover não encontrados"
fi

echo ""
echo -e "${BLUE}6. Verificando lista de ícones...${NC}"

# Teste 19: Ícones de navegação
if grep -q "fa-home" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Ícones de navegação incluídos"
else
    test_check 1 "Ícones de navegação não encontrados"
fi

# Teste 20: Ícones de usuários
if grep -q "fa-users" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Ícones de usuários incluídos"
else
    test_check 1 "Ícones de usuários não encontrados"
fi

# Teste 21: Ícones de veículos
if grep -q "fa-car.*fa-truck" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Ícones de veículos incluídos"
else
    test_check 1 "Ícones de veículos não encontrados"
fi

# Teste 22: Ícones de combustível
if grep -q "fa-gas-pump" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Ícones de combustível incluídos"
else
    test_check 1 "Ícones de combustível não encontrados"
fi

echo ""
echo -e "${BLUE}7. Verificando funcionalidades JavaScript...${NC}"

# Teste 23: Busca de ícones
if grep -q "iconSearchInput" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Campo de busca implementado"
else
    test_check 1 "Campo de busca não encontrado"
fi

# Teste 24: Contador de ícones
if grep -q "iconCount" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Contador de ícones implementado"
else
    test_check 1 "Contador de ícones não encontrado"
fi

# Teste 25: Seleção com classe
if grep -q "classList.add.*selected" /var/www/html/admin_abastecimento/js/icon_picker.js; then
    test_check 0 "Sistema de seleção implementado"
else
    test_check 1 "Sistema de seleção não encontrado"
fi

echo ""
echo "=========================================="
echo "  RESUMO DOS TESTES"
echo "=========================================="
echo -e "Total de testes: ${BLUE}$TOTAL_TESTS${NC}"
echo -e "Testes aprovados: ${GREEN}$PASSED_TESTS${NC}"
echo -e "Testes reprovados: ${RED}$FAILED_TESTS${NC}"

PERCENTAGE=$((PASSED_TESTS * 100 / TOTAL_TESTS))
echo -e "Taxa de sucesso: ${BLUE}$PERCENTAGE%${NC}"

echo ""
if [ $FAILED_TESTS -eq 0 ]; then
    echo -e "${GREEN}✓ Todos os testes passaram com sucesso!${NC}"
    echo ""
    echo "Próximos passos:"
    echo "1. Acesse: http://localhost/admin_abastecimento/tests/test_icon_picker.php"
    echo "2. Execute os testes interativos"
    echo "3. Teste em: /pages/menu_manager.php"
    echo "4. Teste em: /pages/aplicacoes.php"
else
    echo -e "${RED}✗ Alguns testes falharam. Verifique os erros acima.${NC}"
fi

echo ""
exit $FAILED_TESTS
