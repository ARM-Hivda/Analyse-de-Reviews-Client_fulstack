<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AnalyzeController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// Routes publiques (authentification)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Reviews CRUD
    Route::apiResource('reviews', ReviewController::class);

    // Analyse IA
    Route::post('/analyze', [AnalyzeController::class, 'analyze']);

    // Dashboard / Statistiques
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
});


