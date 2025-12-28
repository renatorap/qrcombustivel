#!/bin/bash

# Script para atualizar cores antigas para nova paleta
# Cores antigas -> Cores novas:
# #1b5175 -> #2f6b8f (azul primário)
# #0d2a42 -> #255a7a (azul escuro)
# #f07a28 -> #f59b4c (laranja)

# Diretório base
BASE_DIR="/var/www/html/admin_abastecimento"

# Cores para substituir (mapeamento)
declare -A COLOR_MAP=(
    ["#1b5175"]="#2f6b8f"
    ["#0d2a42"]="#255a7a"
    ["#f07a28"]="#f59b4c"
    ["rgba(27, 81, 117"]="rgba(47, 107, 143"
    ["rgba(240, 122, 40"]="rgba(245, 155, 76"
    ["rgba(13, 42, 66"]="rgba(37, 90, 122"
)

# Contador de arquivos modificados
FILES_UPDATED=0

echo "=================================================="
echo "  ATUALIZAÇÃO DE CORES - PALETA SUAVIZADA"
echo "=================================================="
echo ""
echo "Cores antigas -> Cores novas:"
echo "  #1b5175 -> #2f6b8f (azul primário)"
echo "  #0d2a42 -> #255a7a (azul escuro)"
echo "  #f07a28 -> #f59b4c (laranja)"
echo ""
echo "Procurando arquivos PHP..."
echo ""

# Encontrar todos os arquivos PHP (exceto vendor e tests)
while IFS= read -r file; do
    # Verificar se o arquivo contém alguma cor antiga
    if grep -qE "#1b5175|#0d2a42|#f07a28|rgba\(27, 81, 117|rgba\(240, 122, 40|rgba\(13, 42, 66" "$file"; then
        echo "Processando: $file"
        
        # Criar backup do arquivo
        cp "$file" "${file}.bak_colors"
        
        # Substituir cada cor
        for old_color in "${!COLOR_MAP[@]}"; do
            new_color="${COLOR_MAP[$old_color]}"
            sed -i "s/$old_color/$new_color/g" "$file"
        done
        
        ((FILES_UPDATED++))
        echo "  ✓ Atualizado"
    fi
done < <(find "$BASE_DIR" -name "*.php" -not -path "*/vendor/*" -not -path "*/tests/*" -type f)

echo ""
echo "=================================================="
echo "  RESUMO"
echo "=================================================="
echo "Total de arquivos atualizados: $FILES_UPDATED"
echo ""
echo "Backups criados com extensão: .bak_colors"
echo "Para remover backups: find $BASE_DIR -name '*.bak_colors' -delete"
echo ""
echo "Para verificar mudanças em um arquivo:"
echo "  diff arquivo.php arquivo.php.bak_colors"
echo ""
echo "Concluído!"
