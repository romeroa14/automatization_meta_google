# 🔧 Corrección del "Parse Incoming" para admetricas.com

## ❌ **PROBLEMA ACTUAL:**
- **Parse Incoming** está diseñado para payload de Instagram directo
- **Datos llegan** desde admetricas.com ya procesados
- **Resultado:** `userId` y `messageText` quedan vacíos

## ✅ **SOLUCIÓN:**

### **🔧 CÓDIGO CORREGIDO PARA "PARSE INCOMING":**

```javascript
/* Parse admetricas.com payload to normalized format */
const body = items[0].json;

// Los datos llegan desde admetricas.com ya procesados
let messageText = '';
let userId = '';
let messageId = '';
let timestamp = '';
let platform = '';

// Verificar si los datos vienen de admetricas.com (formato procesado)
if (body.sender_id && body.message) {
  // Formato de admetricas.com
  userId = body.sender_id || '';
  messageText = body.message || '';
  messageId = body.message_id || '';
  timestamp = body.timestamp || '';
  platform = body.platform || 'instagram';
} 
// Fallback para payload directo de Instagram (si es necesario)
else if (body.entry && body.entry[0] && body.entry[0].changes) {
  const messaging = body.entry[0].changes[0].value;
  if (messaging && messaging.messages && messaging.messages[0]) {
    const m = messaging.messages[0];
    messageText = m.text || (m.message && m.message.text) || '';
    userId = m.from || m.from_id || m.sender || '';
    messageId = m.mid || '';
    timestamp = m.timestamp || '';
    platform = 'instagram';
  }
}

return [{ 
  json: { 
    raw: body, 
    userId, 
    messageText,
    messageId,
    timestamp,
    platform,
    sender_id: userId,  // Alias para compatibilidad
    message: messageText  // Alias para compatibilidad
  } 
}];
```

## 📊 **ESTRUCTURA DE DATOS DE ENTRADA:**

### **🔍 Datos que llegan de admetricas.com:**
```json
{
  "sender_id": "10089262611098650",
  "message": "Interesado en el plan de 9$",
  "message_id": "aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDQ4NjQzMjgwNzkwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1ODY3NDAxODg5NDk1MjI1MDozMjQ0NzYyNzA2MzAxNzkwNDczNDM2MjIwMzU1MzAwNTU2OAZDZD",
  "timestamp": "2025-09-27T16:09:53.547171Z",
  "platform": "instagram"
}
```

### **✅ Datos de salida del Parse Incoming:**
```json
{
  "raw": { /* datos originales */ },
  "userId": "10089262611098650",
  "messageText": "Interesado en el plan de 9$",
  "messageId": "aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDQ4NjQzMjgwNzkwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1ODY3NDAxODg5NDk1MjI1MDozMjQ0NzYyNzA2MzAxNzkwNDczNDM2MjIwMzU1MzAwNTU2OAZDZD",
  "timestamp": "2025-09-27T16:09:53.547171Z",
  "platform": "instagram",
  "sender_id": "10089262611098650",  // Alias
  "message": "Interesado en el plan de 9$"  // Alias
}
```

## 🔧 **CONFIGURACIÓN DEL NODO "APPEND ROW IN SHEET2":**

### **📋 Mapeo de campos:**
```json
{
  "timestamp": "{{$json.timestamp}}",
  "userId": "{{$json.userId}}",
  "messageText": "{{$json.messageText}}",
  "intent": "{{$json.intent || 'general'}}",
  "confidence": "{{$json.confidence || 0.8}}",
  "response": "{{$json.response || ''}}",
  "status": "{{$json.status || 'pending'}}",
  "human_takeover": "{{$json.human_takeover || false}}",
  "assigned_agent": "{{$json.assigned_agent || ''}}"
}
```

## 🎯 **PASOS PARA IMPLEMENTAR:**

### ** PASO 1: Actualizar "Parse Incoming"**
1. **Abrir** el nodo "Parse Incoming" en n8n
2. **Reemplazar** el código JavaScript con el código corregido
3. **Guardar** la configuración

### ** PASO 2: Verificar mapeo en "Append row in sheet2"**
1. **Abrir** el nodo "Append row in sheet2"
2. **Verificar** que los campos estén mapeados correctamente:
   - `timestamp`: `{{$json.timestamp}}`
   - `userId`: `{{$json.userId}}`
   - `messageText`: `{{$json.messageText}}`
3. **Guardar** la configuración

### ** PASO 3: Probar el flujo**
1. **Ejecutar** el workflow
2. **Verificar** que "Parse Incoming" extrae los datos correctamente
3. **Comprobar** que "Append row in sheet2" recibe los datos
4. **Monitorear** logs de n8n

## 🚨 **TROUBLESHOOTING:**

### **Error: "userId is empty"**
- ✅ **Verificar:** Que el código de "Parse Incoming" esté actualizado
- ✅ **Verificar:** Que los datos llegan con `sender_id`
- ✅ **Verificar:** Que el mapeo en "Append row in sheet2" es correcto

### **Error: "messageText is empty"**
- ✅ **Verificar:** Que el código de "Parse Incoming" esté actualizado
- ✅ **Verificar:** Que los datos llegan con `message`
- ✅ **Verificar:** Que el mapeo en "Append row in sheet2" es correcto

### **Error: "No data found"**
- ✅ **Verificar:** Que el flujo está conectado correctamente
- ✅ **Verificar:** Que "Parse Incoming" se ejecuta antes de "Append row in sheet2"
- ✅ **Verificar:** Que los datos se pasan correctamente entre nodos

## 📞 **SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com
