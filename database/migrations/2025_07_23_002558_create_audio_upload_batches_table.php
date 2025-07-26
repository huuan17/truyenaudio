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
        Schema::create('audio_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_files')->default(0);
            $table->integer('completed_files')->default(0);
            $table->integer('failed_files')->default(0);
            $table->integer('processing_files')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'completed_with_errors', 'failed'])->default('pending');
            $table->decimal('progress', 5, 2)->default(0); // 0.00 to 100.00
            $table->json('files'); // Array of file info and status
            $table->json('settings'); // Upload settings (category, tags, etc.)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_upload_batches');
    }
};
