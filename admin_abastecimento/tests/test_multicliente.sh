#!/bin/bash

# Teste de Implementação do Sistema Multicliente
# Valida estrutura de banco, APIs e integração com frontend

echo "=========================================="
echo "  TESTE: Sistema Multicliente"
echo "=========================================="
echo ""

TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# Teste 1: Script SQL de multicliente
if [ -f "/var/www/html/admin_abastecimento/database/multicliente.sql" ]; then
    test_check 0 "Script SQL multicliente.sql criado"
else
    test_check 1 "Script SQL não encontrado"
fi

# Teste 2: API user_cliente
if [ -f "/var/www/html/admin_abastecimento/api/user_cliente.php" ]; then
    test_check 0 "API user_cliente.php criada"
else
    test_check 1 "API user_cliente.php não encontrada"
fi

echo ""
echo -e "${BLUE}2. Verificando estrutura do banco de dados...${NC}"

# Teste 3: Tabela usuario_cliente no SQL
if grep -q "CREATE TABLE.*usuario_cliente" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Tabela usuario_cliente definida no SQL"
else
    test_check 1 "Tabela usuario_cliente não encontrada no SQL"
fi

# Teste 4: Foreign keys corretas
if grep -q "FOREIGN KEY.*usuario_id.*REFERENCES usuarios" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Foreign key usuario_id→usuarios definida"
else
    test_check 1 "Foreign key usuario_id não encontrada"
fi

# Teste 5: Foreign key cliente
if grep -q "FOREIGN KEY.*cliente_id.*REFERENCES cliente" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Foreign key cliente_id→cliente definida"
else
    test_check 1 "Foreign key cliente_id não encontrada"
fi

# Teste 6: Adição de cliente_id em usuarios
if grep -q "ALTER TABLE usuarios ADD COLUMN cliente_id" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Campo cliente_id em usuarios configurado"
else
    test_check 1 "Campo cliente_id em usuarios não configurado"
fi

# Teste 7: Adição de cliente_id em veiculo
if grep -q "ALTER TABLE veiculo ADD COLUMN cliente_id" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Campo cliente_id em veiculo configurado"
else
    test_check 1 "Campo cliente_id em veiculo não configurado"
fi

echo ""
echo -e "${BLUE}3. Verificando API user_cliente.php...${NC}"

# Teste 8: Função listClientesUsuario
if grep -q "function listClientesUsuario" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Função listClientesUsuario() definida"
else
    test_check 1 "Função listClientesUsuario() não encontrada"
fi

# Teste 9: Função switchCliente
if grep -q "function switchCliente" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Função switchCliente() definida"
else
    test_check 1 "Função switchCliente() não encontrada"
fi

# Teste 10: Função getCurrentCliente
if grep -q "function getCurrentCliente" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Função getCurrentCliente() definida"
else
    test_check 1 "Função getCurrentCliente() não encontrada"
fi

# Teste 11: Verificação de administrador
if grep -q "grupos_usuarios.*Administrador" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Verificação de grupo Administrador implementada"
else
    test_check 1 "Verificação de Administrador não encontrada"
fi

# Teste 12: Armazenamento em sessão
if grep -q "_SESSION\['cliente_id'\]" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Armazenamento de cliente_id na sessão"
else
    test_check 1 "Sessão cliente_id não configurada"
fi

echo ""
echo -e "${BLUE}4. Verificando header.php atualizado...${NC}"

# Teste 13: Seletor de cliente
if grep -q "clienteSelector" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Seletor de cliente adicionado ao header"
else
    test_check 1 "Seletor de cliente não encontrado"
fi

# Teste 14: Logo do cliente
if grep -q "cliente_logo" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Exibição de logo do cliente configurada"
else
    test_check 1 "Logo do cliente não configurado"
fi

# Teste 15: Nome do cliente
if grep -q "cliente_nome" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Exibição de nome do cliente configurada"
else
    test_check 1 "Nome do cliente não configurado"
fi

# Teste 16: Função loadClientesSelector
if grep -q "function loadClientesSelector" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Função loadClientesSelector() implementada"
else
    test_check 1 "Função loadClientesSelector() não encontrada"
fi

# Teste 17: Função switchCliente no header
if grep -q "function switchCliente" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Função switchCliente() implementada no header"
else
    test_check 1 "Função switchCliente() não encontrada no header"
fi

# Teste 18: Inicialização de cliente na sessão
if grep -q "if (!isset.*cliente_id" /var/www/html/admin_abastecimento/includes/header.php; then
    test_check 0 "Inicialização automática de cliente na sessão"
