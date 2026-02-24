<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price_monthly', 10, 3)->default(0);
            $table->decimal('price_yearly', 10, 3)->default(0);
            $table->integer('max_users')->default(1);
            $table->integer('max_oldinvoices_per_month')->default(50);
            $table->integer('max_products')->default(100);
            $table->integer('max_customers')->default(100);
            $table->boolean('has_ttn_integration')->default(false);
            $table->boolean('has_multi_currency')->default(false);
            $table->boolean('has_advanced_reports')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
