# 🚀 Guía de Activación de n8n para Admetricas

## ❌ **PROBLEMA ACTUAL:**
- **admetricas.com** está funcionando ✅
- **n8n** no está recibiendo datos ❌
- **Error:** "Webhook not registered" ❌

## 🔧 **SOLUCIÓN PASO A PASO:**

### ** PASO 1: Configurar Webhook Node en n8n**

#### **📋 Configuración del Webhook Node:**
```json
{
  "httpMethod": "POST",
  "path": "instagram-webhook",
  "authentication": "None",
  "respond": "Using 'Respond to Webhook' Node"
}
```

### ** PASO 2: Activar el Webhook**

#### **🎯 Pasos críticos:**
1. **Crear el Webhook Node** en n8n
2. **Hacer clic en "Execute workflow"** ⚠️ **IMPORTANTE**
3. **Esperar** a que aparezca "Listening for test event"
4. **Verificar** que la URL del webhook esté activa

### ** PASO 3: Probar la conexión**

#### **🧪 Comando de prueba:**
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

#### **✅ Respuesta esperada:**
```json
{
  "status": "success",
  "message": "Webhook de n8n procesado",
  "timestamp": "2025-09-27T15:30:00Z"
}
```

### ** PASO 4: Verificar el flujo completo**

#### **🔄 Flujo completo:**
1. **Instagram** → Meta → **admetricas.com** ✅
2. **admetricas.com** → **n8n** ❌ (necesita activación)
3. **n8n** → **admetricas.com** ❌ (necesita activación)
4. **admetricas.com** → **Instagram** ❌ (necesita activación)

## 🚨 **TROUBLESHOOTING:**

### **Error: "Webhook not registered"**
- ✅ **Solución:** Ejecutar workflow en n8n
- ✅ **Verificar:** Que el Webhook Node esté activo
- ✅ **Comprobar:** URL del webhook

### **Error: "Connection refused"**
- ✅ **Solución:** Verificar que n8n esté ejecutándose
- ✅ **Verificar:** URL de Cloudflare tunnel
- ✅ **Comprobar:** Configuración del webhook

### **No responde:**
- ✅ **Solución:** Revisar logs de n8n
- ✅ **Verificar:** Configuración del workflow
- ✅ **Comprobar:** Conexión a admetricas.com

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

## 🎯 **PRÓXIMOS PASOS:**

1. **✅ Configurar** Webhook Node en n8n
2. **✅ Ejecutar** workflow para activar webhook
3. **✅ Probar** conexión con admetricas.com
4. **✅ Verificar** flujo completo
5. **✅ Monitorear** logs de ambos sistemas

## 📞 **SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com
