<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AudioLibrary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportExistingAudioFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audio:import-existing
                            {--dry-run : Show what would be imported without actually importing}
                            {--force : Overwrite existing records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import existing audio files from storage/app/public/audio-library into database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎵 Scanning audio files in storage/app/public/audio-library...');

        $audioPath = 'audio-library';
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Check if directory exists
        if (!Storage::disk('public')->exists($audioPath)) {
            $this->error('❌ Directory storage/app/public/audio-library does not exist!');
            return 1;
        }

        // Get all audio files
        $audioFiles = $this->getAudioFiles($audioPath);

        if (empty($audioFiles)) {
            $this->warn('⚠️  No audio files found in the directory.');
            return 0;
        }

        $this->info("📁 Found " . count($audioFiles) . " audio files");

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No files will be imported');
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar(count($audioFiles));
        $progressBar->start();

        foreach ($audioFiles as $filePath) {
            try {
                $result = $this->importAudioFile($filePath, $isDryRun, $force);

                if ($result === 'imported') {
                    $imported++;
                } elseif ($result === 'skipped') {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("❌ Error importing {$filePath}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('📊 Import Summary:');
        $this->line("✅ Imported: {$imported}");
        $this->line("⏭️  Skipped: {$skipped}");
        $this->line("❌ Errors: {$errors}");

        if ($isDryRun && $imported > 0) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to actually import the files');
        }

        return 0;
    }

    /**
     * Get all audio files from directory
     */
    private function getAudioFiles($directory)
    {
        $audioExtensions = ['mp3', 'wav', 'aac', 'm4a', 'ogg', 'flac'];
        $allFiles = Storage::disk('public')->allFiles($directory);

        return array_filter($allFiles, function($file) use ($audioExtensions) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, $audioExtensions);
        });
    }

    /**
     * Import a single audio file
     */
    private function importAudioFile($filePath, $isDryRun = false, $force = false)
    {
        $fileName = basename($filePath);
        $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);

        // Check if already exists
        $existing = AudioLibrary::where('file_path', $filePath)->first();

        if ($existing && !$force) {
            return 'skipped';
        }

        if ($isDryRun) {
            $this->newLine();
            $this->line("📄 Would import: {$fileName}");
            return 'imported';
        }

        // Get file info
        $fullPath = Storage::disk('public')->path($filePath);
        $fileSize = Storage::disk('public')->size($filePath);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Generate clean title from filename
        $title = $this->generateTitleFromFilename($fileName);

        // Get audio metadata
        $metadata = $this->getAudioMetadata($fullPath);

        // Prepare data
        $audioData = [
            'title' => $title,
            'description' => "Audio file: {$title}",
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_extension' => $extension,
            'file_size' => $fileSize,
            'duration' => $metadata['duration'] ?? 0,
            'format' => $metadata['format'] ?? strtoupper($extension),
            'bitrate' => $metadata['bitrate'] ?? null,
            'sample_rate' => $metadata['sample_rate'] ?? null,
            'category' => 'music',
            'source_type' => 'upload',
            'language' => 'vi',
            'voice_type' => null,
            'mood' => 'neutral',
            'tags' => ['không bản quyền'],
            'metadata' => array_merge($metadata, [
                'imported_from_storage' => true,
                'import_date' => now(),
                'original_path' => $filePath
            ]),
            'is_public' => true,
            'uploaded_by' => 1 // Assume admin user ID 1
        ];

        if ($existing && $force) {
            // Update existing record
            $existing->update($audioData);
            $this->newLine();
            $this->line("🔄 Updated: {$title}");
        } else {
            // Create new record
            AudioLibrary::create($audioData);
            $this->newLine();
            $this->line("✅ Imported: {$title}");
        }

        return 'imported';
    }

    /**
     * Generate clean title from filename
     */
    private function generateTitleFromFilename($filename)
    {
        // Remove file extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Replace common separators with spaces
        $name = str_replace(['_', '-', '.'], ' ', $name);

        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        // Remove common prefixes/suffixes
        $name = preg_replace('/^(audio|track|song|music|sound)\s*/i', '', $name);
        $name = preg_replace('/\s*(audio|track|song|music|sound)$/i', '', $name);

        // Remove numbers at the beginning if they look like track numbers
        $name = preg_replace('/^\d{1,3}[\s\-\.]*/', '', $name);

        // Capitalize each word
        $name = ucwords(strtolower(trim($name)));

        // If empty after cleaning, use original filename
        if (empty($name)) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = ucwords(str_replace(['_', '-'], ' ', $name));
        }

        return $name;
    }

    /**
     * Get audio metadata using basic file info
     */
    private function getAudioMetadata($filePath)
    {
        try {
            // Try to get metadata using getID3 if available
            if (class_exists('\getID3')) {
                $getID3 = new \getID3;
                $fileInfo = $getID3->analyze($filePath);

                return [
                    'duration' => $fileInfo['playtime_seconds'] ?? 0,
                    'bitrate' => $fileInfo['audio']['bitrate'] ?? null,
                    'sample_rate' => $fileInfo['audio']['sample_rate'] ?? null,
                    'format' => $fileInfo['fileformat'] ?? 'unknown',
                    'channels' => $fileInfo['audio']['channels'] ?? null,
                ];
            }

            // Fallback: basic file info
            return [
                'duration' => 0,
                'bitrate' => null,
                'sample_rate' => null,
                'format' => 'unknown',
                'channels' => null,
            ];
        } catch (\Exception $e) {
            return [
                'duration' => 0,
                'bitrate' => null,
                'sample_rate' => null,
                'format' => 'unknown',
                'channels' => null,
            ];
        }
    }
}
