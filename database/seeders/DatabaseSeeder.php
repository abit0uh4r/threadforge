<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Post;
use App\Models\RawContent;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Creator',
            'email' => 'demo@threadforge.dev',
        ]);

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Tech Twitter Pro',
            'target_audience' => 'developer community on X',
            'tone' => 'professional but relaxed, witty',
            'max_length' => 280,
            'max_hashtags' => 1,
            'rules' => [
                'No buzzwords like "synergy" or "revolutionary"',
                'Always cite the source or library',
                'One clear CTA at the end',
                'No emoji spam — max 2 emoji per post',
            ],
        ]);

        $rawContent = RawContent::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'content' => <<<'MARKDOWN'
            # Laravel 11 Queue worker improvements

            Laravel 11 ships with a rewritten queue worker that supports a `--max_jobs`
            flag to limit the number of jobs processed before restarting.

            ## Key changes:
            - New `--max_jobs` flag: restart worker after N jobs
            - New `--max_time` flag: restart worker after N seconds
            - Better memory leak prevention via graceful restarts
            - `queue:listen` now proxies to `queue:work` internally

            This is especially useful for long-running workers in production
            where memory leaks from third-party packages can accumulate.

            Source: https://github.com/laravel/framework/pull/50234
            MARKDOWN,
            'source_type' => 'markdown',
            'status' => 'completed',
        ]);

        Post::create([
            'campaign_id' => $campaign->id,
            'raw_content_id' => $rawContent->id,
            'hook' => 'Laravel 11 quietly shipped a queue worker rewrite that fixes the #1 memory leak pain point.',
            'body_points' => [
                'New --max_jobs flag: auto-restart after N jobs processed',
                'New --max_time flag: auto-restart after N seconds',
                'Graceful restarts prevent memory leaks from 3rd-party packages',
                'queue:listen now proxies to queue:work internally',
            ],
            'readability_score' => 82,
            'suggested_hashtags' => ['#laravel'],
            'tone_justification' => 'Professional but relaxed tone maintained. No buzzwords used. Source cited implicitly via PR link context. CTA implied by the actionable nature of the points.',
            'status' => 'draft',
            'version' => 1,
        ]);
    }
}
