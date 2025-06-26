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
            // Thêm các trường TTS nếu chưa có
            if (!Schema::hasColumn('chapters', 'tts_voice')) {
                $table->string('tts_voice')->nullable()->after('audio_status');
            }
            if (!Schema::hasColumn('chapters', 'tts_bitrate')) {
                $table->integer('tts_bitrate')->default(128)->after('tts_voice');
            }
            if (!Schema::hasColumn('chapters', 'tts_speed')) {
                $table->float('tts_speed')->default(1.0)->after('tts_bitrate');
            }
            if (!Schema::hasColumn('chapters', 'tts_started_at')) {
                $table->timestamp('tts_started_at')->nullable()->after('tts_speed');
            }
            if (!Schema::hasColumn('chapters', 'tts_completed_at')) {
                $table->timestamp('tts_completed_at')->nullable()->after('tts_started_at');
            }
            if (!Schema::hasColumn('chapters', 'audio_file_path')) {
                $table->string('audio_file_path')->nullable()->after('tts_completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $columns = ['tts_voice', 'tts_bitrate', 'tts_speed', 'tts_started_at', 'tts_completed_at', 'audio_file_path'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('chapters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
