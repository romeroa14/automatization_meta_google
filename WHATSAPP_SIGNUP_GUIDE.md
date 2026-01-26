# WhatsApp Embedded Signup - Guía de Ejecución

## ✅ Implementación Completada

Se ha implementado exitosamente el **WhatsApp Embedded Signup v4** en tu sistema CRM Admetricas.

### Archivos Creados/Modificados

#### Frontend
- ✅ `src/components/WhatsAppSignupButton.vue` - Componente de signup con Facebook SDK
- ✅ `src/pages/LoginPage.vue` - Integración del botón de WhatsApp
- ✅ `quasar.config.js` - Cambiado a HTTP en desarrollo

#### Backend
- ✅ `app/Http/Controllers/Api/WhatsAppSignupController.php` - Controlador principal
- ✅ `routes/api.php` - Rutas agregadas
- ✅ `database/migrations/2025_12_14_152545_create_user_facebook_connections_table.php` - Tabla con campos de WhatsApp
- ✅ `database/migrations/production_whatsapp_signup.sql` - SQL para producción
- ✅ `config/services.php` - Configuración agregada
- ✅ `.env.example` - Variables de entorno documentadas

---

## 🚀 Cómo Ejecutar el Sistema (Desarrollo)

### Paso 1: Iniciar Base de Datos
```bash
# Asegurarse de que PostgreSQL esté corriendo
sudo systemctl start postgresql
```

### Paso 2: Iniciar Backend (Laravel)
**Terminal 1:**
```bash
cd /var/www/html/automatization_fb_google
php artisan serve --host=0.0.0.0 --port=8001
```

Deberías ver:
```
INFO  Server running on [http://0.0.0.0:8001]
```

**Nota**: Usamos puerto 8001 porque el 8000 está ocupado por Docker.

### Paso 3: Iniciar Frontend (Quasar)
**Terminal 2:**
```bash
cd /var/www/html/automatization_fb_google/frontend/admetricas-mobile
npm run dev
```

Deberías ver:
```
App URL................ http://localhost:9000/
```

### Paso 4: Abrir Navegador
Abre tu navegador en: **http://localhost:9000/login**

---

## 🧪 Probar WhatsApp Embedded Signup

1. Ve a http://localhost:9000/login
2. Verás el botón **"Conectar WhatsApp Business"** con badge PREMIUM
3. Haz clic en el botón
4. Se abrirá el flujo de Facebook con tu configuración
5. Completa el registro con tu cuenta de WhatsApp Business
6. El sistema te autenticará automáticamente

---

## ⚙️ Configuración Requerida

### Variables de Entorno (.env)
Asegúrate de tener configuradas:
```env
FACEBOOK_WA_SIGNUP_CONFIG_ID=3045859985802477
FACEBOOK_WEBHOOK_VERIFY_TOKEN=admetricas_webhook_token
```

### Facebook App
Ya configuraste en Facebook:
- ✅ Config ID: 3045859985802477
- ✅ Dominios permitidos: localhost:9000, app.admetricas.com
- ✅ OAuth configurado

---

## 📊 Para Producción

### 1. Ejecutar SQL en Base de Datos
```bash
psql -h <host> -U <usuario> -d <database> -f database/migrations/production_whatsapp_signup.sql
```

### 2. Agregar al .env de Producción
```env
FACEBOOK_WA_SIGNUP_CONFIG_ID=3045859985802477
FACEBOOK_WEBHOOK_VERIFY_TOKEN=admetricas_webhook_token
```

### 3. Configurar Webhook en Facebook
- URL: `https://admetricas.com/api/whatsapp-signup/webhook/account-update`
- Verify Token: `admetricas_webhook_token`
- Suscribirse al evento: `account_update`

---

## 🔍 Troubleshooting

### Error: CORS / Network Error
**Problema:** Laravel no está corriendo
**Solución:** Ejecutar `php artisan serve --host=0.0.0.0 --port=8000`

### Error: Connection refused
**Problema:** PostgreSQL no está corriendo
**Solución:** `sudo systemctl start postgresql`

### Error: Facebook Config not found
**Problema:** Falta configurar FACEBOOK_WA_SIGNUP_CONFIG_ID en .env
**Solución:** Agregar la variable con el valor 3045859985802477

### Error: Mixed Content (HTTPS/HTTP)
**Problema:** Frontend en HTTPS, backend en HTTP
**Solución:** Ya resuelto - `quasar.config.js` tiene `https: false`

---

## 📝 Flujo de Usuario

1. **Usuario nuevo llega a /login**
2. Ve opción tradicional (email/password) y opción premium (WhatsApp)
3. Hace clic en "Conectar WhatsApp Business"
4. Facebook SDK carga y muestra el flujo
5. Usuario completa registro en Facebook
6. Sistema recibe:
   - Code (token intercambiable)
   - WABA ID
   - Business ID
   - Phone Number ID
7. Backend intercambia code por access token
8. Backend crea/actualiza usuario en BD
9. Backend retorna token de autenticación Laravel
10. Usuario queda autenticado y redirigido a dashboard

---

## 🎯 Modelo de Negocio

**Instagram:** Base gratuita
**WhatsApp:** Premium/Opcional

Los usuarios pueden:
- Usar Instagram sin WhatsApp
- Agregar WhatsApp como upgrade premium
- Gestionar ambos canales desde una sola app

---

## 📞 Soporte

Si tienes problemas:
1. Verifica que ambos servidores estén corriendo (Laravel y Quasar)
2. Revisa la consola del navegador para errores específicos
3. Revisa logs de Laravel: `storage/logs/laravel.log`
4. Verifica configuración de Facebook en el Panel de Developers
