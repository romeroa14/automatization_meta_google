# API Reference - Admetricas v1

Base URL: `http://localhost:8000/api/v1` (desarrollo)  
Production URL: `https://app.admetricas.com/api/v1`

## Autenticación

### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response (Mobile - Flutter):**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

**Response (SPA - Vue):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

**Headers para Mobile (Flutter):**
```
X-Client-Type: mobile
Authorization: Bearer {token}
```

**Headers para SPA (Vue):**
```
X-Requested-With: XMLHttpRequest
Cookie: laravel_session=...
```

### Logout
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

### Get Current User
```http
GET /api/v1/auth/user
Authorization: Bearer {token}
```

---

## Profile Management

### Get Profile
```http
GET /api/v1/profile
Authorization: Bearer {token}
```

### Update Profile
```http
PUT /api/v1/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com"
}
```

### Generate API Token
```http
POST /api/v1/profile/token
Authorization: Bearer {token}
```

---

## Leads Management

### List Leads
```http
GET /api/v1/leads
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Número de página
- `per_page` (optional): Resultados por página
- `stage` (optional): Filtrar por etapa (nuevo, contactado, interesado, cliente)

### Get Lead
```http
GET /api/v1/leads/{id}
Authorization: Bearer {token}
```

### Create Lead
```http
POST /api/v1/leads
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_name": "Alfredo Romero",
  "phone": "+584241234567",
  "stage": "nuevo",
  "platform": "whatsapp"
}
```

### Update Lead
```http
PUT /api/v1/leads/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "stage": "contactado",
  "notes": "Cliente interesado en campaña"
}
```

### Delete Lead
```http
DELETE /api/v1/leads/{id}
Authorization: Bearer {token}
```

### Get Lead Conversations
```http
GET /api/v1/leads/{id}/conversations
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 113,
      "lead_id": 12,
      "message_text": "Hola, me interesa su servicio",
      "response": "¡Hola! Gracias por contactarnos...",
      "timestamp": "2026-01-04T20:48:50.453Z",
      "platform": "whatsapp"
    }
  ]
}
```

---

## Campaigns Management

### List Campaigns
```http
GET /api/v1/campaigns
Authorization: Bearer {token}
```

### Get Campaign
```http
GET /api/v1/campaigns/{id}
Authorization: Bearer {token}
```

### Create Campaign
```http
POST /api/v1/campaigns
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Campaña Enero 2026",
  "budget": 100,
  "status": "active"
}
```

### Update Campaign
```http
PUT /api/v1/campaigns/{id}
Authorization: Bearer {token}
```

### Delete Campaign
```http
DELETE /api/v1/campaigns/{id}
Authorization: Bearer {token}
```

---

## Facebook Integration

### Get Facebook Login URL
```http
GET /api/v1/facebook/login-url
Authorization: Bearer {token}
```

### Handle Facebook Callback
```http
POST /api/v1/facebook/callback
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "facebook_oauth_code"
}
```

### Get Connection Status
```http
GET /api/v1/facebook/status
Authorization: Bearer {token}
```

### Disconnect Facebook
```http
POST /api/v1/facebook/disconnect
Authorization: Bearer {token}
```

### Get Facebook Campaigns
```http
GET /api/v1/facebook/campaigns
Authorization: Bearer {token}
```

---

## WhatsApp Integration

### Send WhatsApp Message
```http
POST /api/v1/whatsapp/send
Authorization: Bearer {token}
Content-Type: application/json

{
  "lead_id": 12,
  "message": "Hola, ¿cómo estás?"
}
```

### Toggle Bot
```http
POST /api/v1/whatsapp/toggle-bot
Authorization: Bearer {token}
Content-Type: application/json

{
  "lead_id": 12,
  "enabled": true
}
```

---

## Exchange Rates

### Get All Rates
```http
GET /api/v1/exchange-rates
Authorization: Bearer {token}
```

### Get Specific Currency Rate
```http
GET /api/v1/exchange-rates/{currency}
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/v1/exchange-rates/USD
```

---

## Organizations Management

### List Organizations
```http
GET /api/v1/organizations
Authorization: Bearer {token}
```

### Create Organization
```http
POST /api/v1/organizations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Mi Empresa",
  "description": "Descripción de la empresa",
  "website": "https://miempresa.com",
  "email": "contacto@miempresa.com",
  "phone": "+584241234567",
  "plan": "pro"
}
```

### Get Organization
```http
GET /api/v1/organizations/{id}
Authorization: Bearer {token}
```

### Update Organization
```http
PUT /api/v1/organizations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Nuevo Nombre",
  "is_active": true
}
```

### Delete Organization
```http
DELETE /api/v1/organizations/{id}
Authorization: Bearer {token}
```

### Add User to Organization
```http
POST /api/v1/organizations/{id}/users
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 2,
  "role": "admin"
}
```

### Remove User from Organization
```http
DELETE /api/v1/organizations/{id}/users/{userId}
Authorization: Bearer {token}
```

---

## WhatsApp Phone Numbers Management

### List Phone Numbers
```http
GET /api/v1/organizations/{organizationId}/phone-numbers
Authorization: Bearer {token}
```

### Create Phone Number
```http
POST /api/v1/organizations/{organizationId}/phone-numbers
Authorization: Bearer {token}
Content-Type: application/json

{
  "phone_number": "+584241234567",
  "display_name": "Soporte Principal",
  "phone_number_id": "123456789",
  "waba_id": "987654321",
  "access_token": "EAAxxxxxxxxxxxxx",
  "verify_token": "my_verify_token",
  "webhook_url": "https://app.admetricas.com/api/webhook/whatsapp",
  "is_default": true
}
```

### Get Phone Number
```http
GET /api/v1/organizations/{organizationId}/phone-numbers/{phoneNumberId}
Authorization: Bearer {token}
```

### Update Phone Number
```http
PUT /api/v1/organizations/{organizationId}/phone-numbers/{phoneNumberId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "display_name": "Nuevo Nombre",
  "status": "active",
  "quality_rating": "green"
}
```

### Delete Phone Number
```http
DELETE /api/v1/organizations/{organizationId}/phone-numbers/{phoneNumberId}
Authorization: Bearer {token}
```

### Verify Phone Number
```http
POST /api/v1/organizations/{organizationId}/phone-numbers/{phoneNumberId}/verify
Authorization: Bearer {token}
```

### Set as Default Phone Number
```http
POST /api/v1/organizations/{organizationId}/phone-numbers/{phoneNumberId}/set-default
Authorization: Bearer {token}
```

---

## Health Check

### API Health
```http
GET /api/v1/health
```

**Response:**
```json
{
  "status": "ok",
  "version": "v1",
  "timestamp": "2026-01-04T20:48:50.453Z"
}
```

---

## Error Responses

Todos los endpoints pueden devolver los siguientes códigos de error:

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 500 Server Error
```json
{
  "message": "Server Error",
  "error": "Internal server error"
}
```

---

## CORS Configuration

Los siguientes orígenes están permitidos:
- `http://localhost:3000` (Vue development)
- `https://app.admetricas.com` (Production)

**Credentials:** `true` (cookies habilitadas para SPA)

---

## Rate Limiting

- **Límite:** 60 peticiones por minuto por IP
- **Header de respuesta:** `X-RateLimit-Limit`, `X-RateLimit-Remaining`
