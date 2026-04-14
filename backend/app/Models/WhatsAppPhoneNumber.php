<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class WhatsAppPhoneNumber extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'whatsapp_phone_numbers';

    protected $fillable = [
        'organization_id',
        'phone_number',
        'display_name',
        'phone_number_id',
        'waba_id',
        'access_token',
        'verify_token',
        'webhook_url',
        'status',
        'quality_rating',
        'capabilities',
        'settings',
        'verified_at',
        'last_used_at',
        'is_default',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'settings' => 'array',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'verify_token',
    ];

    // Automatically encrypt/decrypt access_token
    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'whatsapp_phone_number_id');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'whatsapp_phone_number_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    public function setAsDefault()
    {
        // Remove default from other numbers in the same organization
        $this->organization->whatsappPhoneNumbers()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function getFormattedPhoneNumber(): string
    {
        // Format phone number for display (e.g., +1 234 567 8900)
        $number = preg_replace('/[^0-9]/', '', $this->phone_number);
        
        if (strlen($number) === 12 && substr($number, 0, 2) === '58') {
            // Venezuelan number
            return '+' . substr($number, 0, 2) . ' ' . substr($number, 2, 3) . ' ' . substr($number, 5);
        }
        
        return '+' . $number;
    }
}
