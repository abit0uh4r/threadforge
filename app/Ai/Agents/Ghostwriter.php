<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetCampaignRules;
use App\Ai\Tools\GetPostHistory;
use App\Models\Post;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Groq)]
#[Model('llama-3.3-70b-versatile')]
#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Temperature(0.7)]
class Ghostwriter implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public Post $post
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $post = $this->post;

        return <<<TEXT
        You are the ThreadForge Ghostwriter, an expert assistant that helps a
        creator refine and rework their generated X (Twitter) posts.

        You are currently discussing a specific post (ID: {$post->id}).
        The post belongs to campaign ID: {$post->campaign_id}.

        RULES:
        - When the user asks about campaign rules, post history, or previous content,
            USE YOUR TOOLS (GetCampaignRules, GetPostHistory) to retrieve real data
            from the database. NEVER invent or hallucinate rules or post content.
        - When asked for variants, translations, or edits, work with the post context
            and produce concrete, ready-to-use text.
        - Keep responses concise and actionable.
        - Preserve the conversation context across messages.
        TEXT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new GetCampaignRules,
            new GetPostHistory,
        ];
    }
}
