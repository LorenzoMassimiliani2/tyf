<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
   public function run(): void
    {
        // === ADMIN USER ===
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Org.123456'), // cambia subito
                'email_verified_at' => now(),
            ]
        );

        // === CATEGORIES ===
        $categories = [
            ['name' => 'Mimo', 'color' => '#f97316'],
            ['name' => 'Quiz Lampo', 'color' => '#22c55e'],
            ['name' => 'Musica', 'color' => '#3b82f6'],
        ];

        foreach ($categories as $data) {
            $category = Category::updateOrCreate(
                ['name' => $data['name']],
                [
                    'color' => $data['color'],
                    'is_active' => true,
                ]
            );

            // === SAMPLE CHALLENGE ===
            Challenge::updateOrCreate(
                [
                    'title' => 'Sfida demo ' . $data['name'],
                    'category_id' => $category->id,
                ],
                [
                    'description' => 'Sfida di esempio',
                    'level' => 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
