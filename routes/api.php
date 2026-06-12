<?php

use App\Http\Controllers\Api\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->name('auth.')->group(function () {

    // Step 1: Basic info (first name, last name, email, phone, password)
    Route::post('/register', [RegisterController::class, 'registerStep1']);

    // Step 2: Onboarding (choose club or player, submit club/team data)
    Route::post('/register/onboarding', [RegisterController::class, 'registerStep2']);

    // Login (same as before)
    Route::post('/login', [AuthController::class, 'login']);
});
