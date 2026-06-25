<?php

namespace App\Http\Resources;

use App\Models\RawContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RawContent */
class RawContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign_id' => $this->campaign_id,
            'content' => $this->content,
            'source_type' => $this->source_type,
            'status' => $this->status,
            'error' => $this->when($this->status === 'failed', $this->error),
            'posts' => PostResource::collection($this->whenLoaded('posts')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
