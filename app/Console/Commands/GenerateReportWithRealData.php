<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\ReportBrand;
use App\Models\ReportCampaign;
use App\Models\FacebookAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateReportWithRealData extends Command
{
    protected $signature = 'reports:generate-with-real-data {report_id : ID del reporte}';
    protected $description = 'Genera un reporte poblándolo con datos reales de Facebook';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);

        if (!$report) {
            $this->error("❌ No se encontró el reporte con ID: {$reportId}");
            return 1;
        }

        $this->info("🚀 Generando reporte con datos reales: {$report->name}");

        try {
            // Limpiar datos existentes
            $this->info("🧹 Limpiando datos existentes...");
            $report->campaigns()->delete();
            $report->brands()->delete();

            // Obtener cuentas de Facebook seleccionadas
            $facebookAccountIds = $report->selected_facebook_accounts ?? [];
            
            foreach ($facebookAccountIds as $accountId) {
                $facebookAccount = FacebookAccount::find($accountId);
                if (!$facebookAccount) {
                    $this->warn("⚠️ Cuenta de Facebook {$accountId} no encontrada");
                    continue;
                }

                $this->info("📊 Procesando cuenta: {$facebookAccount->account_name}");
                
                // Crear o encontrar la marca para esta cuenta
                $brand = $this->createBrandForAccount($report, $facebookAccount);
                
                // Obtener datos reales de las campañas
                $this->fetchRealCampaignData($report, $brand, $facebookAccount);
            }

            $this->info("✅ Reporte poblado con datos reales exitosamente!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error generando reporte con datos reales: " . $e->getMessage());
            return 1;
        }
    }

    protected function createBrandForAccount(Report $report, FacebookAccount $facebookAccount): ReportBrand
    {
        $brand = new ReportBrand();
        $brand->report_id = $report->id;
        $brand->brand_name = $facebookAccount->account_name;
        $brand->brand_identifier = strtoupper(str_replace(' ', '_', $facebookAccount->account_name));
        $brand->campaign_ids = $facebookAccount->selected_campaign_ids ?? [];
        $brand->slide_order = 0;
        $brand->is_active = true;
        $brand->save();

        $this->info("✅ Marca creada: {$brand->brand_name}");
        return $brand;
    }

    protected function fetchRealCampaignData(Report $report, ReportBrand $brand, FacebookAccount $facebookAccount)
    {
        $campaignIds = $facebookAccount->selected_campaign_ids ?? [];
        
        if (empty($campaignIds)) {
            $this->warn("⚠️ No hay campañas seleccionadas para {$facebookAccount->account_name}");
            return;
        }

        foreach ($campaignIds as $campaignId) {
            $this->info("📈 Obteniendo datos para campaña: {$campaignId}");
            
            try {
                $campaignData = $this->fetchCampaignFromFacebook($facebookAccount, $campaignId, $report);
                
                if ($campaignData) {
                    $this->createReportCampaign($report, $brand, $campaignData);
                    $this->info("✅ Campaña procesada: {$campaignData['name']}");
                } else {
                    $this->warn("⚠️ No se pudieron obtener datos para campaña: {$campaignId}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error procesando campaña {$campaignId}: " . $e->getMessage());
                Log::error("Error procesando campaña {$campaignId}: " . $e->getMessage());
            }
        }
    }

    protected function fetchCampaignFromFacebook(FacebookAccount $facebookAccount, string $campaignId, Report $report): ?array
    {
        $accessToken = $facebookAccount->access_token;
        $adAccountId = $facebookAccount->selected_ad_account_id;
        
        // Obtener datos de la campaña
        $campaignUrl = "https://graph.facebook.com/v18.0/{$campaignId}";
        $campaignResponse = Http::get($campaignUrl, [
            'access_token' => $accessToken,
            'fields' => 'id,name,status,objective,created_time,updated_time'
        ]);

        if (!$campaignResponse->successful()) {
            $this->error("Error obteniendo campaña: " . $campaignResponse->body());
            return null;
        }

        $campaign = $campaignResponse->json();

        // Obtener insights de la campaña
        $insightsUrl = "https://graph.facebook.com/v18.0/{$campaignId}/insights";
        $insightsResponse = Http::get($insightsUrl, [
            'access_token' => $accessToken,
            'fields' => 'reach,impressions,clicks,spend,ctr,cpm,cpc,frequency,actions,video_play_actions,video_p100_watched_actions',
            'time_range' => json_encode([
                'since' => $report->period_start->format('Y-m-d'),
                'until' => $report->period_end->format('Y-m-d')
            ])
        ]);

        $insights = [];
        if ($insightsResponse->successful()) {
            $insightsData = $insightsResponse->json();
            if (!empty($insightsData['data'])) {
                $insights = $insightsData['data'][0];
            }
        }

        // Obtener anuncios de la campaña para obtener imágenes
        $adsUrl = "https://graph.facebook.com/v18.0/{$campaignId}/ads";
        $adsResponse = Http::get($adsUrl, [
            'access_token' => $accessToken,
            'fields' => 'id,name,creative{image_url,thumbnail_url}'
        ]);

        $adImageUrl = null;
        if ($adsResponse->successful()) {
            $adsData = $adsResponse->json();
            if (!empty($adsData['data'])) {
                $firstAd = $adsData['data'][0];
                if (isset($firstAd['creative']['image_url'])) {
                    $adImageUrl = $firstAd['creative']['image_url'];
                } elseif (isset($firstAd['creative']['thumbnail_url'])) {
                    $adImageUrl = $firstAd['creative']['thumbnail_url'];
                }
            }
        }

        // Procesar datos
        $reach = intval($insights['reach'] ?? 0);
        $impressions = intval($insights['impressions'] ?? 0);
        $clicks = intval($insights['clicks'] ?? 0);
        $spend = floatval($insights['spend'] ?? 0);
        $ctr = floatval($insights['ctr'] ?? 0);
        $cpm = floatval($insights['cpm'] ?? 0);
        $cpc = floatval($insights['cpc'] ?? 0);
        $frequency = floatval($insights['frequency'] ?? 0);

        // Calcular interacciones
        $totalInteractions = $this->calculateInteractions($insights['actions'] ?? []);
        $interactionRate = $impressions > 0 ? ($totalInteractions / $impressions) * 100 : 0;

        // Calcular video views
        $videoViews = $this->calculateVideoViews($insights['video_play_actions'] ?? []);
        $videoViewsP100 = $this->calculateVideoViewsP100($insights['video_p100_watched_actions'] ?? []);
        $videoCompletionRate = $videoViews > 0 ? ($videoViewsP100 / $videoViews) * 100 : 0;

        return [
            'id' => $campaign['id'],
            'name' => $campaign['name'],
            'status' => $campaign['status'] ?? 'UNKNOWN',
            'objective' => $campaign['objective'] ?? 'UNKNOWN',
            'reach' => $reach,
            'impressions' => $impressions,
            'clicks' => $clicks,
            'spend' => $spend,
            'ctr' => $ctr,
            'cpm' => $cpm,
            'cpc' => $cpc,
            'frequency' => $frequency,
            'total_interactions' => $totalInteractions,
            'interaction_rate' => $interactionRate,
            'video_views' => $videoViews,
            'video_views_p100' => $videoViewsP100,
            'video_completion_rate' => $videoCompletionRate,
            'ad_image_url' => $adImageUrl,
            'created_time' => $campaign['created_time'] ?? null,
            'updated_time' => $campaign['updated_time'] ?? null,
        ];
    }

    protected function calculateInteractions(array $actions): int
    {
        $interactionTypes = ['like', 'comment', 'share', 'post_engagement', 'page_engagement', 'post_reaction'];
        $totalInteractions = 0;

        foreach ($actions as $action) {
            if (in_array($action['action_type'] ?? '', $interactionTypes)) {
                $totalInteractions += intval($action['value'] ?? 0);
            }
        }

        return $totalInteractions;
    }

    protected function calculateVideoViews(array $videoActions): int
    {
        $totalViews = 0;
        foreach ($videoActions as $action) {
            if (($action['action_type'] ?? '') === 'video_view') {
                $totalViews += intval($action['value'] ?? 0);
            }
        }
        return $totalViews;
    }

    protected function calculateVideoViewsP100(array $videoActions): int
    {
        $totalViews = 0;
        foreach ($videoActions as $action) {
            if (($action['action_type'] ?? '') === 'video_p100_watched_actions') {
                $totalViews += intval($action['value'] ?? 0);
            }
        }
        return $totalViews;
    }

    protected function createReportCampaign(Report $report, ReportBrand $brand, array $campaignData): void
    {
        $campaign = new ReportCampaign();
        $campaign->report_id = $report->id;
        $campaign->report_brand_id = $brand->id;
        $campaign->campaign_id = $campaignData['id'];
        $campaign->campaign_name = $campaignData['name'];
        $campaign->ad_account_id = $report->selected_facebook_accounts[0] ?? 'unknown';
        
        $campaign->campaign_data = [
            'status' => $campaignData['status'],
            'objective' => $campaignData['objective'],
            'created_time' => $campaignData['created_time'],
            'updated_time' => $campaignData['updated_time'],
        ];

        $campaign->statistics = [
            'reach' => $campaignData['reach'],
            'impressions' => $campaignData['impressions'],
            'clicks' => $campaignData['clicks'],
            'spend' => $campaignData['spend'],
            'ctr' => $campaignData['ctr'],
            'cpm' => $campaignData['cpm'],
            'cpc' => $campaignData['cpc'],
            'frequency' => $campaignData['frequency'],
            'total_interactions' => $campaignData['total_interactions'],
            'interaction_rate' => $campaignData['interaction_rate'],
            'video_views_p100' => $campaignData['video_views_p100'],
            'video_completion_rate' => $campaignData['video_completion_rate'],
            'inline_link_clicks' => $campaignData['clicks'], // Usar clicks como aproximación
            'unique_clicks' => $campaignData['clicks'], // Usar clicks como aproximación
        ];

        $campaign->ad_image_url = $campaignData['ad_image_url'];
        $campaign->slide_order = 0;
        $campaign->is_active = true;
        $campaign->save();
    }
}
