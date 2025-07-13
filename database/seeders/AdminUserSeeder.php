<?php

namespace Database\Seeders;

use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'sule.aktious@gmail.com'],
            [
                'name' => 'akatious',
                'password' => bcrypt('22Secured'),
                'role' => 'superadmin',
            ]
        );
    }
}
