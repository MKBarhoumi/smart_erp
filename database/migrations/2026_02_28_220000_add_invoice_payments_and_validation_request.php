<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add invoice_id to payments table (optional, for TEIF invoice payments)
        // Note: invoices table uses auto-increment id, not UUID
        if (!Schema::hasColumn('payments', 'invoice_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('oldinvoice_id');
            });
        }
        
        // Add foreign key if not exists
        $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'invoice_id' AND REFERENCED_TABLE_NAME IS NOT NULL"));
        if ($foreignKeys->isEmpty()) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            });
        }

        // Make oldinvoice_id nullable to support payments for both old and new invoices
        DB::statement('ALTER TABLE payments MODIFY oldinvoice_id CHAR(36) NULL');

        // Update invoice status enum to include pending_validation (if not already done)
        $columnInfo = DB::select("SHOW COLUMNS FROM invoices WHERE Field = 'status'");
        if (!empty($columnInfo) && strpos($columnInfo[0]->Type, 'pending_validation') === false) {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'pending_validation', 'validated', 'signed', 'submitted', 'accepted', 'rejected') NOT NULL DEFAULT 'draft'");
        }

        // Add validation request fields to invoices table (only if not already present)
        if (!Schema::hasColumn('invoices', 'validation_requested_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('validation_requested_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('invoices', 'validation_requested_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('validation_requested_at')->nullable()->after('validation_requested_by');
            });
        }
        if (!Schema::hasColumn('invoices', 'validated_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('validated_by')->nullable()->after('validation_requested_at')->constrained('users')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('invoices', 'validated_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            });
        }
        if (!Schema::hasColumn('invoices', 'validation_rejection_reason')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->text('validation_rejection_reason')->nullable()->after('rejection_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validation_requested_by');
            $table->dropColumn('validation_requested_at');
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn('validated_at');
            $table->dropColumn('validation_rejection_reason');
        });

        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'validated', 'signed', 'submitted', 'accepted', 'rejected') NOT NULL DEFAULT 'draft'");

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        DB::statement('ALTER TABLE payments MODIFY oldinvoice_id CHAR(36) NOT NULL');
    }
};
