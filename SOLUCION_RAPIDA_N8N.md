# ⚡ Solución Rápida al Error en n8n

## ❌ Error Actual

```
[ERROR: Can't determine which item to use]
Paired item data for item from node 'Parse AI Response' is unavailable.
```

## ✅ Solución Inmediata

**Cambia `.item` por `.first()` en TODOS los campos del Body:**

### Body Corregido:

```json
{
  "client_phone": "{{ $('Parse Incoming').first().json.fromNumber }}",
  "client_name": "{{ $('Parse Incoming').first().json.profileName }}",
  "message": "{{ $('Parse Incoming').first().json.messageText }}",
  "response": "{{ $('Parse AI Response').first().json.response }}",
  "message_id": "{{ $('Parse Incoming').first().json.messageId }}",
  "response_id": "{{ $('Send IG Message (Graph API)').first().json.messages[0].id }}"
}
```

### Si el nodo está conectado DESPUÉS de "Send IG Message (Graph API)":

Puedes simplificar usando `$json` para los datos del nodo anterior:

```json
{
  "client_phone": "{{ $('Parse Incoming').first().json.fromNumber }}",
  "client_name": "{{ $('Parse Incoming').first().json.profileName }}",
  "message": "{{ $('Parse Incoming').first().json.messageText }}",
  "response": "{{ $('Parse AI Response').first().json.response }}",
  "message_id": "{{ $('Parse Incoming').first().json.messageId }}",
  "response_id": "{{ $json.messages[0].id }}"
}
```

---

## 🔍 ¿Por qué `.first()` funciona?

- `.item` requiere que n8n pueda "emparejar" items entre nodos
- `.first()` toma el primer item del nodo especificado (más simple y confiable)
- `.last()` tomaría el último item
- `.all()[0]` también funciona pero es más verboso

---

## 🎯 Configuración Completa del Nodo

**Nodo:** "Send Message to laravel"

**URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`

**Method:** `POST`

**Headers:**
```
Authorization: Bearer 2|d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2zuf038ce74
Content-Type: application/json
```

**Body (JSON) - CON `.first()`:**
```json
{
  "client_phone": "{{ $('Parse Incoming').first().json.fromNumber }}",
  "client_name": "{{ $('Parse Incoming').first().json.profileName }}",
  "message": "{{ $('Parse Incoming').first().json.messageText }}",
  "response": "{{ $('Parse AI Response').first().json.response }}",
  "message_id": "{{ $('Parse Incoming').first().json.messageId }}",
  "response_id": "{{ $('Send IG Message (Graph API)').first().json.messages[0].id }}"
}
```

---

## ✅ Después de Corregir

1. **Guarda el nodo** en n8n
2. **Ejecuta el flujo** de nuevo
3. **Verifica en los logs de Laravel:**
   ```bash
   docker exec laravel-php tail -f storage/logs/laravel.log | grep "Webhook recibido"
   ```
4. **Deberías ver:**
   ```
   📥 Webhook recibido desde n8n
   ✅ Respuesta del modelo guardada exitosamente
   ```
5. **En la app:** La respuesta del bot debería aparecer en el chat

---

## 🐛 Si Aún No Funciona

### Verifica la estructura de datos:

1. **Haz clic en "Parse AI Response"** en n8n
2. **Revisa la salida (Output)**
3. **Busca el campo que contiene la respuesta:**
   - Puede ser `response`
   - Puede ser `text`
   - Puede ser `aiResponse`
   - Puede ser `message`

4. **Ajusta el Body según lo que veas:**
   ```json
   {
     "response": "{{ $('Parse AI Response').first().json.CAMPO_QUE_VEAS }}"
   }
   ```

### Verifica el wamid:

1. **Haz clic en "Send IG Message (Graph API)"**
2. **Revisa la salida**
3. **Busca dónde está el ID del mensaje:**
   - Puede ser `messages[0].id`
   - Puede ser `wamid`
   - Puede ser `id`

4. **Ajusta `response_id` según lo que veas**


