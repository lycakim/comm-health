<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Barangay extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'barangay_users')
            ->using(BarangayUser::class)
            ->withTimestamps();
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }
}