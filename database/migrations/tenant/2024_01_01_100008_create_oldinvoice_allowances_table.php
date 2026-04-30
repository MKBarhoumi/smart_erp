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
        Schema::create('oldinvoice_allowances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('oldinvoice_id');
            $table->string('allowance_code');
            $table->string('description');
            $table->decimal('amount', 20, 3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oldinvoice_allowances');
    }
};
