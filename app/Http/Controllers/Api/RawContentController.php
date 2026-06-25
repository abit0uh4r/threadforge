<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\RepurposeContentRequest;
use App\Http\Resources\RawContentResource;
use App\Jobs\RepurposeContentJob;
use App\Models\Campaign;
use App\Models\RawContent;
use Illuminate\Http\JsonResponse;

/**
 * @group Content
 *
 * Soumission et traitement du contenu brut pour génération de posts.
 */
class RawContentController extends Controller
{
    /**
     * Soumettre un contenu brut (Repurpose)
     *
     * Envoie un contenu textuel en vrac et déclenche la transformation asynchrone.
     * Retourne immédiatement 202 Accepted sans attendre la génération.
     *
     * @authenticated
     * @bodyParam campaign_id integer required ID de la campagne (Blueprint) à appliquer. Example: 1
     * @bodyParam content string required Contenu brut à transformer (min 10 caractères). Example: Laravel 11 introducedlazy loading prevention...
     * @bodyParam source_type string Type de source (text, markdown, readme). Example: markdown
     * @response 202 {"data": {"id": 1, "campaign_id": 1, "content": "Laravel 11 introduced...", "source_type": "markdown", "status": "pending", "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"content": ["The content field is required."]}}
     */
    public function repurpose(RepurposeContentRequest $request): JsonResponse
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($request->campaign_id);

        $rawContent = RawContent::create([
            'user_id' => $request->user()->id,
            'campaign_id' => $campaign->id,
            'content' => $request->content,
            'source_type' => $request->source_type ?? 'text',
            'status' => 'pending',
        ]);

        RepurposeContentJob::dispatch($rawContent);

        return (new RawContentResource($rawContent))
            ->response()
            ->setStatusCode(202);
    }
}