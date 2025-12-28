#!/bin/bash
#
# Script de Sincronização Rápida - Produção para Localhost
# Executa a sincronização de dados
#

cd "$(dirname "$0")"

echo ""
echo "=================================================="
echo "  Iniciando Sincronização de Dados..."
echo "=================================================="
echo ""

php sincronizar_producao.php

echo ""
echo "=================================================="
echo "  Sincronização Finalizada"
echo "=================================================="
echo ""
