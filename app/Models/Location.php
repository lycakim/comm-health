<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Location extends Model
{
    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    // Scopes for convenience
    public function scopeProvinces($query)
    {
        return $query->where('type', 'province');
    }

    public function scopeCities($query)
    {
        return $query->where('type', 'city');
    }

    public function scopeMunicipalities($query)
    {
        return $query->where('type', 'municipality');
    }
}