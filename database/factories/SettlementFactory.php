<?php

namespace Database\Factories;

use App\Models\Settlement;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        return [
            'group_id' => null,

            'paid_by' => null,

            'paid_to' => null,

            'amount' => 0,

            'note' => fake()->sentence(),
        ];
    }
}