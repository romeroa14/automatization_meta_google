# 🔑 Cómo Obtener el Token para n8n

## Paso 1: Generar el Token desde la App

1. **Abre tu aplicación:**
   - Ve a: `https://app.admetricas.com/profile`
   - Inicia sesión con tus credenciales

2. **Genera el Token:**
   - En la sección **"API Token para n8n"**
   - Haz clic en el botón **"GENERAR NUEVO TOKEN"**
   - **⚠️ IMPORTANTE:** Copia el token inmediatamente, porque solo se muestra una vez

3. **Guarda el Token:**
   - Copia el token completo (es una cadena larga que empieza con algo como `1|...`)
   - Guárdalo en un lugar seguro

## Paso 2: Configurar n8n para Enviar Respuestas del Bot

### Flujo Completo en n8n:

Tu flujo debería verse así:
```
1. Webhook Trigger (recibe mensaje de WhatsApp)
   ↓
2. Procesa mensaje con AI Agent (Gemini)
   ↓
3. Envía respuesta a WhatsApp (Graph API)
   ↓
4. [NUEVO] Envía respuesta al webhook de Laravel
```

### Configuración del Nodo HTTP Request para Enviar Respuesta a Laravel:

**Después de que el AI Agent genere la respuesta y la envíes a WhatsApp, agrega un nodo HTTP Request:**

1. **URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`
2. **Method:** `POST`
3. **Headers:**
   - **Name:** `Authorization`
   - **Value:** `Bearer {TU_TOKEN_AQUI}` (el token que generaste en la app)
   - **Name:** `Content-Type`
   - **Value:** `application/json`
4. **Body Type:** `JSON`
5. **Body (JSON):**
   ```json
   {
     "client_phone": "{{ $json.fromNumber }}",
     "client_name": "{{ $json.profileName }}",
     "response": "{{ $json.aiResponse }}",
     "response_id": "{{ $json.wamid }}",
     "intent": "{{ $json.intent }}"
   }
   ```

### Ejemplo Completo del Flujo:

**Nodo 1: Webhook Trigger**
- Recibe: `messageText`, `fromNumber`, `profileName`, `messageId`

**Nodo 2: AI Agent (Gemini)**
- Input: `messageText`
- Output: `aiResponse` (la respuesta del modelo)

**Nodo 3: Send WhatsApp Message (Graph API)**
- Envía `aiResponse` a WhatsApp
- Recibe: `wamid` (ID del mensaje enviado)

**Nodo 4: HTTP Request → Laravel Webhook** ⭐ **ESTE ES EL NUEVO**
- **URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`
- **Method:** `POST`
- **Headers:**
  ```
  Authorization: Bearer {TU_TOKEN}
  Content-Type: application/json
  ```
- **Body:**
  ```json
  {
    "client_phone": "{{ $('Webhook Trigger').item.json.fromNumber }}",
    "client_name": "{{ $('Webhook Trigger').item.json.profileName }}",
    "message": "{{ $('Webhook Trigger').item.json.messageText }}",
    "response": "{{ $('AI Agent').item.json.aiResponse }}",
    "message_id": "{{ $('Webhook Trigger').item.json.messageId }}",
    "response_id": "{{ $('Send WhatsApp Message').item.json.wamid }}"
  }
  ```

### Nota Importante:

Si ya guardaste el mensaje del cliente en otro nodo, puedes enviar solo la respuesta:
```json
{
  "client_phone": "{{ $json.fromNumber }}",
  "client_name": "{{ $json.profileName }}",
  "response": "{{ $json.aiResponse }}",
  "response_id": "{{ $json.wamid }}"
}
```

## Paso 3: Verificar que Funciona

Después de configurar, prueba enviando un mensaje desde WhatsApp. Deberías ver en los logs de Laravel:

```
📥 Webhook recibido desde n8n
✅ Respuesta del modelo guardada exitosamente
```

## ⚠️ Notas Importantes

- El token expira cuando generas uno nuevo (solo puede haber uno activo)
- Si pierdes el token, genera uno nuevo desde la app
- El token es específico para tu usuario, no lo compartas
- Usa siempre `Bearer` antes del token en el header

## 🔄 Si el Token No Funciona

1. Verifica que estés usando `Bearer ` (con espacio) antes del token
2. Verifica que el token esté completo (no cortado)
3. Genera un nuevo token si es necesario
4. Verifica que estés autenticado en la app cuando generas el token

