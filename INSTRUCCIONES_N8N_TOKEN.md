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
3. Envía respuesta a WhatsApp (Graph API) ← PRIMERO
   ↓
4. Envía respuesta al webhook de Laravel ← DESPUÉS (para guardar en BD)
```

**⚠️ IMPORTANTE:** Debes enviar a WhatsApp PRIMERO, y luego a Laravel. Esto asegura que:
- El cliente reciba el mensaje inmediatamente
- La respuesta se guarde en la base de datos para mostrarla en la app

### Configuración del Nodo HTTP Request "Send Message to laravel":

**Este nodo debe estar DESPUÉS de "Send IG Message (Graph API)" para recibir el `wamid` del mensaje enviado:**

1. **URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`
2. **Method:** `POST`
3. **Headers:**
   - **Name:** `Authorization`
   - **Value:** `Bearer 2d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2z` (tu token generado)
   - **Name:** `Content-Type`
   - **Value:** `application/json`
4. **Body Type:** `JSON`
5. **Body (JSON):**
   ```json
   {
     "client_phone": "{{ $('Parse Incoming').item.json.fromNumber }}",
     "client_name": "{{ $('Parse Incoming').item.json.profileName }}",
     "message": "{{ $('Parse Incoming').item.json.messageText }}",
     "response": "{{ $('Parse AI Response').item.json.response }}",
     "message_id": "{{ $('Parse Incoming').item.json.messageId }}",
     "response_id": "{{ $('Send IG Message (Graph API)').item.json.messages[0].id }}"
   }
   ```

**Nota:** Si el nodo "Send IG Message (Graph API)" devuelve el `wamid` en otra estructura, ajusta la ruta. Por ejemplo:
- `{{ $('Send IG Message (Graph API)').item.json.wamid }}`
- `{{ $('Send IG Message (Graph API)').item.json.id }}`
- `{{ $('Send IG Message (Graph API)').item.json.messages[0].id }}`

### Ejemplo Completo del Flujo (Basado en tu flujo actual):

**Nodo 1: Parse Incoming**
- Recibe del webhook: `messageText`, `fromNumber`, `profileName`, `messageId`

**Nodo 2: AI Agent (Gemini)**
- Input: `messageText` del cliente
- Output: `response` (la respuesta del modelo)

**Nodo 3: Parse AI Response**
- Parsea la respuesta del AI Agent
- Extrae: `response`, `intent`, etc.

**Nodo 4: Send IG Message (Graph API)** ⚡ **PRIMERO**
- Envía la respuesta a WhatsApp
- Recibe: `wamid` o `messages[0].id` (ID del mensaje enviado)

**Nodo 5: Send Message to laravel** ⭐ **DESPUÉS (conectado desde Send IG Message)**
- **URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`
- **Method:** `POST`
- **Headers:**
  ```
  Authorization: Bearer 2d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2z
  Content-Type: application/json
  ```
- **Body:**
  ```json
  {
    "client_phone": "{{ $('Parse Incoming').item.json.fromNumber }}",
    "client_name": "{{ $('Parse Incoming').item.json.profileName }}",
    "message": "{{ $('Parse Incoming').item.json.messageText }}",
    "response": "{{ $('Parse AI Response').item.json.response }}",
    "message_id": "{{ $('Parse Incoming').item.json.messageId }}",
    "response_id": "{{ $('Send IG Message (Graph API)').item.json.messages[0].id }}"
  }
  ```

**Nodos Paralelos (también desde Parse AI Response):**
- **Inserts Records leads1**: Guarda/actualiza el lead
- **Update Records Conversations**: Actualiza la conversación

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

