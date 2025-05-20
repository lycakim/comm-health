<?php

namespace App\Enums;

enum UrgencyEnum: string
{
    case EMERGENCY = 'Emergency';
    case AMBULATORY = 'Ambulatory';
    case MEDICO_LEGAL = 'Medico-Legal';
    case ROUTINE = 'Routine';
    case URGENT = 'Urgent';

    public function getLabel(): string
    {
        return match ($this) {
            self::EMERGENCY => 'Emergency',
            self::AMBULATORY => 'Ambulatory',
            self::MEDICO_LEGAL => 'Medico-Legal',
            self::ROUTINE => 'Routine',
            self::URGENT => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::EMERGENCY => 'danger',
            self::AMBULATORY => 'warning',
            self::MEDICO_LEGAL => 'warning',
            self::ROUTINE => 'info',
            self::URGENT => 'warning',
        };
    }
}