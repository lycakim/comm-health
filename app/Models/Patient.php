<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : 0;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->first_name ? substr($this->first_name, 0, 1) : '';
        $lastInitial = $this->last_name ? substr($this->last_name, 0, 1) : '';
        
        return strtoupper($firstInitial . $lastInitial);
    }
}