# 🔧 Corrección del Flujo de n8n para Instagram Chatbot

## ❌ **PROBLEMA ACTUAL:**
- **Lookup Conversation** se ejecuta **ANTES** de registrar el usuario
- **No encuentra** el usuario porque no existe en Google Sheets
- **Se queda** en el lookup sin generar output

## ✅ **SOLUCIÓN:**

### **🔄 FLUJO CORRECTO:**

#### **1️⃣ Webhook IG (Trigger)**
- Recibe datos de Instagram
- Conecta a: **Parse Incoming**

#### **2️⃣ Parse Incoming (Function)**
- Procesa y extrae datos relevantes
- Conecta a: **Append row in sheet** ✅ **NUEVO ORDEN**

#### **3️⃣ Append row in sheet (Google Sheets)**
- **REGISTRA** el usuario en Google Sheets
- **Operación:** Append
- **Datos a guardar:**
  ```json
  {
    "userId": "{{$json.sender_id}}",
    "messageText": "{{$json.message}}",
    "timestamp": "{{$json.timestamp}}",
    "platform": "{{$json.platform}}",
    "messageId": "{{$json.message_id}}"
  }
  ```
- Conecta a: **Detect Team Member**

#### **4️⃣ Detect Team Member (Function)**
- Lógica para detectar si necesita intervención humana
- Conecta a: **If**

#### **5️⃣ If (Conditional)**
- **True:** Conecta a **Append row in sheet1** (intervención humana)
- **False:** Conecta a **Build Prompt & Plans**

#### **6️⃣ Build Prompt & Plans (Function)**
- Prepara prompt para IA
- Conecta a: **OpenAI - Get Response**

#### **7️⃣ OpenAI - Get Response (HTTP Request)**
- Obtiene respuesta de IA
- Conecta a: **Parse AI Response**

#### **8️⃣ Parse AI Response (Function)**
- Procesa respuesta de IA
- Conecta a: **Wait (simulate typing)**

#### **9️⃣ Wait (simulate typing) (Delay)**
- Simula tiempo de escritura
- Conecta a: **Send IG Message (Graph API)**

#### **🔟 Send IG Message (Graph API) (HTTP Request)**
- Envía respuesta a Instagram
- Conecta a: **Append row in sheet** (logging final)

#### **1️⃣1️⃣ Append row in sheet (Google Sheets)**
- **LOGGING FINAL** de la conversación
- Registra: mensaje del usuario + respuesta del bot

## 🔧 **CONFIGURACIÓN DEL NODO "Append row in sheet":**

### **📋 Parámetros:**
```json
{
  "resource": "Sheet",
  "operation": "Append",
  "spreadsheetId": "mgqyunnF86EVE0eJn0xX5PdUzH1oszH2VJHMCx-pI2Q",
  "range": "Conversations!A:Z",
  "data": {
    "userId": "{{$json.sender_id}}",
    "messageText": "{{$json.message}}",
    "timestamp": "{{$json.timestamp}}",
    "platform": "{{$json.platform}}",
    "messageId": "{{$json.message_id}}"
  }
}
```

### **📊 Estructura de Google Sheet:**
```
| A (userId)           | B (messageText)        | C (timestamp)           | D (platform) | E (messageId) |
|----------------------|------------------------|------------------------|--------------|---------------|
| 10089262611098650    | Interesado en el plan  | 2025-09-27T16:09:53Z   | instagram    | aWdfZAG1fa... |
| 12334                | Hola, quiero info      | 2025-09-27T15:59:14Z   | instagram    | random_mid    |
```

## 🎯 **PASOS PARA CORREGIR:**

### ** PASO 1: Reorganizar conexiones**
1. **Parse Incoming** → **Append row in sheet** (nuevo)
2. **Append row in sheet** → **Detect Team Member**
3. **Detect Team Member** → **If**
4. **If** → **Build Prompt & Plans** (false path)
5. **If** → **Append row in sheet1** (true path)

### ** PASO 2: Configurar nodo "Append row in sheet"**
1. **Resource:** Sheet
2. **Operation:** Append
3. **Spreadsheet ID:** Tu ID correcto
4. **Range:** Conversations!A:Z
5. **Data:** Mapear campos del JSON

### ** PASO 3: Probar el flujo**
1. **Ejecutar** workflow
2. **Verificar** que se registra el usuario
3. **Comprobar** que continúa el flujo
4. **Monitorear** logs de n8n

## 📊 **FLUJO COMPLETO CORREGIDO:**

```
Webhook IG → Parse Incoming → Append row in sheet → Detect Team Member → If
                                                                    ↓
                                            Build Prompt & Plans → OpenAI → Parse AI → Wait → Send IG → Append row (final)
                                                                    ↓
                                                            Append row in sheet1 (humano)
```

## 🚨 **TROUBLESHOOTING:**

### **Error: "No data found" en Lookup**
- ✅ **Solución:** Mover Lookup **DESPUÉS** de Append
- ✅ **Verificar:** Que el usuario se registre primero

### **Error: "Resource not found"**
- ✅ **Solución:** Verificar Spreadsheet ID
- ✅ **Verificar:** Que la hoja "Conversations" existe

### **Error: "Permission denied"**
- ✅ **Solución:** Verificar credenciales de Google
- ✅ **Verificar:** Que la cuenta tiene permisos

## 📞 **SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com
