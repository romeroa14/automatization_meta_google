# 🔧 Configuración Correcta de n8n para Admetricas

## 🎯 **ARQUITECTURA CORRECTA:**

```
Instagram → Meta → admetricas.com → n8n → admetricas.com → Instagram
```

## ❌ **CONFIGURACIÓN INCORRECTA (Actual):**

### **Webhook Node (Primer nodo):**
- **URL:** `https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook`
- **Método:** POST
- **Problema:** n8n recibe directamente de Instagram

## ✅ **CONFIGURACIÓN CORRECTA:**

### **1️⃣ HTTP Request Node (Primer nodo):**
- **Método:** POST
- **URL:** `https://admetricas.com/webhook/n8n`
- **Headers:** `Content-Type: application/json`
- **Body:** JSON con datos de Instagram

### **2️⃣ Flujo Correcto:**

#### **Paso 1: Instagram → admetricas.com**
- Instagram envía mensaje
- Meta webhook → `https://admetricas.com/webhook/instagram`
- admetricas.com procesa y envía a n8n

#### **Paso 2: admetricas.com → n8n**
- admetricas.com envía datos a n8n
- n8n procesa con IA
- n8n responde a admetricas.com

#### **Paso 3: admetricas.com → Instagram**
- admetricas.com recibe respuesta de n8n
- admetricas.com envía respuesta a Instagram

## 🔧 **CONFIGURACIÓN EN N8N:**

### **Nodo 1: HTTP Request (Entrada)**
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

### **Nodo 2: IF Node (Validación)**
```json
{
  "condition": "{{$json.message}}",
  "true": "Procesar mensaje",
  "false": "Terminar flujo"
}
```

### **Nodo 3: Delay Node (Delay humano)**
```json
{
  "delay": "2-5 seconds"
}
```

### **Nodo 4: HTTP Request (IA)**
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

### **Nodo 5: HTTP Request (Respuesta a admetricas.com)**
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

## 🚫 **POR QUÉ NO USAR CLOUDFLARE TUNNEL:**

### **Problemas con Cloudflare Tunnel:**
1. **❌ Inestabilidad** - URLs temporales
2. **❌ Dependencia externa** - No es tu servidor
3. **❌ Limitaciones** - Rate limits
4. **❌ Costos** - Servicios de terceros

### **Ventajas de admetricas.com:**
1. **✅ Estabilidad** - Tu dominio permanente
2. **✅ Control total** - Tu servidor
3. **✅ Sin límites** - Tu infraestructura
4. **✅ Gratis** - No costos adicionales

## 🔗 **URLs CORRECTAS:**

### **Para Instagram (Meta):**
- **Webhook URL:** `https://admetricas.com/webhook/instagram`
- **Verification Token:** `adsbot`

### **Para n8n:**
- **Entrada:** `https://admetricas.com/webhook/n8n`
- **Salida:** `https://admetricas.com/webhook/n8n`

## 🧪 **TESTING:**

### **Probar conexión admetricas.com → n8n:**
```bash
curl -X POST "https://admetricas.com/webhook/n8n" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "123456789",
    "message": "Hola desde admetricas.com",
    "timestamp": "2025-09-27T10:30:00Z",
    "platform": "instagram"
  }'
```

## 📋 **PASOS PARA CORREGIR:**

1. **Eliminar** Webhook Node de n8n
2. **Agregar** HTTP Request Node como primer nodo
3. **Configurar** URL: `https://admetricas.com/webhook/n8n`
4. **Probar** conexión
5. **Eliminar** Cloudflare tunnel

## 🎯 **RESULTADO FINAL:**

- **✅ Arquitectura estable** con tu dominio
- **✅ Control total** del flujo
- **✅ Sin dependencias externas**
- **✅ Escalabilidad** para el futuro
