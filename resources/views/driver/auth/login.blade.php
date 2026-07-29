<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapid Rescue — Driver Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/driver/css/auth.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

    {{-- SUCCESS ALERT --}}
    <div id="successAlert" class="alert alert-success alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display:none;z-index:1055;">
        <span id="successAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideSuccessAlert()"></button>
    </div>

    {{-- ERROR ALERT --}}
    <div id="errorAlert" class="alert alert-danger alert-dismissible fade position-fixed top-0 end-0 m-3 shadow"
        role="alert" style="display:none;z-index:1055;">
        <span id="errorAlertText"></span>
        <button type="button" class="btn-close" aria-label="Close" onclick="hideErrorAlert()"></button>
    </div>

    <div class="driver-wrap">

        {{-- Desktop left hero panel --}}
        <div class="driver-hero">
            <div class="driver-hero-inner">
                <a href="{{ route('home') }}" class="driver-hero-logo">
                    <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    <div class="driver-hero-logo-text">
                        <strong>Rapid Rescue</strong>
                        <small>Ambulance System</small>
                    </div>
                </a>

                <div class="driver-hero-content">
                    <h2>Driver Portal</h2>
                    <p>Sign in to access your dispatch queue, navigate to patients, and update trip status in real time.</p>

                    <div class="driver-status-grid">
                        <div class="driver-status-card">
                            <i class="fa fa-truck-medical"></i>
                            <h6>Dispatch Queue</h6>
                            <span>Incoming calls assigned to you.</span>
                        </div>
                        <div class="driver-status-card">
                            <i class="fa fa-route"></i>
                            <h6>Navigation</h6>
                            <span>Optimised routes to patient.</span>
                        </div>
                        <div class="driver-status-card">
                            <i class="fa fa-signal"></i>
                            <h6>Live Status</h6>
                            <span>Update your availability instantly.</span>
                        </div>
                        <div class="driver-status-card">
                            <i class="fa fa-clipboard-list"></i>
                            <h6>Trip Logs</h6>
                            <span>Full history of your runs.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="driver-hero-footer">
                &copy; {{ date('Y') }} Rapid Rescue. All rights reserved.
            </div>
        </div>

        {{-- Right form panel --}}
        <div class="driver-form-wrap">

            {{-- Mobile hero --}}
            <div class="driver-mobile-hero">
                <div class="driver-mobile-hero-content">
                    <a href="{{ route('home') }}" class="driver-mobile-logo">
                        <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                        <span>
                            <strong>Rapid Rescue</strong>
                            <small>Driver Portal</small>
                        </span>
                    </a>
                    <div class="driver-mobile-badges">
                        <span class="driver-mobile-badge"><i class="fa fa-truck-medical"></i> Dispatch Queue</span>
                        <span class="driver-mobile-badge"><i class="fa fa-route"></i> Navigation</span>
                        <span class="driver-mobile-badge"><i class="fa fa-signal"></i> Live Status</span>
                    </div>
                </div>
                <div class="driver-mobile-wave">
                    <svg viewBox="0 0 375 54" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 54 C90 10 280 45 375 0 L375 54 Z" fill="#ffffff"/>
                    </svg>
                </div>
            </div>

            {{-- Form --}}
            <div class="driver-form-inner">

                <a href="{{ route('home') }}" class="driver-desktop-logo">
                    <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                    <div class="driver-desktop-logo-text">
                        <strong>Rapid Rescue</strong>
                        <small>Ambulance System</small>
                    </div>
                </a>

                <div class="driver-portal-badge">
                    <i class="fa fa-truck-medical"></i> Driver Portal
                </div>

                <h2>Sign in to your<br>driver account</h2>
                <p>Enter your credentials to access dispatch and navigation.</p>

                <form id="drvLoginForm" novalidate>
                    @csrf

                    <div class="drv-field">
                        <label class="drv-label">Driver Email</label>
                        <div class="drv-input-group">
                            <i class="fa fa-envelope field-icon"></i>
                            <input type="email" name="email" id="drv_email" class="drv-input" placeholder="driver@rapidrescue.com"
                                autocomplete="username" required>
                        </div>
                        <small class="drv-err text-danger" id="drv_err_email"></small>
                    </div>

                    <div class="drv-field">
                        <label class="drv-label">Password</label>
                        <div class="drv-input-group">
                            <i class="fa fa-lock field-icon"></i>
                            <input type="password" id="drv_password_input" name="password" class="drv-input drv-input--eye"
                                placeholder="••••••••" autocomplete="current-password" required>
                            <button type="button" class="drv-eye-toggle" onclick="toggleDrvPassword()" aria-label="Toggle password">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <small class="drv-err text-danger" id="drv_err_password"></small>
                    </div>

                    <div class="drv-remember-row">
                        <label class="drv-remember-label">
                            <input type="checkbox" id="drv_remember" checked> Keep me signed in
                        </label>
                    </div>

                    <button type="submit" class="drv-btn" id="drvLoginBtn">
                        <i class="fa fa-truck-medical"></i> Sign In to Portal
                    </button>

                </form>
            </div>
        </div>

    </div>

    {{-- LOGOUT MESSAGE --}}
    @if (session('logout_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var alertEl = document.getElementById('successAlert');
                var textEl  = document.getElementById('successAlertText');
                if (alertEl && textEl) {
                    textEl.textContent = '{{ session('logout_success') }}';
                    alertEl.style.display = 'block';
                    alertEl.classList.add('show');
                    setTimeout(function() {
                        alertEl.classList.remove('show');
                        setTimeout(function() { alertEl.style.display = 'none'; }, 150);
                    }, 4000);
                }
            });
        </script>
    @endif

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.driverRoutes = { loginApi: "{{ route('driver.loginApi') }}", csrfToken: "{{ csrf_token() }}" };
    </script>
    <script src="{{ asset('assets/driver/js/auth.js') }}"></script>
</body>
</html>
