<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class SystemFeedback extends Model
{
    use HasFilamentComments;

    protected $guarded = [];
}