#!/bin/bash
HOST="158.69.215.35"
USER="adminvps"

echo "🕵️  Investigando el contenedor Docker por dentro..."
echo "⚠️  Contraseña: Marketing21"

# Usamos opciones SSH para evitar el error "Too many authentication failures"
# Forzamos usar solo contraseña y no probar todas las llaves SSH locales
ssh -o PreferredAuthentications=password -o PubkeyAuthentication=no -t $USER@$HOST "
    echo '🔍 Listando carpeta raíz (/) del contenedor:'
    sudo docker exec laravel-php ls -F /

    echo ''
    echo '🔍 Listando carpeta /app del contenedor:'
    sudo docker exec laravel-php ls -la /app
    
    echo ''
    echo '🔍 Listando carpeta /var/www/html del contenedor (ruta alternativa):'
    sudo docker exec laravel-php ls -la /var/www/html 2>/dev/null
    
    echo ''
    echo '🔍 Buscando 'artisan' dentro del contenedor (esto puede tardar unos segundos):'
    sudo docker exec laravel-php find / -maxdepth 4 -name artisan 2>/dev/null
"
