<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EtudiantController;
use App\Http\Controllers\Api\V1\CoursController;


Route::prefix('v1')->group(function () {

    // Auth publiques
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);
    });

    
    Route::middleware('auth:sanctum')->group(function () {

        // Auth protégées
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me',      [AuthController::class, 'me']);
        });

        // CRUD Étudiants
        Route::apiResource('etudiants', EtudiantController::class);

        // Many-to-Many Étudiants ↔ Cours
        Route::prefix('etudiants/{etudiant}/cours')->group(function () {
            Route::post('/attach', [EtudiantController::class, 'attachCours']);
            Route::post('/detach', [EtudiantController::class, 'detachCours']);
            Route::post('/sync',   [EtudiantController::class, 'syncCours']);
        });

        // CRUD Cours
        Route::apiResource('cours', CoursController::class)
             ->parameters(['cours' => 'cours']);

    });
    
});

// Route de test
Route::get('/ping', fn() => response()->json(['status' => 'ok']));