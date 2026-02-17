<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_tax_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('tax_type_code')->comment('I-1601, I-1602, I-1603');
            $table->string('tax_type_name');
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('taxable_amount', 20, 3)->default(0);
            $table->decimal('tax_amount', 20, 3)->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['invoice_id', 'tax_type_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_tax_lines');
    }
};
