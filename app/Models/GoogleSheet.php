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
}
