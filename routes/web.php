<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChallengeController;
use App\Http\Controllers\Admin\DbController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/host', [LandingController::class, 'host'])->name('host');
Route::get('/join', [LandingController::class, 'join'])->name('join');
Route::get('/games/{code}', [LandingController::class, 'game'])->name('games.show');

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
      Route::get('/categories', [CategoryController::class, 'index'])->name('dashboard');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories', [CategoryController::class, 'updateByPayload'])->name('categories.update.fallback');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories', [CategoryController::class, 'destroyByPayload'])->name('categories.destroy.fallback');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
    Route::post('/challenges', [ChallengeController::class, 'store'])->name('challenges.store');
    Route::delete('/challenges', [ChallengeController::class, 'destroyByPayload'])->name('challenges.destroy.fallback');
    Route::patch('/challenges/{challenge}', [ChallengeController::class, 'update'])->name('challenges.update');
    Route::delete('/challenges/{challenge}', [ChallengeController::class, 'destroy'])->name('challenges.destroy');

    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{game}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::delete('/sessions', [SessionController::class, 'destroyByPayload'])->name('sessions.destroy.fallback');

    Route::get('/db', [DbController::class, 'index'])->name('db.index');
    Route::post('/db/query', [DbController::class, 'query'])->name('db.query');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
