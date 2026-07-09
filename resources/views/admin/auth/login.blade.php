<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --admin-brand: #006a6c;
            --admin-brand-dark: #00585a;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(1200px 600px at 10% 10%, rgba(255, 255, 255, 0.25), transparent 60%),
                radial-gradient(900px 500px at 90% 20%, rgba(255, 255, 255, 0.18), transparent 55%),
                linear-gradient(135deg, #0b3b3c 0%, var(--admin-brand) 35%, #0b3b3c 100%);
            display: flex;
            align-items: center;
        }

        .auth-shell {
            width: 100%;
            padding: 32px 0;
        }

        .auth-card {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 20px 55px rgba(0, 0, 0, 0.35);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
        }

        .auth-card-header {
            padding: 26px 28px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.75);
        }

        .brand-badge {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--admin-brand) 0%, #12a4a7 100%);
            color: #fff;
            box-shadow: 0 10px 25px rgba(0, 106, 108, 0.35);
        }

        .auth-title {
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 14px 0 4px;
            color: #0f172a;
        }

        .auth-subtitle {
            margin: 0;
            color: rgba(15, 23, 42, 0.7);
            font-size: 0.95rem;
        }

        .auth-card-body {
            padding: 22px 28px 26px;
        }

        .form-label {
            font-weight: 600;
            color: rgba(15, 23, 42, 0.8);
            margin-bottom: 8px;
        }

        .form-control:focus {
            border-color: rgba(0, 106, 108, 0.55);
            box-shadow: 0 0 0 0.25rem rgba(0, 106, 108, 0.18);
        }

        .btn-login {
            background: linear-gradient(90deg, var(--admin-brand) 0%, #12a4a7 100%);
            border: 0;
            font-weight: 600;
            padding: 11px 14px;
            border-radius: 12px;
        }

        .btn-login:hover {
            background: linear-gradient(90deg, var(--admin-brand-dark) 0%, #0f8f92 100%);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .input-group-text {
            background: #fff;
        }

        .auth-footer {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            margin-top: 18px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="auth-shell">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <div class="auth-card-header text-center">
                            <div class="brand-badge mx-auto">
                                <i class="fas fa-shield-halved"></i>
                            </div>
                            <h3 class="auth-title">Admin Login</h3>
                            <p class="auth-subtitle">Secure access to the control panel</p>
                        </div>

                        <div class="auth-card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger mb-4">
                                    <div class="d-flex gap-2 align-items-start">
                                        <i class="fas fa-circle-exclamation mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Login failed</div>
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.login.post') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="far fa-envelope"></i>
                                        </span>
                                        <input
                                            type="email"
                                            class="form-control"
                                            id="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            placeholder="admin@example.com"
                                            required
                                            autofocus
                                        >
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            placeholder="Enter password"
                                            required
                                        >
                                        <button type="button" class="input-group-text" style="cursor:pointer;" onclick="togglePassword()" aria-label="Toggle password visibility">
                                            <i id="password-icon" class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember" @checked(old('remember'))>
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-login w-100 text-white">
                                    <i class="fas fa-right-to-bracket me-1"></i> Login
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="auth-footer">
                        {{ config('app.name') }} · Admin Panel
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>



{{-- <!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login</title>

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #e2e8f0;
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      background: #1e293b;
      border: none;
      border-radius: 16px;
      overflow: hidden;
    }

    .card-header {
      background: #0f172a;
      border-bottom: 1px solid #334155;
      padding: 2.25rem 2rem 1.75rem;
      text-align: center;
    }

    .card-header h2 {
      font-weight: 600;
      letter-spacing: -0.5px;
      margin: 0;
      color: #f1f5f9;
    }

    .card-header p {
      color: #94a3b8;
      font-size: 0.95rem;
      margin: 0.35rem 0 0;
    }

    .form-label {
      font-weight: 500;
      color: #cbd5e1;
      margin-bottom: 0.5rem;
    }

    .form-control {
      background: #0f172a;
      border: 1px solid #334155;
      color: #e2e8f0;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      transition: all 0.2s;
    }

    .form-control:focus {
      background: #0f172a;
      border-color: #60a5fa;
      box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.15);
      color: #f1f5f9;
    }

    .form-control::placeholder {
      color: #64748b;
    }

    .input-group-text {
      background: #0f172a;
      border: 1px solid #334155;
      color: #94a3b8;
      border-radius: 8px;
      cursor: pointer;
      user-select: none;
    }

    .input-group-text:hover {
      color: #cbd5e1;
    }

    .btn-login {
      background: linear-gradient(90deg, #3b82f6, #60a5fa);
      border: none;
      font-weight: 500;
      padding: 0.8rem;
      border-radius: 10px;
      transition: all 0.25s;
    }

    .btn-login:hover {
      background: linear-gradient(90deg, #2563eb, #3b82f6);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35);
    }

    .form-check-label {
      color: #94a3b8;
    }

    a {
      color: #60a5fa;
      text-decoration: none;
      font-weight: 500;
    }

    a:hover {
      color: #93c5fd;
      text-decoration: underline;
    }

    .small-footer {
      color: #64748b;
      font-size: 0.875rem;
      text-align: center;
      margin-top: 2rem;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="card-header">
      <h2>Admin Login</h2>
      <p>Secure access to the control panel</p>
    </div>

    <div class="card-body p-4 p-md-5">
      <form  method="POST" action="{{ route('admin.login') }}">
        @csrf
        <!-- Username / Email -->
        <div class="mb-4">
          <label for="username" class="form-label">Username or Email</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text "><i class="fas fa-user"></i></span>
            <input type="email" class="form-control" id="email"
                   name="email" placeholder="admin@company.com" required autofocus>
          </div>
        </div>

        <!-- Password with toggle -->
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group input-group-lg">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" name="password" id="password"
                   placeholder="Enter your password" required>
            <span class="input-group-text" id="togglePassword">
              <i class="bi bi-eye-slash"></i>
            </span>
          </div>
        </div>

        <!-- Remember & Forgot -->
        <div class="d-flex justify-content-between align-items-center mb-5">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember">
            <label class="form-check-label" for="remember">
              Remember me
            </label>
          </div>
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn btn-login btn-lg w-100">
          Sign In
        </button>
      </form>


    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>

  <!-- Password toggle script -->
  <script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
      // toggle the type attribute
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);

      // toggle the icon
      this.querySelector('i').classList.toggle('bi-eye');
      this.querySelector('i').classList.toggle('bi-eye-slash');
    });
  </script>

</body>
</html> --}}
