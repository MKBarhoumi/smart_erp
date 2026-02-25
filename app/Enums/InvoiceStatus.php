<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case SIGNED = 'signed';
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::VALIDATED => 'Validated',
            self::SIGNED => 'Signed',
            self::SUBMITTED => 'Submitted',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => in_array($target, [self::VALIDATED]),
            self::VALIDATED => in_array($target, [self::SIGNED, self::DRAFT]),
            self::SIGNED => in_array($target, [self::SUBMITTED]),
            self::SUBMITTED => in_array($target, [self::ACCEPTED, self::REJECTED]),
            self::ACCEPTED => false,
            self::REJECTED => in_array($target, [self::DRAFT]),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::VALIDATED => 'blue',
            self::SIGNED => 'indigo',
            self::SUBMITTED => 'yellow',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
        };
    }
}
