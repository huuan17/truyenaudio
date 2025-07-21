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
            // Thêm trường is_public để kiểm soát hiển thị ở frontend
            $table->boolean('is_public')->default(true)->after('crawl_status')
                  ->comment('Truyện có được hiển thị công khai ở frontend không');
            
            // Thêm trường is_active để kiểm soát trạng thái hoạt động
            $table->boolean('is_active')->default(true)->after('is_public')
                  ->comment('Truyện có đang hoạt động không (admin có thể tạm dừng)');
            
            // Thêm index để tối ưu query
            $table->index(['is_public', 'is_active'], 'stories_visibility_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            // Xóa index trước
            $table->dropIndex('stories_visibility_index');
            
            // Xóa các trường
            $table->dropColumn(['is_public', 'is_active']);
        });
    }
};
