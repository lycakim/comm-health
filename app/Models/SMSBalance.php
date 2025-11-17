<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMSBalance extends Model
{
    use HasFactory;

    protected $table = 'sms_balances';

    protected $guarded = [];

    protected $casts = [
        'credit_balance' => 'decimal:2',
        'retrieved_at' => 'datetime',
    ];

    /**
     * Get the latest balance record
     */
    public static function getLatest()
    {
        return self::latest('retrieved_at')->first();
    }

    /**
     * Get balance history for a specific period
     */
    public static function getHistory($days = 30)
    {
        return self::where('retrieved_at', '>=', now()->subDays($days))
            ->orderBy('retrieved_at', 'desc')
            ->get();
    }
}