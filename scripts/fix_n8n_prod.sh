#!/bin/bash
# Script para arreglar problemas de n8n (limpiar caché) en producción
HOST="158.69.215.35"
USER="adminvps"

echo "🔌 Conectando a $HOST para limpiar caché..."
echo "⚠️  Cuando te pida contraseña, escribe: Marketing21"

# Intentamos adivinar la ruta, o usamos una común. Ajustar si es necesario.
# Asumimos que el proyecto está en una carpeta llamada 'automatization_meta_google' o 'public_html'
ssh -t $USER@$HOST "
    echo '🐳 Ejecutando limpieza de caché en contenedor Docker (laravel-php)...'
    echo '⚠️  Si pide contraseña, es para SUDO (Marketing21)'
    
    # Usando ruta absoluta confirmada dentro del contenedor
    sudo docker exec -w /var/www/html laravel-php php artisan config:clear
    sudo docker exec -w /var/www/html laravel-php php artisan cache:clear
    sudo docker exec -w /var/www/html laravel-php php artisan route:clear
    
    echo '✅ ¡Caché del contenedor limpiada!'
"
