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
            $table->string('tax_rate', 10)->change();
        });

        Schema::table('invoice_taxes', function (Blueprint $table) {
            $table->string('tax_rate', 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('tax_rate', 5)->change();
        });

        Schema::table('invoice_taxes', function (Blueprint $table) {
            $table->string('tax_rate', 5)->change();
        });
    }
};
