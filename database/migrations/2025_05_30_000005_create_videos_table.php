<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->onDelete('cascade');
            $table->foreignId('audio_id')->constrained();
            $table->foreignId('image_id')->nullable();
            $table->foreignId('overlay_video_id')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('render_status', ['pending', 'processing', 'done', 'error'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};