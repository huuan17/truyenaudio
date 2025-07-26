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
        Schema::create('video_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, tiktok, youtube, marketing, etc.
            $table->json('settings'); // All video generation settings
            $table->json('required_inputs'); // List of inputs user must provide
            $table->json('optional_inputs')->nullable(); // List of optional inputs
            $table->string('thumbnail')->nullable(); // Preview image
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // Can be shared with other users
            $table->unsignedBigInteger('created_by');
            $table->integer('usage_count')->default(0); // Track how many times used
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['category', 'is_active']);
            $table->index(['created_by', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_templates');
    }
};
