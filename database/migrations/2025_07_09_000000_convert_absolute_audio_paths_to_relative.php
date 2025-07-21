<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert absolute audio paths to relative paths
        $chapters = DB::table('chapters')
            ->whereNotNull('audio_file_path')
            ->where('audio_file_path', '!=', '')
            ->get();

        foreach ($chapters as $chapter) {
            $audioPath = $chapter->audio_file_path;
            
            // Skip if already relative path
            if (!str_contains($audioPath, 'storage/')) {
                continue;
            }
            
            // Extract relative path from absolute path
            $relativePath = substr($audioPath, strpos($audioPath, 'storage/') + 8);
            
            // Update to relative path
            DB::table('chapters')
                ->where('id', $chapter->id)
                ->update(['audio_file_path' => $relativePath]);
                
            echo "Updated chapter {$chapter->id}: {$audioPath} -> {$relativePath}\n";
        }
        
        echo "Converted " . count($chapters) . " audio paths from absolute to relative\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we lose the original absolute paths
        // But we can reconstruct them if needed
        $chapters = DB::table('chapters')
            ->whereNotNull('audio_file_path')
            ->where('audio_file_path', '!=', '')
            ->get();

        foreach ($chapters as $chapter) {
            $audioPath = $chapter->audio_file_path;
            
            // Skip if already absolute path
            if (str_contains($audioPath, 'storage/')) {
                continue;
            }
            
            // Reconstruct absolute path (this is approximate)
            $absolutePath = base_path('storage/' . $audioPath);
            
            DB::table('chapters')
                ->where('id', $chapter->id)
                ->update(['audio_file_path' => $absolutePath]);
        }
    }
};
