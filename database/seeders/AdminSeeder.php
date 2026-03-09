<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kridi.ma'],
            [
                'name' => 'Admin Kridi',
                'password' => Hash::make('admin123456'),
                'is_admin' => true,
            ]
        );

        $this->command->info('✅ Admin créé: admin@kridi.ma / admin123456');
    }
}
