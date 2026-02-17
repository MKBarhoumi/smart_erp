<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Converts a TND amount to French words.
 *
 * Examples:
 *   2.540 → "DEUX DINARS ET CINQ CENT QUARANTE MILLIMES"
 *   0.300 → "TROIS CENT MILLIMES"
 *   1.000 → "UN DINAR"
 *  25.150 → "VINGT CINQ DINARS ET CENT CINQUANTE MILLIMES"
 */
class AmountInWordsService
{
    /** @var array<int, string> */
    private array $units = [
        0 => '', 1 => 'UN', 2 => 'DEUX', 3 => 'TROIS', 4 => 'QUATRE',
        5 => 'CINQ', 6 => 'SIX', 7 => 'SEPT', 8 => 'HUIT', 9 => 'NEUF',
        10 => 'DIX', 11 => 'ONZE', 12 => 'DOUZE', 13 => 'TREIZE',
        14 => 'QUATORZE', 15 => 'QUINZE', 16 => 'SEIZE',
    ];

    /**
     * Convert a numeric TND amount (string with 3 decimals) to French words.
     */
    public function convert(string $amount): string
    {
        $parts = explode('.', $amount);
        $dinars = (int) $parts[0];
        $millimes = (int) str_pad($parts[1] ?? '0', 3, '0', STR_PAD_RIGHT);

        $result = '';

        if ($dinars > 0) {
            $result = $this->numberToWords($dinars);
            $result .= $dinars === 1 ? ' DINAR' : ' DINARS';
        }

        if ($millimes > 0) {
            if ($dinars > 0) {
                $result .= ' ET ';
            }
            $result .= $this->numberToWords($millimes) . ' MILLIMES';
        }

        if ($dinars === 0 && $millimes === 0) {
            $result = 'ZERO DINAR';
        }

        return $result;
    }

    private function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'ZERO';
        }

        if ($number < 0) {
            return 'MOINS ' . $this->numberToWords(abs($number));
        }

        $words = '';

        if ($number >= 1000000) {
            $millions = intdiv($number, 1000000);
            $words .= ($millions === 1 ? 'UN' : $this->numberToWords($millions)) . ' MILLION';
            if ($millions > 1) {
                $words .= 'S';
            }
            $number %= 1000000;
            if ($number > 0) {
                $words .= ' ';
            }
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            if ($thousands === 1) {
                $words .= 'MILLE';
            } else {
                $words .= $this->numberToWords($thousands) . ' MILLE';
            }
            $number %= 1000;
            if ($number > 0) {
                $words .= ' ';
            }
        }

        if ($number >= 100) {
            $hundreds = intdiv($number, 100);
            if ($hundreds === 1) {
                $words .= 'CENT';
            } else {
                $words .= $this->units[$hundreds] . ' CENT';
            }
            $remainder = $number % 100;
            if ($remainder === 0 && $hundreds > 1) {
                $words .= 'S';
            }
            $number = $remainder;
            if ($number > 0) {
                $words .= ' ';
            }
        }

        if ($number > 0) {
            if ($number <= 16) {
                $words .= $this->units[$number];
            } elseif ($number < 20) {
                $words .= 'DIX ' . $this->units[$number - 10];
            } elseif ($number < 70) {
                $tens = intdiv($number, 10);
                $unit = $number % 10;
                $tensWords = match ($tens) {
                    2 => 'VINGT',
                    3 => 'TRENTE',
                    4 => 'QUARANTE',
                    5 => 'CINQUANTE',
                    6 => 'SOIXANTE',
                    default => '',
                };
                $words .= $tensWords;
                if ($unit === 1 && $tens >= 2) {
                    $words .= ' ET UN';
                } elseif ($unit > 0) {
                    $words .= ' ' . $this->units[$unit];
                }
            } elseif ($number < 80) {
                $unit = $number - 60;
                $words .= 'SOIXANTE';
                if ($unit === 11) {
                    $words .= ' ET ONZE';
                } elseif ($unit > 0) {
                    if ($unit === 1) {
                        $words .= ' ET UN';
                    } else {
                        $words .= ' ' . $this->numberToWords($unit);
                    }
                }
            } elseif ($number === 80) {
                $words .= 'QUATRE VINGTS';
            } elseif ($number < 100) {
                $unit = $number - 80;
                $words .= 'QUATRE VINGT ' . $this->numberToWords($unit);
            }
        }

        return $words;
    }
}
