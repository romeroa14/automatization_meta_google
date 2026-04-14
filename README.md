# 🚀 Admetricas - Sistema de Gestión de Leads con IA

Sistema integral de gestión de leads con arquitectura API-First que integra WhatsApp, Instagram, Facebook y análisis con IA para optimizar la conversión de clientes potenciales.

## 🏗️ Arquitectura

Este proyecto utiliza una arquitectura moderna desacoplada:

```
Admetricas/
├── backend/           # Laravel 11 + Filament (API + Admin Panel)
├── frontend-web/      # Vue 3 + Vite (Web Application)
└── mobile-app/        # Flutter + Dart (iOS/Android App)
```

### Backend (Laravel API)
- **Framework**: Laravel 11
- **Admin Panel**: FilamentPHP
- **Autenticación**: Laravel Sanctum
  - Cookies para SPA (Vue)
  - Tokens para Mobile (Flutter)
- **Base de datos**: PostgreSQL
- **APIs**: RESTful JSON API en `/api/v1/*`

### Frontend Web (Vue 3)
- **Framework**: Vue 3 + Composition API
- **Build Tool**: Vite
- **Estado**: Pinia
- **HTTP Client**: Axios
- **UI**: TailwindCSS + Componentes personalizados

### Mobile App (Flutter)
- **Framework**: Flutter 3.x
- **Lenguaje**: Dart
- **Estado**: Provider/Riverpod
- **HTTP**: Dio
- **Almacenamiento**: flutter_secure_storage

## ✨ Características Principales

- 🔄 **Sincronización Automática**: Métricas de Facebook Ads a Google Sheets
- 📊 **Panel de Administración**: Interfaz web completa con Filament
- ⏰ **Tareas Programadas**: Ejecución automática con horarios personalizados
- 🔗 **Google Apps Script Universal**: Un solo script para múltiples hojas
- 📈 **Logs Detallados**: Monitoreo completo de ejecuciones
- 🎯 **Mapeo Dinámico**: Configuración flexible de celdas
- 🚀 **Cola de Jobs**: Procesamiento asíncrono robusto

## 🛠️ Tecnologías Utilizadas

- **Laravel 11** - Framework PHP
- **Filament 3** - Panel de administración
- **Facebook Ads API** - Integración con Meta
- **Google Apps Script** - Automatización de Google Sheets
- **PostgreSQL** - Base de datos
- **Redis** - Cola de jobs (opcional)

## 📋 Requisitos Previos

- PHP 8.2+
- Composer
- Node.js & NPM
- PostgreSQL
- Cuenta de Facebook Ads
- Cuenta de Google Workspace

## 🚀 Instalación

### 1. Clonar el repositorio
```bash
git clone git@github.com:romeroa14/automatization_meta_google.git
cd automatization_meta_google
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
```

Editar `.env` con tus credenciales:
```env
# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=admetricas
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# Facebook Ads API
FACEBOOK_APP_ID=tu_app_id
FACEBOOK_APP_SECRET=tu_app_secret
FACEBOOK_ACCESS_TOKEN=tu_access_token

# Google Apps Script Web App
GOOGLE_WEBAPP_URL=https://script.google.com/macros/s/TU_WEBAPP_ID/exec
```

### 4. Configurar base de datos
```bash
php artisan migrate
php artisan db:seed
```

### 5. Configurar Google Apps Script
```bash
php artisan google:setup-script
```

### 6. Compilar assets
```bash
npm run build
```

