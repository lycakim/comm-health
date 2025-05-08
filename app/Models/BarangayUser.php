<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BarangayUser extends Pivot
{
    protected $table = 'barangay_users';

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}