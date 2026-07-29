<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rapid Rescue — Admin Login</title>

    {{-- FAVICONS --}}
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">

    {{-- BOOTSTRAP CSS - CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">

    {{-- FONTAWESOME CSS - CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- GOOGLE FONTS - CDN --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- CUSTOM CSS - CDN --}}
    <link rel="stylesheet" href="{{ asset('assets/admin/css/auth.css') }}">
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

    {{-- AMDIN LOGIN FORM --}}
    <div class="admin-bg-grid"></div>
    <div class="admin-card">
        <a href="{{ route('home') }}" class="admin-logo">
            <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
            <div class="admin-logo-text">
                <strong>Rapid Rescue</strong>
                <small>Ambulance System</small>
            </div>
        </a>

        <div class="admin-badge">
            <i class="fa fa-shield-halved"></i> Admin Control Panel
        </div>

        <h2>Welcome back,<br>Administrator.</h2>
        <p>Sign in to manage dispatch, drivers, and system settings.</p>

        <div class="admin-divider"></div>

        <div class="admin-security-note">
            <i class="fa fa-lock"></i>
            This area is restricted to authorised personnel only. All activity is logged.
        </div>

        <form id="adminLoginForm">
            @csrf
            <div class="admin-field">
                <label class="admin-label" for="admin_email">Admin Email</label>
                <div class="admin-input-group">
                    <i class="fa fa-envelope field-icon"></i>
                    <input type="email" id="admin_email" name="admin_email" class="admin-input" placeholder="admin@rapidrescue.com" required maxlength="100">
                </div>
            </div>

            <div class="admin-field">
                <label class="admin-label" for="admin_password">Password</label>
                <div class="admin-input-group">
                    <i class="fa fa-lock field-icon"></i>
                    <input type="password" id="admin_password" name="admin_password" class="admin-input admin-input--eye" placeholder="••••••••" required>
                    <button type="button" class="admin-eye-toggle" onclick="toggleAdminPassword()" aria-label="Toggle password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="admin-remember-row">
                <label class="admin-remember-label">
                    <input type="checkbox" id="admin_remember" name="remember" checked> Keep me signed in
                </label>
            </div>

            <button type="button" id="admin-login-btn" onclick="adminLoginCheck()" class="admin-btn">
                <i class="fa fa-right-to-bracket"></i> Sign In to Admin Panel
            </button>
        </form>
    </div>

    {{-- LOGOUT MESSAGE --}}
    @if (session('logout_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var alertEl = document.getElementById('successAlert');
                var textEl = document.getElementById('successAlertText');
                if (alertEl && textEl) {
                    textEl.textContent = '{{ session('logout_success') }}';
                    alertEl.style.display = 'block';
                    alertEl.classList.add('show');
                    setTimeout(function() {
                        alertEl.classList.remove('show');
                        setTimeout(function() {
                            alertEl.style.display = 'none';
                        }, 150);
                    }, 4000);
                }
            });
        </script>
    @endif

    {{-- JQUERY CDN - JS --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- ROUTES --}}
    <script>
        window.adminRoutes = {
            loginApi: "{{ route('admin.loginApi') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    
    {{-- CUSTOM AUTH - JS --}}
    <script src="{{ asset('assets/admin/js/auth.js') }}"></script>
</body>

</html>
