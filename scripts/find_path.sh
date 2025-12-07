#!/bin/bash
HOST="158.69.215.35"
USER="adminvps"

echo "🕵️  Buscando la carpeta del proyecto en el servidor..."
echo "⚠️  Contraseña: Marketing21"

ssh -t $USER@$HOST "
    echo '📂 Contenido de HOME (~):'
    ls -F
    
    echo ''
    echo '🔍 Buscando archivo \"artisan\" (máximo 3 niveles de profundidad)...'
    find . -maxdepth 3 -name \"artisan\" -type f 2>/dev/null
"
