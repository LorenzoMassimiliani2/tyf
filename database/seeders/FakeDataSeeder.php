<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class FakeDataSeeder extends Seeder
{
    /**
     * Seed demo categories and challenges.
     */
    public function run(): void
    {
        $faker = Faker::create('it_IT');

        // Create a handful of playful categories with colors.
        $palette = ['#f97316', '#22c55e', '#3b82f6', '#a855f7', '#facc15', '#ef4444'];
        $names = ['Mimo', 'Quiz Lampo', 'Musica', 'Disegno', 'Parole Pazze', 'Sfide Fisiche', 'Improvvisazione'];

        $categories = collect($names)->map(function ($name, $idx) use ($palette) {
            return Category::firstOrCreate(
                ['name' => $name],
                [
                    'color' => $palette[$idx % count($palette)],
                    'is_active' => true,
                ]
            );
        });

        // Generate random challenges for each level 1-5.
        foreach (range(1, 30) as $_) {
            Challenge::create([
                'category_id' => $categories->random()->id,
                'title' => ucfirst($faker->words(3, true)),
                'description' => $faker->sentence(12),
                'level' => $faker->numberBetween(1, 5),
                'is_active' => true,
            ]);
        }
    }
}
