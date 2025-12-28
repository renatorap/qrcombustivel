#!/bin/bash

# Script para identificar e listar CSS inline em arquivos PHP
# Este script NÃO remove automaticamente - apenas lista para revisão manual

BASE_DIR="/var/www/html/admin_abastecimento"
OUTPUT_FILE="/var/www/html/admin_abastecimento/sync/css_inline_report.txt"

echo "=================================================="
echo "  RELATÓRIO DE CSS INLINE EM ARQUIVOS PHP"
echo "=================================================="
echo "" > "$OUTPUT_FILE"
echo "Data: $(date)" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# Contador
TOTAL_FILES=0

# Encontrar arquivos com <style>
while IFS= read -r file; do
    # Verificar se tem tag <style>
    if grep -q "<style>" "$file"; then
        relative_path="${file#$BASE_DIR/}"
        
        echo "Encontrado CSS inline em: $relative_path"
        echo "================================================" >> "$OUTPUT_FILE"
        echo "ARQUIVO: $relative_path" >> "$OUTPUT_FILE"
        echo "================================================" >> "$OUTPUT_FILE"
        
        # Extrair conteúdo entre <style> e </style>
        awk '/<style>/,/<\/style>/' "$file" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        
        ((TOTAL_FILES++))
    fi
done < <(find "$BASE_DIR" -name "*.php" -not -path "*/vendor/*" -not -path "*/tests/*" -type f)

echo "" >> "$OUTPUT_FILE"
echo "================================================" >> "$OUTPUT_FILE"
echo "RESUMO" >> "$OUTPUT_FILE"
echo "================================================" >> "$OUTPUT_FILE"
echo "Total de arquivos com CSS inline: $TOTAL_FILES" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"
echo "PÁGINAS QUE PODEM USAR CLASSES REUTILIZÁVEIS:" >> "$OUTPUT_FILE"
echo "- Usar .table-modern para tabelas" >> "$OUTPUT_FILE"
echo "- Usar .search-container para campos de busca" >> "$OUTPUT_FILE"
echo "- Usar .btn-action para botões de ação" >> "$OUTPUT_FILE"
echo "- Usar classes de status: .status-ativo, .status-alerta, .status-vencido" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"
echo "Consulte: docs/CSS_CLASSES_GUIDE.md" >> "$OUTPUT_FILE"

echo ""
echo "=================================================="
echo "  RESUMO"
echo "=================================================="
echo "Total de arquivos com CSS inline: $TOTAL_FILES"
echo ""
echo "Relatório salvo em: $OUTPUT_FILE"
echo ""
echo "Próximos passos:"
echo "1. Revisar o relatório: cat $OUTPUT_FILE"
echo "2. Identificar CSS que pode ser movido para style.css"
echo "3. Identificar CSS que pode usar classes reutilizáveis"
echo "4. Para páginas de login/reset: manter CSS inline (páginas standalone)"
echo "5. Para páginas do sistema: mover para style.css"
echo ""
echo "Concluído!"
