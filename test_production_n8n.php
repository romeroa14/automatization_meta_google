<?php

// Script para probar la conexión desde producción
echo "🚀 Probando conexión desde admetricas.com a n8n...\n\n";

$n8nUrl = 'https://combined-bike-bracket-comment.trycloudflare.com/webhook-test/instagram-webhook';

$data = [
    'sender_id' => '12334',
    'message' => 'Hola desde admetricas.com (producción)',
    'message_id' => 'test_production',
    'timestamp' => date('c'),
    'platform' => 'instagram'
];

echo "📤 Enviando datos a n8n desde producción:\n";
echo "URL: $n8nUrl\n";
echo "Datos: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $n8nUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: Admetricas/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📥 Respuesta de n8n:\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($error) {
    echo "❌ Error: $error\n";
} else {
    echo "✅ Conexión exitosa desde producción\n";
}

echo "\n🔍 Verifica que n8n esté ejecutándose y el webhook esté activo.\n";
echo "📋 Comando para activar n8n: Ejecutar workflow en n8n\n";
