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
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('tax_rate_basis');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('tva_rate');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn('discount_rate');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('discount_rate');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('discount_rate');
        });
    }
};
