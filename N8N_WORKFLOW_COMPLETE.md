# 🔧 Workflow Completo de n8n para Admetricas

## 🎯 **CONFIGURACIÓN COMPLETA DEL WORKFLOW:**

### ** PASO 1: Crear Webhook Node (Entrada)**

#### **📋 Configuración:**
```json
{
  "httpMethod": "POST",
  "path": "instagram-webhook",
  "authentication": "None",
  "respond": "Using 'Respond to Webhook' Node"
}
```

### ** PASO 2: Crear IF Node (Validación)**

#### **📋 Configuración:**
```json
{
  "condition": "{{$json.sender_id}}",
  "true": "Procesar mensaje",
  "false": "Terminar flujo"
}
```

### ** PASO 3: Crear Delay Node (Delay humano)**

#### **📋 Configuración:**
```json
{
  "delay": "2-5 seconds"
}
```

### ** PASO 4: Crear HTTP Request Node (Respuesta a admetricas.com)**

#### **📋 Configuración:**
```json
{
  "method": "POST",
  "url": "https://admetricas.com/webhook/n8n",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "sender_id": "{{$json.sender_id}}",
    "message": "¡Hola! 👋 Bienvenido a Admetricas. ¿En qué puedo ayudarte?",
    "timestamp": "{{$json.timestamp}}",
    "platform": "instagram"
  }
}
```

### ** PASO 5: Crear Respond to Webhook Node (Respuesta)**

#### **📋 Configuración:**
```json
{
  "responseCode": 200,
  "responseBody": "OK"
}
```

## 🔄 **FLUJO COMPLETO:**

### **1️⃣ Instagram → Meta → admetricas.com**
- Instagram envía mensaje
- Meta webhook → `https://admetricas.com/webhook/instagram`
- admetricas.com procesa y envía a n8n

### **2️⃣ admetricas.com → n8n**
- admetricas.com envía datos a n8n
- n8n procesa con IA
- n8n responde a admetricas.com

### **3️⃣ n8n → admetricas.com → Instagram**
- admetricas.com recibe respuesta de n8n
- admetricas.com envía respuesta a Instagram
- Usuario recibe respuesta final

## 🧪 **TESTING:**

### **Probar flujo completo:**
```bash
curl -X POST "https://admetricas.com/webhook/instagram" \
  -H "Content-Type: application/json" \
  -d '{
    "field": "messages",
    "value": {
      "sender": {"id": "12334"},
      "recipient": {"id": "23245"},
      "timestamp": "1527459824",
      "message": {
        "mid": "test_789",
        "text": "Hola, quiero información sobre planes"
      }
    }
  }'
```

## 📊 **ESTRUCTURA DE DATOS:**

### **Entrada (admetricas.com → n8n):**
```json
{
  "sender_id": "12334",
  "message": "Hola, quiero información sobre planes",
  "message_id": "test_789",
  "timestamp": "2025-09-27T15:30:00Z",
  "platform": "instagram"
}
```

### **Salida (n8n → admetricas.com):**
```json
{
  "sender_id": "12334",
  "message": "¡Hola! 👋 Bienvenido a Admetricas...",
  "timestamp": "2025-09-27T15:30:00Z",
  "platform": "instagram"
}
```

## 🚨 **TROUBLESHOOTING:**

### **Error 404: "Webhook not registered"**
- ✅ **Solución:** Ejecutar workflow en n8n
- ✅ **Verificar:** Que el Webhook Node esté activo
- ✅ **Comprobar:** URL del webhook

### **Error 500: "Internal Server Error"**
- ✅ **Solución:** Revisar logs de n8n
- ✅ **Verificar:** Configuración del workflow
- ✅ **Comprobar:** Conexión a admetricas.com

### **No responde:**
- ✅ **Solución:** Verificar que el workflow esté ejecutándose
- ✅ **Comprobar:** URLs de webhook
- ✅ **Revisar:** Logs de ambos sistemas

## 🎯 **RESULTADO FINAL:**

- **✅ Instagram** envía mensaje
- **✅ Meta** webhook a admetricas.com
- **✅ admetricas.com** procesa y envía a n8n
- **✅ n8n** procesa con IA y responde
- **✅ admetricas.com** recibe respuesta de n8n
- **✅ admetricas.com** envía respuesta a Instagram
- **✅ Usuario** recibe respuesta final
