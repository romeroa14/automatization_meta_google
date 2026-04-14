<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAdSlideWithImage extends Command
{
    protected $signature = 'test:ad-slide-with-image';
    protected $description = 'Prueba la creación de diapositivas de anuncios con imágenes';

    public function handle()
    {
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
        
        $this->info("🔍 Probando creación de diapositiva de anuncio con imagen");
        $this->info("URL: {$webAppUrl}");
        
        // 1. Crear presentación
        $this->info("\n1️⃣ Creando presentación...");
        $createResponse = Http::timeout(30)->post($webAppUrl, [
            'action' => 'create_presentation',
            'title' => 'Test Ad Slide with Image'
        ]);
        
        if (!$createResponse->successful()) {
            $this->error("Error creando presentación: " . $createResponse->body());
            return;
        }
        
        $data = $createResponse->json();
        $presentationId = $data['data']['presentation_id'] ?? null;
        
        if (!$presentationId) {
            $this->error("No se pudo obtener el ID de la presentación");
            return;
        }
        
        $this->info("✅ Presentación creada: {$presentationId}");
        
        // 2. Crear diapositiva de anuncio con imagen
        $this->info("\n2️⃣ Creando diapositiva de anuncio con imagen...");
        
        $slideData = [
            'type' => 'ad',
            'title' => 'Nuevo anuncio de Tráfico',
            'subtitle' => 'Anuncio ID: 120231580651390153',
            'ad_image_url' => 'https://scontent.fccs3-2.fna.fbcdn.net/v/t15.13418-10/529450482_2461924060852423_5142458712024642876_n.jpg?_nc_cat=108&ccb=1-7&_nc_ohc=GgLU6_lQpN8Q7kNvwFiF59m&_nc_oc=AdlSwLie28nPsKP17HvXyNY9UIJhfBGJK3IQHv1LyOMxDsROi3KC6Dixsmx89pysRcY&_nc_zt=23&_nc_ht=scontent.fccs3-2.fna&edm=AAT1rw8EAAAA&_nc_gid=0LuDAf1VH-vC-jozBOHa3Q&stp=c0.5000x0.5000f_dst-emg0_p64x64_q75_tt6&ur=ace027&_nc_sid=58080a&oh=00_AfVvqRxQgHN51uwcNm7N0Z72eb_1-D7S02iFQFf_XwLzvQ&oe=68AF8EAA',
            'metrics' => [
                'alcance' => '8,845',
                'impresiones' => '12,453',
                'frecuencia' => '1.41',
                'clicks' => '829',
                'ctr' => '6.66%',
                'costo_por_resultado' => '$0.01',
                'importe_gastado' => '$12.31',
                'resultados' => '829',
                'cpm' => '$0.99',
                'cpc' => '$0.01',
                'frecuencia_media' => '1.41',
                'alcance_neto' => '8,845'
            ],
            'followers' => [
                'facebook' => '0',
                'instagram' => '0'
            ]
        ];
        
        $slideResponse = Http::timeout(60)->post($webAppUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_data' => $slideData
        ]);
        
        $this->info("Status: " . $slideResponse->status());
        $this->info("Response: " . $slideResponse->body());
        
        if ($slideResponse->successful()) {
            $this->info("✅ Diapositiva creada exitosamente");
            $this->info("🔗 URL de la presentación: https://docs.google.com/presentation/d/{$presentationId}/edit");
        } else {
            $this->error("❌ Error creando diapositiva");
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
