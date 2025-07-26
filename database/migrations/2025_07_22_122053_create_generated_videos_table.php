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
        Schema::create('generated_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform'); // tiktok, youtube
            $table->string('media_type'); // images, video, mixed
            $table->string('file_path');
            $table->string('file_name');
            $table->bigInteger('file_size')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->string('thumbnail_path')->nullable();
            $table->json('metadata')->nullable(); // store generation parameters
            $table->enum('status', ['generated', 'scheduled', 'published', 'failed'])->default('generated');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('video_generation_tasks')->onDelete('set null');
            $table->index(['platform', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_videos');
    }
};
