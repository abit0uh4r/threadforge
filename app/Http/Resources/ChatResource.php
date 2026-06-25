<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function __construct(
        public string $response,
        public ?string $conversationId
    ) {
        parent::__construct(null);
    }

    public function toArray(Request $request): array
    {
        return [
            'response' => $this->response,
            'conversation_id' => $this->conversationId,
        ];
    }
}
