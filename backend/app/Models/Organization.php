<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website',
        'email',
        'phone',
        'settings',
        'n8n_webhook_url',
        'is_active',
        'trial_ends_at',
        'plan',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($organization) {
            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name);
            }
        });
    }

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function whatsappPhoneNumbers()
    {
        return $this->hasMany(WhatsAppPhoneNumber::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function isOwner(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function isAdmin(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->exists();
    }

    public function getDefaultPhoneNumber()
    {
        return $this->whatsappPhoneNumbers()
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();
    }
}
