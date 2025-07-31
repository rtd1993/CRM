#!/bin/bash

# Test rapido di tutte le versioni di modifica_cliente.php
echo "🔧 Test Versioni Modifica Cliente - Verifica Sintassi"
echo "======================================================="

files=(
    "modifica_cliente_micro.php"
    "modifica_cliente_ultraleggero.php" 
    "modifica_cliente_ottimizzato.php"
    "modifica_cliente_simple.php"
    "modifica_cliente.php"
)

for file in "${files[@]}"; do
    echo -n "Testing $file... "
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo "✅ SYNTAX OK"
        else
            echo "❌ SYNTAX ERROR"
            php -l "$file"
        fi
    else
        echo "❌ FILE NOT FOUND"
    fi
done

echo ""
echo "🌐 Test URL (sostituire con l'URL reale del tuo server):"
echo "http://localhost/modifica_cliente_micro.php?id=1"
echo "http://localhost/modifica_cliente_ultraleggero.php?id=1"
echo "http://localhost/modifica_cliente_ottimizzato.php?id=1"
echo "http://localhost/modifica_cliente_simple.php?id=1"
echo "http://localhost/modifica_cliente.php?id=1"
