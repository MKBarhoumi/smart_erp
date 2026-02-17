<?php

namespace Database\Factories;

use App\Enums\DocumentTypeCode;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $totalHT = fake()->randomFloat(3, 100, 50000);
        $totalTVA = bcmul($totalHT, '0.19', 3);
        $timbre = '1.000';
        $totalTTC = bcadd(bcadd($totalHT, $totalTVA, 3), $timbre, 3);

        return [
            'customer_id' => Customer::factory(),
            'created_by' => User::factory(),
            'invoice_number' => 'FAC/' . date('Y') . '/' . fake()->unique()->numerify('####'),
            'document_identifier' => 'DOC/' . date('Y') . '/' . fake()->unique()->numerify('########'),
            'document_type_code' => DocumentTypeCode::FACTURE,
            'status' => InvoiceStatus::DRAFT,
            'invoice_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'total_ht' => number_format((float) $totalHT, 3, '.', ''),
            'total_tva' => number_format((float) $totalTVA, 3, '.', ''),
            'timbre_fiscal' => $timbre,
            'total_ttc' => number_format((float) $totalTTC, 3, '.', ''),
        ];
    }

    public function validated(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::VALIDATED]);
    }

    public function signed(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::SIGNED]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::ACCEPTED,
            'ref_ttn_val' => 'TTN-' . fake()->numerify('##########'),
        ]);
    }
}
