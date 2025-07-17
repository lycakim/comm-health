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
        'member_of_4ps' => 'boolean',
        'nhts_member' => 'boolean',
        'birth_plan' => 'boolean',
        'prenatal_dates' => 'boolean',
        'mother_status' => 'boolean',
        'hepa_b' => 'boolean',
        'nbs' => 'boolean',
        'husband_philhealth' => 'boolean',
        'husband_member_of_4ps' => 'boolean',
        'husband_nhts_member' => 'boolean',
        'mother_and_child_book' => 'boolean',
        'bcg_date' => 'date',
        'prenatal_date' => 'date',
        'polio_date' => 'date',
        'ipv_date' => 'date',
        'pcv_date' => 'date',
        'measles_date' => 'date',
        'mmr_date' => 'date',
        'mother_lmp_date' => 'date',
        'mother_edc_date' => 'date',
        'imm_received_dates' => 'date',
        'tt1_date' => 'date',
        'tt2_date' => 'date',
        'tt3_date' => 'date',
        'tt4_date' => 'date',
        'tt5_date' => 'date',
        'tt_imm' => 'date',
        'disabilities' => 'json',
        'child_weight' => 'decimal:2',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
        'fundal_height' => 'decimal:2',
        'fetal_hydronephrosis' => 'decimal:2',
        'age_of_gestation' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purok(): BelongsTo
    {
        return $this->belongsTo(Purok::class);
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class, 'consultation_id');
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

    /**
     * Get formatted husband's full name.
     */
    public function getHusbandFullNameAttribute(): string
    {
        return trim("{$this->husband_first_name} {$this->husband_middle_name} {$this->husband_last_name}");
    }

    public function person_type(): BelongsTo
    {
        return $this->belongsTo(PersonType::class);
    }
}