else
    test_check 1 "Inicialização de cliente não configurada"
fi

echo ""
echo -e "${BLUE}5. Verificando filtros por cliente nas APIs...${NC}"

# Teste 19: Filtro em veiculo.php - list
if grep -q "cliente_id.*SESSION" /var/www/html/admin_abastecimento/api/veiculo.php; then
    test_check 0 "Filtro por cliente implementado em veiculo.php"
else
    test_check 1 "Filtro por cliente não encontrado em veiculo.php"
fi

# Teste 20: Filtro em usuarios.php - list
if grep -q "u.cliente_id.*clienteId" /var/www/html/admin_abastecimento/api/usuarios.php; then
    test_check 0 "Filtro por cliente implementado em usuarios.php"
else
    test_check 1 "Filtro por cliente não encontrado em usuarios.php"
fi

# Teste 21: INSERT com cliente_id em veiculo
if grep -q "INSERT INTO veiculo.*cliente_id" /var/www/html/admin_abastecimento/api/veiculo.php; then
    test_check 0 "INSERT com cliente_id em veiculo.php"
else
    test_check 1 "INSERT sem cliente_id em veiculo.php"
fi

# Teste 22: INSERT com cliente_id em usuarios
if grep -q "INSERT INTO usuarios.*cliente_id" /var/www/html/admin_abastecimento/api/usuarios.php; then
    test_check 0 "INSERT com cliente_id em usuarios.php"
else
    test_check 1 "INSERT sem cliente_id em usuarios.php"
fi

echo ""
echo -e "${BLUE}6. Verificando vinculações automáticas...${NC}"

# Teste 23: Vincular administradores
if grep -q "INSERT INTO usuario_cliente.*FROM usuarios u.*Administrador" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Vinculação automática de Administradores"
else
    test_check 1 "Vinculação de Administradores não configurada"
fi

# Teste 24: Vincular usuários ao seu cliente
if grep -q "INSERT INTO usuario_cliente.*WHERE cliente_id IS NOT NULL" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Vinculação de usuários aos seus clientes"
else
    test_check 1 "Vinculação de usuários não configurada"
fi

# Teste 25: Atribuir cliente aos veículos
if grep -q "UPDATE veiculo.*SET.*cliente_id" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Atribuição de cliente aos veículos existentes"
else
    test_check 1 "Atribuição de cliente aos veículos não configurada"
fi

echo ""
echo -e "${BLUE}7. Verificando priorização Nome Fantasia...${NC}"

# Teste 26: Prioridade nome_fantasia na API
if grep -q "nome_fantasia.*razao_social" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Priorização de nome_fantasia sobre razão_social"
else
    test_check 1 "Priorização não encontrada"
fi

# Teste 27: Campo nome_exibicao
if grep -q "nome_exibicao" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Campo nome_exibicao criado"
else
    test_check 1 "Campo nome_exibicao não encontrado"
fi

echo ""
echo -e "${BLUE}8. Verificando segurança...${NC}"

# Teste 28: Verificação de autenticação
if grep -q "if (!.*userId)" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Verificação de autenticação na API"
else
    test_check 1 "Verificação de autenticação não encontrada"
fi

# Teste 29: Validação de acesso ao cliente
if grep -q "usuario_cliente.*WHERE.*usuario_id" /var/www/html/admin_abastecimento/api/user_cliente.php; then
    test_check 0 "Validação de acesso ao cliente"
else
    test_check 1 "Validação de acesso não encontrada"
fi

# Teste 30: Unique constraint
if grep -q "UNIQUE KEY.*usuario_cliente" /var/www/html/admin_abastecimento/database/multicliente.sql; then
    test_check 0 "Constraint UNIQUE para evitar duplicação"
else
    test_check 1 "Constraint UNIQUE não encontrada"
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
    echo -e "${YELLOW}PRÓXIMOS PASSOS:${NC}"
    echo "1. Executar o script SQL:"
    echo "   mysql -u root -p admin_abastecimento < database/multicliente.sql"
    echo ""
    echo "2. Testar o seletor de cliente no header"
    echo ""
    echo "3. Verificar filtros nas páginas:"
    echo "   - /pages/usuarios.php"
    echo "   - /pages/veiculo.php"
    echo "   - /pages/dashboard.php"
    echo ""
    echo "4. Testar troca de cliente e atualização de dados"
else
    echo -e "${RED}✗ Alguns testes falharam. Verifique os erros acima.${NC}"
fi

echo ""
exit $FAILED_TESTS
