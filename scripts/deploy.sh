#!/bin/bash
# Script de despliegue simple
HOST="158.69.215.35"
USER="adminvps"

echo "🚀 Iniciando despliegue a producción..."
echo "⚠️  Contraseña: Marketing21"

ssh -t $USER@$HOST "
    # Ruta donde está el docker-compose y la carpeta app/
    cd /opt/docker/laravel/app

    echo '📂 En carpeta de código:' \$(pwd)
    
    # Poner en modo mantenimiento
    sudo docker exec -w /var/www/html laravel-php php artisan down || true

    # Corregir permisos (root a veces se adueña de archivos)
    echo '🔒 Corrigiendo permisos...'
    sudo chown -R adminvps:adminvps .

    # Actualizar código
    echo '⬇️  Haciendo git pull...'
    # Corregir error de propiedad de git
    git config --global --add safe.directory /opt/docker/laravel/app
    # Git se ejecuta en el HOST, no en el container, porque el .git está en el host (en app/)
    git pull origin master

    # Instalar dependencias dentro del container (como application para evitar problemas de permisos)
    echo '📦 Instalando dependencias...'
    sudo docker exec -u application -w /var/www/html laravel-php composer install --no-dev --optimize-autoloader

    # Migraciones (idealmente como application, pero si falla por permisos de DB, dejar sin -u. Probemos application)
    echo '🗄️  Ejecutando migraciones...'
    sudo docker exec -u application -w /var/www/html laravel-php php artisan migrate --force

    # Limpiar caché (ESTO ES CRÍTICO: Debe ser application)
    echo '🧹 Limpiando caché...'
    sudo docker exec -u application -w /var/www/html laravel-php php artisan config:clear
    sudo docker exec -u application -w /var/www/html laravel-php php artisan route:clear
    sudo docker exec -u application -w /var/www/html laravel-php php artisan view:clear

    # Salir de mantenimiento
    sudo docker exec -u application -w /var/www/html laravel-php php artisan up
    echo '✅ Despliegue Docker completado con éxito.'
"
