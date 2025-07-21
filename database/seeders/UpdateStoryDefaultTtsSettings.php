<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Story;
use Illuminate\Support\Facades\DB;

class UpdateStoryDefaultTtsSettings extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating default TTS settings for all stories...');
        
        // Update all stories with default TTS settings
        $stories = Story::all();
        $count = 0;
        
        foreach ($stories as $story) {
            $updates = [];
            
            // Set default TTS voice if not set
            if (empty($story->default_tts_voice)) {
                $updates['default_tts_voice'] = 'hn_female_ngochuyen_full_48k-fhg';
            }
            
            // Set default TTS bitrate if not set
            if (empty($story->default_tts_bitrate)) {
                $updates['default_tts_bitrate'] = 128;
            }
            
            // Set default TTS speed if not set
            if (empty($story->default_tts_speed)) {
                $updates['default_tts_speed'] = 1.0;
            }
            
            // Set default TTS volume if not set
            if (empty($story->default_tts_volume)) {
                $updates['default_tts_volume'] = 1.0;
            }
            
            // Update story if there are changes
            if (!empty($updates)) {
                $story->update($updates);
                $count++;
            }
        }
        
        $this->command->info("Updated {$count} stories with default TTS settings.");
    }
}
