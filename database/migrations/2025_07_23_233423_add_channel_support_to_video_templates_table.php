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
            $table->unsignedBigInteger('default_channel_id')->nullable()->after('created_by');
            $table->json('channel_metadata_template')->nullable()->after('default_channel_id');

            $table->foreign('default_channel_id')->references('id')->on('channels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_templates', function (Blueprint $table) {
            $table->dropForeign(['default_channel_id']);
            $table->dropColumn(['default_channel_id', 'channel_metadata_template']);
        });
    }
};
