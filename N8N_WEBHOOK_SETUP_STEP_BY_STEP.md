# 🔧 Configuración Paso a Paso del Webhook Node en n8n

## 🎯 **PASOS PARA CONFIGURAR N8N:**

### ** PASO 1: Crear Webhook Node**

1. **Abrir n8n** en tu navegador
2. **Crear nuevo workflow**
3. **Arrastrar Webhook Node** al canvas
4. **Configurar parámetros:**

#### **Configuración del Webhook Node:**
```json
{
  "httpMethod": "POST",
  "path": "instagram-webhook",
  "authentication": "None",
  "respond": "Using 'Respond to Webhook' Node"
}
```

### ** PASO 2: Configurar el flujo completo**

#### **Nodo 1: Webhook Node (Entrada)**
- **HTTP Method:** POST
- **Path:** `instagram-webhook`
- **Authentication:** None
- **Respond:** Using 'Respond to Webhook' Node

#### **Nodo 2: IF Node (Validación)**
```json
{
  "condition": "{{$json.sender_id}}",
  "true": "Procesar mensaje",
  "false": "Terminar flujo"
}
```

#### **Nodo 3: Delay Node (Delay humano)**
```json
{
  "delay": "2-5 seconds"
}
```

#### **Nodo 4: HTTP Request Node (Respuesta a admetricas.com)**
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

### ** PASO 3: Activar el workflow**

1. **Hacer clic en "Execute workflow"** en n8n
2. **Esperar** a que se active el webhook
3. **Verificar** que aparezca la URL del webhook

### ** PASO 4: Probar la conexión**

#### **Comando de prueba:**
```bash
curl -X POST "https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "12334",
    "message": "Hola desde admetricas.com",
    "message_id": "test_123",
    "timestamp": "2025-09-27T15:30:00Z",
    "platform": "instagram"
  }'
```

#### **Respuesta esperada:**
```json
{
  "status": "success",
  "message": "Webhook de n8n procesado",
  "timestamp": "2025-09-27T15:30:00Z"
}
```

### ** PASO 5: Probar el flujo completo**

#### **Desde admetricas.com:**
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
        "mid": "test_123",
        "text": "Hola, quiero información sobre planes"
      }
    }
  }'
```

## 🔍 **VERIFICACIÓN:**

### **Logs de admetricas.com:**
- ✅ "Instagram webhook recibido"
- ✅ "Mensaje de prueba de Facebook procesado"
- ✅ "Mensaje enviado a n8n exitosamente"

### **Logs de n8n:**
- ✅ Webhook recibido
- ✅ Datos procesados
- ✅ Respuesta enviada

### **Respuesta final:**
- ✅ Mensaje enviado a Instagram
- ✅ Usuario recibe respuesta

## 🚨 **TROUBLESHOOTING:**

### **Error 404: "Webhook not registered"**
- ✅ Ejecutar workflow en n8n
- ✅ Verificar que el Webhook Node esté activo
- ✅ Comprobar la URL del webhook

### **Error 500: "Internal Server Error"**
- ✅ Revisar logs de n8n
- ✅ Verificar configuración del workflow
- ✅ Comprobar conexión a admetricas.com

### **No responde:**
- ✅ Verificar que el workflow esté ejecutándose
- ✅ Comprobar URLs de webhook
- ✅ Revisar logs de ambos sistemas

## 📊 **ESTRUCTURA DE DATOS:**

### **Entrada (admetricas.com → n8n):**
```json
{
  "sender_id": "12334",
  "message": "Hola, quiero información sobre planes",
  "message_id": "random_mid",
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

## 🎯 **RESULTADO FINAL:**

- **✅ Instagram** envía mensaje
- **✅ Meta** webhook a admetricas.com
- **✅ admetricas.com** procesa y envía a n8n
- **✅ n8n** procesa con IA y responde
- **✅ admetricas.com** recibe respuesta de n8n
- **✅ admetricas.com** envía respuesta a Instagram
- **✅ Usuario** recibe respuesta final
