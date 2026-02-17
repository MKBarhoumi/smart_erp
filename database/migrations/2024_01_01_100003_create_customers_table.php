<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('identifier_type')->comment('I-01 to I-04');
            $table->string('identifier_value');
            $table->string('matricule_fiscal')->nullable();
            $table->string('category_type', 1)->nullable()->comment('I-811');
            $table->string('person_type', 1)->nullable()->comment('I-812');
            $table->string('tax_office', 3)->nullable()->comment('I-813');
            $table->string('registre_commerce')->nullable()->comment('I-815');
            $table->string('legal_form')->nullable()->comment('I-816');

            // Address
            $table->string('address_description')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country_code', 3)->default('TN');

            // Contact
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['identifier_type', 'identifier_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
