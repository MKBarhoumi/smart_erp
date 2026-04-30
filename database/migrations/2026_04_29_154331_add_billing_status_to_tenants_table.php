<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('billing_status')->default('trial')->after('data');
            $table->timestamp('trial_ends_at')->nullable()->after('billing_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->timestamp('last_payment_at')->nullable()->after('subscription_ends_at');
            $table->timestamp('next_payment_at')->nullable()->after('last_payment_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'billing_status',
                'trial_ends_at',
                'subscription_ends_at',
                'last_payment_at',
                'next_payment_at',
            ]);
        });
    }
};
