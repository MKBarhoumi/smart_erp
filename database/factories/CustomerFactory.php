<?php

namespace Database\Factories;

use App\Enums\IdentifierType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'identifier_type' => IdentifierType::MATRICULE_FISCALE,
            'identifier_value' => $this->generateMF(),
            'matricule_fiscale' => null,
            'tax_category_code' => 'A',
            'secondary_establishment' => '000',
            'address_street' => fake()->streetAddress(),
            'address_city' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Gabès', 'Ariana', 'Monastir']),
            'address_postal_code' => fake()->numerify('####'),
            'address_country_code' => 'TN',
            'phone' => fake()->numerify('+216 ## ### ###'),
            'email' => fake()->companyEmail(),
        ];
    }

    private function generateMF(): string
    {
        $digits = fake()->numerify('#######');
        $letter = fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z']);
        $category = fake()->randomElement(['A', 'B', 'D', 'N', 'P']);
        $person = fake()->randomElement(['C', 'M', 'N', 'P']);
        return "{$digits}{$letter}/{$category}/{$person}/000";
    }
}
