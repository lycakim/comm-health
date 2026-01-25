<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_child' => 'boolean',
        'is_maternal' => 'boolean',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'age_min_months' => 'integer',
        'age_max_months' => 'integer',
    ];

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    /** Whether this category's age range includes the given age. */
    public function containsAge(int $age): bool
    {
        if ($this->age_min !== null && $age < $this->age_min) {
            return false;
        }
        if ($this->age_max !== null && $age > $this->age_max) {
            return false;
        }
        return true;
    }

    /** Human-readable age range (e.g. "0–2", "60+", "3–20", "0y 6m–2y 3m"). */
    public function getAgeRangeDisplayAttribute(): ?string
    {
        if ($this->age_min === null && $this->age_max === null) {
            return null;
        }
        
        $formatAge = function($years, $months) {
            $parts = [];
            if ($years !== null && $years > 0) {
                $parts[] = $years . 'y';
            }
            if ($months !== null && $months > 0) {
                $parts[] = $months . 'm';
            }
            return !empty($parts) ? implode(' ', $parts) : '0';
        };
        
        $minStr = null;
        $maxStr = null;
        
        if ($this->age_min !== null || $this->age_min_months !== null) {
            $minStr = $formatAge($this->age_min ?? 0, $this->age_min_months ?? 0);
        }
        
        if ($this->age_max !== null || $this->age_max_months !== null) {
            $maxStr = $formatAge($this->age_max ?? 0, $this->age_max_months ?? 0);
        }
        
        if ($minStr === null && $maxStr === null) {
            return null;
        }
        
        if ($minStr === null) {
            return $maxStr . ' and below';
        }
        
        if ($maxStr === null) {
            return $minStr . '+';
        }
        
        return $minStr . '–' . $maxStr;
    }

    /**
     * Find a category whose age range contains the given age.
     * Only considers categories with at least age_min or age_max set (avoids Maternal, Chronic, etc.).
     * Order by age_min asc, then age_max asc (narrower ranges first when same min).
     */
    public static function findByAge(int $age): ?self
    {
        return static::query()
            ->where(function ($q) {
                $q->whereNotNull('age_min')->orWhereNotNull('age_max');
            })
            ->where(function ($q) use ($age) {
                $q->whereNull('age_min')->orWhere('age_min', '<=', $age);
            })
            ->where(function ($q) use ($age) {
                $q->whereNull('age_max')->orWhere('age_max', '>=', $age);
            })
            ->orderBy('age_min')
            ->orderBy('age_max')
            ->first();
    }

    /** First category with is_maternal (for maternal charts). */
    public static function findMaternal(): ?self
    {
        return static::where('is_maternal', true)->first();
    }
}
