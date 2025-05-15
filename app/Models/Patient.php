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

    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->birth_date)->age,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}