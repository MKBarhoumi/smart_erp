<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'stripe_price_id' => null,
                'description' => 'Perfect for getting started',
                'price' => 0,
                'currency' => 'USD',
                'interval' => 'month',
                'max_users' => 2,
                'max_customers' => 10,
                'max_products' => 20,
                'max_invoices' => 50,
                'features' => [
                    '2 users',
                    '10 customers',
                    '20 products',
                    '50 invoices per month',
                    'Basic support',
                    'Standard templates',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'stripe_price_id' => env('STRIPE_PRO_PRICE_ID'),
                'description' => 'For growing businesses',
                'price' => 2900, // $29.00
                'currency' => 'USD',
                'interval' => 'month',
                'max_users' => 10,
                'max_customers' => 100,
                'max_products' => 500,
                'max_invoices' => 1000,
                'features' => [
                    '10 users',
                    '100 customers',
                    '500 products',
                    '1000 invoices per month',
                    'Priority support',
                    'Custom templates',
                    'API access',
                    'Advanced reporting',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'stripe_price_id' => env('STRIPE_ENTERPRISE_PRICE_ID'),
                'description' => 'For large organizations',
                'price' => 9900, // $99.00
                'currency' => 'USD',
                'interval' => 'month',
                'max_users' => null, // unlimited
                'max_customers' => null, // unlimited
                'max_products' => null, // unlimited
                'max_invoices' => null, // unlimited
                'features' => [
                    'Unlimited users',
                    'Unlimited customers',
                    'Unlimited products',
                    'Unlimited invoices',
                    '24/7 dedicated support',
                    'Custom branding',
                    'Advanced API access',
                    'Custom integrations',
                    'SLA guarantee',
                    'Dedicated account manager',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Plans seeded successfully!');
    }
}