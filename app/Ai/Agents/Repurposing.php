<?php

namespace App\Ai\Agents;

use App\Models\Campaign;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-20b')]
#[MaxTokens(2048)]
#[Temperature(0.7)]
class Repurposing implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public Campaign $campaign
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $c = $this->campaign;

        $rulesText = collect($c->rules ?? ['No specific additional rules.'])
            ->map(fn ($r) => "- {$r}")
            ->implode(PHP_EOL);

        return <<<TEXT
        You are ThreadForge, an expert repurposing engine that transforms raw tech
        content (notes, blog markdown, GitHub README) into punchy X (Twitter) posts.

        STRICT STYLE CONSTRAINTS (Campaign Blueprint "{$c->name}"):
        - Target audience: {$c->target_audience}
        - Tone: {$c->tone}
        - Maximum length: {$c->max_length} characters
        - Maximum hashtags: {$c->max_hashtags}

        Additional rules:
        {$rulesText}

        INSTRUCTIONS:
        1. Craft a compelling hook (hook_propose) — max 280 chars — that grabs attention.
        2. Extract 2-4 body_points — concise, actionable, one idea each.
        3. Rate the technical readability score (0-100, higher = clearer for non-experts).
        4. Suggest at most the allowed number of hashtags.
        5. Justify how the output complies with the tone constraints.
        TEXT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'hook_propose' => $schema->string()->required(),
            'body_points' => $schema->array()
                ->items($schema->string())
                ->required(),
            'technicalreadabilityscore' => $schema->integer()->min(0)->max(100)->required(),
            'suggested_hashtags' => $schema->array()
                ->items($schema->string())
                ->required(),
            'tonecompliancejustification' => $schema->string()->required(),
        ];
    }
}
