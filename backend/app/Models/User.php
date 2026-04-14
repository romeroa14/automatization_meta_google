<?php

namespace App\Models;

use Filament\Panel;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements HasTenants
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationships para Multi-Tenancy (Workspace)
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /**
     * Filament HasTenants implementation
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->workspaces;
    }

    public function canAccessTenant(Model $tenant, Panel $panel): bool
    {
        return $this->workspaces()->whereKey($tenant)->exists();
    }
}
