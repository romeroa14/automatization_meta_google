#!/bin/bash
# Script para reconstruir el frontend en producción con la nueva configuración

HOST="158.69.215.35"
USER="adminvps"

echo "🔨 Reconstruyendo Frontend en Producción"
echo "=========================================="
echo ""

echo "1️⃣ Conectando al servidor..."
ssh -t $USER@$HOST "
    cd /opt/docker/laravel/app
    
    echo ''
    echo '2️⃣ Verificando cambios en el repositorio...'
    git status
    
    echo ''
    echo '3️⃣ Actualizando código del repositorio...'
    git pull origin master
    
    echo ''
    echo '4️⃣ Reconstruyendo imagen Docker del frontend...'
    docker-compose -f docker-compose.frontend.yml build --no-cache
    
    echo ''
    echo '5️⃣ Reiniciando contenedor del frontend...'
    docker-compose -f docker-compose.frontend.yml down
    docker-compose -f docker-compose.frontend.yml up -d
    
    echo ''
    echo '6️⃣ Verificando que el contenedor esté corriendo...'
    docker ps --filter 'name=frontend-app' --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
    
    echo ''
    echo '7️⃣ Verificando logs del contenedor...'
    docker logs frontend-app --tail 20
    
    echo ''
    echo '✅ Frontend reconstruido y desplegado'
    echo ''
    echo '📋 Verificación:'
    echo '   - Visita: https://app.admetricas.com'
    echo '   - Abre la consola del navegador (F12)'
    echo '   - Verifica que las peticiones vayan a: https://admetricas.com/api'
"

echo ""
echo "✅ Proceso completado"

