<?php

namespace App\Services;

use App\Models\Laboratory;

class ConsultationFormOptionServices
{
    public static function getDisabilitiesOptions()
    {
        return [
            'Hearing Impairment' => 'Hearing Impairment',
            'Visual Impairment' => 'Visual Impairment',
            'Mental Impairment' => 'Mental Impairment',
            'Physical Impairment' => 'Physical Impairment',
            'Other' => 'Other',
        ];
    }

    public static function getTypeOptions()
    {
        return [
            'Student' => 'Student',
            'Out of School Youth' => 'Out of School Youth',
            'Working' => 'Working',
        ];
    }

    public static function getLaboratoryOptions()
    {
        return Laboratory::query()
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }
}