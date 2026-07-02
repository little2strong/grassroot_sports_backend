<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $title = 'Dashboard';

        $stats = [
            'clubs' => Club::count(),
            'clubs_verified' => Club::where('is_verified', true)->count(),
            'players' => User::where('user_type', 'player')->count(),
            'players_active' => User::where('user_type', 'player')->where('is_active', true)->count(),
            'fixtures_total' => Fixture::count(),
            'fixtures_upcoming' => Fixture::whereIn('status', ['published', 'draft'])
                ->where('scheduled_date', '>=', now()->toDateString())->count(),
            'fixtures_today' => Fixture::whereDate('scheduled_date', today())
                ->whereIn('status', ['published', 'live', 'paused', 'completed'])->count(),
            'fixtures_live' => Fixture::whereIn('status', ['live', 'paused'])->count(),
            'fixtures_completed' => Fixture::where('status', 'completed')->count(),
            'invitations_pending' => Invitation::where('status', 'pending')->count(),
        ];

        $playerChart = User::where('user_type', 'player')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $clubChart = Club::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $fixtureChart = Fixture::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dates = collect();
        $chartMax = 1;

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $players = (int) ($playerChart->get($date)?->count ?? 0);
            $clubs = (int) ($clubChart->get($date)?->count ?? 0);
            $fixtures = (int) ($fixtureChart->get($date)?->count ?? 0);
            $chartMax = max($chartMax, $players, $clubs, $fixtures);

            $dates->put($date, [
                'date' => $date,
                'label' => now()->subDays($i)->format('d M'),
                'players' => $players,
                'clubs' => $clubs,
                'fixtures' => $fixtures,
            ]);
        }

        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->limit(15)
            ->get();

        $upcomingFixtures = Fixture::with(['club', 'homeTeam', 'awayTeam', 'venue'])
            ->whereIn('status', ['published', 'draft'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(6)
            ->get();

        $liveFixtures = Fixture::with(['club', 'homeTeam', 'awayTeam', 'venue', 'scorer'])
            ->whereIn('status', ['live', 'paused'])
            ->orderBy('started_at', 'desc')
            ->get();

        $todayFixtures = Fixture::with(['club', 'homeTeam', 'awayTeam', 'venue'])
            ->whereDate('scheduled_date', today())
            ->whereIn('status', ['published', 'live', 'paused', 'completed', 'abandoned'])
            ->orderBy('scheduled_time')
            ->get();

        $pendingInvitations = Invitation::with(['club', 'team', 'invitedBy'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        $recentClubs = Club::with('owner')
            ->latest()
            ->limit(5)
            ->get();

        $recentPlayers = User::where('user_type', 'player')
            ->with('playerProfile')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'title',
            'stats',
            'dates',
            'chartMax',
            'recentActivity',
            'upcomingFixtures',
            'liveFixtures',
            'todayFixtures',
            'pendingInvitations',
            'recentClubs',
            'recentPlayers'
        ));
    }

    public function cacheClear()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return back()->with('success', 'Cache cleared successfully!');
    }
}
