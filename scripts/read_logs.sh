#!/bin/bash
HOST="158.69.215.35"
USER="adminvps"

echo "🕵️  Consultando últimos logs de Laravel..."
echo "⚠️  Contraseña: Marketing21"

ssh -t $USER@$HOST "
    # Verificar logs dentro del contenedor
    sudo docker exec -w /var/www/html laravel-php tail -n 100 storage/logs/laravel.log
"
