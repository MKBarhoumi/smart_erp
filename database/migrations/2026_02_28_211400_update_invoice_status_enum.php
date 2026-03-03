<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include all possible values
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'validated', 'signed', 'submitted', 'accepted', 'rejected') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft', 'signed', 'validated', 'rejected') NOT NULL DEFAULT 'draft'");
    }
};
