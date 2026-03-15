<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGoogleSlidesFunctions extends Command
{
    protected $signature = 'slides:test-functions {function=all : Función a probar (test, create_presentation, create_slide, all)}';
    protected $description = 'Prueba las funciones del Google Apps Script de forma individual';

    public function handle()
    {
        $function = $this->argument('function');
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');

        $this->info("🧪 Probando funciones del Google Apps Script");
        $this->info("🔗 URL: " . $webAppUrl);
        $this->info("🎯 Función: " . $function);

        try {
            switch ($function) {
                case 'test':
                    $this->testBasicConnection($webAppUrl);
                    break;
                case 'create_presentation':
                    $this->testCreatePresentation($webAppUrl);
                    break;
                case 'create_slide':
                    $this->testCreateSlide($webAppUrl);
                    break;
                case 'all':
                    $this->testBasicConnection($webAppUrl);
                    $this->testCreatePresentation($webAppUrl);
                    $this->testCreateSlide($webAppUrl);
                    break;
                default:
                    $this->error("❌ Función no válida: " . $function);
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error probando Google Slides Script: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function testBasicConnection($webAppUrl)
    {
        $this->info("📡 Probando conexión básica...");

        $response = Http::timeout(30)->get($webAppUrl);
        
        $this->info("📊 Status Code: " . $response->status());
        $this->info("📄 Response: " . $response->body());

        if ($response->successful()) {
            $this->info("✅ Conexión exitosa!");
        } else {
            $this->error("❌ Error en la conexión");
        }
    }

    protected function testCreatePresentation($webAppUrl)
    {
        $this->info("📄 Probando creación de presentación...");

        $data = [
            'action' => 'create_presentation',
            'title' => 'Prueba de Función - ' . date('Y-m-d H:i:s'),
            'description' => 'Presentación de prueba para verificar la función create_presentation'
        ];

        $this->info("📤 Enviando datos: " . json_encode($data, JSON_PRETTY_PRINT));

        $response = Http::timeout(60)->post($webAppUrl, $data);
        
        $this->info("📊 Status Code: " . $response->status());
        $this->info("📄 Response: " . $response->body());

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['success']) && $responseData['success']) {
                $this->info("✅ Presentación creada exitosamente!");
                $this->info("🆔 ID: " . ($responseData['data']['presentation_id'] ?? 'N/A'));
                $this->info("🔗 URL: " . ($responseData['data']['presentation_url'] ?? 'N/A'));
                return $responseData['data']['presentation_id'] ?? null;
            } else {
                $this->error("❌ Error en la respuesta: " . ($responseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $response->status());
        }

        return null;
    }

    protected function testCreateSlide($webAppUrl)
    {
        $this->info("📄 Probando creación de diapositiva...");

        // Primero crear una presentación
        $presentationId = $this->testCreatePresentation($webAppUrl);
        
        if (!$presentationId) {
            $this->error("❌ No se pudo crear la presentación para probar diapositivas");
            return;
        }

        $this->info("✅ Presentación creada: " . $presentationId);

        // Ahora crear una diapositiva con datos simples
        $slideData = [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => 1,
            'slide_data' => [
                'type' => 'general_summary',
                'title' => 'Diapositiva de Prueba',
                'subtitle' => 'Probando datos simples',
                'content' => [
                    'total_reach' => '50,000',
                    'total_impressions' => '150,000',
                    'total_clicks' => '2,500',
                    'total_spend' => '$5,000',
                    'average_ctr' => '1.67%',
                    'average_cpm' => '$33.33',
                    'average_cpc' => '$2.00'
                ]
            ]
        ];

        $this->info("📤 Enviando datos de diapositiva: " . json_encode($slideData, JSON_PRETTY_PRINT));

        $slideResponse = Http::timeout(60)->post($webAppUrl, $slideData);
        
        $this->info("📊 Status Code: " . $slideResponse->status());
        $this->info("📄 Response: " . $slideResponse->body());

        if ($slideResponse->successful()) {
            $slideResponseData = $slideResponse->json();
            if (isset($slideResponseData['success']) && $slideResponseData['success']) {
                $this->info("✅ Diapositiva creada exitosamente!");
            } else {
                $this->error("❌ Error en la respuesta: " . ($slideResponseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $slideResponse->status());
        }
    }
}
