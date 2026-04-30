<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tenant\RegisterTenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TenantRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $tenant = app(RegisterTenant::class)->execute($request->all());

        return response()->json([
            'message' => 'Tenant created successfully',
            'tenant_id' => $tenant->id,
            'domain' => $tenant->domains->first()->domain,
        ], 201);
    }
}