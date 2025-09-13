<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el bot de Telegram que permite crear campañas
    | de Meta de forma automatizada.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') . '/api/telegram/webhook'),
    
    'allowed_users' => [
        // IDs de usuarios de Telegram permitidos (opcional)
        // Si está vacío, cualquier usuario puede usar el bot
    ],
    
    'max_file_size' => 20 * 1024 * 1024, // 20MB
    
    'supported_media_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'video/mp4',
        'video/avi',
        'video/mov',
    ],
    
    'default_facebook_account_id' => env('DEFAULT_FACEBOOK_ACCOUNT_ID'),
    
    'default_ad_account_id' => env('DEFAULT_AD_ACCOUNT_ID'),
];
