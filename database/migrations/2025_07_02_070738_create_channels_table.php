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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên kênh
            $table->string('slug')->unique(); // Slug cho URL
            $table->enum('platform', ['tiktok', 'youtube']); // Nền tảng
            $table->string('channel_id')->nullable(); // ID kênh trên platform
            $table->string('username')->nullable(); // Username/handle
            $table->text('description')->nullable(); // Mô tả kênh

            // Logo và branding
            $table->string('logo_path')->nullable(); // Đường dẫn logo riêng
            $table->json('logo_config')->nullable(); // Config logo (position, size)

            // API credentials
            $table->text('api_credentials')->nullable(); // JSON encrypted credentials

            // Upload settings
            $table->json('upload_settings')->nullable(); // Cài đặt upload mặc định
            $table->string('default_privacy')->default('private'); // public, private, unlisted
            $table->json('default_tags')->nullable(); // Tags mặc định
            $table->string('default_category')->nullable(); // Category mặc định

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_upload')->default(false); // Tự động upload
            $table->timestamp('last_upload_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Thông tin bổ sung
            $table->timestamps();

            // Indexes
            $table->index(['platform', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
