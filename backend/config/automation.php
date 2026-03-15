<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Automatización
    |--------------------------------------------------------------------------
    |
    | Configuraciones generales para el sistema de automatización
    | de Facebook Ads a Google Sheets.
    |
    */

    'facebook' => [
        'date_preset' => 'last_7d', // last_7d, last_30d, last_90d, etc.
        'level' => 'campaign', // campaign, adset, ad
        'limit' => 1000, // Máximo de registros a obtener
        'fields' => [
            'campaign_name',
            'impressions',
            'clicks',
            'spend',
            'reach',
            'frequency',
            'cpm',
            'cpc',
            'ctr',
            'date_start',
            'date_stop'
        ],
    ],

    'google_sheets' => [
        'value_input_option' => 'RAW', // RAW, USER_ENTERED
        'major_dimension' => 'ROWS', // ROWS, COLUMNS
    ],

    'scheduler' => [
        'frequency' => 'everyFiveMinutes', // Frecuencia de verificación
        'without_overlapping' => true, // Evitar ejecuciones simultáneas
        'run_in_background' => true, // Ejecutar en segundo plano
    ],

    'queue' => [
        'timeout' => 300, // 5 minutos
        'tries' => 3, // Número de intentos
        'backoff' => [60, 180, 360], // Tiempo entre reintentos (segundos)
    ],

    'notifications' => [
        'on_success' => true, // Notificar éxitos
        'on_failure' => true, // Notificar fallos
        'on_completion' => true, // Notificar completado
    ],
]; 