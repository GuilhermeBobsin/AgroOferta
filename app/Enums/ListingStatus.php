<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Paused => 'Pausado',
            self::Sold => 'Vendido',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Paused => 'bg-yellow-100 text-yellow-800',
            self::Sold => 'bg-gray-200 text-gray-700',
        };
    }
}
