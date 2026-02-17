<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxTypeCode: string
{
    case DROIT_TIMBRE = 'I-1601';
    case TVA = 'I-1602';
    case OTHER = 'I-1603';

    public function label(): string
    {
        return match ($this) {
            self::DROIT_TIMBRE => 'droit de timbre',
            self::TVA => 'TVA',
            self::OTHER => 'Autre taxe',
        };
    }
}
