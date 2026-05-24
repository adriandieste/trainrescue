<?php

namespace App\Support;

class ChronoTime
{
    public const PATTERN = '/^(\d{1,2}):(\d{2})\.(\d{2})$/';

    public static function isValid(string $value): bool
    {
        if (!preg_match(self::PATTERN, trim($value), $matches)) {
            return false;
        }

        $seconds = (int) $matches[2];

        return $seconds >= 0 && $seconds <= 59;
    }

    public static function toCentiseconds(string $value): int
    {
        preg_match(self::PATTERN, trim($value), $matches);

        $minutes = (int) $matches[1];
        $seconds = (int) $matches[2];
        $centiseconds = (int) $matches[3];

        return ($minutes * 60 * 100) + ($seconds * 100) + $centiseconds;
    }

    public static function fromCentiseconds(int $value): string
    {
        $minutes = intdiv($value, 6000);
        $remaining = $value % 6000;
        $seconds = intdiv($remaining, 100);
        $centiseconds = $remaining % 100;

        return sprintf('%02d:%02d.%02d', $minutes, $seconds, $centiseconds);
    }
}

