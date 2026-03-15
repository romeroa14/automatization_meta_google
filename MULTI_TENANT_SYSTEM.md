# 🏢 Sistema Multi-Tenant para Gestión de WhatsApp

## 📋 Descripción General

Sistema completo de gestión multi-tenant que permite a múltiples organizaciones administrar sus propios números de WhatsApp Business de forma independiente y escalable.

## 🎯 Características Principales

### 1. **Organizaciones (Multi-Tenant)**
- Cada organización es completamente independiente
- Gestión de usuarios con roles (owner, admin, member)
- Configuraciones personalizadas por organización
- Planes de suscripción (free, basic, pro, enterprise)
- Período de prueba configurable

### 2. **Números de WhatsApp**
- Múltiples números por organización
- Gestión completa del ciclo de vida
- Encriptación de tokens de acceso
- Monitoreo de calidad (green, yellow, red)
- Número por defecto configurable
- Webhooks personalizados por número

### 3. **Seguridad y Aislamiento**
- Datos completamente aislados por organización
- Control de acceso basado en roles
- Tokens de acceso encriptados
- Validación de permisos en cada endpoint

## 📊 Modelo de Datos

### Organizations
```sql
- id
- name
- slug (único)
- description
- logo_url
- website
- email
- phone
- settings (JSON)
- is_active
- trial_ends_at
- plan (free, basic, pro, enterprise)
- timestamps
- soft_deletes
```

### WhatsApp Phone Numbers
```sql
- id
- organization_id (FK)
- phone_number (único, E.164)
- display_name
- phone_number_id (Meta)
- waba_id (WhatsApp Business Account)
- access_token (encriptado)
- verify_token
- webhook_url
- status (pending, active, suspended, inactive)
- quality_rating (green, yellow, red)
- capabilities (JSON)
- settings (JSON)
- verified_at
- last_used_at
- is_default
- timestamps
- soft_deletes
```

### Organization User (Pivot)
```sql
- id
- organization_id (FK)
- user_id (FK)
- role (owner, admin, member)
- timestamps
```

### Relaciones Actualizadas
- **Leads**: Ahora pertenecen a una organización y número específico
- **Conversations**: Vinculadas a organización y número de WhatsApp
- **Users**: Pueden pertenecer a múltiples organizaciones con diferentes roles

## 🔌 API Endpoints

### Organizaciones

#### Listar Organizaciones del Usuario
```http
GET /api/v1/organizations
```

#### Crear Organización
```http
POST /api/v1/organizations
{
  "name": "Mi Empresa",
  "description": "...",
  "plan": "pro"
}
```

#### Ver Organización
```http
GET /api/v1/organizations/{id}
```

#### Actualizar Organización
```http
PUT /api/v1/organizations/{id}
```

#### Eliminar Organización
```http
DELETE /api/v1/organizations/{id}
```

#### Gestión de Usuarios
```http
POST /api/v1/organizations/{id}/users
DELETE /api/v1/organizations/{id}/users/{userId}
```

### Números de WhatsApp

#### Listar Números
```http
GET /api/v1/organizations/{orgId}/phone-numbers
```

#### Crear Número
```http
POST /api/v1/organizations/{orgId}/phone-numbers
{
  "phone_number": "+584241234567",
  "display_name": "Soporte",
  "phone_number_id": "123456789",
  "waba_id": "987654321",
  "access_token": "EAAxxxxx",
  "is_default": true
}
```

#### Actualizar Número
```http
PUT /api/v1/organizations/{orgId}/phone-numbers/{numberId}
```

#### Verificar Número
```http
POST /api/v1/organizations/{orgId}/phone-numbers/{numberId}/verify
```

#### Establecer como Predeterminado
```http
POST /api/v1/organizations/{orgId}/phone-numbers/{numberId}/set-default
```

#### Eliminar Número
```http
DELETE /api/v1/organizations/{orgId}/phone-numbers/{numberId}
```

## 🔐 Control de Acceso

### Roles y Permisos

#### Owner (Propietario)
- Todos los permisos de Admin
- Eliminar organización
- Transferir propiedad
- Cambiar plan de suscripción

#### Admin (Administrador)
- Gestionar números de WhatsApp
- Agregar/remover usuarios
- Actualizar configuraciones
- Ver todas las estadísticas

#### Member (Miembro)
- Ver información de la organización
- Ver números de WhatsApp
- Gestionar leads asignados
- Ver conversaciones

### Validación de Permisos

Cada endpoint valida:
1. Usuario autenticado
2. Pertenencia a la organización
3. Rol suficiente para la acción
4. Propiedad del recurso

