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
        Schema::create('oldinvoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('oldinvoice_id');
            $table->integer('line_number');
            $table->string('product_code')->nullable();
            $table->string('product_name');
            $table->decimal('quantity', 20, 3);
            $table->string('unit_of_measure');
            $table->decimal('unit_price', 20, 3);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('net_amount', 20, 3);
            $table->decimal('tax_rate', 5, 2)->default(19);
            $table->decimal('tax_amount', 20, 3);
            $table->decimal('gross_amount', 20, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oldinvoice_lines');
    }
};
