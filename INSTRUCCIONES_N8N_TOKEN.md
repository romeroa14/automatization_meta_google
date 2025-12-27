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

## Paso 2: Usar el Token en n8n

### En tu flujo de n8n, cuando necesites enviar datos a la app:

**Endpoint:** `https://admetricas.com/api/auth/facebook/leads/webhook`

**Método:** `POST`

**Headers:**
```
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "client_phone": "584242536795",
  "client_name": "Alfredo Romero",
  "message": "Hola, quiero información",
  "response": "¡Hola! Claro, con gusto te ayudo...",
  "message_id": "wamid.xxx",
  "response_id": "wamid.yyy",
  "intent": "consulta"
}
```

### Ejemplo en n8n (HTTP Request Node):

1. **URL:** `https://admetricas.com/api/auth/facebook/leads/webhook`
2. **Method:** `POST`
3. **Authentication:** `Generic Credential Type`
   - **Name:** `Authorization`
   - **Value:** `Bearer {TU_TOKEN}`
4. **Body Type:** `JSON`
5. **Body:**
   ```json
   {
     "client_phone": "{{ $json.fromNumber }}",
     "client_name": "{{ $json.profileName }}",
     "message": "{{ $json.messageText }}",
     "response": "{{ $json.responseText }}",
     "message_id": "{{ $json.messageId }}",
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

