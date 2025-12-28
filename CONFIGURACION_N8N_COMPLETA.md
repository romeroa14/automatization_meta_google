# 📋 Configuración Completa de n8n para AdMetricas

## 🔑 Tu Token de API

**Token:** `2d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2z`

⚠️ **IMPORTANTE:** Guarda este token de forma segura. Si lo pierdes, genera uno nuevo desde la app.

---

## 🔄 Flujo Completo Recomendado

```
1. Webhook Trigger (recibe mensaje de WhatsApp)
   ↓
2. Parse Incoming (parsea datos del webhook)
   ↓
3. Insert Conversations (guarda mensaje del cliente)
   ↓
4. AI Agent (Gemini) (genera respuesta)
   ↓
5. Parse AI Response (parsea respuesta del AI)
   ↓
6. [BRANCH] Se divide en 3 caminos:
   ├─→ Send IG Message (Graph API) → Send Message to laravel
   ├─→ Inserts Records leads1
   └─→ Update Records Conversations
```

---

## 📝 Configuración del Nodo "Send Message to laravel"

### Ubicación en el Flujo:
**Debe estar DESPUÉS de "Send IG Message (Graph API)"** para obtener el `wamid` del mensaje enviado.

### Configuración:

**1. Tipo de Nodo:** HTTP Request

**2. URL:**
```
https://admetricas.com/api/auth/facebook/leads/webhook
```

**3. Method:**
```
POST
```

**4. Authentication:**
```
None (usaremos headers manuales)
```

**5. Headers:**
```
Authorization: Bearer 2d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2z
Content-Type: application/json
```

**6. Body Type:**
```
JSON
```

**7. Body (JSON):**

**⚠️ IMPORTANTE:** El nodo debe estar conectado DESPUÉS de "Send IG Message (Graph API)" para obtener el `wamid`.

**Opción A - Si "Send Message to laravel" recibe datos directamente de "Send IG Message":**
```json
{
  "client_phone": "{{ $('Parse Incoming').item.json.fromNumber }}",
  "client_name": "{{ $('Parse Incoming').item.json.profileName }}",
  "message": "{{ $('Parse Incoming').item.json.messageText }}",
  "response": "{{ $('Parse AI Response').item.json.response }}",
  "message_id": "{{ $('Parse Incoming').item.json.messageId }}",
  "response_id": "{{ $json.messages[0].id }}"
}
```

**Opción B - Si necesitas usar Merge para combinar datos:**
1. Crea un nodo "Merge" que combine:
   - Input 1: "Parse AI Response" (para obtener `response`)
   - Input 2: "Send IG Message (Graph API)" (para obtener `wamid`)
   - Input 3: "Parse Incoming" (para obtener datos del cliente)

2. Conecta "Send Message to laravel" DESPUÉS del Merge

3. Body:
```json
{
  "client_phone": "{{ $json.fromNumber }}",
  "client_name": "{{ $json.profileName }}",
  "message": "{{ $json.messageText }}",
  "response": "{{ $json.response }}",
  "message_id": "{{ $json.messageId }}",
  "response_id": "{{ $json.messages[0].id }}"
}
```

**Opción C - Si el error persiste, usa variables:**
1. En "Parse AI Response", agrega un nodo "Set" que guarde `{{ $json.response }}` en `$vars.aiResponse`
2. En "Send IG Message", agrega un nodo "Set" que guarde `{{ $json.messages[0].id }}` en `$vars.wamid`
3. En "Send Message to laravel", usa:
```json
{
  "client_phone": "{{ $('Parse Incoming').item.json.fromNumber }}",
  "client_name": "{{ $('Parse Incoming').item.json.profileName }}",
  "message": "{{ $('Parse Incoming').item.json.messageText }}",
  "response": "{{ $vars.aiResponse }}",
  "message_id": "{{ $('Parse Incoming').item.json.messageId }}",
  "response_id": "{{ $vars.wamid }}"
}
```

---

## 🔍 Cómo Obtener los Valores Correctos

### Si los nombres de tus nodos son diferentes:

1. **Para obtener datos del mensaje del cliente:**
   - Busca el nodo que recibe el webhook inicial
   - Usa: `{{ $('NOMBRE_DEL_NODO').item.json.CAMPO }}`

2. **Para obtener la respuesta del AI:**
   - Busca el nodo que parsea la respuesta del AI Agent
   - Usa: `{{ $('NOMBRE_DEL_NODO').item.json.response }}`

3. **Para obtener el wamid (ID del mensaje enviado a WhatsApp):**
   - Busca el nodo que envía a WhatsApp (Graph API)
   - El `wamid` puede estar en:
     - `{{ $('Send IG Message').item.json.messages[0].id }}`
     - `{{ $('Send IG Message').item.json.wamid }}`
     - `{{ $('Send IG Message').item.json.id }}`

### Para verificar la estructura de datos:

1. Ejecuta el flujo hasta el nodo "Send IG Message"
2. Haz clic en el nodo y revisa la salida
3. Busca dónde está el `wamid` o `id` del mensaje enviado
4. Ajusta la ruta en el Body del nodo "Send Message to laravel"

---

## ✅ Verificación

Después de configurar, cuando ejecutes el flujo:

1. **En n8n:** Deberías ver que el nodo "Send Message to laravel" se ejecuta exitosamente (verde)

2. **En Laravel (logs):**
   ```bash
   docker exec laravel-php tail -f storage/logs/laravel.log | grep "Webhook recibido"
   ```
   Deberías ver:
   ```
   📥 Webhook recibido desde n8n
   ✅ Respuesta del modelo guardada exitosamente
   ```

3. **En la App:**
   - Ve a `https://app.admetricas.com/leads/{lead_id}/conversations`
   - Deberías ver la respuesta del bot en el chat (burbuja verde a la derecha)

---

## 🐛 Troubleshooting

### Error 401 (Unauthorized):
- Verifica que el token esté completo y correcto
- Verifica que uses `Bearer ` (con espacio) antes del token
- Genera un nuevo token si es necesario

### Error 404 (Not Found):
- Verifica que la URL sea: `https://admetricas.com/api/auth/facebook/leads/webhook`
- Verifica que el método sea `POST`

### No se guarda la respuesta:
- Verifica que el campo `response` tenga contenido
- Revisa los logs de Laravel para ver qué está recibiendo
- Verifica que el `response_id` (wamid) esté correcto

### No aparece en el chat:
- Verifica que las conversaciones se estén ordenando por `created_at ASC`
- Revisa la consola del navegador para ver qué datos se están recibiendo
- Verifica que `is_client_message = false` para las respuestas del bot

---

## 📞 Soporte

Si tienes problemas, revisa:
1. Los logs de Laravel: `docker exec laravel-php tail -f storage/logs/laravel.log`
2. La consola del navegador (F12) en la app
3. Los logs de n8n en el nodo "Send Message to laravel"

