<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PublicMatchController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ScorerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('/clubs/{club}/players', [ProfileController::class, 'publicClubPlayers']);
Route::get('/clubs/{club}/available-players', [ProfileController::class, 'availableClubPlayers']);

/*
|--------------------------------------------------------------------------
| Public match APIs — no authentication required
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('/matches/live', [PublicMatchController::class, 'liveMatches']);
    Route::get('/matches/upcoming', [PublicMatchController::class, 'upcomingMatches']);
    Route::get('/matches/completed', [PublicMatchController::class, 'completedMatches']);
    Route::get('/matches/{slug}/score', [PublicMatchController::class, 'score']);
    Route::get('/matches/{slug}', [PublicMatchController::class, 'show']);
    Route::get('/fixtures/{fixtureId}/score', [PublicMatchController::class, 'scoreByFixtureId']);
});

Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
    Route::get('/player', [ProfileController::class, 'player']);
    Route::post('/player', [ProfileController::class, 'updatePlayer']);

    Route::get('/club', [ProfileController::class, 'club']);
    Route::post('/club', [ProfileController::class, 'updateClub']);
    Route::post('/club/invitations', [ProfileController::class, 'invitePlayerToClub']);
    Route::post('/club/squads', [ProfileController::class, 'createSquad']);
    Route::post('/club/squads/{teamId}/players', [ProfileController::class, 'addPlayerToSquad']);

    Route::get('/invitations', [ProfileController::class, 'listInvitations']);
    Route::post('/invitations/{token}/accept', [ProfileController::class, 'acceptInvitation']);
    Route::post('/invitations/{token}/reject', [ProfileController::class, 'rejectInvitation']);

    Route::get('/notifications', [ProfileController::class, 'listNotifications']);
    Route::post('/notifications/mark-all-as-read', [ProfileController::class, 'markAllNotificationsAsRead']);
    Route::post('/notifications/{notificationId}/read', [ProfileController::class, 'markNotificationAsRead']);
});

Route::middleware('auth:sanctum')->prefix('player')->group(function () {
    Route::get('/fixtures', [PlayerController::class, 'listFixtures']);
    Route::get('/fixtures/{fixtureId}', [PlayerController::class, 'showFixture']);
    Route::post('/fixtures/{fixtureId}/availability', [PlayerController::class, 'setFixtureAvailability']);
    Route::post('/availability', [PlayerController::class, 'bulkSetAvailability']);
});

Route::middleware('auth:sanctum')->prefix('club')->group(function () {
    Route::post('/invitations', [ProfileController::class, 'invitePlayerToClub']);
    Route::get('/{clubId}/squads', [ClubController::class, 'squads']);
    Route::get('/squads/{teamId}/players', [ClubController::class, 'squadPlayers']);
    Route::get('/{clubId}/fixtures', [ClubController::class, 'listFixtures']);
    Route::post('/{clubId}/fixtures', [ClubController::class, 'createFixture']);
    Route::get('/{clubId}/fixtures/{fixtureId}', [ClubController::class, 'showFixture']);
    Route::post('/{clubId}/fixtures/{fixtureId}', [ClubController::class, 'updateFixture']);
    Route::get('/{clubId}/fixtures/{fixtureId}/availability', [ClubController::class, 'listFixtureAvailability']);
    Route::post('/{clubId}/fixtures/{fixtureId}/club-squad', [ClubController::class, 'setFixtureClubSquad']);
    Route::post('/{clubId}/fixtures/{fixtureId}/opponent-players', [ClubController::class, 'setFixtureOpponentPlayers']);
    Route::post('/{clubId}/fixtures/{fixtureId}/scorer', [ClubController::class, 'setFixtureScorer']);
    Route::post('/{clubId}/import/fixtures', [ClubController::class, 'importFixtures']);
    Route::post('/{clubId}/fees', [ClubController::class, 'collectFee']);
});

Route::middleware('auth:sanctum')->prefix('scorer')->group(function () {
    Route::get('/fixtures/{fixtureId}/readiness', [ScorerController::class, 'readiness']);
    Route::post('/fixtures/{fixtureId}/toss', [ScorerController::class, 'recordToss']);
    Route::post('/fixtures/{fixtureId}/start', [ScorerController::class, 'startMatch']);
    Route::get('/matches/{matchId}/live', [ScorerController::class, 'liveScore']);
    Route::post('/matches/{matchId}/balls', [ScorerController::class, 'recordBall']);
    Route::post('/matches/{matchId}/change-bowler', [ScorerController::class, 'changeBowler']);
    Route::post('/matches/{matchId}/change-batter', [ScorerController::class, 'changeBatter']);
    Route::post('/matches/{matchId}/end-innings', [ScorerController::class, 'endInnings']);
    Route::post('/matches/{matchId}/start-second-innings', [ScorerController::class, 'startSecondInnings']);
    Route::post('/matches/{matchId}/pause', [ScorerController::class, 'pauseMatch']);
    Route::post('/matches/{matchId}/resume', [ScorerController::class, 'resumeMatch']);
});

Route::prefix('auth')->name('auth.')->group(function () {

    // Step 1: Basic info (first name, last name, email, phone, password)
    Route::post('/register', [RegisterController::class, 'registerStep1']);
    Route::post('/register/verify-otp', [RegisterController::class, 'verifyEmailOtp']);
    Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp']);

    // Step 2: Onboarding (choose club or player, submit club/team data)
    Route::post('/register/onboarding', [RegisterController::class, 'registerStep2']);

    // Login (same as before)
    Route::post('/login', [AuthController::class, 'login']);

    // Password reset
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

});
