# 🔗 Conexión con n8n - Admetricas Chatbot

## 🎯 **FLUJO COMPLETO CON N8N:**

### **📊 ARQUITECTURA:**

```
Instagram → Meta Webhook → admetricas.com → n8n → Instagram
```

### **🔗 URLs PARA N8N:**

#### **Endpoint de n8n:**
- **URL:** `https://admetricas.com/webhook/n8n`
- **Método:** `POST`
- **Verificación:** `GET` con parámetro `challenge`

#### **Endpoint de Instagram:**
- **URL:** `https://admetricas.com/webhook/instagram`
- **Método:** `POST` y `GET`
- **Token:** `adsbot`

### **🚀 CONFIGURACIÓN EN N8N:**

#### **1️⃣ Webhook Node (Entrada):**
```json
{
  "httpMethod": "POST",
  "path": "instagram-webhook",
  "responseMode": "responseNode"
}
```

#### **2️⃣ HTTP Request Node (Salida a Admetricas):**
```json
{
  "method": "POST",
  "url": "https://admetricas.com/webhook/n8n",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "sender_id": "{{$json.sender.id}}",
    "message": "{{$json.message.text}}",
    "timestamp": "{{$json.timestamp}}",
    "platform": "instagram"
  }
}
```

### **📋 FLUJO DETALLADO:**

#### **1️⃣ Entrada (Instagram → Admetricas):**
- **Instagram** envía mensaje
- **Meta Webhook** → `https://admetricas.com/webhook/instagram`
- **Admetricas** procesa y envía a n8n

#### **2️⃣ Procesamiento (n8n):**
- **Webhook Node** recibe datos
- **IF Node** valida mensaje de texto
- **Delay Node** (2-5 segundos)
- **HTTP Request Node** → OpenAI/Gemini
- **Database Node** → Consulta planes
- **Function Node** → Construye respuesta

#### **3️⃣ Salida (n8n → Admetricas → Instagram):**
- **HTTP Request Node** → `https://admetricas.com/webhook/n8n`
- **Admetricas** recibe respuesta
- **Admetricas** envía a Instagram

### **🧪 TESTING DE CONEXIÓN:**

#### **Probar endpoint de n8n:**
```bash
curl -X GET "https://admetricas.com/webhook/n8n?challenge=test123"
```

#### **Probar webhook de n8n:**
```bash
curl -X POST "https://admetricas.com/webhook/n8n" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "123456789",
    "message": "Hola desde n8n",
    "timestamp": "2025-09-27T10:30:00Z",
    "platform": "instagram"
  }'
```

### **📊 ESTRUCTURA DE DATOS:**

#### **Entrada (Instagram → Admetricas):**
```json
{
  "entry": [{
    "messaging": [{
      "sender": {"id": "123456789"},
      "message": {"text": "Hola, quiero información"},
      "timestamp": 1695814200000
    }]
  }]
}
```

#### **Salida (n8n → Admetricas):**
```json
{
  "sender_id": "123456789",
  "message": "¡Hola! 👋 Bienvenido a Admetricas...",
  "timestamp": "2025-09-27T10:30:00Z",
  "platform": "instagram"
}
```

### **🔧 CONFIGURACIÓN EN N8N:**

#### **Webhook Node (Entrada):**
- **HTTP Method:** POST
- **Path:** `instagram-webhook`
- **Response Mode:** `responseNode`

#### **HTTP Request Node (Salida):**
- **Method:** POST
- **URL:** `https://admetricas.com/webhook/n8n`
- **Headers:** `Content-Type: application/json`
- **Body:** JSON con `sender_id` y `message`

### **📱 CONFIGURACIÓN EN META:**

#### **Webhook Settings:**
- **Callback URL:** `https://admetricas.com/webhook/instagram`
- **Verify Token:** `adsbot`
- **Subscriptions:** `messages`

### **🚨 TROUBLESHOOTING:**

#### **Error 403:**
- ✅ Verificar URL del webhook
- ✅ Comprobar token de verificación
- ✅ Verificar que la app esté activa

#### **Error 500:**
- ✅ Revisar logs de Laravel
- ✅ Verificar tokens de acceso
- ✅ Comprobar conexión a BD

#### **No responde:**
- ✅ Verificar tokens de Instagram
- ✅ Comprobar configuración de n8n
- ✅ Revisar logs de ambos sistemas

### **📈 MONITOREO:**

#### **Logs importantes:**
- Mensajes recibidos de Instagram
- Datos enviados a n8n
- Respuestas recibidas de n8n
- Mensajes enviados a Instagram

#### **Métricas:**
- Mensajes procesados
- Respuestas exitosas
- Tiempo de procesamiento
- Errores de conexión

### **🔗 VENTAJAS DE ESTA ARQUITECTURA:**

1. **✅ Estabilidad:** Tu dominio estable
2. **✅ Control:** Tu servidor, tus reglas
3. **✅ Escalabilidad:** Puedes agregar más funcionalidades
4. **✅ Integración:** Base de datos, IA, CRM
5. **✅ Monitoreo:** Logs completos en tu sistema

### **📞 SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com
