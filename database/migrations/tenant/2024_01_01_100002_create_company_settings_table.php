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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('company_name');
            $table->string('matricule_fiscal')->nullable();
            $table->string('category_type')->nullable();
            $table->string('person_type')->nullable();
            $table->string('tax_office')->nullable();
            $table->string('registre_commerce')->nullable();
            $table->string('legal_form')->nullable();
            $table->text('address_description')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('iban')->nullable();
            $table->string('bank_rib')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch_code')->nullable();
            $table->string('postal_account')->nullable();
            $table->string('oldinvoice_prefix')->nullable();
            $table->string('oldinvoice_number_format')->nullable();
            $table->integer('next_oldinvoice_counter')->default(1);
            $table->decimal('default_timbre_fiscal', 10, 3)->default(0);
            $table->string('certificate_file')->nullable();
            $table->string('certificate_passphrase')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
