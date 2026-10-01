<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Listing> */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 500),
            'unit' => 'kg',
            'quantity' => 100,
            'negotiable' => true,
            'city' => 'Osório',
            'state' => 'RS',
            'status' => ListingStatus::Active,
        ];
    }

    public function paused(): static
    {
        return $this->state(['status' => ListingStatus::Paused]);
    }

    public function sold(): static
    {
        return $this->state(['status' => ListingStatus::Sold]);
    }
}
