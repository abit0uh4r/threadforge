<?php

namespace App\Ai\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPostHistory implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Retrieve the full history and previous versions of a specific generated post from the database. Use this when the user asks about what the post looked like before, its previous versions, or its current content.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $postId = $request['postId'];

        $post = Post::with('campaign:id,name', 'rawContent:id,content,source_type')->find($postId);

        if (! $post) {
            return "Post with ID {$postId} not found.";
        }

        $bodyPoints = collect($post->body_points)->map(fn ($p, $i) => '  '.($i + 1).". {$p}")->implode(PHP_EOL);
        $hashtags = collect($post->suggested_hashtags)->implode(', ');

        return <<<TEXT
        Post History (ID: {$post->id}, Version: {$post->version}):
        Status: {$post->status}
        Campaign: {$post->campaign?->name}
        Hook: {$post->hook}
        Body points:
        {$bodyPoints}
        Hashtags: {$hashtags}
        Readability score: {$post->readability_score}/100
        Tone justification: {$post->tone_justification}

        Original raw content:
        {$post->rawContent?->content}
        TEXT;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'postId' => $schema->integer()->required(),
        ];
    }
}
