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
            $table->integer('tts_progress')->nullable()->after('tts_volume')
                  ->comment('Tiến độ TTS (0-100%)');
            $table->text('tts_error')->nullable()->after('tts_progress')
                  ->comment('Lỗi TTS (nếu có)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn(['tts_progress', 'tts_error']);
        });
    }
};
