<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Ali Khan',
                'email' => 'ali@example.com',
            ],
            [
                'name' => 'Ahmed Raza',
                'email' => 'ahmed@example.com',
            ],
            [
                'name' => 'Usman Ali',
                'email' => 'usman@example.com',
            ],
            [
                'name' => 'Hamza Malik',
                'email' => 'hamza@example.com',
            ],
            [
                'name' => 'Bilal Ahmed',
                'email' => 'bilal@example.com',
            ],
            [
                'name' => 'Hassan Shah',
                'email' => 'hassan@example.com',
            ],
            [
                'name' => 'Owais Khan',
                'email' => 'owais@example.com',
            ],
            [
                'name' => 'Zain Ahmed',
                'email' => 'zain@example.com',
            ],
        ];

        foreach ($users as $user) {
            UserFactory::new()->create($user);
        }
    }
}