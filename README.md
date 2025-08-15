# Sistema de Automatización Facebook Ads → Google Sheets

Sistema completo de automatización para sincronizar datos de Facebook Ads a Google Sheets usando Laravel 11 y FilamentPHP v3.

## 🚀 Características

- **Integración Facebook Ads API**: Obtiene métricas de campañas publicitarias
- **Integración Google Sheets API**: Actualiza hojas de cálculo automáticamente
- **Panel de Administración**: Interfaz completa con FilamentPHP
- **Programación Flexible**: Frecuencias personalizables (hora, día, semana, mes)
- **Monitoreo en Tiempo Real**: Logs detallados y estadísticas
- **Ejecución Manual**: Botón para ejecutar tareas inmediatamente
- **Procesamiento en Colas**: Jobs asíncronos para mejor rendimiento

## 📋 Requisitos

- PHP 8.2+
- Laravel 11
- PostgreSQL
- Composer
- Credenciales de Facebook Business API
- Credenciales de Google Sheets API

## 🛠️ Instalación

1. **Clonar el proyecto**
```bash
git clone <repository-url>
cd data_ia_marketing
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar base de datos**
```bash
# Configurar .env con PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=auto_admetricas
DB_USERNAME=postgres
DB_PASSWORD=123456
```

4. **Ejecutar migraciones**
```bash
php artisan migrate
```

5. **Crear usuario administrador**
```bash
php artisan make:filament-user
```

6. **Instalar dependencias de APIs**
```bash
composer require google/apiclient facebook/php-business-sdk
```

## 🔧 Configuración

### Facebook Business API

1. Crear una aplicación en [Facebook Developers](https://developers.facebook.com/)
2. Obtener App ID y App Secret
3. Generar Access Token con permisos de ads_management
4. Obtener Account ID de la cuenta publicitaria

### Google Sheets API

1. Crear proyecto en [Google Cloud Console](https://console.cloud.google.com/)
2. Habilitar Google Sheets API
3. Crear credenciales de servicio (Service Account)
4. Descargar archivo JSON de credenciales
5. Compartir la hoja de cálculo con el email del service account

## 📖 Uso

### 1. Configurar Cuenta Facebook

1. Ir a **Cuentas Facebook** en el panel
2. Crear nueva cuenta con:
   - Nombre de la cuenta
   - Account ID (sin "act_")
   - App ID
   - App Secret
   - Access Token

### 2. Configurar Google Sheet

1. Ir a **Google Sheets** en el panel
2. Crear nueva configuración con:
   - Nombre de la hoja
   - Spreadsheet ID (de la URL)
   - Nombre de la hoja de trabajo
   - Credenciales JSON
   - Mapeo de celdas (ej: `{"impressions": "B2", "clicks": "B3"}`)

### 3. Crear Tarea de Automatización

1. Ir a **Tareas de Automatización**
2. Crear nueva tarea con:
   - Nombre y descripción
   - Seleccionar cuenta Facebook
   - Seleccionar Google Sheet
   - Configurar frecuencia y hora
   - Activar la tarea

### 4. Ejecutar Manualmente

- Usar el botón **"Ejecutar Ahora"** en la lista de tareas
- O ejecutar desde consola: `php artisan automation:run --task-id=1`

## 🔄 Automatización

### Scheduler

El sistema ejecuta automáticamente las tareas programadas cada 5 minutos:

```bash
# Verificar tareas pendientes
php artisan automation:run

# Ejecutar tarea específica
php artisan automation:run --task-id=1
```

### Colas

Los jobs se procesan en segundo plano:

```bash
# Procesar colas
php artisan queue:work

# Ver estado de colas
php artisan queue:monitor
```

## 📊 Monitoreo

### Dashboard

- **Estadísticas en tiempo real**
- **Tareas activas/inactivas**
- **Ejecuciones del día**
- **Última ejecución**

### Logs

- **Registro detallado** de cada ejecución
- **Tiempo de ejecución**
- **Registros procesados**
- **Errores y mensajes**

## 🗂️ Estructura del Proyecto

```
app/
├── Console/Commands/
│   └── RunAutomationTasks.php      # Comando para ejecutar tareas
├── Jobs/
│   └── SyncFacebookAdsToGoogleSheets.php  # Job de sincronización
├── Models/
│   ├── FacebookAccount.php         # Modelo de cuentas Facebook
│   ├── GoogleSheet.php             # Modelo de hojas Google
│   ├── AutomationTask.php          # Modelo de tareas
│   └── TaskLog.php                 # Modelo de logs
└── Filament/
    ├── Resources/                  # Recursos de Filament
    └── Widgets/
        └── AutomationStats.php     # Widget de estadísticas

database/migrations/               # Migraciones de base de datos
config/automation.php             # Configuración del sistema
routes/console.php                # Configuración del scheduler
```

## 🔐 Seguridad

- **Credenciales encriptadas** en base de datos
- **Acceso por usuario** a configuraciones
- **Logs de auditoría** de todas las acciones
- **Validación de permisos** en APIs

## 🚨 Troubleshooting

### Error de conexión Facebook
- Verificar App ID y App Secret
- Comprobar permisos del Access Token
- Validar Account ID

### Error de conexión Google Sheets
- Verificar credenciales JSON
- Comprobar permisos de la hoja
- Validar Spreadsheet ID

### Jobs no se ejecutan
- Verificar que `queue:work` esté corriendo
- Comprobar configuración de colas
- Revisar logs de Laravel

## 📝 Licencia

Este proyecto es de uso interno para automatización de marketing.

## 🤝 Contribución

Para reportar bugs o solicitar características, crear un issue en el repositorio.
