<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AudioLibrary;
use Illuminate\Support\Facades\DB;

class AudioLibraryStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show audio library statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎵 Audio Library Statistics');
        $this->line('');

        // Total count
        $total = AudioLibrary::count();
        $this->info("📊 Total Audio Files: {$total}");

        // By category
        $this->line('');
        $this->info('📂 By Category:');
        $categories = AudioLibrary::select('category', DB::raw('count(*) as count'))
                                 ->groupBy('category')
                                 ->orderBy('count', 'desc')
                                 ->get();

        foreach ($categories as $category) {
            $this->line("  {$category->category}: {$category->count}");
        }

        // By source type
        $this->line('');
        $this->info('📥 By Source Type:');
        $sources = AudioLibrary::select('source_type', DB::raw('count(*) as count'))
                              ->groupBy('source_type')
                              ->orderBy('count', 'desc')
                              ->get();

        foreach ($sources as $source) {
            $this->line("  {$source->source_type}: {$source->count}");
        }

        // By language
        $this->line('');
        $this->info('🌐 By Language:');
        $languages = AudioLibrary::select('language', DB::raw('count(*) as count'))
                                ->groupBy('language')
                                ->orderBy('count', 'desc')
                                ->get();

        foreach ($languages as $language) {
            $this->line("  {$language->language}: {$language->count}");
        }

        // Public vs Private
        $this->line('');
        $this->info('🔓 Visibility:');
        $public = AudioLibrary::where('is_public', true)->count();
        $private = AudioLibrary::where('is_public', false)->count();
        $this->line("  Public: {$public}");
        $this->line("  Private: {$private}");

        // Total file size
        $this->line('');
        $this->info('💾 Storage:');
        $totalSize = AudioLibrary::sum('file_size');
        $this->line("  Total Size: " . $this->formatBytes($totalSize));

        // Average duration
        $avgDuration = AudioLibrary::where('duration', '>', 0)->avg('duration');
        if ($avgDuration) {
            $this->line("  Average Duration: " . gmdate('H:i:s', $avgDuration));
        }

        // Recent imports
        $this->line('');
        $this->info('📅 Recent Activity:');
        $recentImports = AudioLibrary::whereJsonContains('metadata->imported_from_storage', true)
                                   ->count();
        if ($recentImports > 0) {
            $this->line("  Imported from storage: {$recentImports}");
        }

        $todayUploads = AudioLibrary::whereDate('created_at', today())->count();
        $this->line("  Uploaded today: {$todayUploads}");

        $this->line('');
        $this->info('✅ Statistics complete!');
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
