<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;



    private function settingsDefaults(): array
    {
        return [
            'company_name' => 'Smart ERP Lite SARL',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'FAC',
        ];
    }

    public function test_company_settings_can_be_created(): void
    {
        $settings = CompanySetting::create(array_merge($this->settingsDefaults(), [
            'phone' => '+216 71 123 456',
            'email' => 'info@smarterp.tn',
        ]));

        $this->assertDatabaseHas('company_settings', [
            'company_name' => 'Smart ERP Lite SARL',
            'matricule_fiscal' => '1234567A/B/M/000',
        ]);
    }

    public function test_company_settings_can_be_updated(): void
    {
        CompanySetting::create($this->settingsDefaults());

        $settings = CompanySetting::first();
        $settings->update(['company_name' => 'New Name SARL']);

        $this->assertEquals('New Name SARL', CompanySetting::first()->company_name);
    }

    public function test_invoice_counter_can_be_incremented(): void
    {
        CompanySetting::create(array_merge($this->settingsDefaults(), [
            'next_invoice_counter' => 5,
        ]));

        $settings = CompanySetting::first();
        $settings->increment('next_invoice_counter');

        $this->assertEquals(6, CompanySetting::first()->next_invoice_counter);
    }

    public function test_certificate_fields_are_nullable(): void
    {
        $settings = CompanySetting::create($this->settingsDefaults());

        $this->assertNull($settings->certificate_file);
        $this->assertNull($settings->certificate_expires_at);
    }

    public function test_default_timbre_fiscal(): void
    {
        $settings = CompanySetting::create($this->settingsDefaults());
        $settings->refresh();

        // Default in migration is 1.000
        $this->assertNotNull($settings->default_timbre_fiscal);
    }
}
