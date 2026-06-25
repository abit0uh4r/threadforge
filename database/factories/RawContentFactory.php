<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RawContent> */
class RawContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'content' => fake()->paragraphs(3, true),
            'source_type' => 'text',
            'status' => 'pending',
        ];
    }
}
