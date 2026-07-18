<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@ecedula.local'],
            [
                'name' => 'eCedula Admin',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'delivery@ecedula.local'],
            [
                'name' => 'Delivery Rider',
                'password' => 'password',
                'role' => User::ROLE_DELIVERY,
            ]
        );
    }
}
