<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'requirements' => 'array',
        'program_date' => 'date',
    ];
}