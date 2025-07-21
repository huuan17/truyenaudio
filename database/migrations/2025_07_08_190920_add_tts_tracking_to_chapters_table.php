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
        Schema::table('chapters', function (Blueprint $table) {
            $table->enum('audio_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('audio_file_path');
            $table->integer('tts_progress')->default(0)->after('audio_status'); // 0-100
            $table->text('tts_error')->nullable()->after('tts_progress');
            $table->timestamp('tts_started_at')->nullable()->after('tts_error');
            $table->timestamp('tts_completed_at')->nullable()->after('tts_started_at');

            // Index for performance
            $table->index(['audio_status', 'tts_started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex(['audio_status', 'tts_started_at']);
            $table->dropColumn([
                'audio_status',
                'tts_progress',
                'tts_error',
                'tts_started_at',
                'tts_completed_at'
            ]);
        });
    }
};
