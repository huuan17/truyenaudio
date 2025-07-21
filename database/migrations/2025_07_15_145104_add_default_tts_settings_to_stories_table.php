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
        Schema::table('stories', function (Blueprint $table) {
            // Thêm các cột TTS mặc định cho story
            $table->string('default_tts_voice')->default('hn_female_ngochuyen_full_48k-fhg')->after('auto_tts')
                  ->comment('Giọng đọc mặc định cho tất cả chapter của truyện');

            $table->integer('default_tts_bitrate')->default(128)->after('default_tts_voice')
                  ->comment('Bitrate mặc định cho audio (kbps)');

            $table->float('default_tts_speed')->default(1.0)->after('default_tts_bitrate')
                  ->comment('Tốc độ đọc mặc định (0.5-2.0)');

            $table->float('default_tts_volume')->default(1.0)->after('default_tts_speed')
                  ->comment('Âm lượng mặc định (0.1-2.0)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn([
                'default_tts_voice',
                'default_tts_bitrate',
                'default_tts_speed',
                'default_tts_volume'
            ]);
        });
    }
};
