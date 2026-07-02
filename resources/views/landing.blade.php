<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Cricket OS — Club Management & Live Scoring</title>
    <meta name="description" content="Manage your cricket club, schedule fixtures, track live scores, and run your team from one platform.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('landing/assets/landing.css') }}">
</head>
<body class="landing-body">

    <nav class="landing-nav" id="landingNav">
        <div class="landing-nav-inner">
            <a href="{{ url('/') }}" class="landing-brand">
                <div class="landing-brand-icon"><i class="fas fa-trophy"></i></div>
                <span class="landing-brand-text">Cricket OS</span>
            </a>
            <div class="landing-nav-actions">
                <a href="{{ route('admin.login') }}" class="btn-landing-outline">Admin</a>
                <a href="{{ route('club.login') }}" class="btn-landing-primary">
                    <i class="fas fa-sign-in-alt me-1"></i> Club Login
                </a>
            </div>
        </div>
    </nav>

    <section class="landing-hero">
        <div class="landing-hero-inner">
            <div>
                <div class="landing-hero-badge">
                    <i class="fas fa-trophy"></i> Cricket club management platform
                </div>
                <h1>Run your club. <span>Score live.</span> Win together.</h1>
                <p class="landing-hero-lead">
                    Schedule fixtures, manage squads, invite players, and share ball-by-ball live scores with fans — all from one place.
                </p>
                <div class="landing-hero-cta">
                    <a href="{{ route('club.login') }}" class="btn-landing-hero">
                        <i class="fas fa-building"></i> Club Login
                    </a>
                    <a href="#features" class="btn-landing-ghost">
                        Learn more <i class="fas fa-arrow-down"></i>
                    </a>
                </div>
            </div>

            <div class="landing-hero-card">
                <div class="landing-score-header">
                    <span class="text-muted small fw-semibold">Live Score</span>
                    <span class="landing-live-badge">
                        <span class="landing-live-dot"></span> LIVE
                    </span>
                </div>
                <div class="landing-match-teams">City CC vs Rovers XI</div>
                <div class="landing-score-line">142/3 (18.4)</div>
                <div class="landing-balls">
                    <span class="landing-ball dot">•</span>
                    <span class="landing-ball run">1</span>
                    <span class="landing-ball four">4</span>
                    <span class="landing-ball run">2</span>
                    <span class="landing-ball six">6</span>
                    <span class="landing-ball wicket">W</span>
                    <span class="landing-ball run">1</span>
                    <span class="landing-ball dot">•</span>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-features" id="features">
        <div class="landing-section-title">
            <h2>Everything your club needs</h2>
            <p>From fixture scheduling to live ball-by-ball scoring — built for cricket clubs of every size.</p>
        </div>
        <div class="landing-features-grid">
            <div class="landing-feature-card">
                <div class="landing-feature-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Fixtures</h3>
                <p>Schedule matches, publish fixtures, and manage opponents — internal or external teams.</p>
            </div>
            <div class="landing-feature-card">
                <div class="landing-feature-icon"><i class="fas fa-users"></i></div>
                <h3>Squads & Players</h3>
                <p>Organize teams, invite players, and track availability before every match.</p>
            </div>
            <div class="landing-feature-card">
                <div class="landing-feature-icon"><i class="fas fa-baseball-ball"></i></div>
                <h3>Live Scoring</h3>
                <p>Ball-by-ball scoring with real-time updates fans can follow without logging in.</p>
            </div>
            <div class="landing-feature-card">
                <div class="landing-feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Club Dashboard</h3>
                <p>Overview of teams, members, live matches, and upcoming fixtures at a glance.</p>
            </div>
        </div>
    </section>

    <section class="landing-cta">
        <h2>Ready to manage your club?</h2>
        <p>Sign in to your club panel to get started.</p>
        <a href="{{ route('club.login') }}" class="btn-landing-primary btn-landing-lg">
            <i class="fas fa-sign-in-alt me-1"></i> Club Login
        </a>
    </section>

    <footer class="landing-footer">
        <p class="mb-1">&copy; {{ date('Y') }} Cricket OS. All rights reserved.</p>
        <p class="mb-0">
            <a href="{{ route('club.login') }}">Club Login</a>
            &nbsp;·&nbsp;
            <a href="{{ route('admin.login') }}">Admin</a>
        </p>
    </footer>

    <script>
        const nav = document.getElementById('landingNav');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 20);
        });
    </script>
</body>
</html>
