# 🤖 Activación Automática de n8n para Admetricas

## 🎯 **OBJETIVO:**
Activar n8n para que detecte automáticamente los webhooks de admetricas.com **SIN** tener que presionar "Execute workflow" manualmente.

## 🔧 **SOLUCIONES DISPONIBLES:**

### **1️⃣ MODO PRODUCCIÓN (RECOMENDADO)**

#### **📋 Pasos para activar en producción:**
1. **Abrir n8n** en tu navegador
2. **Ir al workflow** que contiene el Webhook Node
3. **Hacer clic en el botón "Active"** (toggle switch) en la parte superior
4. **Confirmar** la activación
5. **Verificar** que el workflow esté marcado como "Active"

#### **✅ Ventajas:**
- **Funciona 24/7** sin intervención manual
- **Detecta automáticamente** todos los webhooks
- **No se desactiva** después de un tiempo

#### **❌ Desventajas:**
- **Consume recursos** del servidor constantemente
- **Requiere** que n8n esté ejecutándose siempre

### **2️⃣ MODO TEST CON LISTENING**

#### **📋 Pasos para mantener activo:**
1. **Ejecutar workflow** una vez
2. **Mantener** la ventana de n8n abierta
3. **No cerrar** el navegador
4. **Verificar** que esté en "Listening for test event"

#### **✅ Ventajas:**
- **No consume recursos** constantemente
- **Fácil de activar/desactivar**

#### **❌ Desventajas:**
- **Se desactiva** después de un tiempo
- **Requiere** intervención manual periódica

### **3️⃣ CONFIGURACIÓN AUTOMÁTICA**

#### **📋 Configuración avanzada:**
```json
{
  "workflow": {
    "active": true,
    "autoStart": true,
    "webhook": {
      "path": "instagram-webhook",
      "method": "POST",
      "autoActivate": true
    }
  }
}
```

## 🚀 **IMPLEMENTACIÓN RECOMENDADA:**

### ** PASO 1: Activar en Producción**
1. **Abrir n8n** → Tu workflow
2. **Hacer clic en "Active"** (toggle switch)
3. **Confirmar** la activación
4. **Verificar** que esté marcado como "Active"

### ** PASO 2: Verificar activación**
```bash
# Probar que el webhook esté activo
curl -X POST "https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook" \
  -H "Content-Type: application/json" \
  -d '{
    "sender_id": "test_auto",
    "message": "Prueba de activación automática",
    "message_id": "auto_test",
    "timestamp": "2025-09-27T16:00:00Z",
    "platform": "instagram"
  }'
```

### ** PASO 3: Monitorear logs**
- **Verificar** que n8n reciba los datos
- **Comprobar** que el workflow se ejecute automáticamente
- **Monitorear** logs de admetricas.com

## 📊 **ESTRUCTURA DE ACTIVACIÓN:**

### **🔴 Estado Inactivo:**
```
n8n: ❌ No escucha webhooks
admetricas.com: ✅ Envía datos
Resultado: ❌ Datos perdidos
```

### **🟢 Estado Activo:**
```
n8n: ✅ Escucha webhooks automáticamente
admetricas.com: ✅ Envía datos
Resultado: ✅ Flujo completo funcionando
```

## 🎯 **PRÓXIMOS PASOS:**

1. **✅ Activar** workflow en n8n (modo producción)
2. **✅ Verificar** que esté marcado como "Active"
3. **✅ Probar** conexión con admetricas.com
4. **✅ Monitorear** logs de ambos sistemas
5. **✅ Verificar** flujo completo automático

## 📞 **SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com

