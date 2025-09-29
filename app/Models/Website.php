<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'url',
        'name',
        'is_active',
        'is_up',
        'last_checked_at',
        'last_downtime_at',
        'response_time_ms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_up' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_downtime_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(WebsiteCheck::class);
    }

    public function latestChecks(): HasMany
    {
        return $this->hasMany(WebsiteCheck::class)->latest('checked_at');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->url;
    }
}
