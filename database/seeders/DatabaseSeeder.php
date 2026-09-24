<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'name' => 'Demo Accountant',
            'email' => 'accountant@example.com',
            'role' => UserRole::Accountant,
        ]);

        User::factory()->create([
            'name' => 'Demo Viewer',
            'email' => 'viewer@example.com',
            'role' => UserRole::Viewer,
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