### 7. Configurar cron job
```bash
# Agregar al crontab
* * * * * cd /ruta/a/tu/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Configuración de Facebook Ads

### 1. Crear aplicación en Facebook Developers
- Ve a [Facebook Developers](https://developers.facebook.com/)
- Crea una nueva aplicación
- Configura Facebook Login
- Obtén App ID y App Secret

### 2. Configurar permisos
- `ads_management`
- `ads_read`
- `business_management`

### 3. Generar Access Token
- Token de larga duración (60 días)
- Con permisos de administrador de anuncios

## 🔧 Configuración de Google Sheets

### 1. Crear Google Apps Script
- Ve a [Google Apps Script](https://script.google.com/)
- Crea un nuevo proyecto
- Copia el código generado por el comando `setup-script`
- Despliega como Web App

### 2. Configurar permisos
- Ejecutar como: "Yo mismo"
- Acceso: "Cualquier persona"

### 3. Actualizar URL en .env
```env
GOOGLE_WEBAPP_URL=https://script.google.com/macros/s/TU_WEBAPP_ID/exec
```

## 🎯 Uso del Sistema

### 1. Acceder al panel de administración
```
http://tu-dominio.com/admin
```

### 2. Configurar cuentas de Facebook
- Ve a "Cuentas Facebook"
- Agrega tus credenciales de Facebook Ads

### 3. Configurar Google Sheets
- Ve a "Google Sheets"
- Agrega el ID del spreadsheet y hoja
- Configura el mapeo de celdas

### 4. Crear tareas de automatización
- Ve a "Tareas de Automatización"
- Selecciona cuenta de Facebook y Google Sheet
- Configura frecuencia y horario
- Activa la tarea

### 5. Monitorear ejecuciones
- Ve a "Logs de Tareas"
- Revisa el estado de las ejecuciones
- Verifica los datos sincronizados

## 📈 Estructura del Proyecto

```
app/
├── Console/Commands/          # Comandos de consola
├── Filament/Resources/        # Recursos del panel admin
├── Jobs/                      # Jobs de sincronización
├── Models/                    # Modelos Eloquent
└── Services/                  # Servicios de negocio

database/
├── migrations/               # Migraciones de BD
└── seeders/                 # Datos iniciales

config/
├── automation.php           # Configuración de automatización
└── services.php            # Configuración de servicios
```

## 🔄 Flujo de Sincronización

1. **Programación**: El sistema verifica tareas pendientes
2. **Despacho**: Se crea un job de sincronización
3. **Facebook API**: Se obtienen métricas de Facebook Ads
4. **Procesamiento**: Se formatean los datos
5. **Google Sheets**: Se actualizan las celdas via Web App
6. **Logging**: Se registra el resultado de la ejecución

## 📊 Métricas Sincronizadas

- **Impressions**: Impresiones
- **Clicks**: Clics
- **Spend**: Gasto
- **Reach**: Alcance
- **CTR**: Tasa de clics
- **CPM**: Costo por mil impresiones
- **CPC**: Costo por clic

## 🛠️ Comandos Útiles

```bash
# Ejecutar tareas manualmente
php artisan automation:run

# Procesar cola de jobs
php artisan queue:work

# Verificar conexión con Facebook
php artisan facebook:test

# Configurar Google Apps Script
php artisan google:setup-script

# Ver logs de tareas
php artisan task:logs
```

## 🔍 Monitoreo y Logs

### Logs de Laravel
```bash
tail -f storage/logs/laravel.log
```

### Logs de Tareas
- Panel de administración → Logs de Tareas
- Detalles de cada ejecución
- Tiempo de ejecución
- Datos sincronizados

## 🚨 Solución de Problemas

### Error de Facebook API
- Verificar credenciales en `.env`
- Comprobar permisos de la aplicación
- Verificar que el token no haya expirado

### Error de Google Sheets
- Verificar URL del Web App
- Comprobar permisos del spreadsheet
- Ejecutar `testUniversalScript()` en Google Apps Script

### Jobs no se procesan
- Verificar configuración de colas
- Ejecutar `php artisan queue:work`
- Revisar logs de Laravel

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Alfredo Romero**
- GitHub: [@romeroa14](https://github.com/romeroa14)

## 🙏 Agradecimientos

- Laravel Team por el framework
- Filament Team por el panel de administración
- Facebook por la API de Ads
- Google por Apps Script

---

⭐ Si este proyecto te ayuda, ¡dale una estrella en GitHub!
