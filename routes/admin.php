<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PlayerController;

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (NO auth required — guest only)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // Login — only for guests
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])
        ->name('login')
        ->middleware('guest:admin');

    Route::post('/login', [AdminLoginController::class, 'login'])
        ->name('login.post')
        ->middleware('guest:admin');

    // Forgot password — only for guests
    Route::get('/forgot', [AdminLoginController::class, 'showForgetForm'])
        ->name('forgot')
        ->middleware('guest:admin');

    Route::post('/forgot', [AdminLoginController::class, 'sendResetLink'])
        ->name('forgot.post')
        ->middleware('guest:admin');

    // Reset password — only for guests
    Route::get('/reset/{token}', [AdminLoginController::class, 'showResetForm'])
        ->name('reset')
        ->middleware('guest:admin');

    Route::post('/reset', [AdminLoginController::class, 'resetPassword'])
        ->name('reset.post')
        ->middleware('guest:admin');

    // Logout — only for authenticated admins
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->name('logout')
        ->middleware('auth:admin');
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (auth:admin required on ALL)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth:admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::group(['prefix' => 'clubs', 'as' => 'clubs.'], function () {
        Route::get('/', [ClubController::class, 'index'])->name('index');
       
    });

    Route::group(['prefix' => 'players', 'as' => 'players.'], function () {
        Route::get('/', [PlayerController::class, 'index'])->name('index');
       
    });


    Route::get('profile', [DashboardController::class, 'adminProfile'])->name('profile');
    Route::get('profile-edit', [DashboardController::class, 'profileEdit'])->name('profile.edit');
    Route::post('profile-update', [DashboardController::class, 'profileUpdate'])->name('profile.update');
    Route::post('password-update', [DashboardController::class, 'passwordUpdate'])->name('password.update');


   
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('store', [SettingsController::class, 'store'])->name('store');
        Route::post('cashback/update', [SettingsController::class, 'cashbackUpdate'])->name('cashback.update');
    });

    // admins routes
    Route::group(['prefix' => 'admins', 'as' => 'admins.'], function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('store', [AdminController::class, 'store'])->name('store');
        Route::get('{user}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('{user}', [AdminController::class, 'update'])->name('update');
        Route::post('{user}/suspend', [AdminController::class, 'suspend'])->name('suspend');
        Route::post('{user}/change-password', [AdminController::class, 'changePassword'])->name('chnage-password');

    });

    Route::group(['prefix' =>  'roles', 'as' => 'roles.'], function () {
        Route::get('/', [RolesController::class, 'index'])->name('index');
        Route::get('/create', [RolesController::class, 'create'])->name('create');
        Route::post('/store', [RolesController::class, 'store'])->name('store');
        Route::get('{id}/edit', [RolesController::class, 'edit'])->name('edit');
        Route::post('{id}/update', [RolesController::class, 'update'])->name('update');

    });
    // Clubs
    // Route::resource('clubs', ClubController::class);

    // // Players
    // Route::resource('players', PlayerController::class);
    // Route::get('players/{player}/clubs', [PlayerController::class, 'clubs'])->name('players.clubs');
    // Route::get('players/{player}/teams', [PlayerController::class, 'teams'])->name('players.teams');

    // // Admins
    // Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');
    // Route::get('admins/create', [AdminUserController::class, 'create'])->name('admins.create');
    // Route::post('admins', [AdminUserController::class, 'store'])->name('admins.store');
    // Route::get('admins/{user}/edit', [AdminUserController::class, 'edit'])->name('admins.edit');
    // Route::put('admins/{user}', [AdminUserController::class, 'update'])->name('admins.update');
    // Route::delete('admins/{user}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
    // Route::post('admins/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('admins.toggle-active');

    // // Roles (super_admin only)
    // Route::middleware('admin.role:super_admin')->group(function () {
    //     Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    //     Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    //     Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    //     Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    //     Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    //     Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    // });
});
