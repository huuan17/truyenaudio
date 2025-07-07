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
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');

            // Video info
            $table->string('video_path'); // Đường dẫn video
            $table->string('video_type')->default('tiktok'); // tiktok, story, custom
            $table->string('title'); // Tiêu đề video
            $table->text('description')->nullable(); // Mô tả
            $table->json('tags')->nullable(); // Tags
            $table->string('category')->nullable(); // Category
            $table->string('privacy')->default('private'); // public, private, unlisted

            // Scheduling
            $table->timestamp('scheduled_at'); // Thời gian hẹn đăng
            $table->string('timezone')->default('Asia/Ho_Chi_Minh'); // Timezone

            // Status
            $table->enum('status', ['pending', 'processing', 'uploaded', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('uploaded_at')->nullable(); // Thời gian đăng thực tế
            $table->string('platform_post_id')->nullable(); // ID bài đăng trên platform
            $table->text('platform_url')->nullable(); // URL bài đăng

            // Error handling
            $table->text('error_message')->nullable(); // Lỗi nếu có
            $table->integer('retry_count')->default(0); // Số lần thử lại
            $table->timestamp('last_retry_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Thông tin bổ sung
            $table->timestamps();

            // Indexes
            $table->index(['status', 'scheduled_at']);
            $table->index(['channel_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
