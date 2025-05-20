<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Consultation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'datetime',
        'disability' => 'boolean',
        'philhealth' => 'boolean',
        '4ps' => 'boolean',
        'nhts_member' => 'boolean',
        'birth_plan' => 'boolean',
        'mother_status' => 'boolean',
        'hepa_b' => 'boolean',
        'nbs' => 'boolean',
        'bcg_date' => 'date',
        'prenatal_date' => 'date',
        'polio_date' => 'date',
        'ipv_date' => 'date',
        'pcv_date' => 'date',
        'measles_date' => 'date',
        'mmr_date' => 'date',
        'disabilities' => 'json',
        'weight' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class);
    }

    public function needsReferral(): bool
    {
        return $this->status === 'needs_referral';
    }

    /**
     * Check if the consultation has been referred.
     */
    public function isReferred(): bool
    {
        return $this->status === 'referred';
    }

    /**
     * Get formatted mother's full name.
     */
    public function getMotherFullNameAttribute(): string
    {
        return trim("{$this->mother_first_name} {$this->mother_middle_name} {$this->mother_last_name}");
    }
}