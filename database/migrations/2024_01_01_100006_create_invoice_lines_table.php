<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_line_id')->nullable();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('line_number');
            $table->string('item_code');
            $table->string('item_description');
            $table->string('item_lang', 5)->default('fr');
            $table->decimal('quantity', 20, 3);
            $table->string('unit_of_measure', 10)->default('U');
            $table->decimal('unit_price', 20, 3);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 20, 3)->default(0);
            $table->decimal('line_net_amount', 20, 3)->default(0);
            $table->decimal('tva_rate', 5, 2)->default(19.00);
            $table->decimal('tva_amount', 20, 3)->default(0);
            $table->decimal('line_total', 20, 3)->default(0);
            $table->timestamps();

            $table->foreign('parent_line_id')->references('id')->on('invoice_lines')->nullOnDelete();
            $table->index(['invoice_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
