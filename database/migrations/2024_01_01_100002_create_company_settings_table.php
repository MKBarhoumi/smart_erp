<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Company identity
            $table->string('company_name');
            $table->string('matricule_fiscal')->comment('MF format: 7digits+letter+cat+person+000');
            $table->string('category_type', 1)->comment('I-811: A,B,D,N,P');
            $table->string('person_type', 1)->comment('I-812: C,M,N,P');
            $table->string('tax_office', 3)->nullable()->comment('I-813: 3-digit code');
            $table->string('registre_commerce')->nullable()->comment('I-815');
            $table->string('legal_form')->nullable()->comment('I-816: SA, SARL, SUARL, etc.');

            // Address
            $table->string('address_description')->nullable();
            $table->string('street')->nullable();
            $table->string('city');
            $table->string('postal_code', 10)->nullable();
            $table->string('country_code', 3)->default('TN');

            // Contact
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Banking
            $table->string('bank_rib')->nullable()->comment('I-141: Bank RIB number');
            $table->string('bank_name')->nullable();
            $table->string('bank_branch_code')->nullable();
            $table->string('postal_account')->nullable()->comment('I-115: Postal account');

            // Certificate
            $table->string('certificate_file')->nullable();
            $table->text('certificate_passphrase')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();

            // Invoicing
            $table->string('invoice_prefix')->default('FA');
            $table->string('invoice_number_format')->default('{prefix}/{YYYY}/{counter}');
            $table->integer('next_invoice_counter')->default(1);
            $table->decimal('default_timbre_fiscal', 20, 3)->default('1.000');

            // Logo
            $table->string('logo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
