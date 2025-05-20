<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Referral extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'surgical_operation' => 'boolean',
        'drug_allergy' => 'boolean',
        'date_completed' => 'datetime',
    ];

    protected $appends = ['resolved_patient'];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function getResolvedPatientAttribute(): ?Patient
    {
        return $this->patient ?? $this->consultation?->patient;
    }
}