<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_line_id')->nullable()
                  ->references('id')->on('invoice_lines')->nullOnDelete();

            $table->string('item_identifier', 35);
            $table->string('item_code', 35);
            $table->string('item_description', 500)->nullable();
            $table->string('item_lang', 6)->nullable();

            // API details - JSON
            $table->json('api_details')->nullable();

            // Quantity
            $table->string('quantity', 35);
            $table->string('measurement_unit', 8);

            // Dates - JSON
            $table->json('dates')->nullable();

            // Tax
            $table->string('tax_type_code', 6);    // I-161..I-1603
            $table->string('tax_type_name', 200);
            $table->string('tax_category', 6)->nullable();
            $table->string('tax_rate', 5);
            $table->string('tax_rate_basis', 35)->nullable();

            // Line allowances - JSON
            $table->json('allowances')->nullable();

            // Line amounts - JSON array of MoaDetails
            $table->json('amounts');

            // Free texts - JSON
            $table->json('free_texts')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};