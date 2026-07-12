<?php

use App\Http\Controllers\Club\ClubLoginController;
use App\Http\Controllers\Club\DashboardController;
use App\Http\Controllers\Club\FixtureController;
use App\Http\Controllers\Club\InvitationController;
use App\Http\Controllers\Club\PlayerController;
use App\Http\Controllers\Club\ProfileController;
use App\Http\Controllers\Club\ScoringController;
use App\Http\Controllers\Club\SquadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

/*
|--------------------------------------------------------------------------
| Club Panel (web routes)
|--------------------------------------------------------------------------
*/
Route::prefix('club')->name('club.')->group(function () {

    Route::get('/login', [ClubLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [ClubLoginController::class, 'login'])->name('login.post');

    Route::post('/logout', [ClubLoginController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'club.panel'])->group(function () {
        Route::redirect('/', '/club/dashboard');

        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/squads', [SquadController::class, 'index'])->name('squads.index');
        Route::get('/squads/create', [SquadController::class, 'create'])->name('squads.create');
        Route::post('/squads', [SquadController::class, 'store'])->name('squads.store');
        Route::get('/squads/{team}/edit', [SquadController::class, 'edit'])->name('squads.edit');
        Route::put('/squads/{team}', [SquadController::class, 'update'])->name('squads.update');
        Route::delete('/squads/{team}', [SquadController::class, 'destroy'])->name('squads.destroy');
        Route::get('/squads/{team}/players', [SquadController::class, 'players'])->name('squads.players');
        Route::post('/squads/{team}/players', [SquadController::class, 'addPlayer'])->name('squads.players.add');
        Route::delete('/squads/{team}/players/{user}', [SquadController::class, 'removePlayer'])->name('squads.players.remove');
        Route::post('/squads/{team}/players/{user}/move', [SquadController::class, 'movePlayer'])->name('squads.players.move');

        Route::get('/fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
        Route::get('/fixtures/create', [FixtureController::class, 'create'])->name('fixtures.create');
        Route::post('/fixtures', [FixtureController::class, 'store'])->name('fixtures.store');
        Route::get('/fixtures/{fixture}/edit', [FixtureController::class, 'edit'])->name('fixtures.edit');
        Route::put('/fixtures/{fixture}', [FixtureController::class, 'update'])->name('fixtures.update');
        Route::delete('/fixtures/{fixture}', [FixtureController::class, 'destroy'])->name('fixtures.destroy');
        Route::get('/fixtures/{fixture}/availability', [FixtureController::class, 'availability'])->name('fixtures.availability');
        Route::post('/fixtures/{fixture}/scorer', [FixtureController::class, 'assignScorer'])->name('fixtures.assign-scorer');
        Route::get('/fixtures/{fixture}/collect-fee', [FixtureController::class, 'showCollectFee'])->name('fixtures.collect-fee');
        Route::post('/fixtures/{fixture}/collect-fee', [FixtureController::class, 'collectFee'])->name('fixtures.collect-fee.store');
        Route::get('/fixtures/{fixture}/bulk-collect-fee', [FixtureController::class, 'showBulkCollectFee'])->name('fixtures.bulk-collect-fee');
        Route::post('/fixtures/{fixture}/bulk-collect-fee', [FixtureController::class, 'bulkCollectFee'])->name('fixtures.bulk-collect-fee.store');

        Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');

        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

        Route::get('/scoring', [ScoringController::class, 'index'])->name('scoring.index');
        Route::get('/scoring/matches', [ScoringController::class, 'matches'])->name('scoring.matches');
        Route::get('/scoring/matches/{fixture}', [ScoringController::class, 'show'])->name('scoring.show');
    });
});
