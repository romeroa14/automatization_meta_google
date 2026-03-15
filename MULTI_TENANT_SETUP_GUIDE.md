# 🏢 Guía de Configuración Multi-Tenant WhatsApp

## ✅ Sistema Implementado

El sistema multi-tenant está **100% funcional** y listo para que agregues tu número de WhatsApp y el de tus clientes.

---

## 🎯 Tu Caso de Uso

**Escenario:**
1. **Tú (Admetricas)** → Eres proveedor Y cliente de tu propia plataforma
2. **Tus clientes futuros** → Cada uno tendrá su propia organización
3. **Mismo flujo n8n** → Todos usan el mismo agente IA, datos aislados por organización

---

## 📋 Datos de Tu Número de WhatsApp

Según las capturas que compartiste, tienes:

- ✅ **Número:** +58 422 2635796
- ✅ **Display Name:** Admetricas
- ✅ **Estado:** Conectado
- ✅ **Calificación:** Alta (verde 🟢)
- ✅ **Webhook:** https://admetricas.com/webhook/whatsapp

**Datos que necesitas obtener de Meta:**

1. **Phone Number ID** 
   - Ubicación: Meta Business → WhatsApp → Números de teléfono → Click en tu número
   - Ejemplo: `123456789012345`

2. **WABA ID** (WhatsApp Business Account ID)
   - Ubicación: Meta Business → WhatsApp → Configuración
   - Ejemplo: `987654321098765`

3. **Access Token**
   - Ubicación: Meta Business → Configuración de la app → Tokens
   - Debe ser un token **permanente** (no temporal)
   - Ejemplo: `EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

4. **Verify Token**
   - Es el token que configuraste en el webhook (aparece con asteriscos)
   - Si no lo recuerdas, puedes crear uno nuevo
   - Ejemplo: `admetricas_verify_token_2024`

---

## 🚀 Paso 1: Configurar Tu Organización

Una vez tengas los datos de Meta, ejecuta este comando:

```bash
cd backend
php artisan org:setup \
  --name="Admetricas Agency" \
  --email="admin@admetricas.com" \
  --phone="+584222635796" \
  --phone-id="TU_PHONE_NUMBER_ID" \
  --waba-id="TU_WABA_ID" \
  --token="TU_ACCESS_TOKEN" \
  --verify-token="TU_VERIFY_TOKEN" \
  --n8n-url="https://n8n.admetricas.com/webhook/whatsapp"
```

**O de forma interactiva:**

```bash
php artisan org:setup
```

El comando te pedirá cada dato paso a paso.

---

## 📊 Resultado Esperado

```
🎉 CONFIGURACIÓN COMPLETADA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Campo              | Valor
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Organización ID    | 1
Organización       | Admetricas Agency
Plan               | enterprise
Usuario Admin      | admin@admetricas.com
Rol                | owner
Número WhatsApp    | +584222635796
Phone Number ID    | 123456789012345
WABA ID            | 987654321098765
Estado             | active
Calidad            | green
Predeterminado     | Sí
Webhook URL        | https://app.admetricas.com/api/webhook/whatsapp
n8n URL            | https://n8n.admetricas.com/webhook/whatsapp
```

---

## 🔄 Paso 2: Actualizar Webhook en Meta

1. Ve a **Meta Business → WhatsApp → Configuración**
2. En la sección **Webhook**, configura:
   - **URL de devolución de llamada:** `https://app.admetricas.com/api/webhook/whatsapp`
   - **Token de verificación:** El mismo que usaste en el comando
3. Suscríbete a los campos:
   - ✅ `messages`
   - ✅ `account_update`
4. Click en **Verificar y guardar**

---

## 🎨 Paso 3: Flujo de Trabajo

### **Cuando llega un mensaje a tu número:**

