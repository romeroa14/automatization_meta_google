# 🔧 Variables de Entorno para Laravel Cloud

## 📋 Variables Requeridas

Copia y pega estas variables en el dashboard de Laravel Cloud → Environment Variables:

### **Configuración Base de Laravel**
```env
APP_NAME=ADMETRICAS.COM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.laravelcloud.com
```

### **Base de Datos (Laravel Cloud MySQL)**
```env
DB_CONNECTION=mysql
DB_HOST=tu_host_mysql_de_laravel_cloud
DB_PORT=3306
DB_DATABASE=tu_database_name
DB_USERNAME=tu_usuario_mysql
DB_PASSWORD=tu_password_mysql
```

### **Configuración de Telegram Bot**
```env
TELEGRAM_BOT_TOKEN=tu_bot_token_de_telegram
TELEGRAM_WEBHOOK_URL=https://tu-dominio.laravelcloud.com/api/telegram/webhook
```

### **Configuración de Meta/Facebook**
```env
DEFAULT_FACEBOOK_ACCOUNT_ID=tu_cuenta_facebook_id
DEFAULT_AD_ACCOUNT_ID=tu_cuenta_publicitaria_id
```

### **Configuración de Archivos**
```env
FILESYSTEM_DISK=public
```

## 🚀 Pasos de Configuración

### 1. Obtener Token de Telegram Bot
1. Busca `@BotFather` en Telegram
2. Envía `/newbot`
3. Sigue las instrucciones
4. Copia el token que te proporciona

### 2. Configurar Variables en Laravel Cloud
1. Ve a tu proyecto en Laravel Cloud
2. Navega a **Environment Variables**
3. Agrega cada variable de la lista anterior
4. Guarda los cambios

### 3. Ejecutar Comandos Post-Despliegue
```bash
# En el terminal de Laravel Cloud
php artisan migrate --force
php artisan storage:link
php artisan cache:clear
php artisan config:clear
php artisan telegram:setup --token=TU_BOT_TOKEN --webhook=https://tu-dominio.laravelcloud.com/api/telegram/webhook
```

## 🔍 Verificación

### Probar Bot de Telegram
1. Busca tu bot en Telegram
2. Envía `/start`
3. Verifica que responda correctamente

### Probar Panel de Administración
1. Ve a `https://tu-dominio.laravelcloud.com/admin`
2. Verifica que aparezcan todos los recursos
3. Navega entre las secciones

### Probar API Endpoints
```bash
# Probar información del bot
curl https://tu-dominio.laravelcloud.com/api/telegram/bot-info

# Probar tasas de cambio
curl https://tu-dominio.laravelcloud.com/api/exchange-rates
```

## 📱 URLs Importantes

- **Panel Admin:** `https://tu-dominio.laravelcloud.com/admin`
- **Webhook Telegram:** `https://tu-dominio.laravelcloud.com/api/telegram/webhook`
- **API Tasas:** `https://tu-dominio.laravelcloud.com/api/exchange-rates`

## 🚨 Solución de Problemas

### Bot no responde
- Verificar `TELEGRAM_BOT_TOKEN`
- Verificar `TELEGRAM_WEBHOOK_URL`
- Revisar logs en Laravel Cloud

### Error de base de datos
- Verificar variables de DB
- Ejecutar `php artisan migrate --force`

### Archivos no se suben
- Ejecutar `php artisan storage:link`
- Verificar permisos de storage

---

**¡Configuración lista para producción! 🚀**
