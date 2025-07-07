<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Story;
use App\Models\Genre;
use Illuminate\Support\Str;

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
            $slug = Str::slug($story->title);
            $originalSlug = $slug;
            $counter = 1;

            // Ensure unique slug
            while (Story::where('slug', $slug)->where('id', '!=', $story->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $story->update(['slug' => $slug]);
            $this->command->info("Generated slug for story: {$story->title} -> {$slug}");
        }

        // Generate slugs for genres
        $genres = Genre::whereNull('slug')->orWhere('slug', '')->get();
        foreach ($genres as $genre) {
            $slug = Str::slug($genre->name);
            $originalSlug = $slug;
            $counter = 1;

            // Ensure unique slug
            while (Genre::where('slug', $slug)->where('id', '!=', $genre->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $genre->update(['slug' => $slug]);
            $this->command->info("Generated slug for genre: {$genre->name} -> {$slug}");
        }
    }
}
