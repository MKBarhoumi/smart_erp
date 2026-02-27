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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->longText('signed_xml')->nullable()->after('created_by');
            $table->timestamp('submitted_at')->nullable()->after('signed_xml');
            $table->timestamp('accepted_at')->nullable()->after('submitted_at');
            $table->text('rejection_reason')->nullable()->after('accepted_at');
            $table->text('notes')->nullable()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'signed_xml', 'submitted_at', 'accepted_at', 'rejection_reason', 'notes']);
        });
    }
};
