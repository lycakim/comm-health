<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'title',
        'category',
        'description',
        'period_start',
        'period_end',
        'generated_by',
        'location',
        'frequency',
        'report_type',
        'format',
        'file_path',
        'file_name',
        'file_size',
        'user_id',
        'barangay_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'file_size' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * Get the URL to download the report file
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Check if the file exists in storage
     */
    public function fileExists(): bool
    {
        if (!$this->file_path) {
            return false;
        }

        return Storage::disk('public')->exists($this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }
}