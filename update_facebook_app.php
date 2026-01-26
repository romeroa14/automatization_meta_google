<?php

/**
 * Script para actualizar/crear Facebook Account con la app correcta
 * para WhatsApp Embedded Signup
 * 
 * INSTRUCCIONES:
 * 1. Reemplaza 'TU_APP_SECRET_AQUI' con el App Secret de la app 1332344178547966
 * 2. Ejecuta: php artisan tinker < update_facebook_app.php
 */

use App\Models\FacebookAccount;

// Configuración de la app correcta
$appId = '1332344178547966';
$appSecret = 'b20246192839045aa7b22182982da543'; // Cámbialo por el real

// Buscar o crear la cuenta
$account = FacebookAccount::where('app_id', $appId)->first();

if (!$account) {
    // Crear nueva cuenta
    $account = FacebookAccount::create([
        'app_id' => $appId,
        'app_secret' => $appSecret,
        'is_active' => true,
    ]);
    
    echo "✅ Nueva cuenta de Facebook creada con App ID: {$appId}\n";
} else {
    // Actualizar existente
    $account->update([
        'app_secret' => $appSecret,
        'is_active' => true,
    ]);
    
    echo "✅ Cuenta de Facebook actualizada con App ID: {$appId}\n";
}

// Desactivar las otras cuentas
FacebookAccount::where('app_id', '!=', $appId)->update(['is_active' => false]);

echo "✅ Otras cuentas desactivadas\n";
echo "App activa: {$account->app_id}\n";
