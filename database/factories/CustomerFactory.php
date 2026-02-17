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
            'identifier_type' => IdentifierType::MATRICULE_FISCAL,
            'identifier_value' => $this->generateMF(),
            'matricule_fiscal' => null,
            'category_type' => fake()->randomElement(['A', 'B', 'D', 'N', 'P']),
            'person_type' => fake()->randomElement(['C', 'M', 'N', 'P']),
            'tax_office' => fake()->numerify('###'),
            'registre_commerce' => fake()->optional()->bothify('RC-######'),
            'legal_form' => fake()->randomElement(['SA', 'SARL', 'EI', 'SP']),

            'address_description' => fake()->sentence(),
            'street' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Gabès', 'Ariana', 'Monastir']),
            'postal_code' => fake()->numerify('####'),
            'country_code' => 'TN',

            'phone' => fake()->numerify('+216 ## ### ###'),
            'fax' => null,
            'email' => fake()->companyEmail(),
            'website' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
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
