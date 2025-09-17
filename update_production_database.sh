#!/bin/bash

# Script para actualizar la base de datos de producción
# Ejecutar desde el directorio del proyecto

echo "🚀 Actualizando base de datos de producción..."

# 1. Agregar la columna token_expires_at
echo "📝 Agregando columna token_expires_at..."
php artisan psql:prod << 'EOF'
ALTER TABLE facebook_accounts 
ADD COLUMN IF NOT EXISTS token_expires_at TIMESTAMP NULL;
EOF

# 2. Verificar que la columna se agregó
echo "✅ Verificando estructura de la tabla..."
php artisan psql:prod << 'EOF'
\d facebook_accounts;
EOF

# 3. Ejecutar el seeder para actualizar las cuentas
echo "🌱 Ejecutando seeder para actualizar cuentas de Facebook..."
php artisan db:seed --class=FacebookAccountSeeder

echo "🎉 ¡Actualización completada!"
echo ""
echo "📊 Resumen de cambios:"
echo "   • Columna token_expires_at agregada"
echo "   • Token de larga duración configurado"
echo "   • Cuentas de Facebook actualizadas"
echo ""
echo "🔧 Próximos pasos:"
echo "   1. Verificar que las cuentas estén activas"
echo "   2. Probar la API de Meta"
echo "   3. Probar el bot de Telegram"

