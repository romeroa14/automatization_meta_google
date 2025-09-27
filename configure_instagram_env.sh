#!/bin/bash

# Script para configurar variables de entorno de Instagram Chatbot
# Ejecutar en el servidor de producción

echo "🤖 Configurando variables de entorno para Instagram Chatbot..."

# Variables de Instagram (configurar con tus valores reales)
echo "📝 Configurando variables de Instagram..."

# 1. INSTAGRAM_ACCESS_TOKEN
echo "Ingresa tu Instagram Access Token:"
read -p "INSTAGRAM_ACCESS_TOKEN: " INSTAGRAM_ACCESS_TOKEN

# 2. INSTAGRAM_APP_SECRET
echo "Ingresa tu Instagram App Secret:"
read -p "INSTAGRAM_APP_SECRET: " INSTAGRAM_APP_SECRET

# 3. Verificar si ya existen las variables
if grep -q "INSTAGRAM_ACCESS_TOKEN=" .env; then
    echo "✅ INSTAGRAM_ACCESS_TOKEN ya existe, actualizando..."
    sed -i "s/INSTAGRAM_ACCESS_TOKEN=.*/INSTAGRAM_ACCESS_TOKEN=$INSTAGRAM_ACCESS_TOKEN/" .env
else
    echo "INSTAGRAM_ACCESS_TOKEN=$INSTAGRAM_ACCESS_TOKEN" >> .env
fi

if grep -q "INSTAGRAM_APP_SECRET=" .env; then
    echo "✅ INSTAGRAM_APP_SECRET ya existe, actualizando..."
    sed -i "s/INSTAGRAM_APP_SECRET=.*/INSTAGRAM_APP_SECRET=$INSTAGRAM_APP_SECRET/" .env
else
    echo "INSTAGRAM_APP_SECRET=$INSTAGRAM_APP_SECRET" >> .env
fi

# 4. Verificar configuración
echo "🔍 Verificando configuración..."
echo "INSTAGRAM_VERIFY_TOKEN=adsbot" >> .env

# 5. Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✅ Configuración completada!"
echo ""
echo "📋 Variables configuradas:"
echo "   - INSTAGRAM_ACCESS_TOKEN: $INSTAGRAM_ACCESS_TOKEN"
echo "   - INSTAGRAM_VERIFY_TOKEN: adsbot"
echo "   - INSTAGRAM_APP_SECRET: $INSTAGRAM_APP_SECRET"
echo ""
echo "🔗 Webhook URL para Meta:"
echo "   https://admetricas.com/webhook/instagram"
echo ""
echo "🧪 Para probar el webhook:"
echo "   curl -X GET \"https://admetricas.com/webhook/instagram?hub_mode=subscribe&hub_verify_token=adsbot&hub_challenge=test123\""
