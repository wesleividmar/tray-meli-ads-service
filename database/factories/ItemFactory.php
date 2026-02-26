<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'item_id' => 'MLB' . $this->faker->unique()->numerify('#########'),
            'seller_id' => 252254392,
            'title' => $this->faker->sentence(6),
            'status' => 'active',
            'sync_status' => 'synced',
            'raw' => ['id' => 'x'],
            'fetched_at' => now(),
            'synced_at' => now(),
        ];
    }
}