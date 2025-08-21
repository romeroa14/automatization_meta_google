<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGoogleSlidesScript extends Command
{
    protected $signature = 'slides:test-script {action=test : Acción a probar (test, create_presentation, create_slide)}';
    protected $description = 'Prueba la conexión con el Google Apps Script para Slides';

    public function handle()
    {
        $action = $this->argument('action');
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');

        $this->info("🧪 Probando Google Apps Script para Slides");
        $this->info("🔗 URL: " . $webAppUrl);
        $this->info("🎯 Acción: " . $action);

        try {
            switch ($action) {
                case 'test':
                    $this->testConnection($webAppUrl);
                    break;
                case 'create_presentation':
                    $this->testCreatePresentation($webAppUrl);
                    break;
                case 'create_slide':
                    $this->testCreateSlide($webAppUrl);
                    break;
                default:
                    $this->error("❌ Acción no válida: " . $action);
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error probando Google Slides Script: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function testConnection($webAppUrl)
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
            'title' => 'Prueba de Conexión - ' . date('Y-m-d H:i:s'),
            'description' => 'Presentación de prueba para verificar la conexión'
        ];

        $response = Http::timeout(60)->post($webAppUrl, $data);
        
        $this->info("📊 Status Code: " . $response->status());
        $this->info("📄 Response: " . $response->body());

        if ($response->successful()) {
            $responseData = $response->json();
            if (isset($responseData['success']) && $responseData['success']) {
                $this->info("✅ Presentación creada exitosamente!");
                $this->info("🆔 ID: " . ($responseData['data']['presentation_id'] ?? 'N/A'));
                $this->info("🔗 URL: " . ($responseData['data']['presentation_url'] ?? 'N/A'));
            } else {
                $this->error("❌ Error en la respuesta: " . ($responseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $response->status());
        }
    }

    protected function testCreateSlide($webAppUrl)
    {
        $this->info("📄 Probando creación de diapositiva...");

        // Primero crear una presentación
        $presentationData = [
            'action' => 'create_presentation',
            'title' => 'Prueba de Diapositivas - ' . date('Y-m-d H:i:s'),
            'description' => 'Presentación para probar diapositivas'
        ];

        $presentationResponse = Http::timeout(60)->post($webAppUrl, $presentationData);
        
        if (!$presentationResponse->successful()) {
            $this->error("❌ No se pudo crear la presentación");
            return;
        }

        $presentationResponseData = $presentationResponse->json();
        if (!isset($presentationResponseData['success']) || !$presentationResponseData['success']) {
            $this->error("❌ Error creando presentación: " . ($presentationResponseData['error'] ?? 'Error desconocido'));
            return;
        }

        $presentationId = $presentationResponseData['data']['presentation_id'];
        $this->info("✅ Presentación creada: " . $presentationId);

        // Ahora crear una diapositiva
        $slideData = [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => 1,
            'slide_data' => [
                'type' => 'general_summary',
                'title' => 'Diapositiva de Prueba',
                'subtitle' => 'Probando diferentes tipos de contenido',
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

        $slideResponse = Http::timeout(60)->post($webAppUrl, $slideData);
        
        $this->info("📊 Status Code: " . $slideResponse->status());
        $this->info("📄 Response: " . $slideResponse->body());

        if ($slideResponse->successful()) {
            $slideResponseData = $slideResponse->json();
            if (isset($slideResponseData['success']) && $slideResponseData['success']) {
                $this->info("✅ Diapositiva creada exitosamente!");
                $this->info("🔗 URL de la presentación: " . $presentationResponseData['data']['presentation_url']);
            } else {
                $this->error("❌ Error en la respuesta: " . ($slideResponseData['error'] ?? 'Error desconocido'));
            }
        } else {
            $this->error("❌ Error HTTP: " . $slideResponse->status());
        }
    }
}
