<?php

use App\Http\Controllers\Api\GameController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('games')->group(function () {
    Route::post('/', [GameController::class, 'store']);
    Route::post('/{code}/join', [GameController::class, 'join']);
    Route::post('/{code}/start', [GameController::class, 'start']);
    Route::get('/{code}/state', [GameController::class, 'state']);
    Route::post('/{code}/choose', [GameController::class, 'chooseChallenge']);
    Route::post('/{code}/vote', [GameController::class, 'vote']);
    Route::post('/{code}/players/{player}/drinks', [GameController::class, 'updateDrinks']);
    Route::post('/{code}/join/{player}/approve', [GameController::class, 'approveJoin']);
    Route::post('/{code}/join/{player}/reject', [GameController::class, 'rejectJoin']);
    Route::post('/{code}/players/{player}/remove', [GameController::class, 'removePlayer']);
    Route::post('/{code}/leave', [GameController::class, 'leave']);
});
