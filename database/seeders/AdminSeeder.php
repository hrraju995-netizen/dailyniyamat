<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Masala Valley primary admin
        User::updateOrCreate(
            ['email' => 'admin@masalavalley.com'],
            [
                'name'     => 'Masala Valley Admin',
                'password' => Hash::make('Masalavalley@1919'),
                'is_admin' => true,
                'phone'    => '01700000000',
            ]
        );

        // Daily Niyamat primary admin
        User::updateOrCreate(
            ['email' => 'admin@dailyniyamat.com'],
            [
                'name'     => 'Daily Niyamat Admin',
                'password' => Hash::make('raju@1919'),
                'is_admin' => true,
                'phone'    => '01700000000',
            ]
        );

        // Secondary admin
        User::updateOrCreate(
            ['email' => 'admin@shuvo.com'],
            [
                'name'     => 'Shuvo Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'phone'    => '01700000000',
            ]
        );
    }
}
