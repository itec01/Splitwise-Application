<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(
                fake()->numberBetween(1, 3),
                true
            ),

            'description' => fake()->sentence(),

            'owner_id' => null,

            'member_ids' => [],
        ];
    }
}