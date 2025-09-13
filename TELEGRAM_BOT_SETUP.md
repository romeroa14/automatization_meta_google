# 🤖 Bot de Telegram para Campañas de Meta

## 📋 Descripción

Este bot de Telegram permite crear campañas de Facebook e Instagram de forma automatizada mediante conversación natural. El bot guía al usuario paso a paso para configurar todos los parámetros necesarios de la campaña.

## 🚀 Funcionalidades

### ✅ Implementadas
- ✅ **Flujo de conversación completo** - Guía paso a paso
- ✅ **Múltiples objetivos** - Tráfico, conversiones, alcance, etc.
- ✅ **Configuración de presupuestos** - A nivel campaña o adset
- ✅ **Fechas de campaña** - Inicio y fin configurables
- ✅ **Subida de archivos** - Imágenes y videos
- ✅ **Texto de anuncio** - Copy personalizado
- ✅ **Targeting automático** - Configuración inteligente
- ✅ **Almacenamiento de datos** - Base de datos completa
- ✅ **Interfaz de gestión** - Panel de administración en Filament

### 🔄 En Desarrollo
- 🔄 **Integración real con Meta API** - Creación de campañas reales
- 🔄 **Templates de campañas** - Plantillas predefinidas
- 🔄 **Targeting avanzado** - Configuración detallada
- 🔄 **Análisis de resultados** - Métricas y reportes

## 🛠️ Instalación y Configuración

### 1. Crear Bot en Telegram

1. Abre Telegram y busca `@BotFather`
2. Envía `/newbot`
3. Sigue las instrucciones para crear tu bot
4. Guarda el **token** que te proporciona

### 2. Configurar el Bot

```bash
# Ejecutar comando de configuración
php artisan telegram:setup

# O con parámetros directos
php artisan telegram:setup --token=TU_BOT_TOKEN --webhook=https://tu-dominio.com/api/telegram/webhook
```

### 3. Variables de Entorno

Agregar al archivo `.env`:

```env
TELEGRAM_BOT_TOKEN=tu_bot_token_aqui
TELEGRAM_WEBHOOK_URL=https://tu-dominio.com/api/telegram/webhook
DEFAULT_FACEBOOK_ACCOUNT_ID=tu_cuenta_facebook_id
DEFAULT_AD_ACCOUNT_ID=tu_cuenta_publicitaria_id
```

### 4. Configurar Webhook (Automático)

El comando `telegram:setup` configura automáticamente el webhook. Si necesitas hacerlo manualmente:

```bash
curl -X POST "https://api.telegram.org/bot<TU_BOT_TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://tu-dominio.com/api/telegram/webhook"}'
```

## 📱 Uso del Bot

### Comandos Disponibles

- `/start` - Iniciar creación de campaña
- `/help` - Mostrar ayuda (próximamente)

### Flujo de Creación de Campaña

1. **Nombre de campaña** - Escribe el nombre descriptivo
2. **Objetivo** - Selecciona de la lista (TRAFFIC, CONVERSIONS, etc.)
3. **Tipo de presupuesto** - Campaign o AdSet
4. **Presupuesto diario** - Monto en USD
5. **Fecha de inicio** - Formato DD/MM/YYYY
6. **Fecha de fin** - Formato DD/MM/YYYY o 'sin_fecha'
7. **Targeting** - Automático por defecto
8. **Texto del anuncio** - Copy personalizado
9. **Archivo multimedia** - Imagen o video (opcional)
10. **Confirmación** - Revisar y confirmar

### Ejemplo de Conversación

```
Usuario: /start
Bot: 🚀 ¡Bienvenido al Bot de Campañas de Meta!...

Usuario: Mi Campaña de Verano
Bot: ✅ Nombre de campaña: Mi Campaña de Verano
     🎯 Selecciona el objetivo de tu campaña:...

Usuario: TRAFFIC
Bot: ✅ Objetivo: Tráfico
     💰 ¿Dónde quieres establecer el presupuesto?...

Usuario: campaign
Bot: ✅ Tipo de presupuesto: Campaña
     💵 ¿Cuál es tu presupuesto diario?...

Usuario: 25.50
Bot: ✅ Presupuesto diario: $25.50
     📅 ¿Cuándo quieres que inicie la campaña?...

Usuario: 15/09/2025
Bot: ✅ Fecha de inicio: 15/09/2025
     📅 ¿Cuándo quieres que termine la campaña?...

Usuario: 30/09/2025
Bot: ✅ Fecha de fin: 30/09/2025
     🎯 ¿Quieres configurar targeting específico?...

Usuario: no
Bot: ✅ Targeting: Automático
     📝 Escribe el texto de tu anuncio:...

Usuario: ¡Descubre nuestros productos increíbles! Oferta limitada.
Bot: ✅ Texto del anuncio: ¡Descubre nuestros productos increíbles! Oferta limitada.
     🖼️ Ahora envía la imagen o video para tu anuncio...

Usuario: [Envía una imagen]
Bot: 📋 RESUMEN DE TU CAMPAÑA
     📝 Nombre: Mi Campaña de Verano
     🎯 Objetivo: Tráfico
     💰 Presupuesto: $25.50 diarios
     📅 Inicio: 15/09/2025
     📅 Fin: 30/09/2025
     📝 Texto: ¡Descubre nuestros productos increíbles! Oferta limitada.
     🖼️ Media: image
     ¿Confirmas la creación de esta campaña?

Usuario: si
Bot: ⏳ Creando campaña en Meta... Esto puede tomar unos minutos.
     ✅ ¡Campaña creada exitosamente!
     📊 Detalles:
     • ID Campaña: act_123456789
     • ID AdSet: 987654321
     • ID Anuncio: 456789123
     Tu campaña está lista y activa. ¡Usa /start para crear otra!
```

