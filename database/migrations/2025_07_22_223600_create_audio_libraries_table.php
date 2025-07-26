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
        Schema::create('audio_libraries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension', 10);
            $table->bigInteger('file_size'); // in bytes
            $table->integer('duration'); // in seconds
            $table->string('format')->nullable(); // MP3, WAV, AAC, etc.
            $table->integer('bitrate')->nullable(); // kbps
            $table->integer('sample_rate')->nullable(); // Hz
            $table->string('category')->default('general'); // general, story, music, voice, effect, etc.
            $table->string('source_type')->default('upload'); // upload, tts, story, imported
            $table->unsignedBigInteger('source_id')->nullable(); // ID from source (story_id, chapter_id, etc.)
            $table->string('language', 10)->default('vi'); // vi, en, etc.
            $table->string('voice_type')->nullable(); // male, female, child, etc.
            $table->string('mood')->nullable(); // happy, sad, dramatic, calm, etc.
            $table->json('tags')->nullable(); // searchable tags
            $table->json('metadata')->nullable(); // additional metadata
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('uploaded_by');
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['category', 'is_active']);
            $table->index(['source_type', 'source_id']);
            $table->index(['language', 'voice_type']);
            $table->index(['uploaded_by', 'is_active']);
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_libraries');
    }
};
