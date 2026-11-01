<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $user = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('password123'),
                'phone_number' => '08137889172',
                'role' => 'admin',
            ]
        );

        Log::info('user created');
        $this->command?->info("User {$user->email} berhasil dibuat!");
    }
}
