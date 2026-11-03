
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Masuk Admin | CassieRess</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #181c32;
            --muted: #7e8299;
            --line: #eff0f6;
            --primary: #3e67f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: #3f4254;
            background: #f7f8fc;
            font-family: Inter, system-ui, sans-serif;
        }

        .login-layout {
            display: grid;
            min-height: 100vh;
            grid-template-columns:
                minmax(0, 1.05fr)
                minmax(430px, .95fr);
        }

        /* LEFT PANEL */

        .login-aside {
            position: relative;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 42px clamp(34px, 7vw, 110px);
            color: #fff;
            background: linear-gradient(
                145deg,
                #3158e8 0%,
                #3e67f6 52%,
                #6584fa 100%
            );
        }

        .login-aside::before,
        .login-aside::after {
            position: absolute;
            width: 410px;
            height: 410px;
            border: 1px solid rgba(255,255,255,.13);
            border-radius: 50%;
            content: '';
            pointer-events: none;
        }

        .login-aside::before {
            right: -175px;
            bottom: -170px;
            box-shadow:
                0 0 0 46px rgba(255,255,255,.035),
                0 0 0 92px rgba(255,255,255,.025);
        }

        .login-aside::after {
            top: 29%;
            left: -320px;
            width: 490px;
            height: 490px;
        }

        .brand {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 11px;
            color: #fff;
            font-size: 1.08rem;
            font-weight: 800;
            letter-spacing: -.035em;
            text-decoration: none;
        }

        .brand:hover {
            color: #fff;
        }

        .brand-mark {
            display: grid;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            place-items: center;
            border: 1px solid rgba(255,255,255,.27);
            border-radius: 11px;
            background: rgba(255,255,255,.17);
        }

        /* LOTTIE ANIMATION */

        .aside-animation {
            position: relative;
            z-index: 1;
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 30px 0;
        }

        .login-animation {
            display: block;
            width: min(100%, 540px);
            height: min(65vh, 480px);
            margin: 0 auto;
        }

        .aside-foot {
            position: relative;
            z-index: 2;
            color: rgba(255,255,255,.65);
            font-size: .77rem;
        }

        /* RIGHT PANEL */

        .login-main {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px clamp(24px, 6vw, 88px);
        }

        .login-card {
            width: min(100%, 410px);
        }

        .mobile-brand {
            display: none;
        }

        .login-card h2 {
            margin: 0 0 8px;
            color: var(--ink);
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -.045em;
        }

        .login-subtitle {
            margin: 0 0 29px;
            color: var(--muted);
            font-size: .9rem;
            line-height: 1.6;
        }

        .alert {
            border: 0;
            border-radius: 8px;
            font-size: .83rem;
        }

        .form-label {
            margin-bottom: 7px;
            color: #5e6278;
            font-size: .8rem;
            font-weight: 600;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap > i {
            position: absolute;
            top: 50%;
            left: 14px;
            color: #a1a5b7;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-control {
            min-height: 48px;
            padding: 11px 42px;
            border-color: #e5e7ee;
            border-radius: 8px;
            color: var(--ink);
            font-size: .87rem;
        }

        .form-control:focus {
            border-color: #aabaff;
            box-shadow: 0 0 0 .22rem rgba(62,103,246,.11);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            padding: 4px;
            border: 0;
            color: #a1a5b7;
            background: transparent;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .form-check-input:checked {
            border-color: var(--primary);
            background-color: var(--primary);
        }

        .form-check-label {
            color: #7e8299;
            font-size: .81rem;
        }

        .btn-primary {
            min-height: 48px;
            border-color: var(--primary);
            border-radius: 8px;
            background: var(--primary);
            font-size: .88rem;
            font-weight: 600;
            transition: all .2s ease;
        }

        .btn-primary:hover {
            border-color: #2f55df;
            background: #2f55df;
        }

        .access-note {
            display: flex;
            gap: 10px;
            padding: 13px 14px;
            margin-top: 25px;
            border: 1px solid #e7ebfa;
            border-radius: 8px;
            color: #7e8299;
            background: #fafbff;
            font-size: .76rem;
            line-height: 1.55;
        }

        .access-note i {
            margin-top: 2px;
            color: var(--primary);
        }

        /* RESPONSIVE */

        @media (max-width: 850px) {
            .login-layout {
                grid-template-columns: 1fr;
            }

            .login-aside {
                display: none;
            }

            .login-main {
                min-height: 100vh;
                padding: 30px 22px;
            }

            .mobile-brand {
                display: flex;
                margin-bottom: 39px;
                color: var(--ink);
            }

            .mobile-brand:hover {
                color: var(--ink);
            }

            .mobile-brand .brand-mark {
                color: #fff;
                background: var(--primary);
            }
        }

        @media (max-height: 600px) and (min-width: 851px) {
            .login-aside {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .login-animation {
                height: 280px;
            }
        }
    </style>
</head>

<body>
<main class="login-layout">
    <!-- LEFT PANEL -->
    <section class="login-aside" aria-label="CassieRess admin">
        <!-- CENTER LOTTIE ANIMATION -->
        <div class="aside-animation">
            <dotlottie-wc
                class="login-animation"
                src="{{ asset('assets/login-character-animation.lottie') }}"
                autoplay
                loop
                aria-label="Animasi karakter login">
            </dotlottie-wc>
        </div>

        <div class="aside-foot">
            &copy; {{ now()->year }} CassieRess.
            All rights reserved.
        </div>

    </section>

    <!-- RIGHT PANEL -->
    <section class="login-main">
        <div class="login-card">

            <!-- MOBILE BRAND -->
            <a href="{{ route('login') }}" class="brand mobile-brand">
                <span class="brand-mark">
                    <i class="fa-solid fa-layer-group"></i>
                </span>
                CASSIERESS
            </a>

            <h2>Selamat datang</h2>

            <p class="login-subtitle">
                Masuk dengan akun admin atau owner
                untuk melanjutkan.
            </p>

            <!-- VALIDATION ERRORS -->
            @if ($errors->any())
                <div class="alert alert-danger mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- LOGIN FORM -->
            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <!-- EMAIL -->
                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope"></i>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="nama@email.com"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Tampilkan password"
                            aria-pressed="false"
                        >
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- REMEMBER ME -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="form-check-input"
                            value="1"
                            {{ old('remember') ? 'checked' : '' }}
                        >

                        <label class="form-check-label" for="remember">
                            Ingat saya
                        </label>
                    </div>
                </div>

                <!-- SUBMIT -->
                <button type="submit" class="btn btn-primary w-100">
                    Masuk
                    <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>

            </form>

            <!-- ACCESS NOTE -->
            <div class="access-note">
                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Akses panel admin hanya tersedia untuk akun
                    dengan role <strong>admin</strong>
                    atau <strong>owner</strong>.
                </span>
            </div>

        </div>
    </section>

</main>

<!-- LOTTIE PLAYER -->
<script
    type="module"
    src="https://cdn.jsdelivr.net/npm/@lottiefiles/dotlottie-wc@latest/dist/dotlottie-wc.js">
</script>

<!-- PASSWORD VISIBILITY -->
<script>
    document.getElementById('togglePassword')
        .addEventListener('click', (event) => {
            const input = document.getElementById('password');
            const button = event.currentTarget;

            const visible = input.type === 'password';

            input.type = visible ? 'text' : 'password';

            button.setAttribute(
                'aria-label',
                visible ? 'Sembunyikan password' : 'Tampilkan password'
            );

            button.setAttribute('aria-pressed', String(visible));

            button.innerHTML = `
                <i class="fa-regular ${
                    visible ? 'fa-eye-slash' : 'fa-eye'
                }"></i>
            `;
        });
</script>

</body>
</html>
