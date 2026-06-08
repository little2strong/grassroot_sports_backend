<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #006a6c;
        }
        .btn_login{
            background-color: #006a6c;
        }
        .btn_login:hover {
            background-color: #00585a;
        }

        .login-container {
            margin-top: 100px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        /* .btn-login {
            background-color: #0d6efd;
            color: #fff;
        }

        .btn-login:hover {
            background-color: #0b5ed7;
        } */
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card p-4 card_bottom">
            <div class="card-body">
                <!-- Title -->
                <h3 class="card-title text-center mb-1">Admin Login</h3>

                <!-- Subtitle -->
                <p class="text-center text-muted mb-4">
                    Access Your Business Dashboard
                </p>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.post') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="far fa-envelope"></i>
                            </span>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="Enter email"
                                required
                            >
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
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
                            <span class="input-group-text bg-white" style="cursor:pointer;" onclick="togglePassword()">
                                <i id="password-icon" class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <button type="submit" class="btn btn_login w-100 text-white">
                        Login
                    </button>
                </form>

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
