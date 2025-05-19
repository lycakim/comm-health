<?php

namespace App\Enums;

enum EducationalAttainmentEnum: string
{
    case NONE = 'none';
    case ELEMENTARY_LEVEL = 'elementary_level';
    case ELEMENTARY_GRADE = 'elementary_graduate';
    case HIGH_SCHOOL_LEVEL = 'highschool_level';
    case HIGH_SCHOOL_GRADE = 'highschool_graduate';
    case COLLEGE_LEVEL = 'college_level';
    case COLLEGE_GRADE = 'college_graduate';
    case POST_GRADUATE = 'post_graduate';

    public function getLabel(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::ELEMENTARY_LEVEL => 'Elementary Level',
            self::ELEMENTARY_GRADE => 'Elementary Graduate',
            self::HIGH_SCHOOL_LEVEL => 'Highschool Level',
            self::HIGH_SCHOOL_GRADE => 'Highschool Graduate',
            self::COLLEGE_LEVEL => 'College Level',
            self::COLLEGE_GRADE => 'College Graduate',
            self::POST_GRADUATE => 'Post Graduate',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NONE => 'gray',
            self::ELEMENTARY_LEVEL => 'info',
            self::ELEMENTARY_GRADE => 'info',
            self::HIGH_SCHOOL_LEVEL => 'info',
            self::HIGH_SCHOOL_GRADE => 'info',
            self::COLLEGE_LEVEL => 'info',
            self::COLLEGE_GRADE => 'info',
            self::POST_GRADUATE => 'info',
        };
    }
}