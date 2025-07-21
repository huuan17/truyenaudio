<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Author;
use App\Models\Story;
use Illuminate\Support\Str;

class MigrateAuthorDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting migration of author data from stories...');
        
        // Lấy tất cả stories có trường author
        $stories = Story::whereNotNull('author')
            ->where('author', '!=', '')
            ->whereNull('author_id')
            ->get();

        $this->command->info("Found {$stories->count()} stories with author data to migrate.");

        $createdAuthors = 0;
        $updatedStories = 0;

        foreach ($stories as $story) {
            $authorName = trim($story->author);
            
            if (empty($authorName)) {
                continue;
            }

            // Tìm hoặc tạo author
            $author = Author::where('name', $authorName)->first();
            
            if (!$author) {
                // Tạo author mới
                $author = Author::create([
                    'name' => $authorName,
                    'slug' => Str::slug($authorName),
                    'bio' => "Tác giả của truyện \"{$story->title}\" và nhiều tác phẩm khác.",
                    'is_active' => true,
                    'meta_title' => $authorName . ' - Tác giả truyện audio',
                    'meta_description' => "Tìm hiểu về tác giả {$authorName}. Đọc và nghe truyện audio của {$authorName} tại Audio Lara.",
                    'meta_keywords' => $authorName . ', tác giả, truyện audio, sách nói'
                ]);
                
                $createdAuthors++;
                $this->command->info("Created author: {$authorName}");
            }

            // Cập nhật story với author_id
            $story->update(['author_id' => $author->id]);
            $updatedStories++;
            
            $this->command->line("Linked story '{$story->title}' to author '{$authorName}'");
        }

        $this->command->info("Migration completed!");
        $this->command->info("- Created {$createdAuthors} new authors");
        $this->command->info("- Updated {$updatedStories} stories with author links");
        
        // Hiển thị thống kê
        $totalAuthors = Author::count();
        $storiesWithAuthors = Story::whereNotNull('author_id')->count();
        $storiesWithoutAuthors = Story::whereNull('author_id')->count();
        
        $this->command->info("\nCurrent statistics:");
        $this->command->info("- Total authors: {$totalAuthors}");
        $this->command->info("- Stories with authors: {$storiesWithAuthors}");
        $this->command->info("- Stories without authors: {$storiesWithoutAuthors}");
    }
}