## 🗄️ Estructura de Base de Datos

### Tabla: `telegram_conversations`
- `id` - ID único
- `telegram_user_id` - ID del usuario en Telegram
- `telegram_username` - Username de Telegram
- `telegram_first_name` - Nombre del usuario
- `telegram_last_name` - Apellido del usuario
- `current_step` - Paso actual de la conversación
- `conversation_data` - Datos recopilados (JSON)
- `is_active` - Si la conversación está activa
- `last_activity` - Última actividad
- `created_at` / `updated_at` - Timestamps

### Tabla: `telegram_campaigns`
- `id` - ID único
- `telegram_user_id` - ID del usuario en Telegram
- `telegram_conversation_id` - ID de la conversación
- `campaign_name` - Nombre de la campaña
- `objective` - Objetivo de la campaña
- `budget_type` - Tipo de presupuesto
- `daily_budget` - Presupuesto diario
- `start_date` - Fecha de inicio
- `end_date` - Fecha de fin
- `targeting_data` - Datos de targeting (JSON)
- `ad_data` - Datos del anuncio (JSON)
- `media_type` - Tipo de archivo multimedia
- `media_url` - URL del archivo
- `ad_copy` - Texto del anuncio
- `meta_campaign_id` - ID de la campaña en Meta
- `meta_adset_id` - ID del adset en Meta
- `meta_ad_id` - ID del anuncio en Meta
- `status` - Estado (pending, created, failed)
- `error_message` - Mensaje de error si falla
- `created_at` / `updated_at` - Timestamps

## 🔧 API Endpoints

### Webhook de Telegram
```
POST /api/telegram/webhook
```
Recibe los mensajes de Telegram y procesa las conversaciones.

### Configurar Webhook
```
POST /api/telegram/set-webhook
```
Configura el webhook de Telegram.

### Información del Bot
```
GET /api/telegram/bot-info
```
Obtiene información del bot de Telegram.

## 📊 Panel de Administración

El bot incluye un panel de administración completo en Filament:

- **Campañas de Telegram** - Lista todas las campañas creadas
- **Filtros avanzados** - Por estado, objetivo, tipo de presupuesto
- **Vista detallada** - Información completa de cada campaña
- **Gestión de errores** - Visualización de errores y fallos

## 🔒 Seguridad

- **Validación de entrada** - Todos los datos se validan
- **Límites de archivo** - Tamaño máximo de 20MB
- **Tipos de archivo** - Solo formatos soportados
- **Logs de actividad** - Registro completo de acciones
- **Manejo de errores** - Captura y registro de excepciones

## 🚀 Próximas Funcionalidades

1. **Integración real con Meta API** - Crear campañas reales
2. **Templates de campañas** - Plantillas predefinidas
3. **Targeting avanzado** - Configuración detallada de audiencias
4. **Análisis de resultados** - Métricas y reportes
5. **Notificaciones** - Alertas de estado de campañas
6. **Multi-idioma** - Soporte para múltiples idiomas
7. **Comandos avanzados** - Más comandos de gestión

## 🐛 Solución de Problemas

### Bot no responde
1. Verificar que el webhook esté configurado correctamente
2. Revisar los logs de Laravel
3. Verificar que el token del bot sea correcto

### Error al subir archivos
1. Verificar permisos de escritura en `storage/app/public`
2. Revisar límites de tamaño de archivo
3. Verificar tipos de archivo soportados

### Campañas no se crean
1. Verificar configuración de Meta API
2. Revisar tokens de acceso de Facebook
3. Verificar permisos de la cuenta publicitaria

## 📞 Soporte

Para soporte técnico o reportar bugs, contacta al administrador del sistema.

---

**¡Disfruta creando campañas de Meta con tu bot de Telegram! 🚀**
