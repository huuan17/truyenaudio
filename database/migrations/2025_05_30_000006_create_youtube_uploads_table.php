<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYoutubeUploadsTable extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id')->nullable();
            $table->string('status')->default('pending'); // uploading | done | error
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_uploads');
    }
}
