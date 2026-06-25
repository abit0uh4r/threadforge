<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Campaign> */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'target_audience' => 'tech community',
            'tone' => 'professional but relaxed',
            'max_length' => 280,
            'max_hashtags' => 1,
            'rules' => ['No buzzwords', 'Always cite sources', 'One CTA max'],
        ];
    }
}
