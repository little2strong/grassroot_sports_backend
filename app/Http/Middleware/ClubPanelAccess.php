<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClubPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->user_type !== 'club' || !$user->is_active) {
            return redirect()->route('club.login')
                ->withErrors(['email' => 'Please login with an active club account.']);
        }

        $club = $user->ownedClub()->first();

        if (!$club) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('club.login')
                ->withErrors(['email' => 'No club profile is linked to this account.']);
        }

        $request->attributes->set('club', $club);

        return $next($request);
    }
}
