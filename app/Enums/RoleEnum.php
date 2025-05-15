<?php

namespace App\Enums;

enum RoleEnum: string
{
    case BHW = 'bhw';
    case MIDWIFE = 'midwife';
    case ADMIN = 'admin';
    case MHO = 'mho';
    case RESIDENT = 'resident';

    public function getLabel(): string
    {
        return match ($this) {
            self::BHW => 'Health Worker',
            self::MIDWIFE => 'Midwife',
            self::ADMIN => 'Administrator',
            self::MHO => 'Municipal Health Officer',
            self::RESIDENT => 'Resident',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BHW, self::MIDWIFE => 'success',
            self::ADMIN => 'danger',
            self::MHO => 'warning',
            self::RESIDENT => 'info',
        };
    }
}