```
1. WhatsApp → Webhook Laravel
   ↓
2. Laravel identifica organización por phone_number_id
   ↓
3. Crea/actualiza lead con organization_id = 1
   ↓
4. Guarda conversación con organization_id = 1
   ↓
5. Envía a n8n con contexto:
   {
     "organizationId": 1,
     "organizationName": "Admetricas Agency",
     "leadId": 123,
     "messageText": "Hola, quiero información",
     "accessToken": "tu_token_encriptado",
     "phoneNumberId": "123456789012345"
   }
   ↓
6. n8n procesa con tu agente IA
   ↓
7. Responde usando las credenciales de tu número
```

---

## 👥 Paso 4: Agregar Cliente Nuevo

Cuando tengas un cliente nuevo, usa el mismo comando:

```bash
php artisan org:setup \
  --name="Tienda XYZ" \
  --email="cliente@tiendaxyz.com" \
  --phone="+584129876543" \
  --phone-id="PHONE_ID_DEL_CLIENTE" \
  --waba-id="WABA_ID_DEL_CLIENTE" \
  --token="TOKEN_DEL_CLIENTE" \
  --verify-token="verify_token_cliente" \
  --n8n-url="https://n8n.admetricas.com/webhook/whatsapp"
```

**Resultado:**
- ✅ Organización ID: 2
- ✅ Leads del cliente → `organization_id = 2`
- ✅ Mismo flujo n8n, datos aislados
- ✅ Cliente ve solo SUS leads en el frontend

---

## 🔐 Aislamiento de Datos

### **Base de Datos:**

```sql
-- Tus leads
SELECT * FROM leads WHERE organization_id = 1;

-- Leads del cliente
SELECT * FROM leads WHERE organization_id = 2;

-- Tus conversaciones
SELECT * FROM conversations WHERE organization_id = 1;

-- Conversaciones del cliente
SELECT * FROM conversations WHERE organization_id = 2;
```

### **API Endpoints:**

```bash
# Tus leads (solo verás los tuyos)
GET /api/v1/organizations/1/leads

# Leads del cliente (solo verá los suyos)
GET /api/v1/organizations/2/leads
```

### **Frontend:**

Cada usuario ve solo las organizaciones a las que pertenece:

```typescript
// El backend filtra automáticamente
const organizations = await organizationStore.fetchOrganizations()
// Solo retorna organizaciones donde el usuario es member/admin/owner
```

---

## 🎯 Configuración de n8n

### **Opción A: Un flujo maestro con lógica condicional**

```javascript
// En n8n, recibe:
const organizationId = $json.organizationId;
const organizationName = $json.organizationName;
const settings = $json.organizationSettings;

// Personalizar prompt según organización
const prompts = {
  1: "Eres el asistente de Admetricas, agencia de marketing digital...",
  2: "Eres el asistente de Tienda XYZ, vendemos productos de tecnología...",
};

const systemPrompt = prompts[organizationId] || settings?.ai_prompt || "Prompt por defecto";

// Usar el prompt personalizado en tu agente IA
```

### **Opción B: Webhook único por organización**

```bash
# Organización 1 (Admetricas)
n8n_webhook_url = "https://n8n.admetricas.com/webhook/org-1"

# Organización 2 (Cliente)
n8n_webhook_url = "https://n8n.admetricas.com/webhook/org-2"
```

---

## 📱 Panel de Administración (Filament)

Accede a: `https://app.admetricas.com/admin`

**Recursos disponibles:**
- 🏢 **Organizations** - Gestionar organizaciones
- 📱 **Números de WhatsApp** - Gestionar números por organización
- 👥 **Users** - Gestionar usuarios
- 📊 **Leads** - Ver leads (filtrados por organización)
- 💬 **Conversations** - Ver conversaciones

---

## 🌐 Frontend Web

Accede a: `https://app.admetricas.com`

**Vistas disponibles:**
- `/organizations` - Lista de tus organizaciones
- `/organizations/1` - Detalle de organización
- `/organizations/1/phone-numbers` - Números de WhatsApp
- `/leads` - Tus leads
- `/leads/:id/conversations` - Conversaciones de un lead

---

## 🔍 Verificar que Todo Funciona

