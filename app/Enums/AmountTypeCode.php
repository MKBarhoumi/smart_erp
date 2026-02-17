<?php

declare(strict_types=1);

namespace App\Enums;

enum AmountTypeCode: string
{
    case LINE_NET = 'I-171';
    case LINE_GROSS = 'I-172';
    case UNIT_PRICE = 'I-173';
    case DISCOUNT = 'I-174';
    case CHARGE = 'I-175';
    case TOTAL_HT = 'I-176';
    case TAXABLE_AMOUNT = 'I-177';
    case TAX_AMOUNT = 'I-178';
    case TOTAL_GROSS = 'I-179';
    case TOTAL_TTC = 'I-180';
    case TOTAL_TVA = 'I-181';
    case TOTAL_NET_BEFORE_DISC = 'I-182';
    case LINE_UNIT_PRICE = 'I-183';
    case ADVANCE_PAYMENT = 'I-184';
    case REMAINING_BALANCE = 'I-185';
    case TOTAL_ALLOWANCES = 'I-186';
    case TOTAL_CHARGES = 'I-187';
    case ROUNDING = 'I-188';
}
