<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'intention',
        'stage',
        'confidence',
        'telegram_conversation_id', // Keeping this just in case, but focused on 'conversations' relation
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
