# Actualizar APP_KEY en Laravel Cloud

## 🔧 Variable a actualizar:

**Nombre:** `APP_KEY`
**Valor:** `base64:x1IaR6AdJ6j0RRkI1G3WIehzhAdw1GMagoIua714j60=`

## 📋 Pasos en Laravel Cloud Dashboard:

1. **Ir a:** [Laravel Cloud Dashboard](https://cloud.laravel.com)
2. **Seleccionar proyecto:** `automatization_fb_google`
3. **Ir a:** Environment Variables
4. **Buscar:** `APP_KEY`
5. **Actualizar valor a:** `base64:x1IaR6AdJ6j0RRkI1G3WIehzhAdw1GMagoIua714j60=`
6. **Guardar cambios**

## 🚀 Después de actualizar:

```bash
# En Laravel Cloud, ejecutar:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## ✅ Verificar que funciona:

```bash
# Verificar que la APP_KEY se aplicó correctamente
php artisan tinker
>>> config('app.key')
```

**Debería mostrar:** `base64:x1IaR6AdJ6j0RRkI1G3WIehzhAdw1GMagoIua714j60=`
