<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Story;
use App\Models\Genre;
use Illuminate\Support\Str;
use App\Helpers\SlugHelper;

class GenerateSlugsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Generate slugs for stories
        $stories = Story::whereNull('slug')->orWhere('slug', '')->get();
        foreach ($stories as $story) {
            $slug = SlugHelper::createUniqueSlug($story->title, Story::class, $story->id);
            $story->update(['slug' => $slug]);
            $this->command->info("Generated slug for story: {$story->title} -> {$slug}");
        }

        // Generate slugs for genres
        $genres = Genre::whereNull('slug')->orWhere('slug', '')->get();
        foreach ($genres as $genre) {
            $slug = SlugHelper::createUniqueSlug($genre->name, Genre::class, $genre->id);
            $genre->update(['slug' => $slug]);
            $this->command->info("Generated slug for genre: {$genre->name} -> {$slug}");
        }
    }
}
