<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterTenant
{
    public function execute(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'name' => $data['name'],
                'data' => [
                    'database_name' => 'tenant_' . Str::slug($data['name']) . '_' . time(),
                    'status' => 'trial',
                    'trial_ends_at' => now()->addDays(14)->toIso8601String(),
                ],
            ]);

            $tenant->domains()->create([
                'domain' => $data['domain'],
                'is_primary' => true,
            ]);

            app(TenantProvisioningService::class)->provision($tenant);

            return $tenant;
        });
    }
}