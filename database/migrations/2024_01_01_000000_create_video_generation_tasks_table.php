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
        Schema::create('video_generation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // 'tiktok', 'youtube'
            $table->string('type'); // 'single', 'batch'
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed', 'cancelled'
            $table->integer('priority')->default(2); // 1=low, 2=normal, 3=high, 4=urgent
            
            // Task parameters and results
            $table->json('parameters'); // Command parameters
            $table->json('result')->nullable(); // Result data
            
            // Progress tracking
            $table->integer('progress')->default(0); // 0-100
            $table->integer('estimated_duration')->nullable(); // Estimated duration in seconds
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Batch processing
            $table->string('batch_id')->nullable(); // UUID for batch operations
            $table->integer('batch_index')->nullable(); // Order in batch
            $table->integer('total_in_batch')->nullable(); // Total items in batch
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['status', 'priority', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['platform', 'status']);
            $table->index(['batch_id', 'batch_index']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_generation_tasks');
    }
};
