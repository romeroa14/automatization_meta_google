<?php

namespace App\Services;

use App\Models\FacebookAccount;
use App\Models\AdvertisingPlan;
use Illuminate\Support\Facades\Log;
use Exception;

class CampaignParserService
{
    protected array $validObjectives = [
        'TRAFFIC', 'CONVERSIONES', 'CONVERSIONS', 'ALCANCE', 'REACH', 
        'BRAND_AWARENESS', 'ENGAGEMENT', 'LEAD_GENERATION', 'SALES',
        'TRÁFICO', 'CONVERSIONES', 'ALCANCE', 'CONCIENCIA_MARCA', 
        'COMPROMISO', 'GENERACIÓN_LEADS', 'VENTAS'
    ];

    protected array $objectiveMapping = [
        'TRÁFICO' => 'TRAFFIC',
        'CONVERSIONES' => 'CONVERSIONS',
        'ALCANCE' => 'REACH',
        'CONCIENCIA_MARCA' => 'BRAND_AWARENESS',
        'COMPROMISO' => 'ENGAGEMENT',
        'GENERACIÓN_LEADS' => 'LEAD_GENERATION',
        'VENTAS' => 'SALES',
    ];

    public function parseCampaignData(string $message): array
    {
        try {
            Log::info('🔍 Iniciando parseo de datos de campaña', [
                'message' => $message,
                'timestamp' => now()
            ]);

            $data = [
                'name' => null,
                'objective' => null,
                'daily_budget' => null,
                'duration_days' => null,
                'facebook_account' => null,
                'start_date' => null,
                'end_date' => null,
                'raw_data' => $message,
                'parsed_at' => now(),
                'errors' => [],
                'warnings' => []
            ];

            // Extraer nombre de campaña
            $data['name'] = $this->extractCampaignName($message);
            
            // Extraer objetivo
            $data['objective'] = $this->extractObjective($message);
            
            // Extraer presupuesto diario
            $data['daily_budget'] = $this->extractDailyBudget($message);
            
            // Extraer duración
            $data['duration_days'] = $this->extractDuration($message);
            
            // Extraer cuenta de Facebook
            $data['facebook_account'] = $this->extractFacebookAccount($message);
            
            // Extraer fechas
            $dates = $this->extractDates($message);
            $data['start_date'] = $dates['start_date'];
            $data['end_date'] = $dates['end_date'];

            // Validar datos
            $this->validateCampaignData($data);

            Log::info('✅ Datos de campaña parseados exitosamente', [
                'parsed_data' => $data,
                'timestamp' => now()
            ]);

            return $data;

        } catch (Exception $e) {
            Log::error('❌ Error parseando datos de campaña', [
                'error' => $e->getMessage(),
                'message' => $message,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function extractCampaignName(string $message): ?string
    {
        // Buscar patrones como "Nombre: ..." o "Name: ..."
        $patterns = [
            '/Nombre:\s*([^|]+)/i',
            '/Name:\s*([^|]+)/i',
            '/Campaign:\s*([^|]+)/i',
            '/Campaña:\s*([^|]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $name = trim($matches[1]);
                if (!empty($name)) {
                    return $name;
                }
            }
        }

        return null;
    }

    private function extractObjective(string $message): ?string
    {
        $patterns = [
            '/Objetivo:\s*([^\s]+)/i',
            '/Objective:\s*([^\s]+)/i',
            '/Goal:\s*([^\s]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $objective = strtoupper(trim($matches[1]));
                
                // Mapear objetivo en español a inglés
                if (isset($this->objectiveMapping[$objective])) {
                    $objective = $this->objectiveMapping[$objective];
                }
                
                if (in_array($objective, $this->validObjectives)) {
                    return $objective;
                }
            }
        }

        return null;
    }

    private function extractDailyBudget(string $message): ?float
    {
        $patterns = [
            '/Presupuesto:\s*([0-9.]+)/i',
            '/Budget:\s*([0-9.]+)/i',
            '/Daily Budget:\s*([0-9.]+)/i',
            '/Presupuesto diario:\s*([0-9.]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $budget = floatval($matches[1]);
                if ($budget > 0) {
                    return $budget;
                }
            }
        }

        return null;
    }

    private function extractDuration(string $message): ?int
    {
        $patterns = [
            '/Duración:\s*([0-9]+)/i',
            '/Duration:\s*([0-9]+)/i',
            '/Days:\s*([0-9]+)/i',
            '/Días:\s*([0-9]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $duration = intval($matches[1]);
                if ($duration > 0) {
                    return $duration;
                }
            }
        }

        return null;
    }

    private function extractFacebookAccount(string $message): ?string
    {
        $patterns = [
            '/Cuenta:\s*([^|]+)/i',
            '/Account:\s*([^|]+)/i',
            '/Facebook:\s*([^|]+)/i',
            '/FB:\s*([^|]+)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $account = trim($matches[1]);
                if (!empty($account)) {
                    return $account;
                }
            }
        }

