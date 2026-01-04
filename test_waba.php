<?php

use App\Models\UserFacebookConnection;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$connection = UserFacebookConnection::latest()->first();

if (!$connection) {
    echo "No hay conexion activa.\n";
    exit(1);
}

echo "Usando Token de: " . $connection->facebook_name . "\n";
$accessToken = $connection->access_token;
$apiVersion = 'v18.0';

// 1. Fetch Pages and check for Connected WhatsApp Account
echo "Fetching Pages and Connected WhatsApp Numbers...\n";
$url = "https://graph.facebook.com/{$apiVersion}/me/accounts";
$response = Http::get($url, [
    'access_token' => $accessToken,
    'fields' => 'id,name,connected_whatsapp_account{id,name,number}',
]);

$pages = $response->json();

if (isset($pages['data']) && count($pages['data']) > 0) {
    foreach ($pages['data'] as $page) {
        echo "Page Found: {$page['name']} ({$page['id']})\n";
        
        if (isset($page['connected_whatsapp_account'])) {
            $wa = $page['connected_whatsapp_account'];
            echo "  [SUCCESS] -> Connected WhatsApp: {$wa['number']} (ID: {$wa['id']}, Name: " . ($wa['name'] ?? 'N/A') . ")\n";
        } else {
            echo "  -> No WhatsApp connected directly to Page.\n";
        }
    }
} else {
    echo "No se encontraron Páginas.\n";
    print_r($pages);
}
