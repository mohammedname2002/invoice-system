<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Demo Admin',
            'password' => Hash::make('password'),
            'email' => 'admin@example.com',
        ]);
    }
}
