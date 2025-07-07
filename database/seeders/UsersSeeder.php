<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('Creating 500 users...');

        // Disable mass assignment protection
        User::unguard();

        // Use chunking to avoid memory issues
        $chunkSize = 100;
        $totalUsers = 500;

        for ($i = 0; $i < $totalUsers; $i += $chunkSize) {
            $remaining = min($chunkSize, $totalUsers - $i);

            $this->command->info("Creating users batch: " . ($i + 1) . " to " . ($i + $remaining));

            // Create users in chunks
            $users = User::factory($remaining)->create();

            // Progress indicator
            $progress = round((($i + $remaining) / $totalUsers) * 100, 1);
            $this->command->info("Progress: {$progress}% (" . ($i + $remaining) . "/{$totalUsers})");
        }

        // Re-enable mass assignment protection
        User::reguard();

        $this->command->info('✅ Successfully created 500 users!');
    }
}
