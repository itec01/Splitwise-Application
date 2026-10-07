<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'group_id' => null,

            'description' => fake()->randomElement([
                'Dinner',
                'Lunch',
                'Hotel',
                'Groceries',
                'Transport',
                'Coffee',
                'Shopping',
                'Movie',
            ]),

            'amount' => fake()->randomFloat(
                2,
                20,
                500
            ),

            'paid_by' => null,

            'split_type' => 'equal',

            'participants' => [],
        ];
    }
}