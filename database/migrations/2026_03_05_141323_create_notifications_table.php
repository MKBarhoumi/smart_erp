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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // validation_request, validation_response, low_stock, payment_received, etc.
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable(); // icon name/path
            $table->string('link')->nullable(); // URL to redirect when clicked
            $table->string('notifiable_type')->nullable(); // polymorphic relation
            $table->uuid('notifiable_id')->nullable();
            $table->json('data')->nullable(); // extra data
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'read_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
