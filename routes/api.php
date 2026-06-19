<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::get('/clubs/{club}/players', [ProfileController::class, 'publicClubPlayers']);
Route::get('/invitations/{token}/accept', [ProfileController::class, 'acceptInvitation']);
Route::get('/invitations/{token}/reject', [ProfileController::class, 'rejectInvitation']);

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/player', [ProfileController::class, 'player']);
    Route::post('/player', [ProfileController::class, 'updatePlayer']);

    Route::get('/club', [ProfileController::class, 'club']);
    Route::post('/club', [ProfileController::class, 'updateClub']);
    Route::post('/club/invitations', [ProfileController::class, 'invitePlayerToClub']);
    Route::post('/club/squads', [ProfileController::class, 'createSquad']);
    Route::post('/club/squads/{teamId}/players', [ProfileController::class, 'addPlayerToSquad']);
});

Route::middleware('auth:sanctum')->prefix('club')->group(function () {
    Route::post('/invitations', [ProfileController::class, 'invitePlayerToClub']);
    Route::get('/{clubId}/squads', [ClubController::class, 'squads']);
    Route::get('/squads/{teamId}/players', [ClubController::class, 'squadPlayers']);
    Route::post('/{clubId}/fixtures', [ClubController::class, 'createFixture']);
    Route::post('/{clubId}/fixtures/import', [ClubController::class, 'importFixtures']);
});

Route::prefix('auth')->name('auth.')->group(function () {

    // Step 1: Basic info (first name, last name, email, phone, password)
    Route::post('/register', [RegisterController::class, 'registerStep1']);

    // Step 2: Onboarding (choose club or player, submit club/team data)
    Route::post('/register/onboarding', [RegisterController::class, 'registerStep2']);

    // Login (same as before)
    Route::post('/login', [AuthController::class, 'login']);

});
