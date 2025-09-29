<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function activeWebsites(): HasMany
    {
        return $this->hasMany(Website::class)->where('is_active', true);
    }
}
