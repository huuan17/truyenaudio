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
        Schema::table('chapters', function (Blueprint $table) {
            if (!Schema::hasColumn('chapters', 'is_crawled')) {
                $table->boolean('is_crawled')->default(false)->after('content');
            }
            if (!Schema::hasColumn('chapters', 'file_path')) {
                $table->string('file_path')->nullable()->after('is_crawled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            if (Schema::hasColumn('chapters', 'is_crawled')) {
                $table->dropColumn('is_crawled');
            }
            if (Schema::hasColumn('chapters', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
