# 🔧 Solución al Error en n8n: "Paired item data unavailable"

## ❌ Error Actual

```
Problem in node 'Send Message to laravel'
Paired item data for item from node 'Parse AI Response' is unavailable. 
Ensure 'Parse AI Response' is providing the required output.
```

## 🔍 Causa del Problema

El nodo "Send Message to laravel" está intentando acceder a datos de "Parse AI Response" usando una sintaxis incorrecta o el nodo no está conectado correctamente en el flujo.

## ✅ Solución: Configuración Correcta del Nodo

### Opción 1: Conectar Directamente desde "Parse AI Response"

**El nodo "Send Message to laravel" debe recibir datos DIRECTAMENTE de "Parse AI Response":**

1. **Conecta el nodo así:**
   ```
   Parse AI Response
     ↓
   Send Message to laravel
   ```

2. **Body del nodo "Send Message to laravel":**
   ```json
   {
     "client_phone": "{{ $('Parse Incoming').item.json.fromNumber }}",
     "client_name": "{{ $('Parse Incoming').item.json.profileName }}",
     "message": "{{ $('Parse Incoming').item.json.messageText }}",
     "response": "{{ $json.response }}",
     "message_id": "{{ $('Parse Incoming').item.json.messageId }}",
     "response_id": "{{ $('Send IG Message (Graph API)').item.json.messages[0].id }}"
   }
   ```

   **Nota:** Usa `{{ $json.response }}` (sin especificar nodo) porque los datos vienen directamente de "Parse AI Response".

### Opción 2: Usar Merge para Combinar Datos

Si necesitas datos de múltiples nodos:

1. **Crea un nodo Merge antes de "Send Message to laravel":**
   ```
   Parse AI Response → Input 1
   Send IG Message (Graph API) → Input 2
   Parse Incoming → Input 3 (opcional, si necesitas datos del mensaje original)
     ↓
   Merge
     ↓
   Send Message to laravel
   ```

2. **Body del nodo "Send Message to laravel" (después del Merge):**
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

### Opción 3: Usar Variables de Entorno o Configuración

Si los datos no están disponibles, puedes guardarlos en variables:

1. **En "Parse AI Response", guarda la respuesta:**
   - Agrega un nodo "Set" después de "Parse AI Response"
   - Guarda `{{ $json.response }}` en una variable como `aiResponse`

2. **En "Send Message to laravel", usa la variable:**
   ```json
   {
     "response": "{{ $vars.aiResponse }}"
   }
   ```

---

## 🎯 Configuración Recomendada (Basada en tu Flujo)

### Flujo Correcto:

```
Parse AI Response
  ├─→ Inserts Records leads1
  ├─→ Send IG Message (Graph API) → [envía a WhatsApp]
  │     ↓
  │   Send Message to laravel → [guarda respuesta en BD]
  └─→ Update Records Conversations
```

### Configuración del Nodo "Send Message to laravel":

**1. Conecta desde "Send IG Message (Graph API)":**
- Esto asegura que tengas el `wamid` del mensaje enviado

**2. URL:**
```
https://admetricas.com/api/auth/facebook/leads/webhook
```

**3. Method:** `POST`

**4. Headers:**
```
Authorization: Bearer 2d33t5VTGTh4zfF7uSc8EDWYpM1NbJoYfyudhg2z
Content-Type: application/json
```

**5. Body (JSON):**

**⚠️ SOLUCIÓN AL ERROR:** Usa `.first()` en lugar de `.item`:

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

**O si el nodo está conectado DESPUÉS de "Send IG Message (Graph API)":**

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

**Nota:** `$json` sin especificar nodo usa los datos del nodo anterior (en este caso "Send IG Message").

---

## 🔍 Verificar la Estructura de Datos

Para ver qué datos tiene "Parse AI Response":

1. Ejecuta el flujo hasta "Parse AI Response"
2. Haz clic en el nodo
3. Revisa la salida (Output)
4. Busca el campo que contiene la respuesta del AI (puede ser `response`, `aiResponse`, `text`, etc.)
5. Ajusta el Body del nodo "Send Message to laravel" según lo que veas

---

## 🐛 Si el Chatbot Responde 2 Veces

Esto puede ser porque:
1. El webhook se está ejecutando dos veces
2. Hay dos flujos activos
3. El nodo "Send IG Message" se está ejecutando dos veces
4. "Parse AI Response" tiene múltiples salidas y cada una ejecuta "Send IG Message"

**Solución:**
- Verifica que solo haya UN flujo activo
- Agrega un nodo "If" antes de "Send IG Message" para verificar si ya se envió
- Revisa los logs de n8n para ver cuántas veces se ejecuta cada nodo
- Si "Parse AI Response" devuelve múltiples items, usa "Split In Batches" o filtra para procesar solo uno

## 🔧 Solución Rápida: Usar Merge para Evitar el Error

**La forma MÁS SEGURA de evitar el error es usar un nodo Merge:**

1. **Desconecta "Send Message to laravel" de donde esté ahora**

2. **Crea un nodo "Merge" y conéctalo así:**
   ```
   Parse AI Response → Input 1 del Merge
   Send IG Message (Graph API) → Input 2 del Merge
   Parse Incoming → Input 3 del Merge (opcional, si necesitas datos originales)
     ↓
   Merge
     ↓
   Send Message to laravel
   ```

3. **Configura el Merge:**
   - **Mode:** "Merge By Index" o "Merge By Key"
   - **Merge By Key:** Usa `messageId` o `fromNumber` como key común

4. **Body del nodo "Send Message to laravel" (después del Merge):**
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

   **Nota:** Después del Merge, todos los datos están en `$json`, no necesitas usar `$('Nodo').item.json`

