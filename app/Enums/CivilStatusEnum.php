<?php

namespace App\Enums;

enum CivilStatusEnum: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case WIDOWED = 'widowed';
    case SEPARATED = 'separated';
    case LIVE_IN = 'live-in';

    public function getLabel(): string
    {
        return match ($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::WIDOWED => 'Widowed',
            self::SEPARATED => 'Separated',
            self::LIVE_IN => 'Live-in',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SINGLE => 'success',
            self::MARRIED => 'info',
            self::WIDOWED => 'warning',
            self::SEPARATED => 'danger',
            self::LIVE_IN => 'gray',
        };
    }
}