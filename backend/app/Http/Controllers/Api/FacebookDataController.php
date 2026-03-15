<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserFacebookConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookDataController extends Controller
{
    protected string $graphApiVersion = 'v18.0';
    protected string $graphApiUrl = 'https://graph.facebook.com';

    /**
     * Guardar preferencias de selección (Ad Account, Page)
     */
    public function selectAssets(Request $request)
    {
        $request->validate([
            'ad_account_id' => 'required|string',
            'page_id' => 'nullable|string',
        ]);

        $user = $request->user();
        $connection = UserFacebookConnection::where('user_id', $user->id)->active()->firstOrFail();

        $connection->update([
            'selected_ad_account_id' => $request->ad_account_id,
            'selected_page_id' => $request->page_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Preferencias guardadas correctamente',
        ]);
    }

    /**
     * Obtener Campañas
     */
    public function getCampaigns(Request $request)
    {
        $user = $request->user();
        $connection = UserFacebookConnection::where('user_id', $user->id)->active()->first();

        if (!$connection) {
            return response()->json(['error' => 'No conectado a Facebook'], 400);
        }

        if (!$connection->selected_ad_account_id) {
            return response()->json(['error' => 'No has seleccionado una cuenta publicitaria'], 400);
        }

        try {
            // Obtener campañas
            $fields = 'id,name,status,objective,daily_budget,lifetime_budget,spend,insights.date_preset(maximum){impressions,clicks,spend,cpp,cpm,reach,ctr}';
            
            $url = "{$this->graphApiUrl}/{$this->graphApiVersion}/{$connection->selected_ad_account_id}/campaigns";
            
            $response = Http::get($url, [
                'access_token' => $connection->access_token,
                'fields' => $fields,
                'limit' => 50,
            ]);

            if (!$response->successful()) {
                throw new \Exception($response->json()['error']['message'] ?? 'Error fetching campaigns');
            }

            $campaigns = collect($response->json()['data'])->map(function ($campaign) {
                $insights = $campaign['insights']['data'][0] ?? [];
                return [
                    'id' => $campaign['id'],
                    'name' => $campaign['name'],
                    'status' => $campaign['status'],
                    'objective' => $campaign['objective'] ?? null,
                    'daily_budget' => isset($campaign['daily_budget']) ? $campaign['daily_budget'] / 100 : null,
                    'amount_spent' => $campaign['spend'] ?? ($insights['spend'] ?? 0),
                    'impressions' => $insights['impressions'] ?? 0,
                    'clicks' => $insights['clicks'] ?? 0,
                    'ctr' => $insights['ctr'] ?? 0,
                    'cpm' => $insights['cpm'] ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $campaigns,
                'ad_account_id' => $connection->selected_ad_account_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching Facebook campaigns', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
