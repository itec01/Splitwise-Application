<?php

namespace Database\Seeders;

use App\Models\Group;
use Database\Factories\SettlementFactory;
use Illuminate\Database\Seeder;

class SettlementSeeder extends Seeder
{
    public function run(): void
    {
        $group = Group::where(
            'name',
            'Weekend Trip'
        )->first();

        if (! $group) {
            throw new \RuntimeException(
                'Weekend Trip group was not found.'
            );
        }

        $members = $group->member_ids;

        /*
        |--------------------------------------------------------------------------
        | Hotel Expense
        |--------------------------------------------------------------------------
        |
        | Ali paid $120.
        |
        | Ali's share    = $40
        | Ahmed's share  = $40
        | Usman's share  = $40
        |
        | Ahmed owes Ali $40.
        |
        | We record a partial settlement of $20.
        |
        */

        SettlementFactory::new()->create([
            'group_id' => (string) $group->getKey(),

            'paid_by' => (string) $members[1],

            'paid_to' => (string) $members[0],

            'amount' => 20.00,

            'note' => 'Partial settlement for hotel expense.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Second Settlement
        |--------------------------------------------------------------------------
        |
        | Usman pays Ali another $20.
        |
        */

        SettlementFactory::new()->create([
            'group_id' => (string) $group->getKey(),

            'paid_by' => (string) $members[2],

            'paid_to' => (string) $members[0],

            'amount' => 20.00,

            'note' => 'Hotel expense settlement.',
        ]);
    }
}