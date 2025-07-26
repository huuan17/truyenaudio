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
        Schema::table('video_templates', function (Blueprint $table) {
            $table->string('background_music_type')->nullable()->after('channel_metadata_template'); // 'none', 'upload', 'library', 'random'
            $table->string('background_music_file')->nullable()->after('background_music_type'); // Path to uploaded file
            $table->unsignedBigInteger('background_music_library_id')->nullable()->after('background_music_file'); // Library audio ID
            $table->string('background_music_random_tag')->nullable()->after('background_music_library_id'); // Random tag for selection
            $table->integer('background_music_volume')->default(30)->after('background_music_random_tag'); // Volume percentage

            $table->foreign('background_music_library_id')->references('id')->on('audio_libraries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_templates', function (Blueprint $table) {
            $table->dropForeign(['background_music_library_id']);
            $table->dropColumn([
                'background_music_type',
                'background_music_file',
                'background_music_library_id',
                'background_music_random_tag',
                'background_music_volume'
            ]);
        });
    }
};
