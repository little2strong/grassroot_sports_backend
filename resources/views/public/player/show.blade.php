@extends('club.layouts.public')

@section('title', $user->name . ' - Player Profile')

@push('style')
<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f7fb;
        color: #1e293b;
    }

    /* PROFILE HEADER - Gradient & Premium Look */
    .profile-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, rgba(0,0,0,0) 60%);
        pointer-events: none;
    }

    .player-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.2);
        object-fit: cover;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }

    .player-name {
        font-size: 2.25rem;
        font-weight: 700;
        letter-spacing: -0.5px;
        color: #ffffff;
        margin-bottom: 0.25rem;
    }

    .player-role-badge {
        display: inline-block;
        background-color: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-top: 0.5rem;
        border: 1px solid rgba(56, 189, 248, 0.3);
    }

    /* CARDS (Glassmorphism & Soft Shadows) */
    .premium-card {
        background: #ffffff;
        border: 1px solid #f1f5f9;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .premium-card-header {
        background-color: #f8fafc;
        border-bottom: 2px solid #f1f5f9;
        padding: 1rem 1.5rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
    }

    .premium-card-body {
        padding: 1.5rem;
    }

    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1.5rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border-color: #cbd5e1;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #4f46e5;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        color: #64748b;
        letter-spacing: 0.5px;
    }

    /* INFO LIST */
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-list li {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-list li:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #64748b;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .info-value {
        color: #1e293b;
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* TABLES */
    .cric-table {
        margin-bottom: 0;
        width: 100%;
    }
    .cric-table th {
        background-color: #f8fafc;
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #f1f5f9;
        padding: 0.75rem 1rem;
    }
    .cric-table td {
        vertical-align: middle;
        font-size: 0.9rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .cric-table tbody tr:hover {
        background-color: #f8fafc;
    }
</style>
@endpush

@section('content')
<main class="club-page" style="max-width: 900px; margin: 0 auto;">
    
    <!-- Profile Header -->
    <div class="profile-header d-flex flex-column flex-md-row align-items-center text-center text-md-start">
        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="player-avatar mb-3 mb-md-0 me-md-4">
        <div>
            <h1 class="player-name">{{ $user->name }}</h1>
            @if($profile && $profile->primary_role)
                <div class="player-role-badge">
                    <i class="fas fa-star me-1"></i> {{ $profile->role_label }}
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Info & Teams -->
        <div class="col-md-4">
            <div class="premium-card">
                <div class="premium-card-header">Personal Info</div>
                <div class="premium-card-body">
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Batting Style</span>
                            <span class="info-value">{{ $profile ? $profile->batting_style_label : 'N/A' }}</span>
                        </li>
                        <li>
                            <span class="info-label">Bowling Style</span>
                            <span class="info-value">{{ $profile ? $profile->bowling_style_label : 'N/A' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            @if($user->teams->isNotEmpty())
            <div class="premium-card">
                <div class="premium-card-header">Teams</div>
                <div class="premium-card-body p-0">
                    <ul class="list-group list-group-flush rounded-bottom">
                        @foreach($user->teams as $team)
                            <li class="list-group-item d-flex align-items-center py-3 border-light">
                                @if($team->club && $team->club->logo)
                                    <img src="{{ asset('storage/' . $team->club->logo) }}" alt="Logo" class="rounded-circle me-3" style="width: 32px; height: 32px; object-fit: cover;">
                                @else
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ substr($team->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $team->name }}</div>
                                    @if($team->club)
                                        <div class="text-muted" style="font-size: 0.8rem;">{{ $team->club->name }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Stats & Form -->
        <div class="col-md-8">
            <div class="premium-card">
                <div class="premium-card-header">Career Statistics</div>
                <div class="premium-card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_matches ?? 0 }}</div>
                            <div class="stat-label">Matches</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_runs ?? 0 }}</div>
                            <div class="stat-label">Runs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->highest_score ?? 0 }}</div>
                            <div class="stat-label">Highest</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->average ?? '—' }}</div>
                            <div class="stat-label">Average</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_fifties ?? 0 }}</div>
                            <div class="stat-label">50s</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_hundreds ?? 0 }}</div>
                            <div class="stat-label">100s</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_wickets ?? 0 }}</div>
                            <div class="stat-label">Wickets</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $profile->total_five_wickets ?? 0 }}</div>
                            <div class="stat-label">5W</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Matches -->
            @if($recentBatting->isNotEmpty() || $recentBowling->isNotEmpty())
            <div class="premium-card">
                <div class="premium-card-header">Recent Form</div>
                <div class="table-responsive">
                    <table class="cric-table table">
                        <thead>
                            <tr>
                                <th>Match</th>
                                <th>Date</th>
                                <th class="text-center">Batting</th>
                                <th class="text-center">Bowling</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Combine and sort recent matches
                                $matches = collect();
                                foreach($recentBatting as $bat) {
                                    if($bat->match) $matches->put($bat->match_id, ['match' => $bat->match, 'bat' => $bat, 'bowl' => null]);
                                }
                                foreach($recentBowling as $bowl) {
                                    if($bowl->match) {
                                        if($matches->has($bowl->match_id)) {
                                            $item = $matches->get($bowl->match_id);
                                            $item['bowl'] = $bowl;
                                            $matches->put($bowl->match_id, $item);
                                        } else {
                                            $matches->put($bowl->match_id, ['match' => $bowl->match, 'bat' => null, 'bowl' => $bowl]);
                                        }
                                    }
                                }
                                $matches = $matches->sortByDesc(function($item) {
                                    return $item['match']->start_time ?? $item['match']->created_at;
                                })->take(5);
                            @endphp

                            @foreach($matches as $m)
                                <tr>
                                    <td>
                                        @if($m['match']->fixture && $m['match']->fixture->is_public)
                                            <a href="{{ route('public.score', $m['match']->fixture->public_share_slug) }}" class="text-primary text-decoration-none fw-bold" target="_blank">
                                                {{ $m['match']->fixture->home_display_name }} vs {{ $m['match']->fixture->away_display_name }}
                                            </a>
                                        @else
                                            <span class="fw-bold">{{ $m['match']->fixture->home_display_name ?? 'Team A' }} vs {{ $m['match']->fixture->away_display_name ?? 'Team B' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        {{ $m['match']->start_time ? $m['match']->start_time->format('M d, Y') : 'Unknown' }}
                                    </td>
                                    <td class="text-center">
                                        @if($m['bat'])
                                            @if($m['bat']->has_batted)
                                                <span class="fw-bold">{{ $m['bat']->runs }}</span> 
                                                <span class="text-muted" style="font-size: 0.8rem;">({{ $m['bat']->balls_faced }})</span>
                                                @if(in_array($m['bat']->dismissal_type, ['not_out', 'retired_hurt', 'retired_not_out']))
                                                    <span class="text-danger">*</span>
                                                @endif
                                            @else
                                                <span class="text-muted">DNB</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($m['bowl'])
                                            <span class="fw-bold">{{ $m['bowl']->wickets }}/{{ $m['bowl']->runs_conceded }}</span>
                                            <span class="text-muted" style="font-size: 0.8rem;">({{ $m['bowl']->overs }} ov)</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
        </div>
    </div>
</main>
@endsection
