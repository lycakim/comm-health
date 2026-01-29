<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Purok;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory, Notifiable, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'age' => 'integer',
        'pregnant' => 'boolean',
        'ip' => 'boolean',
        'with_fence' => 'boolean',
        'trained_for_first_aid' => 'boolean',
        'weeks_pregnant' => 'integer',
        'months_pregnant' => 'integer',
        'no_of_house' => 'integer',
        'sugar_level' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'current_family_planning_method' => 'array',
        'health_statuses' => 'array',
        'medication_maintenance' => 'array',
        'water_supply_sources' => 'array',
        'toilet_types' => 'array',
        'drainage_disposals' => 'array',
        'livestock' => 'array',
        'bmi' => 'decimal:2',
    ];

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function purok(): BelongsTo
    {
        return $this->belongsTo(Purok::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->first_name} {$this->last_name}",
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                $firstInitial = $this->first_name ? substr($this->first_name, 0, 1) : '';
                $lastInitial = $this->last_name ? substr($this->last_name, 0, 1) : '';
                
                return strtoupper($firstInitial . $lastInitial);
            }
        );
    }

    protected function calculatedAge(): Attribute
    {
        return Attribute::make(
            get: function() {
                if (!$this->birth_date) {
                    return null;
                }
                
                $birthDate = Carbon::parse($this->birth_date);
                $now = Carbon::now();
                
                $years = $birthDate->diffInYears($now);
                $months = $birthDate->copy()->addYears($years)->diffInMonths($now);
                
                $parts = [];
                if ($years > 0) {
                    $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years');
                }
                if ($months > 0) {
                    $parts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
                }
                
                return !empty($parts) ? implode(' ', $parts) : '0 months';
            },
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            if ($patient->birth_date) {
                $patient->age = (int) Carbon::parse($patient->birth_date)->age;
            }
        });

        static::updating(function ($patient) {
            if ($patient->birth_date) {
                $patient->age = (int) Carbon::parse($patient->birth_date)->age;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(fn(string $eventName) => "Patient {$this->name} has been {$eventName}");
    }
}