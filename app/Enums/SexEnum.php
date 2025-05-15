<?php

namespace App\Enums;

enum SexEnum: string
{
    case FEMALE = 'female';
    case MALE = 'male';

    public function getLabel(): string
    {
        return match ($this) {
            self::FEMALE => 'Female',
            self::MALE => 'Male',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FEMALE => 'success',
            self::MALE => 'info',
        };
    }
}