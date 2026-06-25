<?php

namespace App\Ai\Tools;

use App\Models\Campaign;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCampaignRules implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Retrieve the style rules and constraints of a specific campaign (Blueprint) from the database. Use this when the user asks about their current campaign rules, tone, max length, or max hashtags.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $campaignId = $request['campaignId'];

        $campaign = Campaign::find($campaignId);

        if (! $campaign) {
            return "Campaign with ID {$campaignId} not found.";
        }

        $rules = collect($campaign->rules ?? [])->map(fn ($r) => "  - {$r}")->implode(PHP_EOL);

        return <<<TEXT
        Campaign Blueprint "{$campaign->name}" (ID: {$campaign->id}):
        - Target audience: {$campaign->target_audience}
        - Tone: {$campaign->tone}
        - Max length: {$campaign->max_length} characters
        - Max hashtags: {$campaign->max_hashtags}
        - Additional rules:
        {$rules}
        TEXT;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'campaignId' => $schema->integer()->required(),
        ];
    }
}
