<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique()->comment('Internal product code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('item_lang', 5)->default('fr');
            $table->decimal('unit_price', 20, 3);
            $table->string('unit_of_measure', 10)->default('U')->comment('U=unit, KG, L, M, etc.');
            $table->decimal('tva_rate', 5, 2)->default(19.00)->comment('TVA rate %');
            $table->boolean('is_subject_to_timbre')->default(false);
            $table->boolean('track_inventory')->default(false);
            $table->decimal('current_stock', 20, 3)->default(0.000);
            $table->decimal('min_stock_alert', 20, 3)->default(0.000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
