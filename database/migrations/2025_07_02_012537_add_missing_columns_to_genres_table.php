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
        Schema::table('genres', function (Blueprint $table) {
            // Chỉ thêm slug nếu chưa tồn tại
            if (!Schema::hasColumn('genres', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
