<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_rejects_unauthenticated_request_with_401(): void
    {
        $response = $this->getJson('/api/campaigns');

        $response->assertStatus(401);
    }

    public function test_index_returns_only_authenticated_user_campaigns(): void
    {
        $user = User::factory()->create();
        Campaign::factory()->count(3)->create(['user_id' => $user->id]);
        Campaign::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/campaigns');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'target_audience', 'tone', 'max_length', 'max_hashtags', 'rules', 'posts_count', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_store_creates_campaign_with_validated_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/campaigns', [
            'name' => 'Tech Twitter',
            'target_audience' => 'dev community',
            'tone' => 'pro but casual',
            'max_length' => 280,
            'max_hashtags' => 1,
            'rules' => ['No buzzwords', 'Cite sources'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Tech Twitter')
            ->assertJsonPath('data.rules', ['No buzzwords', 'Cite sources']);
    }

    public function test_store_rejects_empty_name_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/campaigns', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_returns_campaign_with_posts_count(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/campaigns/{$campaign->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $campaign->id)
            ->assertJsonPath('data.posts_count', 0);
    }

    public function test_show_rejects_other_user_campaign_with_404(): void
    {
        $user = User::factory()->create();
        $otherCampaign = Campaign::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/campaigns/{$otherCampaign->id}");

        $response->assertStatus(404);
    }
}
