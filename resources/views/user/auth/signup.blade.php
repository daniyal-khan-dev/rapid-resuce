<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapid Rescue - Create Account</title>
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
    <div id="successAlert" class="alert alert-success alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display: none; z-index: 1055;">
        <span id="successAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideSuccessAlert()"></button>
    </div>

    <div id="errorAlert" class="alert alert-danger alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display: none; z-index: 1055;">
        <span id="errorAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideErrorAlert()"></button>
    </div>

    <div class="rr-auth">
        {{-- Desktop visual side --}}
        <div class="rr-auth__visual">
            <div class="rr-auth__visual-inner">
                <a class="navbar-brand rr-logo" href="{{ route('home') }}">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue"
                            style="max-height:48px;filter:brightness(0) invert(1);">
                    </span>

                    <span class="rr-logo-text ms-2">
                        <strong style="color: white;">Rapid <span style="color: white;">Rescue</span></strong>
                        <small class="d-block" style="color: white;">Ambulance System</small>
                    </span>
                </a>

                <div style="margin-top:60px;">
                    <h2>Join Rapid Rescue.</h2>
                    <p>Create a free account in under a minute. Your personal medical card means paramedics can help you faster when every second counts.</p>

                    <div class="rr-auth__feature">
                        <i class="fa fa-id-card"></i>
                        <div>
                            <h6>Personal Medical Card</h6>
                            <span>Allergies, conditions and emergency contacts at hand.</span>
                        </div>
                    </div>

                    <div class="rr-auth__feature">
                        <i class="fa fa-history"></i>
                        <div>
                            <h6>Request History</h6>
                            <span>Every dispatch logged, ready when you need it.</span>
                        </div>
                    </div>

                    <div class="rr-auth__feature">
                        <i class="fa fa-bell"></i>
                        <div>
                            <h6>Real-time Notifications</h6>
                            <span>Stay informed from dispatch to arrival.</span>
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
                        <span class="rr-auth__mobile-badge"><i class="fa fa-id-card"></i> Medical Card</span>
                        <span class="rr-auth__mobile-badge"><i class="fa fa-history"></i> Full History</span>
                        <span class="rr-auth__mobile-badge"><i class="fa fa-bell"></i> Live Alerts</span>
                    </div>
                </div>

                <div class="rr-auth__mobile-wave">
                    <svg viewBox="0 0 375 54" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 54 C90 10 280 45 375 0 L375 54 Z" fill="#ffffff" />
                    </svg>
                </div>
            </div>

            {{-- Signup Form card --}}
            <div class="rr-auth__form" id="SignupFormDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}"
                    style="margin-bottom: .5rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>

                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <h2>Create your account</h2>
                <p>It only takes a minute to get registered.</p>

                <form id="signUpForm" enctype="multipart/form-data">
                    @csrf
                    {{-- First Name & Last Name --}}
                    <div class="rr-field-row">
                        <div class="rr-field">
                            <label class="rr-label">First Name</label>
                            <div class="rr-input-group">
                                <i class="fa fa-user"></i>
                                <input type="text" maxlength="15" name="first_name" id="first_name" class="rr-input" placeholder="John" required oninput="allowOnlyAlphabets(this)" autocomplete="given-name">
                            </div>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Last Name</label>
                            <div class="rr-input-group">
                                <i class="fa fa-user"></i>
                                <input type="text" maxlength="15" name="last_name" id="last_name" class="rr-input" placeholder="Doe" required oninput="allowOnlyAlphabets(this)" autocomplete="family-name">
                            </div>
                        </div>
                    </div>

                    {{-- Username & Profile Picture --}}
                    <div class="rr-field-row">
                        <div class="rr-field">
                            <label class="rr-label">Username</label>
                            <div class="rr-input-group">
                                <i class="fa fa-user"></i>
                                <input type="text" maxlength="30" name="username" id="username" class="rr-input" placeholder="john_doe" required oninput="validateUsername(this)" autocomplete="given-name">
                            </div>
                            <small id="username_feedback" style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Profile Picture
                                <small style="font-weight:400;color:var(--rr-text-light);">(optional)</small>
                            </label>
                            <input type="file" id="pfp" name="pfp" class="rr-input" style="padding:10px;" accept="image/*">
                        </div>
                    </div>

                    {{-- Email & Phone --}}
                    <div class="rr-field-row">
                        <div class="rr-field">
                            <label class="rr-label">Email Address</label>
                            <div class="rr-input-group">
                                <i class="fa fa-envelope"></i>
                                <input type="email" name="email" id="email" class="rr-input" maxlength="30" placeholder="you@example.com" required autocomplete="email">
                            </div>
                            <small id="email_feedback" style="display:none;font-size:0.82rem;margin-top:4px;"></small>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Phone Number</label>
                            <div class="rr-input-group">
                                <i class="fa fa-phone"></i>
                                <input type="tel" name="phone" id="phone" class="rr-input" placeholder="03xxxxxxxxx" required autocomplete="tel" oninput="validatePakPhone(this)">
                            </div>
                            <small id="phone_error" class="text-danger"></small>
                            <small id="phone_feedback" style="display:none;font-size:0.82rem;margin-top:2px;"></small>
                        </div>
                    </div>

                    {{-- Password & Confirm Password --}}
                    <div class="rr-field-row">
                        <div class="rr-field">
                            <label class="rr-label">Password</label>
                            <div class="rr-input-group rr-input-group--eye">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="reg_password" name="reg_password" class="rr-input" placeholder="••••••••" required autocomplete="new-password" oninput="validatePassword(this)" maxlength="30">
                                <button type="button" class="rr-eye-toggle"
                                    onclick="togglePassword('reg_password', this)" aria-label="Toggle password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            <small id="password_error" class="text-danger"></small>
                        </div>

                        <div class="rr-field">
                            <label class="rr-label">Confirm Password</label>
                            <div class="rr-input-group rr-input-group--eye">
                                <i class="fa fa-lock"></i>
                                <input type="password" id="regCPassword" name="regCPassword" class="rr-input" placeholder="••••••••" required autocomplete="new-password" oninput="validateConfirmPassword()" maxlength="30">
                                <button type="button" class="rr-eye-toggle" onclick="togglePassword('regCPassword', this)"
                                    aria-label="Toggle confirm password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            <small id="confirm_password_error" class="text-danger"></small>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" />
                        <small style="font-weight:400;color:var(--rr-text-light);">By clicking "Sign up", you agree to
                            our <a href="{{ route('terms') }}" target="_blank">Terms of Service</a> and acknowledge you have read our <a
                                href="{{ route('privacy') }}" target="_blank">Privacy Policy</a>.</small>
                    </div>

                    <div id="signup-recaptcha-widget" class="mb-3 d-flex justify-content-center"></div>
                    <p id="signup-recaptcha-error" class="text-danger text-center" style="display:none;font-size:0.84rem;margin-top:-8px;margin-bottom:8px;">Please complete the human verification.</p>

                    <button type="button" id="signUp-btn" onclick="signUpValidationCheck()"
                        class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg">
                        Sign Up
                    </button>
                </form>

                <div class="rr-auth__alt">
                    Already have an account? <a href="{{ route('login') }}">Sign in</a>
                </div>
            </div>

            {{-- Email Verify Form card --}}
            <div class="rr-auth__form d-none" id="EmailVerificationDiv">
                <a class="navbar-brand rr-logo rr-auth__desktop-logo" href="{{ route('home') }}"
                    style="margin-bottom: 1rem">
                    <span class="rr-logo-mark">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    </span>

                    <span class="rr-logo-text ms-2">
                        <strong>Rapid <span>Rescue</span></strong>
                        <small class="d-block">Ambulance System</small>
                    </span>
                </a>

                <h2>Verify your email</h2>
                <p id="verifySubtitle">
                    We've sent a 6-digit verification code to your email address.
                    Please enter it below to activate your account.
                </p>

                <form id="emailVerificationForm">
                    @csrf
                    {{-- Email Address (disabled, auto-filled from signup) --}}
                    <div class="rr-field">
                        <label class="rr-label">Email Address</label>
                        <div class="rr-input-group">
                            <i class="fa fa-envelope"></i>
                            <input type="email" name="verify_email" id="verify_email" maxlength="30" class="rr-input" placeholder="you@example.com" readonly disabled autocomplete="off" style="background:#f3f4f6;cursor:not-allowed;color:#6b7280;">
                        </div>
                    </div>

                    {{-- Verification Code --}}
                    <div class="rr-field mt-3">
                        <label class="rr-label">Verification Code</label>
                        <div class="rr-input-group">
                            <i class="fa fa-shield-alt"></i>
                            <input type="text" name="verification_code" id="verification_code" class="rr-input" maxlength="6" placeholder="Enter 6-digit code" oninput="onVerificationCodeInput(this)" autocomplete="one-time-code" inputmode="numeric">
                        </div>
                    </div>

                    {{-- Verify Button --}}
                    <button type="button" id="verifyEmailBtn" onclick="submitVerifyEmail()" class="rr-btn rr-btn--primary rr-btn--block rr-btn--lg mt-4" disabled>
                        Verify Email
                    </button>

                    {{-- Resend Code --}}
                    <button type="button" id="resendCodeBtn" onclick="resendVerificationCode()" class="rr-btn rr-btn--secondary rr-btn--block rr-btn--lg mt-2" disabled>
                        <span id="resendBtnText">Resend Code</span>
                        <span id="resendTimer" style="display:none;font-size:0.85rem;opacity:.8;margin-left:6px;"></span>
                    </button>
                    <p id="resendLimitMsg" class="text-danger text-center mt-2" style="display:none;font-size:0.83rem;"></p>
                </form>

                <div class="rr-auth__alt">
                    Wrong email? <a href="{{ route('signup') }}">Go back to Sign Up</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('assets/user/js/signup.js') }}"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoad&render=explicit" async defer></script>

    <script>
        @if (session('success'))
            showAlert('success', @json(session('success')));
        @elseif (session('error'))
            showAlert('error', @json(session('error')));
        @endif

        window.routes = {
            signSubmit:    "{{ route('signupApi') }}",
            checkAvail:    "{{ route('checkAvailability') }}",
            sendVerify:    "{{ route('email.verify.send') }}",
            resendVerify:  "{{ route('email.verify.resend') }}",
            verifyCode:    "{{ route('email.verify.verify') }}",
            csrfToken:     "{{ csrf_token() }}"
        };

        window.recaptchaSiteKey = '{{ config("recaptcha.site_key") }}';

        var signupWidgetId;

        function onRecaptchaLoad() {
            signupWidgetId = grecaptcha.render('signup-recaptcha-widget', {
                sitekey: window.recaptchaSiteKey
            });
        }
    </script>
</body>

</html>
