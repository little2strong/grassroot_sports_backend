<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Club Login — Cricket OS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('club_panel/assets/login.css') }}">
</head>
<body class="club-login-body">
    <div class="club-login-mobile-brand">
        <div class="club-login-logo"><i class="fas fa-trophy"></i></div>
        <div>
            <h1>Cricket OS</h1>
            <p>Club management platform</p>
        </div>
    </div>

    <div class="club-login-wrapper">
        <div class="club-login-brand d-none d-lg-flex">
            <div class="club-login-brand-content">
                <div class="club-login-logo"><i class="fas fa-trophy"></i></div>
                <h1>Cricket OS</h1>
                <p>Manage your club, squads, fixtures, and live scoring from one powerful dashboard.</p>
                <div class="club-feature-grid">
                    <div class="club-feature-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Schedule & publish fixtures</span>
                    </div>
                    <div class="club-feature-item">
                        <i class="fas fa-users"></i>
                        <span>Squads & availability</span>
                    </div>
                    <div class="club-feature-item">
                        <i class="fas fa-baseball-ball"></i>
                        <span>Live ball-by-ball scoring</span>
                    </div>
                    <div class="club-feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Performance insights</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="club-login-form-side">
            <div class="club-login-card">
                <h2>Welcome back</h2>
                <p class="subtitle">Sign in to your club dashboard</p>

                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('club.login.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ old('email') }}" placeholder="club@example.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Enter password" required>
                            <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword()" role="button" aria-label="Toggle password">
                                <i id="password-icon" class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Sign in to Club Panel</button>
                </form>

                <p class="club-login-footer">
                    Platform admin? <a href="{{ route('admin.login') }}">Admin login</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
