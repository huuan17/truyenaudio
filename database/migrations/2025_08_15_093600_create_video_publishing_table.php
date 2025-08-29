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
        Schema::create('video_publishing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_video_id')->constrained('generated_videos')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('channels')->onDelete('set null');
            $table->string('platform'); // youtube, tiktok, facebook, etc.
            $table->enum('status', [
                'draft',           // Video created but not scheduled
                'scheduled',       // Scheduled for publishing
                'publishing',      // Currently being published
                'published',       // Successfully published
                'failed',          // Publishing failed
                'cancelled'        // Publishing cancelled
            ])->default('draft');
            $table->enum('publish_mode', ['auto', 'scheduled', 'manual'])->default('manual');
            
            // Publishing details
            $table->string('post_title')->nullable();
            $table->text('post_description')->nullable();
            $table->json('post_tags')->nullable(); // Array of tags
            $table->string('post_privacy')->default('private'); // public, private, unlisted
            $table->string('post_category')->nullable();
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            
            // Platform-specific data
            $table->string('platform_post_id')->nullable(); // YouTube video ID, TikTok post ID, etc.
            $table->string('platform_url')->nullable(); // Direct URL to the post
            $table->json('platform_metadata')->nullable(); // Platform-specific metadata
            
            // Error handling
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index(['platform', 'status']);
            $table->index(['scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_publishing');
    }
};
