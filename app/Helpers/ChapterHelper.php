<?php

namespace App\Helpers;

class ChapterHelper
{
    /**
     * Parse chapter numbers từ input string
     * VD: "1,3,5-10,15" => [1,3,5,6,7,8,9,10,15]
     */
    public static function parseChapterNumbers($input)
    {
        $chapters = [];
        $parts = explode(',', $input);

        foreach ($parts as $part) {
            $part = trim($part);

            if (strpos($part, '-') !== false) {
                // Xử lý range: 5-10
                list($start, $end) = explode('-', $part, 2);
                $start = (int) trim($start);
                $end = (int) trim($end);

                if ($start > 0 && $end > 0 && $start <= $end) {
                    for ($i = $start; $i <= $end; $i++) {
                        $chapters[] = $i;
                    }
                }
            } else {
                // Xử lý single number
                $num = (int) $part;
                if ($num > 0) {
                    $chapters[] = $num;
                }
            }
        }

        return array_unique($chapters);
    }

    /**
     * Format file size to human readable
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get content type counts for a story
     */
    public static function getContentTypeCounts($storyId)
    {
        $totalChapters = \App\Models\Chapter::where('story_id', $storyId)->count();
        
        // Chapters có text content
        $textChapters = \App\Models\Chapter::where('story_id', $storyId)
            ->where(function($q) {
                $q->whereNotNull('content')
                  ->where('content', '!=', '')
                  ->orWhere(function($subQ) {
                      $subQ->whereNotNull('file_path')
                           ->where('file_path', '!=', '');
                  });
            })->count();
            
        // Chapters có audio
        $audioChapters = \App\Models\Chapter::where('story_id', $storyId)
            ->where('audio_status', 'done')
            ->whereNotNull('audio_file_path')
            ->where('audio_file_path', '!=', '')
            ->count();
            
        // Chapters có video
        $videoChapters = \App\Models\Chapter::where('story_id', $storyId)
            ->whereHas('video', function($q) {
                $q->where('render_status', 'done')
                  ->whereNotNull('file_path')
                  ->where('file_path', '!=', '');
            })->count();
            
        // Chapters không có content
        $noContentChapters = \App\Models\Chapter::where('story_id', $storyId)
            ->where(function($q) {
                $q->where('content', '')
                  ->orWhereNull('content');
            })->where(function($q) {
                $q->where('file_path', '')
                  ->orWhereNull('file_path');
            })->count();
        
        return [
            'all' => $totalChapters,
            'text' => $textChapters,
            'audio' => $audioChapters,
            'video' => $videoChapters,
            'no_content' => $noContentChapters,
        ];
    }
}
