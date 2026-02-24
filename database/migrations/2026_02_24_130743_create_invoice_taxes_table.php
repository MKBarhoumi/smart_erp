<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->string('tax_type_code', 6);
            $table->string('tax_type_name', 200);
            $table->string('tax_category', 6)->nullable();
            $table->string('tax_rate', 5);
            $table->string('tax_rate_basis', 35)->nullable();

            // Tax amounts - JSON array of MoaDetails
            $table->json('amounts');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_taxes');
    }
};