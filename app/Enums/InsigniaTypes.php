<?php

namespace App\Enums;

enum InsigniaTypes
{
    case WATER;
    case FIRE;
    case EARTH;
    case AIR;
    case DARKNESS;
    case LIGHT;

    public static function random()
    {
        $cases = self::cases();
        return $cases[array_rand($cases)];
    }

    public static function random_key(): int
    {
        return array_rand(self::cases());
    }

    public static function from_key($key)
    {
        return self::cases()[$key];
    }

    public static function count()
    {
        return count(self::cases());
    }

    public function label()
    {
        return match ($this) {
            self::WATER    => 'Water',
            self::FIRE     => 'Fire',
            self::EARTH    => 'Earth',
            self::AIR      => 'Air',
            self::DARKNESS => 'Darkness',
            self::LIGHT    => 'Light',
        };
    }
}
