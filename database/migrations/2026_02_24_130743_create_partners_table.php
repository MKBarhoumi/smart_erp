<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            // functionCode: I-61(seller) I-62(buyer/MF tested) I-63..I-68
            $table->string('function_code', 6);

            // NAD - Partner Identifier
            $table->string('partner_identifier', 35);
            $table->string('partner_identifier_type', 6)->default('I-01');

            // Partner Name
            $table->string('partner_name', 200)->nullable();
            $table->string('partner_name_type', 20)->nullable(); // Physical|Qualification

            // Address
            $table->string('address_description', 500)->nullable();
            $table->string('street', 35)->nullable();
            $table->string('city', 35)->nullable();
            $table->string('postal_code', 17)->nullable();
            $table->string('country', 6)->nullable();
            $table->string('country_code_list', 20)->nullable();
            $table->string('address_lang', 6)->nullable();

            // Location - JSON array
            $table->json('locations')->nullable();

            // References - JSON array [{refID, value, dates}]
            $table->json('references')->nullable();

            // Contacts - JSON array [{functionCode, identifier, name, communication}]
            $table->json('contacts')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_partners');
    }
};