        return null;
    }

    private function extractDates(string $message): array
    {
        $dates = ['start_date' => null, 'end_date' => null];

        // Buscar patrones de fechas como "15/09 - 19/09" o "15/09/2025 - 19/09/2025"
        $datePatterns = [
            '/(\d{1,2}\/\d{1,2})\s*-\s*(\d{1,2}\/\d{1,2})/',
            '/(\d{1,2}\/\d{1,2}\/\d{4})\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})/',
            '/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/'
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                try {
                    $startDate = $this->parseDate($matches[1]);
                    $endDate = $this->parseDate($matches[2]);
                    
                    if ($startDate && $endDate) {
                        $dates['start_date'] = $startDate;
                        $dates['end_date'] = $endDate;
                        break;
                    }
                } catch (Exception $e) {
                    Log::warning('Error parseando fechas', ['error' => $e->getMessage()]);
                }
            }
        }

        return $dates;
    }

    private function parseDate(string $dateString): ?string
    {
        try {
            // Si no tiene año, agregar el año actual
            if (!preg_match('/\d{4}/', $dateString)) {
                $dateString .= '/' . date('Y');
            }

            $date = \DateTime::createFromFormat('d/m/Y', $dateString);
            if (!$date) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateString);
            }
            
            if ($date) {
                return $date->format('Y-m-d');
            }
        } catch (Exception $e) {
            Log::warning('Error parseando fecha individual', ['date' => $dateString, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function validateCampaignData(array &$data): void
    {
        $errors = [];
        $warnings = [];

        // Validar nombre
        if (empty($data['name'])) {
            $errors[] = 'Nombre de campaña es requerido';
        }

        // Validar objetivo
        if (empty($data['objective'])) {
            $errors[] = 'Objetivo de campaña es requerido';
        } elseif (!in_array($data['objective'], $this->validObjectives)) {
            $errors[] = 'Objetivo no válido: ' . $data['objective'];
        }

        // Validar presupuesto
        if (empty($data['daily_budget'])) {
            $errors[] = 'Presupuesto diario es requerido';
        } elseif ($data['daily_budget'] < 1) {
            $errors[] = 'Presupuesto diario debe ser mayor a $1';
        } elseif ($data['daily_budget'] > 1000) {
            $warnings[] = 'Presupuesto diario muy alto: $' . $data['daily_budget'];
        }

        // Validar duración
        if (empty($data['duration_days'])) {
            $errors[] = 'Duración es requerida';
        } elseif ($data['duration_days'] < 1) {
            $errors[] = 'Duración debe ser mayor a 0 días';
        } elseif ($data['duration_days'] > 365) {
            $warnings[] = 'Duración muy larga: ' . $data['duration_days'] . ' días';
        }

        // Validar cuenta de Facebook
        if (empty($data['facebook_account'])) {
            $errors[] = 'Cuenta de Facebook es requerida';
        } else {
            // Verificar si la cuenta existe en la base de datos
            $account = FacebookAccount::where('name', 'like', '%' . $data['facebook_account'] . '%')
                ->orWhere('account_id', 'like', '%' . $data['facebook_account'] . '%')
                ->first();
            
            if (!$account) {
                $warnings[] = 'Cuenta de Facebook no encontrada: ' . $data['facebook_account'];
            } else {
                $data['facebook_account_id'] = $account->id;
                $data['facebook_account_data'] = $account;
            }
        }

        // Validar fechas
        if ($data['start_date'] && $data['end_date']) {
            $start = new \DateTime($data['start_date']);
            $end = new \DateTime($data['end_date']);
            
            if ($start >= $end) {
                $errors[] = 'Fecha de inicio debe ser anterior a fecha de fin';
            }
            
            $diff = $start->diff($end)->days;
            if ($data['duration_days'] && $diff != $data['duration_days']) {
                $warnings[] = 'Duración calculada (' . $diff . ' días) no coincide con duración especificada (' . $data['duration_days'] . ' días)';
            }
        }

        $data['errors'] = $errors;
        $data['warnings'] = $warnings;
        $data['is_valid'] = empty($errors);
    }

    public function getAvailableFacebookAccounts(): array
    {
        return FacebookAccount::where('is_active', true)
            ->select('id', 'name', 'account_id', 'access_token')
            ->get()
            ->toArray();
    }

    public function getAvailableObjectives(): array
    {
        return [
            'TRAFFIC' => 'Tráfico al sitio web',
            'CONVERSIONS' => 'Conversiones',
            'REACH' => 'Alcance',
            'BRAND_AWARENESS' => 'Conciencia de marca',
            'ENGAGEMENT' => 'Compromiso',
            'LEAD_GENERATION' => 'Generación de leads',
            'SALES' => 'Ventas'
        ];
    }

    public function formatCampaignSummary(array $data): string
    {
        $summary = "📊 *Resumen de Campaña*\n\n";
        
        $summary .= "🏷️ *Nombre:* " . ($data['name'] ?? 'No especificado') . "\n";
        $summary .= "🎯 *Objetivo:* " . ($data['objective'] ?? 'No especificado') . "\n";
        $summary .= "💰 *Presupuesto diario:* $" . ($data['daily_budget'] ?? 'No especificado') . "\n";
        $summary .= "📅 *Duración:* " . ($data['duration_days'] ?? 'No especificado') . " días\n";
        $summary .= "📱 *Cuenta FB:* " . ($data['facebook_account'] ?? 'No especificada') . "\n";
        
        if ($data['start_date'] && $data['end_date']) {
            $summary .= "📆 *Fechas:* " . $data['start_date'] . " - " . $data['end_date'] . "\n";
        }
        
        $summary .= "\n";
        
        if (!empty($data['errors'])) {
            $summary .= "❌ *Errores:*\n";
            foreach ($data['errors'] as $error) {
                $summary .= "• " . $error . "\n";
            }
        }
        
        if (!empty($data['warnings'])) {
            $summary .= "⚠️ *Advertencias:*\n";
            foreach ($data['warnings'] as $warning) {
                $summary .= "• " . $warning . "\n";
            }
        }
        
        return $summary;
    }
}
