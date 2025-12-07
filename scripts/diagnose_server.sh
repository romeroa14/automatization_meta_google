#!/bin/bash
HOST="158.69.215.35"
USER="adminvps"

echo "🕵️  Diagnóstico profundo del servidor..."
echo "⚠️  Contraseña: Marketing21"

ssh -t $USER@$HOST "
    echo '📂 Listando /opt/docker/laravel:'
    ls -la /opt/docker/laravel
    
    echo ''
    echo '🐳 Buscando contenedores Docker activos:'
    sudo docker ps --format 'table {{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Status}}' || echo '⚠️ No se pudo ejecutar docker ps (¿falta sudo?)'
    
    echo ''
    echo '🐳 Buscando docker-compose:'
    ls -la /opt/docker/laravel/docker-compose.yml 2>/dev/null
    
    echo ''
    echo '🔍 Buscando artisan en subdirectorios:'
    find /opt/docker/laravel -maxdepth 3 -name 'artisan' 2>/dev/null
"
