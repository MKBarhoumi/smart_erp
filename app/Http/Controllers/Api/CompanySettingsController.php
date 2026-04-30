<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettingsRequest;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = CompanySetting::resolveForUser($request->user());

        return response()->json([
            'data' => $settings ? $this->payload($settings) : null,
        ]);
    }

    public function update(CompanySettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // Find existing user settings or get the global one as a template
        $existing = CompanySetting::where('user_id', $user->id)->first();
        $global = CompanySetting::whereNull('user_id')->first();

        $settings = CompanySetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $validated['company_name'],
                'matricule_fiscal' => $validated['matricule_fiscal'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'street' => $validated['address'],
                'city' => $validated['city'],
                'country_code' => strtoupper($validated['country']),
                'bank_name' => $validated['bank_name'],
                'bank_rib' => $validated['iban'],
                'iban' => $validated['iban'],
                'default_timbre_fiscal' => $validated['default_timbre_fiscal'],
                // Preserve or copy important TEIF fields from global if not present
                'category_type' => $existing->category_type ?? $global->category_type ?? 'A',
                'person_type' => $existing->person_type ?? $global->person_type ?? 'M',
                'tax_office' => $existing->tax_office ?? $global->tax_office ?? '001',
                'legal_form' => $existing->legal_form ?? $global->legal_form ?? 'SARL',
            ],
        );

        return response()->json([
            'message' => 'Company settings saved successfully.',
            'data' => $this->payload($settings),
        ]);
    }

    /**
     * @return array<string, string|float>
     */
    private function payload(CompanySetting $settings): array
    {
        return [
            'id' => $settings->id,
            'company_name' => $settings->company_name ?? '',
            'matricule_fiscal' => $settings->matricule_fiscal ?? '',
            'email' => $settings->email ?? '',
            'phone' => $settings->phone ?? '',
            'address' => $settings->street ?? '',
            'city' => $settings->city ?? '',
            'country' => $settings->country_code ?? '',
            'bank_name' => $settings->bank_name ?? '',
            'iban' => $settings->iban ?? $settings->bank_rib ?? '',
            'default_timbre_fiscal' => (float) ($settings->default_timbre_fiscal ?? 0),
        ];
    }
}