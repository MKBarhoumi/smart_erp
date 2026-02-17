<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->uuid('parent_invoice_id')->nullable();

            // Document identification
            $table->string('invoice_number')->unique();
            $table->string('document_identifier')->unique();
            $table->string('document_type_code')->comment('I-11 to I-16');
            $table->string('status')->default('draft');

            // Dates
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();

            // Monetary amounts TND (3 decimal places, NEVER float)
            $table->decimal('total_gross', 20, 3)->default(0);
            $table->decimal('total_discount', 20, 3)->default(0);
            $table->decimal('total_net_before_disc', 20, 3)->default(0);
            $table->decimal('total_ht', 20, 3)->default(0);
            $table->decimal('total_tva', 20, 3)->default(0);
            $table->decimal('timbre_fiscal', 20, 3)->default(0);
            $table->decimal('total_ttc', 20, 3)->default(0);

            // TTN / El Fatoora
            $table->string('ref_ttn_val')->nullable()->comment('TTN validation reference');
            $table->text('cev_qr_content')->nullable()->comment('CEV QR code content');
            $table->text('signed_xml')->nullable()->comment('Signed TEIF XML');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->index('status');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
