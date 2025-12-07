#!/bin/bash
HOST="158.69.215.35"
USER="adminvps"

echo "🔧 Arreglando permisos de Docker en Producción..."
echo "⚠️  Contraseña: Marketing21"

ssh -t $USER@$HOST "
    echo '🐳 Cambiando dueño de storage/ y bootstrap/cache a application:application...'
    
    # En imágenes webdevops, el usuario web es 'application' (id 1000)
    sudo docker exec laravel-php chown -R application:application /var/www/html/storage
    sudo docker exec laravel-php chown -R application:application /var/www/html/bootstrap/cache
    
    echo '✅ Permisos corregidos. Limpiando caché correctamente...'
    
    # Ejecutamos los comandos de limpieza COMO el usuario application para no romper permisos de nuevo
    sudo docker exec -u application -w /var/www/html laravel-php php artisan view:clear
    sudo docker exec -u application -w /var/www/html laravel-php php artisan config:clear
    
    echo '🚀 Listo. Prueba recargar la web.'
"
