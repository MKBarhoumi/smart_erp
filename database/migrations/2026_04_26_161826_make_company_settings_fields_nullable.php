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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('category_type', 1)->nullable()->change();
            $table->string('person_type', 1)->nullable()->change();
            $table->string('tax_office', 3)->nullable()->change();
            $table->string('city')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('category_type', 1)->nullable(false)->change();
            $table->string('person_type', 1)->nullable(false)->change();
            $table->string('tax_office', 3)->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
        });
    }
};
