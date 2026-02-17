<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentTypeCode: string
{
    case FACTURE = 'I-11';
    case RECTIFICATIVE = 'I-12';
    case AVOIR = 'I-13';
    case NOTE_DEBIT = 'I-14';
    case PROFORMA = 'I-15';
    case ACOMPTE = 'I-16';

    public function label(): string
    {
        return match ($this) {
            self::FACTURE => 'Facture',
            self::RECTIFICATIVE => 'Facture rectificative',
            self::AVOIR => 'Avoir',
            self::NOTE_DEBIT => 'Note de débit',
            self::PROFORMA => 'Facture proforma',
            self::ACOMPTE => "Facture d'acompte",
        };
    }
}
