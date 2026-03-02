<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Patient;
use App\Models\Category;

class Program extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'program_start_date' => 'datetime',
        'program_end_date' => 'datetime',
        'report_field' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function coordinatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator', 'id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the query for residents who are recipients of this program (for SMS/list).
     * When category is "Profiled/Registered Members", returns all residents in the barangay.
     */
    public function getSmsRecipientsQuery(): Builder
    {
        $query = Patient::query()->where('barangay_id', $this->barangay_id);

        $category = $this->relationLoaded('category') ? $this->category : Category::find($this->category_id);
        if ($category?->isProfiledRegisteredMembers()) {
            return $query;
        }

        return $query->where('category_id', $this->category_id);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['program_name', 'category_id', 'barangay_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = Auth::user()?->name ?? 'System';
                $programName = $this->name ?? 'unknown program';
                return "{$userName} {$eventName} a program {$programName}";
            });
    }
}