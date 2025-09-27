# 🤖 Instagram Chatbot Setup - Admetricas

## 📋 **CONFIGURACIÓN COMPLETA DEL CHATBOT**

### **🔗 URLs PARA META:**
- **Webhook URL:** `https://admetricas.com/webhook/instagram`
- **Verification Token:** `adsbot`

### **⚙️ VARIABLES DE ENTORNO NECESARIAS:**

```env
# Instagram API
INSTAGRAM_ACCESS_TOKEN=tu_token_de_acceso_aqui
INSTAGRAM_VERIFY_TOKEN=adsbot
INSTAGRAM_APP_SECRET=tu_app_secret_aqui

# Gemini AI (opcional)
GEMINI_API_KEY=tu_gemini_api_key_aqui
```

### **🚀 FLUJO DEL CHATBOT:**

#### **1️⃣ Entrada (Trigger)**
- **Webhook Node:** `POST /webhook/instagram`
- Recibe mensajes de usuarios en Instagram

#### **2️⃣ Validación inicial**
- **IF Node:** Verifica que sea mensaje de texto
- Ignora reacciones, follow, etc.

#### **3️⃣ Delay humano**
- **Wait/Delay:** 2-5 segundos aleatorios
- Simula que alguien leyó el mensaje

#### **4️⃣ Consulta a Base de Datos**
- **Database Node:** Busca planes en `AdvertisingPlan`
- Lookup por palabras clave en nombre/descripción

#### **5️⃣ Procesamiento con IA**
- **HTTP Request Node:** Gemini API
- Contexto: "Eres asistente de Admetricas"
- Instrucciones: Responder breve, clara, tono humano

#### **6️⃣ Construcción de Respuesta**
- **Function Node:** Une IA + datos reales de BD
- Ejemplo: "Hola 👋, tenemos el Plan Básico por $30..."

#### **7️⃣ Respuesta en Instagram**
- **HTTP Request Node:** Meta Messenger API
- Envía respuesta al usuario

#### **8️⃣ Registro en CRM**
- **Database Node:** Guarda en `TelegramConversation`
- Campos: user_id, platform, user_message, bot_response, status

### **📊 ESTRUCTURA DE DATOS:**

#### **AdvertisingPlan (Planes disponibles):**
```php
- id
- name (ej: "Plan Básico")
- description (ej: "Campaña de 10 días")
- total_budget (ej: 30.00)
- status (active/inactive)
```

#### **TelegramConversation (CRM):**
```php
- user_id (Instagram sender ID)
- platform ('instagram')
- user_message (mensaje del usuario)
- bot_response (respuesta del bot)
- status ('active', 'closed', 'human_intervention')
```

### **🔧 CONFIGURACIÓN EN META:**

1. **Ve a Meta for Developers**
2. **Selecciona tu app**
3. **Webhooks > Instagram**
4. **Configurar:**
   - **Callback URL:** `https://admetricas.com/webhook/instagram`
   - **Verify Token:** `adsbot`
   - **Suscribirse a:** `messages`

### **🧪 TESTING:**

#### **Probar webhook:**
```bash
curl -X GET "https://admetricas.com/webhook/instagram?hub_mode=subscribe&hub_verify_token=adsbot&hub_challenge=test123"
```

#### **Probar mensaje:**
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

### **📈 EMBUDO DE PERSUASIÓN:**

#### **Clasificación automática:**
- **Interesado:** Guardar en lista de seguimiento
- **Calificado:** Pasar a WhatsApp
- **Venta:** Marcar como cerrado

#### **Intervención humana:**
- **Fallback:** Si `status = 'human_intervention'`
- **Bot se detiene** y pasa control a humano

### **🎯 PALABRAS CLAVE PARA PLANES:**

```php
// Ejemplos de matching
"plan básico" -> Plan Básico ($30)
"combo 2" -> Combo Escolar 2 ($25)
"marketing" -> Plan Marketing ($50)
"publicidad" -> Plan Publicidad ($40)
```

### **📱 RESPUESTAS AUTOMÁTICAS:**

#### **Saludo:**
"Hola 👋, bienvenido a Admetricas. ¿En qué puedo ayudarte?"

#### **Precios:**
"💰 Para información sobre precios, visita: https://admetricas.com"

#### **Contacto:**
"📞 Escríbenos a WhatsApp: https://wa.me/584241234567"

#### **Servicios:**
"🛠️ Ofrecemos marketing digital y automatización. ¿Te interesa algún servicio específico?"

### **🔍 MONITOREO:**

#### **Logs importantes:**
- Mensajes recibidos
- Respuestas enviadas
- Errores de API
- Conversaciones registradas

#### **Métricas:**
- Mensajes procesados
- Respuestas exitosas
- Conversiones a WhatsApp
- Ventas cerradas

### **🚨 TROUBLESHOOTING:**

#### **Error 403:**
- Verificar `INSTAGRAM_VERIFY_TOKEN`
- Comprobar URL del webhook

#### **Error 500:**
- Revisar logs de Laravel
- Verificar tokens de acceso
- Comprobar conexión a BD

#### **No responde:**
- Verificar `INSTAGRAM_ACCESS_TOKEN`
- Comprobar permisos de la app
- Revisar configuración de webhook

### **📞 SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com