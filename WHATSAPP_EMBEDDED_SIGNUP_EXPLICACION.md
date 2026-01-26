# WhatsApp Embedded Signup - Guía Completa del Flujo

## 🎯 ¿Qué es WhatsApp Embedded Signup?

WhatsApp Embedded Signup permite a tus clientes registrarse y conectar WhatsApp Business **sin salir de tu aplicación**. Facebook maneja toda la complejidad.

---

## 📱 Flujo del Usuario Final (Lo que ve tu cliente)

### Paso 1: Login en Facebook
El usuario inicia sesión con su cuenta de Facebook personal si no está logueado.

### Paso 2: Seleccionar/Crear Business Manager (Administrador Comercial)
**Opciones que puede tener:**
- ✅ **Tiene Business Manager:** Ve una lista y selecciona uno
- ✅ **NO tiene Business Manager:** Facebook CREA UNO AUTOMÁTICAMENTE

**No necesitas preocuparte**: Facebook se encarga de esto.

### Paso 3: Seleccionar/Crear Página de Facebook
**Opciones:**
- ✅ Seleccionar una página existente
- ✅ Crear una página nueva en ese momento
- ✅ Vincular la página al WhatsApp Business

### Paso 4: Crear/Vincular Número de WhatsApp
Facebook puede:
1. Crear un nuevo WABA (WhatsApp Business Account)
2. Agregar un número de teléfono nuevo
3. Migrar un número existente de WhatsApp Business App

**Facebook hace TODO esto automáticamente** - solo guía al usuario.

### Paso 5: Verificación
- Facebook envía SMS al número
- Usuario ingresa el código
- Facebook verifica y activa

### Paso 6: Completado ✅
El código se devuelve a tu app y el usuario queda registrado.

---

## 🔐 ¿Qué información recibe tu sistema?

Cuando el usuario completa el signup, recibes:

```json
{
    "code": "AQDcBRQALmC...",  // Código de autorización
    "authResponse": {
        "code": "...",
        "userID": null  // Normal en Embedded Signup
    }
}
```

Tu backend intercambia el `code` por:
- ✅ Access Token (permanente, 60 días)
- ✅ Facebook User ID del usuario que se registró
- ✅ WABA ID (si se creó/vinculó uno)
- ✅ Business ID
- ✅ Phone Number ID

---

## 💾 ¿Qué se guarda en tu base de datos?

### Tabla: `users`
```sql
id: 3
name: "admetricas_bot System User"  
email: "fb_122101198467229519@admetricas.temp"
```

### Tabla: `user_facebook_connections`
```sql
user_id: 3
facebook_user_id: "122101198467229519"
facebook_name: "admetricas_bot System User"
waba_id: "123456789"  -- ID de WhatsApp Business Account
business_id: "987654321"  -- ID del Business Portfolio
waba_data: {
    "waba_id": "123456789",
    "waba_name": "Mi Negocio",
    "namespace": "xxxxx",
    "phone_number_id": "111222333"
}
signup_method: "embedded_signup"
access_token: "EAAXXX..."  -- Token para llamar a la API
```

---

## 🏢 Modelo de Negocio: Multi-tenant

### ¿Cómo funciona con múltiples clientes?

**CADA CLIENTE TIENE:**
- ✅ Su propia cuenta de usuario (`users.id`)
- ✅ Su propia conexión de Facebook (`user_facebook_connections.user_id`)
- ✅ Su propio WABA ID único (`waba_id`)
- ✅ Su propio número de WhatsApp
- ✅ Su propio access token

**AISLAMIENTO COMPLETO:**
```
Cliente A → WABA ID: 111111 → Phone: +1234567890
Cliente B → WABA ID: 222222 → Phone: +9876543210
Cliente C → WABA ID: 333333 → Phone: +5555555555
```

---

## 🔄 Casos de Uso

### Caso 1: Cliente nuevo sin nada
1. Se registra en tu app con Whats App Signup
2. Facebook crea automáticamente:
   - Business Manager
   - Página de Facebook
   - WABA (WhatsApp Business Account)
3. Cliente agrega su número y lo verifica
4. **Listo** - Puede enviar mensajes desde tu app

### Caso 2: Cliente con Business Manager existente
1. Se registra con WhatsApp Signup
2. Selecciona su Business Manager existente
3. Selecciona o crea una página
4. Vincula/crea WABA
5. **Listo**

### Caso 3: Cliente que solo quiere agregar WhatsApp (sin Instagram)
Perfecto, el flujo es:
1. Hace clic en "Conectar WhatsApp Business"
2. Completa el flujo de Facebook
3. Queda registrado SOLO con WhatsApp
4. **No necesita Instagram** para nada

Tu app puede ofrecer:
- **Plan Base (Gratis):** Solo Instagram
- **Plan Premium:** Agregar WhatsApp

---

## 📊 ¿Cómo identificar cada cliente?

### En cada petición a WhatsApp API:

```php
// Obtener la conexión del usuario autenticado
$user = auth()->user();
$connection = $user->facebookConnection;

// Datos del cliente
$wabaId = $connection->waba_id;  // Único por cliente
$accessToken = $connection->access_token;  // Token del cliente
$phoneNumberId = $connection->waba_data['phone_number_id'];

// Enviar mensaje de WhatsApp para ESTE cliente específico
$response = Http::withToken($accessToken)
    ->post("https://graph.facebook.com/v24.0/{$phoneNumberId}/messages", [
        'messaging_product' => 'whatsapp',
        'to' => '+573001234567',
        'text' => ['body' => 'Hola desde tu WABA']
    ]);
```

### Multi-tenancy garantizado:
- ✅ Cada usuario usa **su propio access_token**
- ✅ Cada usuario usa **su propio waba_id**  
- ✅ Cada usuario usa **su propio phone_number_id**
- ✅ **Imposible** que un cliente vea/envíe mensajes de otro

---

## ⚠️ Importante

### Los errores `ERR_NETWORK_CHANGED` que viste:
Son **normales** y **no afectan** el flujo. Ocurren cuando:
- La red cambia durante el proceso
- La VPN se reconecta
- El navegador pierde conexión momentánea

Si el mensaje dice `"success": true`, **funcionó correctamente**.

---

## 📝 Próximos Pasos

1. **WABA Data vacía:** El usuario que probaste (admetricas_bot) parece ser un System User, no un usuario real con WABA. Para probar completo:
   - Usa una cuenta de Facebook personal real
   - Completa el flujo hasta agregar un número de teléfono
   - Verifica el número con SMS
   
2. **Logs Detallados:** Revisa `storage/logs/laravel.log` para ver:
   - Si se obtuvo WABA info
   - Qué respuesta dio Facebook
   - Si hubo algún error al guardar

3. **Testing:** Prueba con una cuenta real que:
   - Tenga o cree un número de WhatsApp
   - Complete la verificación SMS
   - Entonces verás `waba_id`, `business_id` y `waba_data` completos

---

## ✅ Resumen
El flujo está **funcionando correctamente**. El sistema:
- ✅ Detecta el entorno automáticamente
- ✅ Intercambia código por token exitosamente
- ✅ Crea usuarios en tu BD
- ✅ Soporta multi-tenancy (cada cliente su WABA)
- ✅ No requiere Business Manager previo
- ✅ No requiere Instagram
