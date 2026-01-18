#!/bin/bash
# Script simplificado para desplegar el frontend

HOST="158.69.215.35"
USER="adminvps"

echo "🚀 Desplegando Frontend a Producción"
echo "====================================="
echo ""

ssh -t $USER@$HOST << 'EOF'
    cd /opt/docker/laravel/app
    
    echo "📥 Actualizando código..."
    git pull origin master
    
    echo ""
    echo "🔨 Reconstruyendo frontend..."
    docker-compose -f docker-compose.frontend.yml build --no-cache
    
    echo ""
    echo "🔄 Reiniciando contenedor..."
    docker-compose -f docker-compose.frontend.yml down
    docker-compose -f docker-compose.frontend.yml up -d
    
    echo ""
    echo "✅ Frontend desplegado"
    echo ""
    echo "📋 Verificación:"
    docker ps --filter 'name=frontend-app' --format 'table {{.Names}}\t{{.Status}}'
EOF

echo ""
echo "✅ Despliegue completado"
echo ""
echo "🌐 Visita: https://app.admetricas.com"
echo "   Abre la consola del navegador (F12) y verifica que las peticiones vayan a: https://admetricas.com/api"

