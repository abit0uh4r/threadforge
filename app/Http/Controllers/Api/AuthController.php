<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentification
 *
 * APIs pour la gestion de l'authentification (inscription, connexion, déconnexion).
 */
class AuthController extends Controller
{
    /**
     * Inscription
     *
     * Crée un nouveau compte créateur et retourne un Bearer Token d'accès.
     *
     * @unauthenticated
     * @bodyParam name string required Nom du créateur. Example: Jane Doe
     * @bodyParam email string required Adresse email unique. Example: jane@example.com
     * @bodyParam password string required Mot de passe (min 8 caractères). Example: secret123
     * @response 201 {
     *   "data": {"id": 1, "name": "Jane Doe", "email": "jane@example.com", "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"},
     *   "token": "1|abcdef123456..."
     * }
     * @response 422 {"message": "The given data was invalid.", "errors": {"email": ["The email has already been taken."]}}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $token = $user->createToken('api-token')->plainTextToken;

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Connexion
     *
     * Authentifie un créateur et retourne un Bearer Token d'accès.
     *
     * @unauthenticated
     * @bodyParam email string required Adresse email. Example: jane@example.com
     * @bodyParam password string required Mot de passe. Example: secret123
     * @bodyParam device_name string Nom de l'appareil (optionnel). Example: iPhone 15
     * @response 200 {"data": {"id": 1, "name": "Jane Doe", "email": "jane@example.com", "created_at": "2026-06-25T12:00:00+00:00", "updated_at": "2026-06-25T12:00:00+00:00"}, "token": "1|abcdef123456..."}
     * @response 422 {"message": "The given data was invalid.", "errors": {"email": ["These credentials do not match our records."]}}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();
        $token = $user->createToken($request->device_name ?? 'api-token')->plainTextToken;

        return (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Déconnexion
     *
     * Révoque le token courant.
     *
     * @authenticated
     * @response 200 {"message": "Logged out"}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out'], 200);
    }
}