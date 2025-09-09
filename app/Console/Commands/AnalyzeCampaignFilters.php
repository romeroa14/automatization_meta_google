<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;
use Carbon\Carbon;

class AnalyzeCampaignFilters extends Command
{
    protected $signature = 'campaigns:analyze-filters {ad_account_id}';
    protected $description = 'Analiza qué campañas se están filtrando y por qué';

    public function handle()
    {
        $adAccountId = $this->argument('ad_account_id');
        
        $facebookAccount = FacebookAccount::first();
        if (!$facebookAccount) {
            $this->error('No se encontró cuenta de Facebook');
            return;
        }

        $this->info("Analizando filtros para cuenta publicitaria: {$adAccountId}");
        
        $url = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status,start_time&limit=250&access_token={$facebookAccount->access_token}";
        $response = @file_get_contents($url);
        
        if (!$response) {
            $this->error('Error obteniendo datos de la API');
            return;
        }
        
        $data = json_decode($response, true);
        if (!isset($data['data'])) {
            $this->error('No se encontraron datos en la respuesta');
            return;
        }
        
        $this->info("Total de campañas de la API: " . count($data['data']));
        
        $active = 0;
        $paused = 0;
        $pausedRecent = 0;
        $pausedOld = 0;
        $excluded = [];
        $included = [];
        
        foreach ($data['data'] as $campaign) {
            $isActive = $campaign['status'] === 'ACTIVE';
            $isRecent = false;
            
            if (isset($campaign['start_time'])) {
                $startTime = Carbon::parse($campaign['start_time']);
                $isRecent = $startTime->isAfter(now()->subYears(2));
            }
            
            if ($isActive) {
                $active++;
                $included[] = $campaign['name'] . ' (ACTIVE)';
            } else {
                $paused++;
                if ($isRecent) {
                    $pausedRecent++;
                    $included[] = $campaign['name'] . ' (PAUSED - Recent)';
                } else {
                    $pausedOld++;
                    $excluded[] = $campaign['name'] . ' (PAUSED - Old, Start: ' . ($campaign['start_time'] ?? 'N/A') . ')';
                }
            }
        }
        
        $this->info("\n=== RESUMEN ===");
        $this->info("ACTIVE: {$active}");
        $this->info("PAUSED (recientes): {$pausedRecent}");
        $this->info("PAUSED (antiguas - EXCLUIDAS): {$pausedOld}");
        $this->info("Total que se procesarían: " . ($active + $pausedRecent));
        $this->info("Total excluidas: {$pausedOld}");
        
        if (!empty($excluded)) {
            $this->warn("\n=== CAMPAÑAS PAUSED EXCLUIDAS (ANTIGUAS) ===");
            foreach ($excluded as $campaign) {
                $this->line("  - {$campaign}");
            }
        }
        
        if (!empty($included)) {
            $this->info("\n=== CAMPAÑAS INCLUIDAS ===");
            foreach ($included as $campaign) {
                $this->line("  - {$campaign}");
            }
        }
    }
}
