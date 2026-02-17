<?php

declare(strict_types=1);

namespace App\Enums;

enum IdentifierType: string
{
    case MATRICULE_FISCAL = 'I-01';
    case CIN = 'I-02';
    case CARTE_SEJOUR = 'I-03';
    case OTHER = 'I-04';

    public function label(): string
    {
        return match ($this) {
            self::MATRICULE_FISCAL => 'Matricule Fiscale',
            self::CIN => "Carte d'Identité Nationale",
            self::CARTE_SEJOUR => 'Carte de Séjour',
            self::OTHER => 'Autre',
        };
    }

    public function validationPattern(): string
    {
        return match ($this) {
            self::MATRICULE_FISCAL => '/^[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}$/',
            self::CIN => '/^[0-9]{8}$/',
            self::CARTE_SEJOUR => '/^[0-9]{9}$/',
            self::OTHER => '/^.{1,35}$/',
        };
    }

    public function expectedLength(): ?int
    {
        return match ($this) {
            self::MATRICULE_FISCAL => 13,
            self::CIN => 8,
            self::CARTE_SEJOUR => 9,
            self::OTHER => null,
        };
    }
}
