<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'spreadsheet_id',
        'worksheet_name',
        'cell_mapping',
        'is_active',
        'settings',
        'individual_ads',
        'start_row'
    ];

    protected static function boot()
    {
        parent::boot();

        // Antes de guardar, convertir el formato del repeater al formato correcto
        static::saving(function ($googleSheet) {
            if (isset($googleSheet->attributes['cell_mapping']) && is_array($googleSheet->attributes['cell_mapping'])) {
                $mapping = $googleSheet->attributes['cell_mapping'];
                
                // Si es un array de objetos (formato del repeater), convertirlo
                if (!empty($mapping) && isset($mapping[0]) && is_array($mapping[0])) {
                    $formattedMapping = [];
                    foreach ($mapping as $item) {
                        if (isset($item['metric']) && isset($item['column'])) {
                            $formattedMapping[$item['metric']] = $item['column'];
                        }
                    }
                    $googleSheet->attributes['cell_mapping'] = $formattedMapping;
                }
            }
        });
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'spreadsheet_id' => 'required|string|max:255',
        'worksheet_name' => 'required|string|max:255',
        'cell_mapping' => 'required|array',
        'is_active' => 'required|boolean',
        'settings' => 'nullable|array',
        'individual_ads' => 'boolean',
        'start_row' => 'nullable|integer|min:2',
    ];

    protected $casts = [
        'cell_mapping' => 'array',
        'is_active' => 'boolean',
        'settings' => 'array',
        'individual_ads' => 'boolean',
    ];

    public function automationTasks(): HasMany
    {
        return $this->hasMany(AutomationTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getSpreadsheetUrlAttribute(): string
    {
        return "https://docs.google.com/spreadsheets/d/{$this->spreadsheet_id}";
    }

    /**
     * Convierte el nuevo formato de mapeo (array de objetos) al formato antiguo (array asociativo)
     */
    public function getFormattedCellMappingAttribute(): array
    {
        $mapping = $this->cell_mapping ?? [];
        
        // Si ya es un array asociativo (formato antiguo), devolverlo tal como está
        if (!empty($mapping) && is_array($mapping) && !isset($mapping[0])) {
            return $mapping;
        }
        
        // Si es un array de objetos (nuevo formato), convertirlo
        $formattedMapping = [];
        foreach ($mapping as $item) {
            if (isset($item['metric']) && isset($item['column'])) {
                $formattedMapping[$item['metric']] = $item['column'];
            }
        }
        
        return $formattedMapping;
    }

    /**
     * Convierte el formato antiguo al nuevo formato para el formulario
     */
    public function getFormMappingAttribute(): array
    {
        $mapping = $this->cell_mapping ?? [];
        
        // Si es un array de objetos (nuevo formato), devolverlo tal como está
        if (!empty($mapping) && is_array($mapping) && isset($mapping[0])) {
            return $mapping;
        }
        
        // Si es un array asociativo (formato antiguo), convertirlo
        $formMapping = [];
        foreach ($mapping as $metric => $column) {
            $formMapping[] = [
                'metric' => $metric,
                'column' => $column,
            ];
        }
        
        return $formMapping;
    }
}