## 🚀 Flujo de Uso

### 1. Crear Organización
```javascript
POST /api/v1/organizations
{
  "name": "Admetricas Agency",
  "plan": "pro"
}
// El creador se convierte automáticamente en owner
```

### 2. Agregar Número de WhatsApp
```javascript
POST /api/v1/organizations/1/phone-numbers
{
  "phone_number": "+584241234567",
  "phone_number_id": "123456789",
  "waba_id": "987654321",
  "access_token": "EAAxxxxx",
  "is_default": true
}
```

### 3. Verificar Número
```javascript
POST /api/v1/organizations/1/phone-numbers/1/verify
// Activa el número y lo marca como verificado
```

### 4. Agregar Miembros del Equipo
```javascript
POST /api/v1/organizations/1/users
{
  "user_id": 2,
  "role": "admin"
}
```

### 5. Gestionar Leads
Los leads ahora se crean automáticamente vinculados a:
- La organización del número que recibió el mensaje
- El número de WhatsApp específico
- El usuario asignado (si aplica)

## 📈 Escalabilidad

### Ventajas del Sistema Multi-Tenant

1. **Aislamiento de Datos**: Cada organización tiene sus propios datos
2. **Escalabilidad Horizontal**: Fácil agregar nuevas organizaciones
3. **Personalización**: Configuraciones únicas por organización
4. **Monetización**: Diferentes planes y límites
5. **Gestión Centralizada**: Un solo sistema para múltiples clientes

### Límites por Plan

```javascript
const PLAN_LIMITS = {
  free: {
    phone_numbers: 1,
    users: 2,
    leads_per_month: 100
  },
  basic: {
    phone_numbers: 3,
    users: 5,
    leads_per_month: 1000
  },
  pro: {
    phone_numbers: 10,
    users: 20,
    leads_per_month: 10000
  },
  enterprise: {
    phone_numbers: -1, // ilimitado
    users: -1,
    leads_per_month: -1
  }
}
```

## 🔧 Próximos Pasos

### Pendientes de Implementación

1. **Ejecutar Migraciones**
   ```bash
   cd backend
   php artisan migrate
   ```

2. **Panel de Filament**
   - Crear recursos para Organizations
   - Crear recursos para WhatsAppPhoneNumbers
   - Dashboard con métricas por organización

3. **Webhooks Dinámicos**
   - Enrutar webhooks al número correcto
   - Validar verify_token por número
   - Logging por organización

4. **Billing & Subscriptions**
   - Integración con Stripe/PayPal
   - Límites por plan
   - Facturación automática

5. **Analytics**
   - Métricas por organización
   - Uso de números de WhatsApp
   - Calidad de servicio

## 📝 Notas Técnicas

### Encriptación de Tokens
Los `access_token` se encriptan automáticamente usando Laravel Crypt:
```php
// Al guardar
$this->attributes['access_token'] = Crypt::encryptString($value);

// Al leer
return Crypt::decryptString($value);
```

### Número por Defecto
Solo un número puede ser predeterminado por organización:
```php
$phoneNumber->setAsDefault();
// Automáticamente desmarca otros números
```

### Soft Deletes
Todas las entidades usan soft deletes para mantener historial:
- Organizations
- WhatsAppPhoneNumbers

## 🎓 Ejemplos de Uso

### Frontend (Vue 3)
```typescript
// Obtener organizaciones del usuario
const organizations = await apiClient.get('/organizations')

// Crear número de WhatsApp
const phoneNumber = await apiClient.post(
  `/organizations/${orgId}/phone-numbers`,
  {
    phone_number: '+584241234567',
    display_name: 'Soporte Principal',
    // ...
  }
)

// Establecer como predeterminado
await apiClient.post(
  `/organizations/${orgId}/phone-numbers/${numberId}/set-default`
)
```

### Mobile (Flutter)
```dart
// Listar números de la organización
final response = await dio.get(
  '/api/v1/organizations/$orgId/phone-numbers',
  options: Options(headers: {'Authorization': 'Bearer $token'})
);

final phoneNumbers = response.data['data'];
```

## 🔍 Monitoreo y Logs

Todos los eventos importantes se registran:
- Creación/actualización de organizaciones
- Gestión de números de WhatsApp
- Cambios de permisos de usuarios
- Verificaciones de números
- Cambios de número predeterminado

```php
Log::info('WhatsApp phone number created', [
    'phone_number_id' => $phoneNumber->id,
    'organization_id' => $organization->id,
    'user_id' => $request->user()->id,
]);
```

---

**Sistema implementado y listo para ejecutar migraciones** ✅
