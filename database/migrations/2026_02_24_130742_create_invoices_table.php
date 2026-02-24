<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('version', 6)->default('1.8.8');
            $table->string('controlling_agency', 20)->default('TTN');

            // Header
            $table->string('sender_identifier', 35);
            $table->string('sender_type', 6)->default('I-01');
            $table->string('receiver_identifier', 35);
            $table->string('receiver_type', 6)->nullable();

            // BGM
            $table->string('document_identifier', 70);
            $table->string('document_type_code', 6)->nullable(); // I-11..I-16
            $table->string('document_type_name', 35);

            // DTM dates - stored as JSON array of {functionCode, format, value}
            $table->json('dates');

            // Payment section - JSON
            $table->json('payment_section')->nullable();

            // Free text - JSON
            $table->json('free_texts')->nullable();

            // Special conditions - JSON
            $table->json('special_conditions')->nullable();

            // Location section - JSON
            $table->json('loc_section')->nullable();

            // Invoice MOA totals - JSON
            $table->json('invoice_amounts');

            // Allowances - JSON
            $table->json('invoice_allowances')->nullable();

            // RefTtnVal
            $table->string('ref_ttn_id', 6)->nullable();
            $table->string('ref_ttn_value', 70)->nullable();
            $table->string('ref_cev', 4000)->nullable(); // base64 QR
            $table->json('ref_ttn_dates')->nullable();

            // Signatures - JSON array
            $table->json('signatures')->nullable();

            $table->enum('status', ['draft', 'signed', 'validated', 'rejected'])
                  ->default('draft');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};