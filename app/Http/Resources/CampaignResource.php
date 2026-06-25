<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Campaign */
class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_audience' => $this->target_audience,
            'tone' => $this->tone,
            'max_length' => $this->max_length,
            'max_hashtags' => $this->max_hashtags,
            'rules' => $this->rules,
            'posts_count' => $this->whenCounted('posts', fn () => $this->posts_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}