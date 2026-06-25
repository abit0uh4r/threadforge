<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/health', fn () => response()->json(['status' => 'ok']));
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('campaigns', CampaignController::class)->only(['index', 'store', 'show']);
});