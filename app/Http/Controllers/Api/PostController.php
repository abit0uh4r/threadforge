<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Posts
 *
 * Gestion du cycle de vie des posts générés (draft, archived, posted).
 */
class PostController extends Controller
{
    /**
     * Liste des posts
     *
     * Retourne tous les posts générés par le créateur, avec eager loading.
     *
     * @authenticated
     * @queryParam status string Filtrer par statut (draft, archived, posted). Example: draft
     * @response 200 {"data": [{"id": 1, "campaign_id": 1, "raw_content_id": 1, "hook": "Laravel 11 ships with...", "body_points": ["Point 1", "Point 2"], "readability_score": 85, "suggested_hashtags": ["#laravel"], "tone_justification": "...", "status": "draft", "version": 1, "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}]}
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()
            ->whereHas('rawContent', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with(['campaign:id,name', 'rawContent:id,source_type,status']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->latest()->get();

        return PostResource::collection($posts)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Détail d'un post
     *
     * @authenticated
     * @urlParam id integer required ID du post. Example: 1
     * @response 200 {"data": {"id": 1, "campaign_id": 1, "raw_content_id": 1, "hook": "Laravel 11 ships with...", "body_points": ["Point 1", "Point 2"], "readability_score": 85, "suggested_hashtags": ["#laravel"], "tone_justification": "...", "status": "draft", "version": 1, "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}}
     * @response 404 {"message": "No query results for model [App\\Models\\Post]."}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $post = Post::whereHas('rawContent', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })
            ->with(['campaign:id,name', 'rawContent:id,source_type,status'])
            ->findOrFail($id);

        return (new PostResource($post))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Changer le statut d'un post
     *
     * Met à jour le cycle de vie du post (draft, archived, posted).
     *
     * @authenticated
     * @urlParam id integer required ID du post. Example: 1
     * @bodyParam status string required Nouveau statut (draft, archived, posted). Example: posted
     * @response 200 {"data": {"id": 1, "campaign_id": 1, "raw_content_id": 1, "hook": "...", "body_points": [], "readability_score": 85, "suggested_hashtags": [], "tone_justification": "...", "status": "posted", "version": 1, "created_at": "...", "updated_at": "..."}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"status": ["The selected status is invalid."]}}
     */
    public function updateStatus(UpdatePostStatusRequest $request, int $id): JsonResponse
    {
        $post = Post::whereHas('rawContent', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($id);

        $post->update(['status' => $request->status]);

        return (new PostResource($post->fresh(['campaign:id,name', 'rawContent:id,source_type,status'])))
            ->response()
            ->setStatusCode(200);
    }
}