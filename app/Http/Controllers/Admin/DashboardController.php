<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\Invitation;
use App\Models\MatchFee;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $data['title'] = "Dashboard";
         $stats = [
            'clubs' => Club::count(),
            'clubs_verified' => Club::where('is_verified', true)->count(),
            'players' => User::whereHas('roles', fn ($q) => $q->where('name', 'player'))->count(),
            'players_active' => User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'player'))->count(),
            'admins' => \App\Models\Admin::count(),
            'admins_active' => \App\Models\Admin::where('status', '1')->count(),
            'fixtures_upcoming' => Fixture::whereIn('status', ['published', 'draft'])
                ->where('scheduled_date', '>=', now()->toDateString())->count(),
            'fixtures_today' => Fixture::whereDate('scheduled_date', today())
                ->whereIn('status', ['published', 'live', 'completed'])->count(),
            'fixtures_live' => Fixture::where('status', 'live')->count(),
            'fees_pending' => MatchFee::where('status', 'paid_pending_verification')->count(),
            'fees_amount' => MatchFee::where('status', 'paid_pending_verification')->sum('amount'),
            'invitations_pending' => Invitation::where('status', 'pending')->count(),
        ];

        // Last 30 days registration chart
        $playerChart = User::whereHas('roles', fn ($q) => $q->where('name', 'player'))
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $clubChart = Club::where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill missing dates with 0
        $dates = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates->put($date, [
                'date' => $date,
                'label' => now()->subDays($i)->format('d M'),
                'players' => $playerChart->get($date)?->count ?? 0,
                'clubs' => $clubChart->get($date)?->count ?? 0,
            ]);
        }

        // Recent activity
        $recentActivity = ActivityLog::with('user')
            ->latest()
            ->limit(20)
            ->get();

        // Upcoming fixtures
        $upcomingFixtures = Fixture::with(['homeTeam', 'awayTeam', 'venue'])
            ->whereIn('status', ['published', 'draft'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        // Live fixtures
        $liveFixtures = Fixture::with(['homeTeam', 'awayTeam', 'venue'])
            ->where('status', 'live')
            ->get();

        // Today's fixtures
        $todayFixtures = Fixture::with(['homeTeam', 'awayTeam', 'venue'])
            ->whereDate('scheduled_date', today())
            ->whereIn('status', ['published', 'live', 'completed', 'abandoned'])
            ->orderBy('scheduled_time')
            ->get();

        // Pending invitations
        $pendingInvitations = Invitation::with(['club', 'team', 'invitedBy'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();

        // Pending fee verifications
        $pendingFees = MatchFee::with(['fixture.homeTeam', 'fixture.awayTeam', 'player'])
            ->where('status', 'paid_pending_verification')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'dates', 'recentActivity',
            'upcomingFixtures', 'liveFixtures', 'todayFixtures',
            'pendingInvitations', 'pendingFees'
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
