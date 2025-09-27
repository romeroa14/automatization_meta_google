# 🔑 Cómo Obtener Tokens de Instagram para el Chatbot

## 📋 **PASOS PARA CONFIGURAR INSTAGRAM API:**

### **1️⃣ Crear App en Meta for Developers**

1. **Ve a:** https://developers.facebook.com/
2. **Inicia sesión** con tu cuenta de Meta
3. **Crea una nueva app:**
   - **Tipo:** Business
   - **Nombre:** Admetricas Chatbot
   - **Email de contacto:** tu-email@admetricas.com

### **2️⃣ Configurar Instagram Basic Display**

1. **En tu app, ve a:** "Agregar producto"
2. **Selecciona:** "Instagram Basic Display"
3. **Configura:**
   - **Valid OAuth Redirect URIs:** `https://admetricas.com/auth/instagram/callback`
   - **Deauthorize Callback URL:** `https://admetricas.com/auth/instagram/deauthorize`
   - **Data Deletion Request URL:** `https://admetricas.com/auth/instagram/data-deletion`

### **3️⃣ Obtener Access Token**

#### **Opción A: Token de Usuario (Recomendado)**
1. **Ve a:** Instagram Basic Display > Basic Display
2. **Genera Token de Usuario:**
   - **Permisos:** `user_profile`, `user_media`
   - **Copia el token generado**

#### **Opción B: Token de App (Para testing)**
1. **Ve a:** App Settings > Basic
2. **Copia:** App ID y App Secret
3. **Genera token:** `https://graph.facebook.com/oauth/access_token?client_id=TU_APP_ID&client_secret=TU_APP_SECRET&grant_type=client_credentials`

### **4️⃣ Configurar Webhooks**

1. **Ve a:** Instagram Basic Display > Webhooks
2. **Configurar:**
   - **Callback URL:** `https://admetricas.com/webhook/instagram`
   - **Verify Token:** `adsbot`
   - **Suscribirse a:** `messages`

### **5️⃣ Obtener App Secret**

1. **Ve a:** App Settings > Basic
2. **Copia:** App Secret
3. **Guárdalo** como `INSTAGRAM_APP_SECRET`

## 🔧 **CONFIGURACIÓN EN LARAVEL CLOUD:**

### **Variables de Entorno Necesarias:**

```env
# Instagram Chatbot Configuration
INSTAGRAM_ACCESS_TOKEN=tu_token_de_acceso_aqui
INSTAGRAM_VERIFY_TOKEN=adsbot
INSTAGRAM_APP_SECRET=tu_app_secret_aqui
```

### **Comandos para Configurar:**

```bash
# En el servidor de producción
./configure_instagram_env.sh

# O manualmente:
php artisan config:clear
```

## 🧪 **TESTING DEL WEBHOOK:**

### **1. Verificar Webhook:**
```bash
curl -X GET "https://admetricas.com/webhook/instagram?hub_mode=subscribe&hub_verify_token=adsbot&hub_challenge=test123"
```

**Respuesta esperada:** `test123`

### **2. Probar Mensaje:**
```bash
curl -X POST "https://admetricas.com/webhook/instagram" \
  -H "Content-Type: application/json" \
  -d '{
    "entry": [{
      "messaging": [{
        "sender": {"id": "123456789"},
        "message": {"text": "Hola, quiero información sobre planes"}
      }]
    }]
  }'
```

## 📱 **CONFIGURACIÓN EN META:**

### **Webhook Settings:**
- **Callback URL:** `https://admetricas.com/webhook/instagram`
- **Verify Token:** `adsbot`
- **Subscriptions:** `messages`

### **Permissions Needed:**
- `instagram_basic`
- `instagram_manage_messages`
- `pages_messaging`

## 🚨 **TROUBLESHOOTING:**

### **Error 403 - Forbidden:**
- ✅ Verificar `INSTAGRAM_VERIFY_TOKEN`
- ✅ Comprobar URL del webhook
- ✅ Verificar que la app esté activa

### **Error 500 - Internal Server Error:**
- ✅ Revisar logs: `tail -f storage/logs/laravel.log`
- ✅ Verificar tokens de acceso
- ✅ Comprobar conexión a BD

### **No responde el bot:**
- ✅ Verificar `INSTAGRAM_ACCESS_TOKEN`
- ✅ Comprobar permisos de la app
- ✅ Revisar configuración de webhook

## 📞 **SOPORTE:**

- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com

## 🔗 **ENLACES ÚTILES:**

- **Meta for Developers:** https://developers.facebook.com/
- **Instagram API Docs:** https://developers.facebook.com/docs/instagram-api/
- **Webhook Testing:** https://webhook.site/
- **Laravel Cloud:** https://laravel.com/cloud
