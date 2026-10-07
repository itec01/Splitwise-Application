<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Group;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $weekendTrip = Group::where(
            'name',
            'Weekend Trip'
        )->first();

        $officeTeam = Group::where(
            'name',
            'Office Team'
        )->first();

        $friends = Group::where(
            'name',
            'Friends'
        )->first();

        if (! $weekendTrip || ! $officeTeam || ! $friends) {
            throw new \RuntimeException(
                'Required groups were not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Weekend Trip - Equal Split
        |--------------------------------------------------------------------------
        */

        $members = $weekendTrip->member_ids;

        ExpenseFactory::new()->create([
            'group_id' => (string) $weekendTrip->getKey(),

            'description' => 'Hotel',

            'amount' => 120.00,

            'paid_by' => (string) $members[0],

            'split_type' => 'equal',

            'participants' => [
                [
                    'user_id' => (string) $members[0],
                    'amount' => 40.00,
                ],
                [
                    'user_id' => (string) $members[1],
                    'amount' => 40.00,
                ],
                [
                    'user_id' => (string) $members[2],
                    'amount' => 40.00,
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Weekend Trip - Exact Split
        |--------------------------------------------------------------------------
        */

        ExpenseFactory::new()->create([
            'group_id' => (string) $weekendTrip->getKey(),

            'description' => 'Dinner',

            'amount' => 100.00,

            'paid_by' => (string) $members[1],

            'split_type' => 'exact',

            'participants' => [
                [
                    'user_id' => (string) $members[0],
                    'amount' => 30.00,
                ],
                [
                    'user_id' => (string) $members[1],
                    'amount' => 30.00,
                ],
                [
                    'user_id' => (string) $members[2],
                    'amount' => 40.00,
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Office Team - Percentage Split
        |--------------------------------------------------------------------------
        */

        $members = $officeTeam->member_ids;

        ExpenseFactory::new()->create([
            'group_id' => (string) $officeTeam->getKey(),

            'description' => 'Office Lunch',

            'amount' => 200.00,

            'paid_by' => (string) $members[0],

            'split_type' => 'percentage',

            'participants' => [
                [
                    'user_id' => (string) $members[0],
                    'percentage' => 25,
                    'amount' => 50.00,
                ],
                [
                    'user_id' => (string) $members[1],
                    'percentage' => 25,
                    'amount' => 50.00,
                ],
                [
                    'user_id' => (string) $members[2],
                    'percentage' => 25,
                    'amount' => 50.00,
                ],
                [
                    'user_id' => (string) $members[3],
                    'percentage' => 25,
                    'amount' => 50.00,
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Friends - Equal Split
        |--------------------------------------------------------------------------
        */

        $members = $friends->member_ids;

        ExpenseFactory::new()->create([
            'group_id' => (string) $friends->getKey(),

            'description' => 'Movie',

            'amount' => 90.00,

            'paid_by' => (string) $members[1],

            'split_type' => 'equal',

            'participants' => [
                [
                    'user_id' => (string) $members[0],
                    'amount' => 30.00,
                ],
                [
                    'user_id' => (string) $members[1],
                    'amount' => 30.00,
                ],
                [
                    'user_id' => (string) $members[2],
                    'amount' => 30.00,
                ],
            ],
        ]);
    }
}