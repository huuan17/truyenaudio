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
            // Auto crawl flag - automatically queue crawl jobs to avoid overloading source website
            $table->boolean('auto_crawl')->default(true)->after('crawl_status')
                  ->comment('Tự động crawl - đưa vào queue job để tránh request liên tục');

            // Auto TTS flag - automatically queue TTS conversion jobs to avoid VBee API overload
            $table->boolean('auto_tts')->default(false)->after('auto_crawl')
                  ->comment('Tự động convert audio qua VBee TTS - đưa vào hàng đợi xử lý');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['auto_crawl', 'auto_tts']);
        });
    }
};
