<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Models\CompanySetting;
use App\Services\CertificateManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly CertificateManager $certificateManager,
    ) {
    }

    public function edit(): Response
    {
        $settings = CompanySetting::first();
        $certValid = $this->certificateManager->isValid();
        $certExpiring = $this->certificateManager->isExpiringSoon();

        return Inertia::render('Settings/Edit', [
            'settings' => $settings,
            'certificateStatus' => [
                'valid' => $certValid,
                'expiring_soon' => $certExpiring,
                'expires_at' => $settings?->certificate_expires_at,
            ],
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $settings = CompanySetting::firstOrNew();
        $settings->fill($request->validated());
        $settings->save();

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    public function uploadCertificate(Request $request): RedirectResponse
    {
        $request->validate([
            'certificate' => ['required', 'file', 'max:10240'],
            'passphrase' => ['required', 'string'],
        ]);

        try {
            $p12Contents = file_get_contents($request->file('certificate')->getRealPath());
            $info = $this->certificateManager->uploadAndParse($p12Contents, $request->input('passphrase'));

            return back()->with('success', "Certificat chargé: {$info['subject']} (expire: {$info['valid_to']})");
        } catch (\App\Exceptions\SignatureException $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $path = $request->file('logo')->store('logos', 'public');

        $settings = CompanySetting::firstOrNew();
        $settings->logo_path = $path;
        $settings->save();

        return back()->with('success', 'Logo mis à jour avec succès.');
    }
}
