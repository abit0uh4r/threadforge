<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Campaigns
 *
 * Gestion des Blueprints de campagne (règles de style réutilisables).
 */
class CampaignController extends Controller
{
    /**
     * Liste des Blueprints
     *
     * Retourne toutes les campagnes du créateur authentifié avec le nombre de posts générés.
     *
     * @authenticated
     * @response 200 {
     *   "data": [
     *     {"id": 1, "name": "Tech Twitter", "target_audience": "dev community", "tone": "pro but casual", "max_length": 280, "max_hashtags": 1, "rules": ["No buzzwords", "Always cite sources"], "posts_count": 5, "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->withCount('posts')
            ->latest()
            ->get();

        return CampaignResource::collection($campaigns)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Créer un Blueprint
     *
     * Définit les règles de style strictes pour les futures générations de posts.
     *
     * @authenticated
     * @bodyParam name string required Nom de la campagne. Example: Tech Twitter
     * @bodyParam target_audience string Audience cible. Example: dev community
     * @bodyParam tone string Ton éditorial. Example: pro but casual
     * @bodyParam max_length integer Longueur max du post (défaut: 280). Example: 280
     * @bodyParam max_hashtags integer Nombre max de hashtags (défaut: 1). Example: 1
     * @bodyParam rules string[] Règles additionnelles. Example: ["No buzzwords", "Always cite sources"]
     * @response 201 {"data": {"id": 1, "name": "Tech Twitter", "target_audience": "dev community", "tone": "pro but casual", "max_length": 280, "max_hashtags": 1, "rules": ["No buzzwords", "Always cite sources"], "posts_count": 0, "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"name": ["The name field is required."]}}
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $request->user()->campaigns()->create($request->validated());
        $campaign->loadCount('posts');

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Détail d'un Blueprint
     *
     * Retourne une campagne spécifique avec le nombre de posts générés.
     *
     * @authenticated
     * @urlParam id integer required ID de la campagne. Example: 1
     * @response 200 {"data": {"id": 1, "name": "Tech Twitter", "target_audience": "dev community", "tone": "pro but casual", "max_length": 280, "max_hashtags": 1, "rules": ["No buzzwords", "Always cite sources"], "posts_count": 5, "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}}
     * @response 404 {"message": "No query results for model [App\\Models\\Campaign]."}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->withCount('posts')
            ->findOrFail($id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(200);
    }
}