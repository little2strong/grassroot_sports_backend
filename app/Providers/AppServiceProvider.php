<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['club.*'], function ($view) {
            if (auth()->check() && auth()->user()->user_type === 'club') {
                $view->with('club', auth()->user()->ownedClub);
            }
        });
    }
}
