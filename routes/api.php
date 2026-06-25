<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RawContentController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('campaigns', CampaignController::class)->only(['index', 'store', 'show']);

    Route::post('/content/repurpose', [RawContentController::class, 'repurpose']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::patch('/posts/{id}/status', [PostController::class, 'updateStatus']);
});