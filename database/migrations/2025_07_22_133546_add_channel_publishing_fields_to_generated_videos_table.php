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
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')->nullable()->after('task_id');
            $table->boolean('auto_publish')->default(false)->after('channel_id');
            $table->boolean('publish_to_channel')->default(true)->after('auto_publish');
            $table->timestamp('channel_published_at')->nullable()->after('published_at');
            $table->text('channel_publish_error')->nullable()->after('channel_published_at');

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('set null');
            $table->index(['channel_id', 'auto_publish']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_videos', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropColumn([
                'channel_id',
                'auto_publish',
                'publish_to_channel',
                'channel_published_at',
                'channel_publish_error'
            ]);
        });
    }
};
