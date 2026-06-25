<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Post;
use App\Models\RawContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'raw_content_id' => RawContent::factory(),
            'hook' => fake()->sentence(),
            'body_points' => [fake()->sentence(), fake()->sentence(), fake()->sentence()],
            'readability_score' => fake()->numberBetween(50, 95),
            'suggested_hashtags' => ['#laravel', '#php'],
            'tone_justification' => fake()->paragraph(),
            'status' => 'draft',
            'version' => 1,
        ];
    }
}
