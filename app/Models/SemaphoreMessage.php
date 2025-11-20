<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SemaphoreMessage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'retrieved_at' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get latest messages
     */
    public static function getLatest($limit = 50)
    {
        return self::orderBy('sent_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
