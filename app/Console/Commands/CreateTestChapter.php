<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chapter;

class CreateTestChapter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-chapter {story_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test chapter for TTS testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storyId = $this->argument('story_id');

        $chapter = Chapter::create([
            'story_id' => $storyId,
            'chapter_number' => 9999,
            'title' => 'Test Chapter for TTS',
            'content' => 'Đây là nội dung test cho chức năng chuyển đổi text to speech. Nội dung này sẽ được sử dụng để kiểm tra tính năng TTS.',
            'audio_status' => 'pending'
        ]);

        $this->info("Created test chapter with ID: {$chapter->id}");

        return 0;
    }
}
