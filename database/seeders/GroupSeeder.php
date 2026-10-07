<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Database\Factories\GroupFactory;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 8) {
            throw new \RuntimeException(
                'At least 8 users are required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Weekend Trip
        |--------------------------------------------------------------------------
        */

        $weekendTripMembers = [
            (string) $users[0]->getKey(),
            (string) $users[1]->getKey(),
            (string) $users[2]->getKey(),
        ];

        GroupFactory::new()->create([
            'name' => 'Weekend Trip',

            'description' => 'Weekend trip shared expenses.',

            'owner_id' => (string) $users[0]->getKey(),

            'member_ids' => $weekendTripMembers,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Office Team
        |--------------------------------------------------------------------------
        */

        $officeTeamMembers = [
            (string) $users[3]->getKey(),
            (string) $users[4]->getKey(),
            (string) $users[5]->getKey(),
            (string) $users[6]->getKey(),
        ];

        GroupFactory::new()->create([
            'name' => 'Office Team',

            'description' => 'Office team shared expenses.',

            'owner_id' => (string) $users[3]->getKey(),

            'member_ids' => $officeTeamMembers,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Friends Group
        |--------------------------------------------------------------------------
        */

        $friendsMembers = [
            (string) $users[0]->getKey(),
            (string) $users[4]->getKey(),
            (string) $users[7]->getKey(),
        ];

        GroupFactory::new()->create([
            'name' => 'Friends',

            'description' => 'Friends shared expenses.',

            'owner_id' => (string) $users[4]->getKey(),

            'member_ids' => $friendsMembers,
        ]);
    }
}