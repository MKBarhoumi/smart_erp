<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanySetting;

class OldInvoiceNumberingService
{
    /**
     * Generate the next oldinvoice number based on company settings.
     */
    public function generateNextNumber(): string
    {
        $settings = CompanySetting::first();
        if ($settings === null) {
            return 'INV-0001';
        }

        $format = $settings->oldinvoice_number_format;
        $prefix = $settings->oldinvoice_prefix;
        $counter = $settings->next_oldinvoice_counter;

        $number = str_replace(
            ['{prefix}', '{YYYY}', '{YY}', '{MM}', '{counter}'],
            [
                $prefix,
                date('Y'),
                date('y'),
                date('m'),
                str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            ],
            $format
        );

        $settings->increment('next_oldinvoice_counter');

        return $number;
    }
}
