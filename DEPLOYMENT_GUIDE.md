# 🚀 Guía de Despliegue en Laravel Cloud

## 📋 Checklist Pre-Despliegue

### ✅ Archivos Necesarios
- [x] Todas las migraciones ejecutadas
- [x] Modelos y servicios creados
- [x] Rutas API configuradas
- [x] Configuración de Telegram
- [x] Recursos de Filament

### 🔧 Variables de Entorno Requeridas

#### **Configuración Base de Laravel**
```env
APP_NAME="ADMETRICAS.COM"
APP_ENV=production
APP_KEY=base64:tu_app_key_aqui
APP_DEBUG=false
APP_URL=https://tu-dominio.laravelcloud.com
```

#### **Base de Datos**
```env
DB_CONNECTION=mysql
DB_HOST=tu_host_mysql
DB_PORT=3306
DB_DATABASE=tu_database
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

#### **Configuración de Telegram**
```env
TELEGRAM_BOT_TOKEN=tu_bot_token_de_telegram
TELEGRAM_WEBHOOK_URL=https://tu-dominio.laravelcloud.com/api/telegram/webhook
```

#### **Configuración de Meta/Facebook**
```env
DEFAULT_FACEBOOK_ACCOUNT_ID=tu_cuenta_facebook_id
DEFAULT_AD_ACCOUNT_ID=tu_cuenta_publicitaria_id
```

#### **Configuración de Archivos**
```env
FILESYSTEM_DISK=public
```

## 🚀 Pasos de Despliegue

### 1. Subir Código a Laravel Cloud
```bash
# Si usas Git
git add .
git commit -m "Add Telegram Bot for Meta Campaigns"
git push origin main

# O subir archivos directamente via Laravel Cloud dashboard
```

### 2. Configurar Variables de Entorno
En el dashboard de Laravel Cloud:
1. Ve a **Environment Variables**
2. Agrega todas las variables listadas arriba
3. Guarda los cambios

### 3. Ejecutar Migraciones
```bash
# En Laravel Cloud terminal o via dashboard
php artisan migrate --force
```

### 4. Configurar Storage
```bash
# Crear enlace simbólico para archivos públicos
php artisan storage:link
```

### 5. Configurar Bot de Telegram
```bash
# Configurar webhook de Telegram
php artisan telegram:setup --token=TU_BOT_TOKEN --webhook=https://tu-dominio.laravelcloud.com/api/telegram/webhook
```

### 6. Limpiar Caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

## 🔗 URLs Importantes

### **Panel de Administración**
```
https://tu-dominio.laravelcloud.com/admin
```

### **API Endpoints**
```
https://tu-dominio.laravelcloud.com/api/telegram/webhook
https://tu-dominio.laravelcloud.com/api/telegram/set-webhook
https://tu-dominio.laravelcloud.com/api/telegram/bot-info
```

### **Tasas de Cambio**
```
https://tu-dominio.laravelcloud.com/api/exchange-rates
```

## 🧪 Pruebas Post-Despliegue

### 1. Verificar Panel de Administración
- [ ] Acceder a `/admin`
- [ ] Verificar que aparezcan todos los recursos
- [ ] Probar navegación entre secciones

### 2. Verificar Bot de Telegram
- [ ] Buscar tu bot en Telegram
- [ ] Enviar `/start`
- [ ] Verificar que responda correctamente

### 3. Verificar API Endpoints
```bash
# Probar webhook
curl -X POST https://tu-dominio.laravelcloud.com/api/telegram/webhook \
     -H "Content-Type: application/json" \
     -d '{"message":{"chat":{"id":123},"from":{"id":123,"first_name":"Test"},"text":"/start"}}'

# Probar info del bot
curl https://tu-dominio.laravelcloud.com/api/telegram/bot-info
```

### 4. Verificar Base de Datos
- [ ] Verificar que las tablas se crearon correctamente
- [ ] Probar crear una campaña desde el bot
- [ ] Verificar que se guarde en la base de datos

## 🔒 Configuración de Seguridad

### 1. HTTPS Obligatorio
Laravel Cloud ya proporciona HTTPS por defecto.

### 2. Variables Sensibles
- ✅ No incluir tokens en el código
- ✅ Usar variables de entorno
- ✅ Rotar tokens periódicamente

### 3. Permisos de Archivos
```bash
# Configurar permisos correctos
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 📊 Monitoreo

### 1. Logs de Laravel
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

### 2. Logs de Telegram
Los logs del bot se guardan en:
- `storage/logs/laravel.log`
- Buscar por "TelegramBotService"

### 3. Métricas de Uso
- Monitorear uso de base de datos
- Verificar espacio de almacenamiento
- Revisar logs de errores

## 🚨 Solución de Problemas

### Bot no responde
1. Verificar que `TELEGRAM_BOT_TOKEN` esté configurado
2. Verificar que `TELEGRAM_WEBHOOK_URL` sea correcta
3. Revisar logs de Laravel
4. Probar endpoint manualmente

### Error de base de datos
1. Verificar variables de entorno de DB
2. Verificar que las migraciones se ejecutaron
3. Revisar permisos de base de datos

### Archivos no se suben
1. Verificar que `storage:link` se ejecutó
2. Verificar permisos de directorio `storage/`
3. Verificar configuración de `FILESYSTEM_DISK`

## 📱 Comandos Útiles Post-Despliegue

```bash
# Verificar configuración
php artisan config:show telegram

# Limpiar todo el caché
php artisan optimize:clear

# Verificar rutas
php artisan route:list --path=api/telegram

# Verificar migraciones
php artisan migrate:status

# Verificar storage
php artisan storage:link
```

## 🎯 Próximos Pasos

1. **Configurar dominio personalizado** (opcional)
2. **Configurar backup automático** de base de datos
3. **Configurar monitoreo** de uptime
4. **Optimizar rendimiento** según uso
5. **Configurar notificaciones** de errores

---

**¡Tu bot de Telegram estará listo para crear campañas de Meta en producción! 🚀**
