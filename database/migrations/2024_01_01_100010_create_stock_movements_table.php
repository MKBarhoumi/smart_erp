<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->comment('in, out, adjustment');
            $table->decimal('quantity', 20, 3);
            $table->decimal('stock_before', 20, 3);
            $table->decimal('stock_after', 20, 3);
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
