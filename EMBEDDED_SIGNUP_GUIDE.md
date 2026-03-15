# 📱 Guía: Embedded Signup de WhatsApp para Clientes

## 🎯 Respondiendo tu Pregunta

**Pregunta:** "Si yo estoy configurando el administrador de registro insertado... ahí estarían las nuevas organizaciones pero de ellos no tendré el WABA ID, el Phone Number ID... ¿o sí?"

**Respuesta:** **SÍ, los tendrás automáticamente.** Meta te los proporciona cuando el cliente completa el Embedded Signup.

---

## 🔄 Dos Escenarios Diferentes

### **Escenario 1: TÚ (Proveedor)**
**Lo que acabas de hacer:**
- ✅ Creaste tu app en Meta
- ✅ Obtuviste manualmente: Phone Number ID, WABA ID, Access Token
- ✅ Los ingresaste con `php artisan org:setup`

**Uso:** Para TU número de WhatsApp (+584222536796)

---

### **Escenario 2: CLIENTES (Embedded Signup)**
**Lo que sucederá automáticamente:**

1. **Cliente hace clic en "Conectar WhatsApp"** en tu interfaz web
2. **Meta muestra popup** con el flujo de autorización
3. **Cliente autoriza** tu app para usar su WhatsApp Business
4. **Meta te devuelve un código** de autorización
5. **Tu backend intercambia el código** por:
   - ✅ `phone_number_id` del cliente
   - ✅ `waba_id` del cliente  
   - ✅ `access_token` del cliente
6. **Tu backend guarda** automáticamente en la BD

**Resultado:** Cada cliente tiene SUS propios IDs, NO los tuyos.

---

## 🛠️ Implementación del Embedded Signup

### **Paso 1: Configurar en Meta**

1. Ve a **Meta for Developers** → Tu App
2. En **WhatsApp → Embedded Signup**, configura:
   - **Redirect URL:** `https://app.admetricas.com/whatsapp/callback`
   - **Webhook URL:** `https://app.admetricas.com/api/webhook/whatsapp`

### **Paso 2: Frontend - Botón de Conexión**

```vue
<!-- OrganizationDetail.vue -->
<template>
  <v-card>
    <v-card-title>Conectar WhatsApp Business</v-card-title>
    <v-card-text>
      <v-btn 
        color="success" 
        @click="connectWhatsApp"
        prepend-icon="mdi-whatsapp"
      >
        Conectar mi número de WhatsApp
      </v-btn>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
const connectWhatsApp = () => {
  const appId = '1332441785477966' // Tu App ID de Meta
  const redirectUri = encodeURIComponent('https://app.admetricas.com/whatsapp/callback')
  const state = btoa(JSON.stringify({
    organizationId: currentOrganization.value.id,
    userId: currentUser.value.id
  }))
  
  const url = `https://www.facebook.com/v21.0/dialog/oauth?` +
    `client_id=${appId}` +
    `&redirect_uri=${redirectUri}` +
    `&state=${state}` +
    `&scope=whatsapp_business_management,whatsapp_business_messaging` +
    `&response_type=code` +
    `&config_id=YOUR_CONFIG_ID` // Obtener de Meta
  
  window.location.href = url
}
</script>
```

### **Paso 3: Backend - Callback Handler**

```php
// routes/web.php
Route::get('/whatsapp/callback', [WhatsAppEmbeddedSignupController::class, 'handleCallback']);
```

```php
// app/Http/Controllers/WhatsAppEmbeddedSignupController.php
<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\WhatsAppPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppEmbeddedSignupController extends Controller
{
    public function handleCallback(Request $request)
    {
        // 1. Obtener código de autorización
        $code = $request->query('code');
        $state = json_decode(base64_decode($request->query('state')), true);
        
        if (!$code) {
            return redirect('/organizations')->with('error', 'Error en la autorización');
        }
        
        // 2. Intercambiar código por access token
        $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id' => config('services.facebook.app_id'),
            'client_secret' => config('services.facebook.app_secret'),
            'code' => $code,
        ]);
        
        if (!$response->successful()) {
            Log::error('Error obteniendo access token', ['response' => $response->json()]);
            return redirect('/organizations')->with('error', 'Error obteniendo token');
        }
        
        $accessToken = $response->json()['access_token'];
        
        // 3. Obtener información del WABA
        $wabaResponse = Http::get('https://graph.facebook.com/v21.0/debug_token', [
            'input_token' => $accessToken,
            'access_token' => config('services.facebook.app_token'),
        ]);
        
        $wabaId = $wabaResponse->json()['data']['granular_scopes'][0]['target_ids'][0] ?? null;
        
        // 4. Obtener números de teléfono asociados
        $phonesResponse = Http::get("https://graph.facebook.com/v21.0/{$wabaId}/phone_numbers", [
            'access_token' => $accessToken,
        ]);
        
        $phoneNumbers = $phonesResponse->json()['data'] ?? [];
        
        // 5. Guardar en la base de datos
        $organization = Organization::find($state['organizationId']);
        
        foreach ($phoneNumbers as $phone) {
            WhatsAppPhoneNumber::create([
                'organization_id' => $organization->id,
                'phone_number' => $phone['display_phone_number'],
                'display_name' => $phone['verified_name'],
                'phone_number_id' => $phone['id'],
                'waba_id' => $wabaId,
                'access_token' => $accessToken, // Se encripta automáticamente
                'verify_token' => \Str::random(32),
                'webhook_url' => config('app.url') . '/api/webhook/whatsapp',
                'status' => 'active',
                'quality_rating' => $phone['quality_rating'] ?? 'unknown',
                'is_default' => true,
                'verified_at' => now(),
            ]);
            
            Log::info('✅ Número de WhatsApp conectado vía Embedded Signup', [
                'organization_id' => $organization->id,
                'phone_number' => $phone['display_phone_number'],
                'phone_number_id' => $phone['id'],
            ]);
        }
        
        return redirect("/organizations/{$organization->id}")
            ->with('success', '¡WhatsApp conectado exitosamente!');
    }
}
```

---

## 📊 Comparación: Manual vs Embedded Signup

| Aspecto | Manual (Tú) | Embedded Signup (Clientes) |
|---------|-------------|----------------------------|
| **Quién lo hace** | Tú manualmente | Cliente con 1 clic |
| **Dónde obtener IDs** | Meta Business Manager | Automático vía API |
| **Phone Number ID** | Copiar/pegar | Meta lo devuelve |
| **WABA ID** | Copiar/pegar | Meta lo devuelve |
| **Access Token** | Copiar/pegar | Meta lo devuelve |
| **Tiempo** | 5-10 minutos | 30 segundos |
| **Complejidad** | Media | Muy fácil |

---

## 🎯 Flujo Completo para Cliente

```
1. Cliente se registra en tu plataforma
   ↓
