<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapid Rescue - Login In</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/user/css/style.css') }}">
</head>

<body>
    {{-- Alerts --}}
    <div id="successAlert" class="alert alert-success alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display:none;z-index:1055;">
        <span id="successAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideSuccessAlert()"></button>
    </div>

    <div id="errorAlert" class="alert alert-danger alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display:none;z-index:1055;">
        <span id="errorAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideErrorAlert()"></button>
    </div>

    <div class="rr-auth">
        {{-- Desktop visual side --}}
        <div class="rr-auth__visual">
            <div class="rr-auth__visual-inner">
                <a class="navbar-brand rr-logo" href="{{ route('home') }}">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue" style="max-height:48px;filter:brightness(0) invert(1);">
                    </span>

                    <span class="rr-logo-text ms-2">
                        <strong style="color:white;">Rapid <span style="color:white;">Rescue</span></strong>
                        <small class="d-block" style="color:white;">Ambulance System</small>
                    </span>
                </a>

                <div style="margin-top:60px;">
                    <h2>Welcome back to Rapid Rescue.</h2>
                    <p>Log in to track requests, manage your medical card and respond faster to emergencies — for yourself and your loved ones.</p>

                    <div class="rr-auth__feature">
                        <i class="fa fa-bolt"></i>
                        <div>
                            <h6>Fastest dispatch in the region</h6>
                            <span>Average response under 90 seconds, 24/7.</span>
                        </div>
                    </div>

                    <div class="rr-auth__feature">
                        <i class="fa fa-shield-heart"></i>
                        <div>
                            <h6>Certified paramedics</h6>
                            <span>Trained in advanced life support.</span>
                        </div>
                    </div>

                    <div class="rr-auth__feature">
                        <i class="fa fa-location-dot"></i>
                        <div>
                            <h6>Live GPS tracking</h6>
                            <span>Watch your ambulance every step of the way.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rr-auth__visual-inner" style="opacity:.7;font-size:.86rem;">
                &copy; {{ date('Y') }} Rapid Rescue. All rights reserved.
            </div>
        </div>

        {{-- Form side --}}
        <div class="rr-auth__form-wrap">
            {{-- Mobile-only hero header --}}
            <div class="rr-auth__mobile-hero">
                <div class="rr-auth__mobile-hero-bg"></div>
                <div class="rr-auth__mobile-hero-content">
                    <a href="{{ route('home') }}" class="rr-auth__mobile-logo">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                        <span>
                            <strong>Rapid Rescue</strong>
                            <small>Ambulance System</small>
                        </span>
                    </a>

                    <div class="rr-auth__mobile-hero-badges">
                        <span class="rr-auth__mobile-badge"><i class="fa fa-bolt"></i> 90s Response</span>
                        <span class="rr-auth__mobile-badge"><i class="fa fa-shield-heart"></i> Certified Paramedics</span>
                        <span class="rr-auth__mobile-badge"><i class="fa fa-location-dot"></i> GPS Tracking</span>
                    </div>
                </div>

                <div class="rr-auth__mobile-wave">
                    <svg viewBox="0 0 375 54" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 54 C90 10 280 45 375 0 L375 54 Z" fill="#ffffff"/>
                    </svg>
                </div>
            </div>

            {{-- ① Login Form --}}
            <div class="rr-auth__form" id="loginFormDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}" style="margin-bottom:1rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>

                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <h2>Sign in to your account</h2>
                <p>Enter your username and password to continue.</p>

                <form id="loginForm" enctype="multipart/form-data">
                    @csrf
                    <div class="rr-field">
                        <label class="rr-label">Username</label>
                        <div class="rr-input-group">
                            <i class="fa fa-user"></i>
                            <input type="text" name="username" id="username" class="rr-input" maxlength="30" placeholder="john_doe" required autocomplete="username" oninput="validateUsername(this)">
                        </div>
                    </div>

                    <div class="rr-field">
                        <label class="rr-label">Password</label>
                        <div class="rr-input-group rr-input-group--eye">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="login_password" name="password" class="rr-input" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="rr-eye-toggle" onclick="togglePassword('login_password', this)" aria-label="Toggle password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="rr-auth__remember-row" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;">
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;color:var(--rr-text-muted);">
                            <input id="remember" type="checkbox" checked style="accent-color:var(--rr-primary);width:16px;height:16px;">
                            Remember me
                        </label>
                        <a href="#" onclick="showForgotPasswordDiv(); return false;" style="color:var(--rr-primary);font-weight:700;">Forgot password?</a>
                    </div>

                    <div id="login-recaptcha-widget" class="mb-3 d-flex justify-content-center"></div>
                    <p id="login-recaptcha-error" class="text-danger text-center" style="display:none;font-size:0.84rem;margin-top:-8px;margin-bottom:8px;">Please complete the human verification.</p>

                    <button type="button" id="login-btn" onclick="loginValidationCheck()"
                        class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg">
                        Sign In
                    </button>
                </form>

                <div class="rr-auth__alt">
                    New to Rapid Rescue? <a href="{{ route('signup') }}">Create an account</a>
                </div>
            </div>

            {{-- ② Forgot Password — Enter Email --}}
            <div class="rr-auth__form d-none" id="ForgotPasswordDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}" style="margin-bottom:1rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>
                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <button type="button" onclick="showLoginFormDiv()" class="rr-btn rr-btn--secondary rr-btn--sm mb-3" style="display:inline-flex;align-items:center;gap:6px;">
                    <i class="fa fa-arrow-left"></i> Back to Sign In
                </button>

                <h2>Forgot your password?</h2>
                <p>No worries. Enter your registered email address and we'll send you a 6-digit reset code.</p>

                <form id="forgotPasswordForm">
                    @csrf
                    <div class="rr-field">
                        <label class="rr-label">Email Address</label>
                        <div class="rr-input-group">
                            <i class="fa fa-envelope"></i>
                            <input type="email" name="forgot_email" id="forgot_email" maxlength="100" class="rr-input" placeholder="you@example.com" required autocomplete="email">
                        </div>
                    </div>

                    <div id="forgot-recaptcha-widget" class="mt-4 d-flex justify-content-center"></div>
                    <p id="forgot-recaptcha-error" class="text-danger text-center" style="display:none;font-size:0.84rem;margin-top:4px;margin-bottom:8px;">Please complete the human verification.</p>

                    <button type="button" id="sendResetCodeBtn" onclick="sendPasswordResetCode()" class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg mt-3">
                        Send Reset Code
                    </button>
                </form>

                <div class="rr-auth__alt">
                    Remember your password? <a href="#" onclick="showLoginFormDiv(); return false;">Sign in</a>
                </div>
            </div>

            {{-- ③ Forgot Password — Verify Code --}}
            <div class="rr-auth__form d-none" id="ForgotPasswordVerifyDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}" style="margin-bottom:1rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>
                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <button type="button" onclick="showForgotPasswordDiv()" class="rr-btn rr-btn--secondary rr-btn--sm mb-3" style="display:inline-flex;align-items:center;gap:6px;">
                    <i class="fa fa-arrow-left"></i> Back
                </button>

                <h2>Verify Reset Code</h2>
                <p>We've sent a 6-digit reset code to your email. Enter it below to continue.</p>

                <form id="forgotPasswordVerifyForm">
                    @csrf
                    <div class="rr-field">
                        <label class="rr-label">Email Address</label>
                        <div class="rr-input-group">
                            <i class="fa fa-envelope"></i>
                            <input type="email" name="reset_email" id="reset_email" class="rr-input" disabled autocomplete="off">
                        </div>
                    </div>

                    <div class="rr-field mt-3">
                        <label class="rr-label">Reset Code</label>
                        <div class="rr-input-group">
                            <i class="fa fa-shield-alt"></i>
                            <input type="text" name="reset_code" id="reset_code" class="rr-input" maxlength="6" placeholder="Enter 6-digit code" autocomplete="one-time-code" oninput="onResetCodeInput()">
                        </div>
                    </div>

                    <button type="button" id="verifyResetCodeBtn" onclick="verifyResetCode()" class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg mt-4" disabled>
                        Verify Code
                    </button>

                    <button type="button" id="resendResetCodeBtn" onclick="resendResetCode()" class="rr-btn rr-btn--secondary rr-btn--block rr-btn--lg mt-2" disabled>
                        Resend Code (<span id="resendResetTimer">60</span>s)
                    </button>
                </form>

                <div class="rr-auth__alt">
                    Back to <a href="#" onclick="showLoginFormDiv(); return false;">Sign in</a>
                </div>
            </div>

            {{-- ④ Reset Password --}}
            <div class="rr-auth__form d-none" id="ResetPasswordDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}" style="margin-bottom:1rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>
                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <h2>Create New Password</h2>
                <p>Your identity has been verified. Please create a new strong password for your account.</p>

                <form id="resetPasswordForm">
                    @csrf
                    <div class="rr-field">
                        <label class="rr-label">New Password</label>
                        <div class="rr-input-group rr-input-group--eye">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" maxlength="30" class="rr-input" placeholder="••••••••" required autocomplete="new-password" oninput="validateNewPassword(this)">
                            <button type="button" class="rr-eye-toggle" onclick="togglePassword('new_password', this)" aria-label="Toggle password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <small id="new_password_error" class="text-danger"></small>
                    </div>

                    <div class="rr-field mt-3">
                        <label class="rr-label">Confirm New Password</label>
                        <div class="rr-input-group rr-input-group--eye">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" maxlength="30" class="rr-input" placeholder="••••••••" required autocomplete="new-password" oninput="validateConfirmNewPassword()">
                            <button type="button" class="rr-eye-toggle" onclick="togglePassword('confirm_new_password', this)" aria-label="Toggle confirm password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <small id="confirm_new_password_error" class="text-danger"></small>
                    </div>

                    <button type="button" id="resetPasswordBtn" onclick="submitResetPassword()" class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg mt-4">
                        Reset Password
                    </button>
                </form>

                <div class="rr-auth__alt">
                    Back to <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/user/js/login.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>

    <script>
        @if (session('success'))
            showAlert('success', @json(session('success')));
        @elseif (session('error'))
            showAlert('error', @json(session('error')));
        @endif

        window.routes = {
            loginSubmit:        "{{ route('loginApi') }}",
            passwordResetSend:  "{{ route('password.reset.send') }}",
            passwordResetResend:"{{ route('password.reset.resend') }}",
            passwordResetVerify:"{{ route('password.reset.verify') }}",
            passwordResetReset: "{{ route('password.reset.reset') }}",
        };

        window.recaptchaSiteKey = '{{ config("recaptcha.site_key") }}';

        var loginWidgetId;
        var forgotWidgetId;

        function onRecaptchaLoad() {
            loginWidgetId = grecaptcha.render('login-recaptcha-widget', {
                sitekey: window.recaptchaSiteKey
            });
            forgotWidgetId = grecaptcha.render('forgot-recaptcha-widget', {
                sitekey: window.recaptchaSiteKey
            });
        }
    </script>
</body>

</html>
