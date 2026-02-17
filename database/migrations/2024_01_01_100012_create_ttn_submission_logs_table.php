<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttn_submission_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->default('outbound')->comment('outbound or inbound');
            $table->longText('payload')->nullable();
            $table->longText('response')->nullable();
            $table->integer('http_status')->nullable();
            $table->string('status')->default('pending');
            $table->string('ref_ttn_val')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ttn_submission_logs');
    }
};
