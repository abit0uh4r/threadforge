<?php

namespace App\Jobs;

use App\Ai\Agents\Repurposing;
use App\Models\RawContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class RepurposeContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public RawContent $rawContent
    ) {}

    public function handle(): void
    {
        $raw = $this->rawContent;
        $raw->update(['status' => 'processing']);

        try {
            $response = (new Repurposing($raw->campaign))
                ->prompt($raw->content);

            $data = $response->toArray();

            $this->validateContract($data);

            $raw->posts()->create([
                'campaign_id' => $raw->campaign_id,
                'hook' => $data['hook_propose'],
                'body_points' => $data['body_points'],
                'readability_score' => $data['technicalreadabilityscore'],
                'suggested_hashtags' => $data['suggested_hashtags'],
                'tone_justification' => $data['tonecompliancejustification'],
                'status' => 'draft',
                'version' => 1,
            ]);

            $raw->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            $raw->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            Log::error('RepurposeContentJob failed', [
                'raw_content_id' => $raw->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate that the AI response respects the exact structured output contract.
     */
    private function validateContract(array $data): void
    {
        $validator = Validator::make($data, [
            'hook_propose' => ['required', 'string', 'max:10000'],
            'body_points' => ['required', 'array', 'min:1'],
            'body_points.*' => ['string'],
            'technicalreadabilityscore' => ['required', 'integer', 'between:0,100'],
            'suggested_hashtags' => ['required', 'array'],
            'suggested_hashtags.*' => ['string'],
            'tonecompliancejustification' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }
    }
}