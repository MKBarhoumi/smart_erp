<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\IdentifierType;

class MatriculeFiscaleValidator
{
    /**
     * Validate an identifier value based on its type.
     */
    public function validate(string $identifierType, string $value): bool
    {
        $type = IdentifierType::tryFrom($identifierType);
        if ($type === null) {
            return false;
        }

        return (bool) preg_match($type->validationPattern(), $value);
    }

    /**
     * Validate specifically a Matricule Fiscale (I-01).
     */
    public function validateMatriculeFiscale(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}$/',
            $value
        );
    }

    /**
     * Get a user-friendly error message for an invalid identifier.
     */
    public function getErrorMessage(string $identifierType): string
    {
        $type = IdentifierType::tryFrom($identifierType);

        return match ($type) {
            IdentifierType::MATRICULE_FISCAL => 'Invalid Matricule Fiscale format. Expected: 7 digits + letter + A/B/D/N/P + C/M/N/P + 000',
            IdentifierType::CIN => 'Invalid CIN format. Expected: exactly 8 digits.',
            IdentifierType::CARTE_SEJOUR => 'Invalid Carte de Séjour format. Expected: exactly 9 digits.',
            IdentifierType::OTHER => 'Identifier must be 1-35 characters.',
            null => 'Invalid identifier type.',
        };
    }
}
