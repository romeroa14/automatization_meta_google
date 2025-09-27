# 🔧 Configuración Correcta de n8n para Admetricas

## 🎯 **ARQUITECTURA CORRECTA:**

```
Instagram → Meta → admetricas.com → n8n → admetricas.com → Instagram
```

## ✅ **ADMETRICAS ES EL PUENTE:**

### **1️⃣ Recibe webhook de Meta:**
- **URL:** `https://admetricas.com/webhook/instagram`
- **Método:** POST
- **Datos:** Mensajes de Instagram

### **2️⃣ Envía a n8n:**
- **URL:** `https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook`
- **Método:** POST
- **Datos:** Procesados por admetricas.com

### **3️⃣ Recibe respuesta de n8n:**
- **URL:** `https://admetricas.com/webhook/n8n`
- **Método:** POST
- **Datos:** Respuesta procesada

### **4️⃣ Envía a Instagram:**
- **API:** Meta Messenger API
- **Método:** POST
- **Datos:** Respuesta final

## 🔧 **CONFIGURACIÓN EN N8N:**

### **❌ CONFIGURACIÓN INCORRECTA:**
- **Webhook Node** como primer nodo
- **Recibe directamente** de Instagram
- **No funciona** porque Instagram no envía a n8n

### **✅ CONFIGURACIÓN CORRECTA:**

#### **Nodo 1: Webhook Node (Entrada)**
```json
{
  "httpMethod": "POST",
  "path": "instagram-webhook",
  "responseMode": "responseNode"
}
```

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

#### **Nodo 4: HTTP Request Node (IA)**
```json
{
  "method": "POST",
  "url": "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "contents": [{
      "parts": [{
        "text": "Eres el asistente de Admetricas..."
      }]
    }]
  }
}
```

#### **Nodo 5: HTTP Request Node (Respuesta a admetricas.com)**
```json
{
  "method": "POST",
  "url": "https://admetricas.com/webhook/n8n",
  "headers": {
    "Content-Type": "application/json"
  },
  "body": {
    "sender_id": "{{$json.sender_id}}",
    "message": "{{$json.ai_response}}",
    "timestamp": "{{$json.timestamp}}",
    "platform": "instagram"
  }
}
```

## 📊 **ESTRUCTURA DE DATOS:**

### **Entrada (admetricas.com → n8n):**
```json
{
  "sender_id": "12334",
  "message": "Hola, quiero información sobre planes",
  "message_id": "random_mid",
  "timestamp": "2025-09-27T15:11:35Z",
  "platform": "instagram"
}
```

### **Salida (n8n → admetricas.com):**
```json
{
  "sender_id": "12334",
  "message": "¡Hola! 👋 Bienvenido a Admetricas...",
  "timestamp": "2025-09-27T15:11:35Z",
  "platform": "instagram"
}
```

## 🧪 **TESTING:**

### **Probar entrada a n8n:**
```bash
curl -X POST "https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "12334",
    "message": "Hola desde admetricas.com",
    "timestamp": "2025-09-27T15:11:35Z",
    "platform": "instagram"
  }'
```

### **Probar salida de n8n:**
```bash
curl -X POST "https://admetricas.com/webhook/n8n" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "12334",
    "message": "Respuesta desde n8n",
    "timestamp": "2025-09-27T15:11:35Z",
    "platform": "instagram"
  }'
```

## 🎯 **VENTAJAS DE ESTA ARQUITECTURA:**

1. **✅ Estabilidad** - Tu dominio estable
2. **✅ Control total** - Tu servidor maneja todo
3. **✅ Escalabilidad** - Puedes agregar más funcionalidades
4. **✅ Monitoreo** - Logs completos en tu sistema
5. **✅ Fallback** - Si n8n falla, respuesta automática

## 📋 **PASOS PARA CONFIGURAR:**

1. **✅ admetricas.com** ya configurado
2. **✅ n8n** configurar Webhook Node
3. **✅ Probar** flujo completo
4. **✅ Monitorear** logs
5. **✅ Optimizar** respuestas

## 🚨 **TROUBLESHOOTING:**

### **Error 404 en n8n:**
- ✅ Verificar que el Webhook Node esté activo
- ✅ Comprobar la URL del webhook
- ✅ Ejecutar el workflow en n8n

### **Error 500 en admetricas.com:**
- ✅ Revisar logs de Laravel
- ✅ Verificar tokens de Instagram
- ✅ Comprobar conexión a n8n

### **No responde:**
- ✅ Verificar configuración de n8n
- ✅ Comprobar URLs de webhook
- ✅ Revisar logs de ambos sistemas
