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
        // First, add 'none' to existing enum
        DB::statement("ALTER TABLE chapters MODIFY COLUMN audio_status ENUM('none', 'pending', 'processing', 'done', 'error') DEFAULT 'none'");

        // Update existing data - set all pending chapters to 'none' to prevent auto-queueing
        DB::statement("UPDATE chapters SET audio_status = 'none' WHERE audio_status = 'pending'");

        // Keep 'done' and 'error' as they are for now (we can rename later if needed)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            // Revert back to original enum and default
            $table->enum('audio_status', ['pending', 'processing', 'done', 'error'])
                  ->default('pending')
                  ->change();
        });
    }
};
