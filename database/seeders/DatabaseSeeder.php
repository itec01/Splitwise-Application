<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Seeding Order
        |--------------------------------------------------------------------------
        |
        | Users
        |   ↓
        | Groups
        |   ↓
        | Expenses
        |   ↓
        | Settlements
        |
        */

        $this->call([
            UserSeeder::class,
            GroupSeeder::class,
            ExpenseSeeder::class,
            SettlementSeeder::class,
        ]);
    }
}