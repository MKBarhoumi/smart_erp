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
        Schema::create('tenant_usages', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('metric'); // users, customers, products, invoices, etc.
            $table->integer('value')->default(0);
            $table->string('period'); // monthly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'metric', 'period', 'period_start']);
            $table->index('tenant_id');
            $table->index('metric');
            $table->index('period');
            $table->index('period_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usages');
    }
};
