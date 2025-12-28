#!/bin/bash

# ========================================
# Script de Execução da Migração
# Remover id_empresa e manter apenas id_cliente
# ========================================

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurações do banco
DB_USER="renatorap"
DB_PASS="J@melancia01"
DB_NAME="conceit1_combustivel"
MIGRATION_FILE="$(dirname "$0")/migration_remove_id_empresa.sql"

# Função para exibir mensagens
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

# Banner
echo -e "${BLUE}"
echo "========================================"
echo "  MIGRAÇÃO: Remover id_empresa"
echo "  Data: $(date +'%Y-%m-%d %H:%M:%S')"
echo "========================================"
echo -e "${NC}"

# Verificar se o arquivo de migração existe
if [ ! -f "$MIGRATION_FILE" ]; then
    error "Arquivo de migração não encontrado: $MIGRATION_FILE"
    exit 1
fi

success "Arquivo de migração encontrado"

# Passo 1: Confirmar execução
echo ""
warning "ATENÇÃO: Esta operação irá modificar o banco de dados!"
warning "Certifique-se de ter um backup antes de continuar."
echo ""
read -p "Deseja continuar? (digite 'SIM' para confirmar): " confirm

if [ "$confirm" != "SIM" ]; then
    error "Operação cancelada pelo usuário"
    exit 1
fi

# Passo 2: Criar backup
echo ""
log "Criando backup do banco de dados..."
BACKUP_FILE="backup_pre_migracao_$(date +%Y%m%d_%H%M%S).sql"

# Criar backup com tratamento de erro (ignorando problemas de definer)
if mysqldump -u "$DB_USER" -p"$DB_PASS" --single-transaction --skip-lock-tables --no-tablespaces "$DB_NAME" > "$BACKUP_FILE" 2>&1; then
    # Verificar se o backup tem conteúdo
    BACKUP_SIZE=$(stat -f%z "$BACKUP_FILE" 2>/dev/null || stat -c%s "$BACKUP_FILE" 2>/dev/null)
    if [ "$BACKUP_SIZE" -lt 10000 ]; then
        warning "Backup criado mas parece pequeno (tamanho: $BACKUP_SIZE bytes)"
        warning "Verifique se o banco tem dados"
    fi
    success "Backup criado: $BACKUP_FILE ($(numfmt --to=iec-i --suffix=B $BACKUP_SIZE 2>/dev/null || echo "$BACKUP_SIZE bytes"))"
else
    error "Falha ao criar backup!"
    error "Verifique as credenciais do banco de dados"
    cat "$BACKUP_FILE" 2>/dev/null | grep -i error | head -5
    rm -f "$BACKUP_FILE"
    exit 1
fi

# Passo 3: Verificar conexão com banco
echo ""
log "Verificando conexão com o banco de dados..."
mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "SELECT 1" >/dev/null 2>&1

if [ $? -eq 0 ]; then
    success "Conexão com banco de dados OK"
else
    error "Falha ao conectar com banco de dados!"
    exit 1
fi

# Passo 4: Contar tabelas com id_empresa antes da migração
echo ""
log "Verificando estado atual do banco..."
TABELAS_ANTES=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND COLUMN_NAME = 'id_empresa';" 2>/dev/null)

log "Tabelas com id_empresa antes da migração: $TABELAS_ANTES"

# Passo 5: Executar migração
echo ""
log "Executando migração SQL..."
log "Isso pode levar alguns minutos..."

mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" < "$MIGRATION_FILE" 2>&1 | tee migration_output.log

if [ ${PIPESTATUS[0]} -eq 0 ]; then
    success "Migração SQL executada com sucesso!"
else
    error "Falha ao executar migração SQL!"
    error "Verifique o arquivo migration_output.log para mais detalhes"
    warning "Você pode restaurar o backup usando:"
    warning "mysql -u $DB_USER -p'$DB_PASS' $DB_NAME < $BACKUP_FILE"
    exit 1
fi

# Passo 6: Verificar resultado
echo ""
log "Verificando resultado da migração..."

TABELAS_DEPOIS=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND COLUMN_NAME = 'id_empresa';" 2>/dev/null)

log "Tabelas com id_empresa após migração: $TABELAS_DEPOIS"

if [ "$TABELAS_DEPOIS" -eq 0 ]; then
    success "✓ Coluna id_empresa removida de todas as tabelas!"
else
    error "✗ Ainda existem $TABELAS_DEPOIS tabelas com id_empresa"
    exit 1
fi

# Passo 7: Verificar integridade dos dados
echo ""
log "Verificando integridade dos dados..."

# Verificar veículos sem cliente
VEICULOS_SEM_CLIENTE=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) FROM veiculo WHERE id_cliente IS NULL;" 2>/dev/null)

# Verificar condutores sem cliente
CONDUTORES_SEM_CLIENTE=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) FROM condutor WHERE id_cliente IS NULL;" 2>/dev/null)

# Verificar abastecimentos sem cliente
ABASTECIMENTOS_SEM_CLIENTE=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) FROM consumo_combustivel WHERE id_cliente IS NULL;" 2>/dev/null)

echo "  • Veículos sem cliente: $VEICULOS_SEM_CLIENTE"
echo "  • Condutores sem cliente: $CONDUTORES_SEM_CLIENTE"
echo "  • Abastecimentos sem cliente: $ABASTECIMENTOS_SEM_CLIENTE"

if [ "$VEICULOS_SEM_CLIENTE" -eq 0 ] && [ "$CONDUTORES_SEM_CLIENTE" -eq 0 ] && [ "$ABASTECIMENTOS_SEM_CLIENTE" -eq 0 ]; then
    success "✓ Integridade dos dados OK!"
else
    warning "⚠ Existem registros sem id_cliente. Verifique manualmente."
fi

# Passo 8: Verificar Foreign Keys
echo ""
log "Verificando Foreign Keys recriadas..."

FKS_CLIENTE=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -N -e "
SELECT COUNT(*) 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = '$DB_NAME' 
AND COLUMN_NAME = 'id_cliente' 
AND REFERENCED_TABLE_NAME IS NOT NULL;" 2>/dev/null)

log "Foreign Keys em id_cliente: $FKS_CLIENTE"

if [ "$FKS_CLIENTE" -gt 0 ]; then
    success "✓ Foreign Keys recriadas!"
else
    warning "⚠ Nenhuma Foreign Key encontrada em id_cliente"
fi

# Resumo final
echo ""
echo -e "${GREEN}"
echo "========================================"
echo "  MIGRAÇÃO CONCLUÍDA COM SUCESSO!"
echo "========================================"
echo -e "${NC}"
echo ""
echo "📊 Resumo:"
echo "  • Tabelas migradas: $TABELAS_ANTES"
echo "  • Backup salvo em: $BACKUP_FILE"
echo "  • Log da migração: migration_output.log"
echo ""
echo "✅ Próximos passos:"
echo "  1. Testar a aplicação"
echo "  2. Verificar funcionalidades principais"
echo "  3. Monitorar logs de erro"
echo ""
echo "🔄 Para reverter (se necessário):"
echo "  mysql -u $DB_USER -p'$DB_PASS' $DB_NAME < $BACKUP_FILE"
echo ""
