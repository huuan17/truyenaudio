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
        Schema::table('stories', function (Blueprint $table) {
            // Chỉ thêm các columns chưa tồn tại
            if (!Schema::hasColumn('stories', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }
            if (!Schema::hasColumn('stories', 'description')) {
                $table->text('description')->nullable()->after('author');
            }
            if (!Schema::hasColumn('stories', 'status')) {
                $table->enum('status', ['ongoing', 'completed', 'paused'])->default('ongoing')->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn(['slug', 'description', 'status']);
        });
    }
};
