<?php

namespace App\Support;

class PhoneNumber
{
    public const COUNTRIES = [
        'BO' => ['name' => 'Bolivia', 'code' => '591', 'national_length' => 8, 'currency' => 'BOB', 'symbol' => 'Bs'],
        'CL' => ['name' => 'Chile', 'code' => '56', 'national_length' => 9, 'currency' => 'CLP', 'symbol' => 'CLP'],
        'AR' => ['name' => 'Argentina', 'code' => '54', 'national_length' => 10, 'currency' => 'ARS', 'symbol' => 'ARS'],
        'PE' => ['name' => 'Peru', 'code' => '51', 'national_length' => 9, 'currency' => 'PEN', 'symbol' => 'S/'],
    ];

    public static function countries(): array
    {
        return self::COUNTRIES;
    }

    public static function normalize(?string $phone, string $country): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        $rule = self::COUNTRIES[$country] ?? self::COUNTRIES['BO'];
        $code = $rule['code'];
        $nationalLength = $rule['national_length'];

        if (strlen($digits) === $nationalLength) {
            return $code.$digits;
        }

        if (str_starts_with($digits, $code) && strlen($digits) === strlen($code) + $nationalLength) {
            return $digits;
        }

        return null;
    }

    public static function hint(string $country): string
    {
        $rule = self::COUNTRIES[$country] ?? self::COUNTRIES['BO'];

        return $rule['name'].' usa '.$rule['code'].' + '.$rule['national_length'].' digitos.';
    }

    public static function currencyFor(string $country): array
    {
        $rule = self::COUNTRIES[$country] ?? self::COUNTRIES['BO'];

        return [
            'currency_code' => $rule['currency'],
            'currency_symbol' => $rule['symbol'],
        ];
    }
}
