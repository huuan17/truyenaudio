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
        // Update enum to include 'failed' and 'completed' values
        DB::statement("ALTER TABLE chapters MODIFY COLUMN audio_status ENUM('none', 'pending', 'processing', 'completed', 'failed', 'done', 'error') DEFAULT 'none'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous enum
        DB::statement("ALTER TABLE chapters MODIFY COLUMN audio_status ENUM('none', 'pending', 'processing', 'done', 'error') DEFAULT 'none'");
    }
};
