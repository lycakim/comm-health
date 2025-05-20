<?php

namespace App\Services;

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
}