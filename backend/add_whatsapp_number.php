<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WhatsAppPhoneNumber;

// Datos de tu número
$data = [
    'organization_id' => 3, // Ads Vzla
    'phone_number' => '+584222536796',
    'display_name' => 'Ads Vzla',
    'phone_number_id' => '850033644850831',
    'waba_id' => '1299176504687614',
    'access_token' => readline('Ingresa el Access Token: '),
    'verify_token' => 'whabot',
    'webhook_url' => 'https://admetricas.com/api/webhook/whatsapp',
    'status' => 'active',
    'quality_rating' => 'green',
    'is_default' => true,
    'verified_at' => now(),
];

try {
    $number = WhatsAppPhoneNumber::create($data);
    echo "✅ Número agregado exitosamente!\n";
    echo "ID: {$number->id}\n";
    echo "Número: {$number->phone_number}\n";
    echo "Organización: {$number->organization->name}\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
