# 🔧 Solución de Error de Google Sheets en n8n

## ❌ **PROBLEMA ACTUAL:**
- **Error:** "The resource you are requesting could not be found"
- **Código:** 404
- **Nodo:** Google Sheets Lookup
- **Causa:** Configuración incorrecta del nodo

## 🔍 **ANÁLISIS DEL ERROR:**

### **📊 Datos que llegan:**
```json
{
  "sender_id": "10089262611098650",
  "message": "Interesado en el plan de 9$",
  "message_id": "aWdfZAG1faXRlbToxOklHTWVzc2FnZAUlEOjE3ODQxNDQ4NjQzMjgwNzkwOjM0MDI4MjM2Njg0MTcxMDMwMTI0NDI1ODY3NDAxODg5NDk1MjI1MDozMjQ0NzYyNzA2MzAxNzkwNDczNDM2MjIwMzU1MzAwNTU2OAZDZD",
  "timestamp": "2025-09-27T16:09:53.547171Z",
  "platform": "instagram"
}
```

### **🔧 CONFIGURACIÓN ACTUAL:**
- **Spreadsheet ID:** `mgqyunnF86EVE0eJn0xX5PdUzH1oszH2VJHMCx-pI2Q`
- **Range:** `Conversations!A:Z`
- **Lookup Column:** `userId`
- **Lookup Value:** `{{$json.userId}}` ❌ **PROBLEMA**

## 🚀 **SOLUCIONES:**

### **1️⃣ CORREGIR LOOKUP VALUE:**

#### **❌ PROBLEMA:**
```json
"Lookup Value": "{{$json.userId}}"  // userId está vacío
```

#### **✅ SOLUCIÓN:**
```json
"Lookup Value": "{{$json.sender_id}}"  // Usar sender_id
```

### **2️⃣ VERIFICAR ESTRUCTURA DE GOOGLE SHEET:**

#### **📋 Estructura requerida:**
```
A1: userId          B1: messageText     C1: timestamp
A2: 10089262611098650  B2: Interesado...   C2: 2025-09-27T16:09:53Z
```

### **3️⃣ CONFIGURACIÓN CORRECTA:**

#### **🔧 Parámetros del nodo:**
- **Resource:** Sheet
- **Operation:** Lookup
- **Spreadsheet ID:** `mgqyunnF86EVE0eJn0xX5PdUzH1oszH2VJHMCx-pI2Q`
- **Range:** `Conversations!A:Z`
- **Data Start Row:** `2`
- **Key Row:** `0`
- **Lookup Column:** `userId`
- **Lookup Value:** `{{$json.sender_id}}` ✅ **CORREGIDO**

### **4️⃣ ALTERNATIVA: CREAR NUEVO REGISTRO:**

#### **🔧 Si no existe el usuario:**
- **Operation:** Append
- **Spreadsheet ID:** `mgqyunnF86EVE0eJn0xX5PdUzH1oszH2VJHMCx-pI2Q`
- **Range:** `Conversations!A:Z`
- **Data:** 
  ```json
  {
    "userId": "{{$json.sender_id}}",
    "messageText": "{{$json.message}}",
    "timestamp": "{{$json.timestamp}}",
    "platform": "{{$json.platform}}"
  }
  ```

## 🎯 **PASOS PARA CORREGIR:**

### ** PASO 1: Verificar Google Sheet**
1. **Abrir** Google Sheets
2. **Verificar** que existe la hoja "Conversations"
3. **Verificar** que la columna A tiene "userId"
4. **Verificar** que hay datos en la fila 2

### ** PASO 2: Corregir Lookup Value**
1. **Abrir** el nodo Google Sheets en n8n
2. **Cambiar** "Lookup Value" de `{{$json.userId}}` a `{{$json.sender_id}}`
3. **Guardar** la configuración

### ** PASO 3: Probar el flujo**
1. **Ejecutar** el workflow
2. **Verificar** que no hay errores
3. **Comprobar** que se encuentra el usuario

## 📊 **ESTRUCTURA DE DATOS ESPERADA:**

### **📋 Google Sheet "Conversations":**
```
| A (userId)           | B (messageText)        | C (timestamp)           | D (platform) |
|----------------------|------------------------|-------------------------|--------------|
| 10089262611098650    | Interesado en el plan  | 2025-09-27T16:09:53Z   | instagram    |
| 12334                | Hola, quiero info      | 2025-09-27T15:59:14Z   | instagram    |
```

### **🔧 Configuración del nodo:**
```json
{
  "resource": "Sheet",
  "operation": "Lookup",
  "spreadsheetId": "mgqyunnF86EVE0eJn0xX5PdUzH1oszH2VJHMCx-pI2Q",
  "range": "Conversations!A:Z",
  "dataStartRow": 2,
  "keyRow": 0,
  "lookupColumn": "userId",
  "lookupValue": "{{$json.sender_id}}"
}
```

## 🚨 **TROUBLESHOOTING:**

### **Error: "Resource not found"**
- ✅ **Verificar** que el Spreadsheet ID es correcto
- ✅ **Verificar** que la hoja "Conversations" existe
- ✅ **Verificar** que la columna "userId" existe

### **Error: "No data found"**
- ✅ **Verificar** que hay datos en la fila 2
- ✅ **Verificar** que el Lookup Value es correcto
- ✅ **Verificar** que el Lookup Column es correcto

### **Error: "Permission denied"**
- ✅ **Verificar** que la cuenta de Google tiene permisos
- ✅ **Verificar** que el spreadsheet es accesible
- ✅ **Verificar** que las credenciales son correctas

## 📞 **SOPORTE:**
- **Email:** info@admetricas.com
- **WhatsApp:** https://wa.me/584241234567
- **Sitio web:** https://admetricas.com

