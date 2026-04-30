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
        Schema::create('ttn_submission_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('oldinvoice_id');
            $table->integer('attempt');
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ttn_submission_logs');
    }
};
