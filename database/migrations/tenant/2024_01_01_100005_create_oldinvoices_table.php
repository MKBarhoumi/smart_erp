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
        Schema::create('oldinvoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_type');
            $table->string('document_number');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->uuid('customer_id');
            $table->decimal('total_ht', 20, 3);
            $table->decimal('total_tva', 20, 3);
            $table->decimal('total_ttc', 20, 3);
            $table->decimal('timbre_fiscal', 20, 3)->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('ttn_reference')->nullable();
            $table->text('ttn_response')->nullable();
            $table->text('cev_qr_data')->nullable();
            $table->timestamp('ttn_submitted_at')->nullable();
            $table->timestamp('ttn_accepted_at')->nullable();
            $table->text('signed_xml')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oldinvoices');
    }
};
