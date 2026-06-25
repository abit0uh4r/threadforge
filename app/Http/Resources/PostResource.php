<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'raw_content_id' => $this->raw_content_id,
            'hook' => $this->hook,
            'body_points' => $this->body_points,
            'readability_score' => $this->readability_score,
            'suggested_hashtags' => $this->suggested_hashtags,
            'tone_justification' => $this->tone_justification,
            'status' => $this->status,
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}