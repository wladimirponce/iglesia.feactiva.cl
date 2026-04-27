<?php

declare(strict_types=1);

final class PhoneNormalizer
{
    public static function toE164(string $phone, string $defaultCountryCode = 'CL'): ?string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return null;
        }

        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $hasPlus = true;
        }

        if ($hasPlus) {
            return self::validE164Digits($digits) ? '+' . $digits : null;
        }

        $countryCode = strtoupper(trim($defaultCountryCode));

        if ($countryCode === 'CL') {
            if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
                $digits = '56' . $digits;
            } elseif (strlen($digits) === 11 && str_starts_with($digits, '56')) {
                // Already includes Chile country code without plus.
            } else {
                return null;
            }

            return self::validE164Digits($digits) ? '+' . $digits : null;
        }

        return self::validE164Digits($digits) ? '+' . $digits : null;
    }

    private static function validE164Digits(string $digits): bool
    {
        return preg_match('/^[1-9][0-9]{7,14}$/', $digits) === 1;
    }
}