2. Crea su organización "Tienda XYZ"
   ↓
3. Click en "Conectar WhatsApp"
   ↓
4. Popup de Meta aparece
   ↓
5. Cliente selecciona su cuenta de WhatsApp Business
   ↓
6. Cliente autoriza tu app
   ↓
7. Meta redirige a tu callback con código
   ↓
8. Tu backend intercambia código por:
   - access_token
   - phone_number_id
   - waba_id
   ↓
9. Tu backend guarda en whatsapp_phone_numbers:
   - organization_id = ID del cliente
   - phone_number_id = del cliente
   - waba_id = del cliente
   - access_token = del cliente (encriptado)
   ↓
10. ✅ Cliente listo para recibir mensajes
```

---

## 🔐 Seguridad y Aislamiento

### **Cada cliente tiene:**
- ✅ Su propio `access_token` (encriptado)
- ✅ Su propio `phone_number_id`
- ✅ Su propio `waba_id`
- ✅ Sus propios leads (`organization_id` diferente)
- ✅ Sus propias conversaciones

### **Tú nunca ves:**
- ❌ Los tokens de tus clientes
- ❌ Las credenciales de WhatsApp de tus clientes
- ❌ Los mensajes de otros clientes

### **El sistema automáticamente:**
- ✅ Encripta todos los tokens
- ✅ Filtra datos por `organization_id`
- ✅ Usa las credenciales correctas para cada mensaje
- ✅ Enruta webhooks a la organización correcta

---

## 🚀 Próximos Pasos

### **Para implementar Embedded Signup:**

1. **Configurar en Meta:**
   - Agregar Redirect URL
   - Obtener Configuration ID
   - Configurar permisos

2. **Crear controlador:**
   - `WhatsAppEmbeddedSignupController`
   - Método `handleCallback`

3. **Crear vista en frontend:**
   - Botón "Conectar WhatsApp"
   - Página de callback

4. **Probar con cuenta de prueba:**
   - Crear organización de prueba
   - Conectar número de prueba
   - Verificar que se guarda correctamente

---

## ❓ Preguntas Frecuentes

### **¿Necesito crear una app de Meta por cada cliente?**
**No.** Todos los clientes usan TU app. Embedded Signup permite que múltiples negocios autoricen tu app.

### **¿Los tokens de los clientes expiran?**
Depende del tipo de token. Puedes solicitar tokens de larga duración (60 días) o permanentes.

### **¿Puedo ver los mensajes de mis clientes?**
Solo si implementas esa funcionalidad. Por defecto, cada organización ve solo SUS datos.

### **¿Qué pasa si un cliente desconecta su WhatsApp?**
Debes implementar un webhook de `account_update` para detectar cuando un cliente revoca el acceso.

---

## 📝 Resumen

**Tu situación actual:**
- ✅ Tienes tu número configurado manualmente
- ✅ Sistema multi-tenant funcionando
- ✅ Webhook recibiendo mensajes

**Para clientes futuros:**
- 🔜 Implementar Embedded Signup
- 🔜 Cliente conecta con 1 clic
- 🔜 Meta te da automáticamente: phone_number_id, waba_id, access_token
- 🔜 Sistema guarda y usa las credenciales correctas

**NO necesitas:**
- ❌ Pedir a clientes que copien/peguen IDs
- ❌ Acceso al Meta Business Manager del cliente
- ❌ Configurar manualmente cada número

**Meta hace todo automáticamente** cuando el cliente autoriza tu app. 🎉
