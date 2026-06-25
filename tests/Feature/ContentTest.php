<?php

namespace Tests\Feature;

use App\Jobs\RepurposeContentJob;
use App\Models\Campaign;
use App\Models\Post;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_repurpose_returns_202_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/repurpose', [
            'campaign_id' => $campaign->id,
            'content' => 'Laravel 11 introduced lazy loading prevention. This is a note about the new features.',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'pending');

        Queue::assertPushed(RepurposeContentJob::class);
    }

    public function test_repurpose_rejects_empty_content_with_422(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/repurpose', [
            'campaign_id' => $campaign->id,
            'content' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_repurpose_rejects_nonexistent_campaign_with_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/repurpose', [
            'campaign_id' => 9999,
            'content' => 'Some valid content here.',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campaign_id']);
    }

    public function test_repurpose_rejects_other_user_campaign_with_404(): void
    {
        $user = User::factory()->create();
        $otherCampaign = Campaign::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/content/repurpose', [
            'campaign_id' => $otherCampaign->id,
            'content' => 'Content for someone elses campaign.',
        ]);

        $response->assertStatus(404);
    }

    public function test_posts_index_returns_only_authenticated_user_posts_with_eager_loading(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $rawContent = RawContent::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
        Post::factory()->create([
            'campaign_id' => $campaign->id,
            'raw_content_id' => $rawContent->id,
        ]);

        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->create(['user_id' => $otherUser->id]);
        $otherRaw = RawContent::factory()->create(['user_id' => $otherUser->id, 'campaign_id' => $otherCampaign->id]);
        Post::factory()->create([
            'campaign_id' => $otherCampaign->id,
            'raw_content_id' => $otherRaw->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_post_body_points_and_hashtags_are_native_php_arrays(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $rawContent = RawContent::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
        Post::factory()->create([
            'campaign_id' => $campaign->id,
            'raw_content_id' => $rawContent->id,
            'body_points' => ['Point 1', 'Point 2'],
            'suggested_hashtags' => ['#laravel', '#php'],
        ]);

        $post = Post::first();

        $this->assertIsArray($post->body_points);
        $this->assertIsArray($post->suggested_hashtags);
        $this->assertSame(['Point 1', 'Point 2'], $post->body_points);
        $this->assertSame(['#laravel', '#php'], $post->suggested_hashtags);
    }

    public function test_post_status_can_be_updated(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $rawContent = RawContent::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
        $post = Post::factory()->create([
            'campaign_id' => $campaign->id,
            'raw_content_id' => $rawContent->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/posts/{$post->id}/status", [
            'status' => 'posted',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'posted');
    }

    public function test_post_status_rejects_invalid_value_with_422(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $rawContent = RawContent::factory()->create(['user_id' => $user->id, 'campaign_id' => $campaign->id]);
        $post = Post::factory()->create([
            'campaign_id' => $campaign->id,
            'raw_content_id' => $rawContent->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/posts/{$post->id}/status", [
            'status' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
