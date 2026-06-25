<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\Ghostwriter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Http\Resources\ChatResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

/**
 * @group Chat
 *
 * Assistant conversationnel (Ghostwriter) pour retravailler les posts générés.
 */
class ChatController extends Controller
{
    /**
     * Discuter avec le Ghostwriter
     *
     * Pose une question en langage naturel sur un post généré. L'assistant
     * utilise des tools PHP pour interroger la base de données et maintient
     * la mémoire de la conversation via son ID.
     *
     * @authenticated
     *
     * @urlParam id integer required ID du post à discuter. Example: 1
     *
     * @bodyParam message string required Le message/question pour l'assistant. Example: Donne-moi 3 variantes plus agressives pour le hook
     * @bodyParam conversation_id string ID de conversation existante pour continuer le contexte (optionnel). Example: conv_abc123
     *
     * @response 200 {"data": {"response": "Here are 3 more aggressive variants...", "conversation_id": "conv_abc123"}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"message": ["The message field is required."]}}
     */
    public function chat(StoreChatRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $post = Post::whereHas('rawContent', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($id);

        $agent = new Ghostwriter($post);

        $agent = $agent->forUser($user);

        if ($request->filled('conversation_id')) {
            $agent = $agent->continue($request->conversation_id, as: $user);
        }

        $response = $agent->prompt($request->message);

        return (new ChatResource(
            response: (string) $response,
            conversationId: $response->conversationId ?? null
        ))->response()->setStatusCode(200);
    }
}
