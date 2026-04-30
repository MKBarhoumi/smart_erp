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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('item_code')->unique();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();
            $table->string('unit_of_measure');
            $table->decimal('unit_price', 20, 3);
            $table->decimal('tva_rate', 5, 2)->default(19);
            $table->string('category')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->integer('current_stock')->default(0);
            $table->integer('min_stock_threshold')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
