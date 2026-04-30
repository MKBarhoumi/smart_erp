<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanySettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'accountant'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_fetch_and_update_company_settings_through_the_api(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/company-settings')
            ->assertOk()
            ->assertJson(['data' => null]);

        $payload = [
            'company_name' => 'Novation SARL',
            'matricule_fiscal' => '0001234/A/B/000',
            'email' => 'contact@novation.tn',
            'phone' => '+216 71 100 200',
            'address' => 'Immeuble Les Jasmin, Bloc B',
            'city' => 'Tunis',
            'country' => 'TN',
            'bank_name' => 'Banque de Tunisie',
            'iban' => 'TN59 1000 6035 1835 9874 1234',
            'default_timbre_fiscal' => 1,
        ];

        $response = $this->actingAs($admin)->putJson('/api/company-settings', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Company settings saved successfully.')
            ->assertJsonPath('data.company_name', 'Novation SARL')
            ->assertJsonPath('data.iban', 'TN5910006035183598741234');

        $this->assertDatabaseHas('company_settings', [
            'user_id' => $admin->id,
            'company_name' => 'Novation SARL',
            'bank_rib' => 'TN5910006035183598741234',
        ]);
    }

    public function test_non_admins_cannot_access_company_settings_api(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson('/api/company-settings')
            ->assertForbidden();
    }

    public function test_invoice_sender_defaults_are_resolved_from_company_settings(): void
    {
        $admin = $this->createUser('admin');

        CompanySetting::query()->create([
            'user_id' => $admin->id,
            'company_name' => 'Novation SARL',
            'matricule_fiscal' => '0001234/A/B/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'country_code' => 'TN',
            'bank_name' => 'Banque de Tunisie',
            'bank_rib' => 'TN5910006035183598741234',
            'iban' => 'TN5910006035183598741234',
            'default_timbre_fiscal' => 1,
        ]);

        self::assertSame([
            'identifier' => '0001234/A/B/000',
            'name' => 'Novation SARL',
            'street' => '',
            'city' => 'Tunis',
            'postal_code' => '',
            'country' => 'TN',
        ], CompanySetting::senderDefaultsForUser($admin));
    }
}
