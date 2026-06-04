<?php

declare(strict_types=1);

namespace app\models;

class PhoneNormalizer
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '8') && strlen($digits) === 11) {
            $digits = '7' . substr($digits, 1);
        }
        if (!str_starts_with($digits, '7') && strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }
}
