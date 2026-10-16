<?php

namespace App\Support;

use Illuminate\Support\Str;

final class BrazilLocation
{
    public const STATES = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
        'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];

    public static function city(string $state, string $city): ?string
    {
        $normalized = self::normalize($city);

        foreach (self::cities()[$state] ?? [] as $knownCity) {
            if (self::normalize($knownCity) === $normalized) {
                return $knownCity;
            }
        }

        return null;
    }

    /** @return array{0: float, 1: float}|null */
    public static function coordinates(string $state, string $city): ?array
    {
        $city = self::city($state, $city);
        $coordinates = $city ? self::coordinatesByCity()[$state][$city] ?? null : null;

        return $coordinates ? [(float) $coordinates[0], (float) $coordinates[1]] : null;
    }

    private static function cities(): array
    {
        static $cities;

        return $cities ??= json_decode(
            file_get_contents(public_path('data/municipalities.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private static function coordinatesByCity(): array
    {
        static $coordinates;

        return $coordinates ??= json_decode(
            file_get_contents(public_path('data/municipality-coordinates.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    private static function normalize(string $value): string
    {
        return Str::upper(Str::ascii(trim($value)));
    }
}