### **1. Verificar organización creada:**

```bash
cd backend
php artisan tinker
>>> Organization::with('users', 'whatsappPhoneNumbers')->first();
```

### **2. Enviar mensaje de prueba:**

Envía un mensaje desde WhatsApp a tu número `+584222635796`

### **3. Verificar logs:**

```bash
tail -f storage/logs/laravel.log | grep "🏢"
```

Deberías ver:
```
🏢 Mensaje asociado a organización
organization_id: 1
organization_name: Admetricas Agency
phone_number: +584222635796
```

### **4. Verificar en base de datos:**

```sql
-- Ver lead creado
SELECT * FROM leads WHERE organization_id = 1 ORDER BY id DESC LIMIT 1;

-- Ver conversación
SELECT * FROM conversations WHERE organization_id = 1 ORDER BY id DESC LIMIT 1;
```

---

## 🎓 Casos de Uso Avanzados

### **1. Configuración personalizada por organización**

```php
// Guardar configuración en settings
$organization->update([
    'settings' => [
        'ai_prompt' => 'Eres un asistente especializado en...',
        'business_hours' => '9:00-18:00',
        'auto_response' => true,
        'language' => 'es',
    ]
]);
```

### **2. Múltiples números por organización**

```bash
# Agregar segundo número a la misma organización
php artisan tinker
>>> $org = Organization::find(1);
>>> WhatsAppPhoneNumber::create([
...   'organization_id' => $org->id,
...   'phone_number' => '+584241234567',
...   'phone_number_id' => 'segundo_phone_id',
...   'waba_id' => 'mismo_waba_id',
...   'access_token' => 'mismo_token',
...   'is_default' => false
... ]);
```

### **3. Reportes por organización**

```php
// Obtener estadísticas
$stats = [
    'total_leads' => $organization->leads()->count(),
    'leads_this_month' => $organization->leads()
        ->whereMonth('created_at', now()->month)
        ->count(),
    'active_conversations' => $organization->conversations()
        ->whereDate('created_at', '>=', now()->subDays(7))
        ->count(),
];
```

---

## 🐛 Troubleshooting

### **Problema: Webhook no verifica**

**Solución:**
1. Verifica que el `verify_token` en Meta sea exactamente el mismo que en la BD
2. Revisa los logs: `tail -f storage/logs/laravel.log | grep "webhook"`
3. Asegúrate que la URL sea accesible públicamente

### **Problema: Mensajes no se asocian a la organización**

**Solución:**
1. Verifica que el `phone_number_id` en la BD coincida con el de Meta
2. Revisa los logs para ver si encuentra el número:
   ```bash
   tail -f storage/logs/laravel.log | grep "⚠️"
   ```

### **Problema: n8n no recibe mensajes**

**Solución:**
1. Verifica que `n8n_webhook_url` esté configurado en la organización
2. Revisa los logs de envío a n8n:
   ```bash
   tail -f storage/logs/laravel.log | grep "📤"
   ```

---

## 📞 Soporte

Si tienes problemas, revisa:
1. **Logs de Laravel:** `storage/logs/laravel.log`
2. **Logs de n8n:** Panel de n8n → Executions
3. **Logs de Meta:** Meta Business → WhatsApp → Registro de actividad

---

## 🎉 ¡Listo!

Tu sistema multi-tenant está configurado y funcionando. Ahora puedes:

✅ Recibir mensajes en tu número de WhatsApp
✅ Procesar con tu agente IA de n8n
✅ Ver tus leads y conversaciones en el panel
✅ Agregar clientes nuevos con sus propios números
✅ Cada cliente verá solo SUS datos
✅ Mismo flujo, datos aislados

**Próximos pasos:**
1. Obtén los datos de Meta (Phone Number ID, WABA ID, Access Token)
2. Ejecuta `php artisan org:setup`
3. Actualiza el webhook en Meta
4. Envía un mensaje de prueba
5. ¡Disfruta de tu sistema multi-tenant! 🚀
