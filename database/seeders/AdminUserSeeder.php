<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo admin user mặc định
        User::updateOrCreate(
            ['email' => 'admin@audiolara.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@audiolara.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Tạo user demo
        User::updateOrCreate(
            ['email' => 'user@audiolara.com'],
            [
                'name' => 'Demo User',
                'email' => 'user@audiolara.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin users created successfully!');
        $this->command->info('Admin: admin@audiolara.com / admin123');
        $this->command->info('User: user@audiolara.com / user123');
    }
}
