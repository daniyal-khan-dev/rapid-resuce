<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rapid Rescue — 24/7 Ambulance Service')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">

    <!-- BOOTSTRAP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Autocomplete styles -->
    <link rel="stylesheet" href="{{ asset('assets/user/css/autocomplete.css') }}">

    <!-- GOOGLE FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet">

    <!-- STYLES -->
    <link rel="stylesheet" href="{{ asset('assets/user/css/style.css') }}">

    <!-- PAGE-SPECIFIC STYLES -->
    @stack('styles')

    <!-- JQUERY -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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

    <!-- Page Loader -->
    <div id="rr-loader">
        <div class="rr-loader-ring"></div>
        <div class="rr-loader-logo">Rapid <span>Rescue</span></div>
    </div>

    {{-- Scroll to top --}}
    <a class="btn btn-secondary btn-lg-square rounded-circle back-to-top" id="scroll-top-button" data-scroll="rrTopbar"
        href="javascript:void(0)">
        <i class="fa fa-arrow-up"></i>
    </a>

    <!-- Top bar -->
    <div class="rr-topbar" id="rrTopbar">
        <div class="container">
            <div class="row align-items-center py-2">
                <div class="col d-flex flex-wrap fle align-items-center gap-2 rr-topbar-left">
                    <span class="rr-info">
                        <i class="fa-solid fa-clock"></i> 24/7 Ambulance Service
                    </span>

                    <a href="tel:{{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}" data-rr-ci="phone">
                        <i class="fas fa-phone-alt"></i> {{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}
                    </a>

                    <a href="mailto:{{ $contactInfo->email ?? 'info@rapidrescue.com' }}" data-rr-ci="email">
                        <i class="fas fa-envelope"></i> {{ $contactInfo->email ?? 'info@rapidrescue.com' }}
                    </a>
                </div>

                <div class="col-lg-4 d-flex justify-content-lg-end">
                    <div class="rr-topbar-socials">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg rr-nav" id="rrNav">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand rr-logo" href="{{ route('home') }}">
                <span class="rr-logo-mark">
                    <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
                </span>

                <span class="rr-logo-text ms-2">
                    <strong>Rapid <span>Rescue</span></strong>
                    <small class="d-block">Ambulance System</small>
                </span>
            </a>

            <!-- Toggler -->
            <button class="navbar-toggler rr-nav-toggle" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Collapsible Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Menu -->
                <ul class="navbar-nav mx-auto rr-nav-menu" id="rrNavMenu">
                    <li class="nav-item">
                        <a class="nav-link" data-scroll="home" href="javascript:void(0)">Home</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="features" href="javascript:void(0)">Features</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="services" href="javascript:void(0)">Services</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="ambulances" href="javascript:void(0)">Ambulances</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="about-us" href="javascript:void(0)">About</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="testimonials" href="javascript:void(0)">Testimonials</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="faq" href="javascript:void(0)">FAQS</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-scroll="contact-form" href="javascript:void(0)">Contact</a>
                    </li>
                </ul>

                <!-- CTA -->
                <div class="rr-nav__cta">
                    @guest('users')
                        <a href="{{ route('login') }}" class="rr-btn rr-btn--outline btn-sm">Login</a>
                        <a href="{{ route('signup') }}" class="rr-btn rr-btn--primary btn-sm">Sign Up</a>
                    @endguest

                    @auth('users')
                        @php
                            $authUser    = Auth::guard('users')->user()->details;
                            $displayName = $authUser ? $authUser->first_name : Auth::guard('users')->user()->username;
                            $pfp         = $authUser ? $authUser->profile_picture : 'default.jpg';
                            $email       = $authUser ? $authUser->email : '';
                            $pfpUrl      = asset('assets/user/img/users/' . $pfp);
                        @endphp

                        <div class="dropdown">
                            <div class="rr-user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="{{ $pfpUrl }}" alt="{{ $displayName }}">
                                <span>{{ $displayName }}</span>
                                <i class="fa fa-chevron-down rr-user-menu__caret"></i>
                            </div>

                            <div class="dropdown-menu dropdown-menu-end rr-user-dropdown">
                                <div class="rr-user-dropdown__header">
                                    <img src="{{ $pfpUrl }}" alt="{{ $displayName }}" class="rr-user-dropdown__avatar">
                                    <div class="rr-user-dropdown__info">
                                        <strong>{{ $displayName }}</strong>
                                        <span>{{ $email }}</span>
                                        <em>Consumer No: {{ $authUser->consumer_no ?? 'N/A' }}</em>
                                    </div>
                                </div>

                                <div class="rr-user-dropdown__divider"></div>

                                <a href="{{ route('profile.grid') }}" class="rr-user-dropdown__item">
                                    <span class="rr-user-dropdown__icon" style="background:var(--rr-primary-50);color:var(--rr-primary);">
                                        <i class="fa fa-user"></i>
                                    </span>

                                    <span class="rr-user-dropdown__label">
                                        <strong>Profile</strong>
                                        <small>View &amp; edit your info</small>
                                    </span>
                                </a>

                                <a href="{{ route('medicalCard.grid') }}" class="rr-user-dropdown__item">
                                    <span class="rr-user-dropdown__icon" style="background:#fff0f1;color:var(--rr-primary);">
                                        <i class="fa fa-id-card-clip"></i>
                                    </span>

                                    <span class="rr-user-dropdown__label">
                                        <strong>Medical Card</strong>
                                        <small>Blood type, allergies &amp; conditions</small>
                                    </span>
                                </a>

                                <a href="{{ route('first-aid') }}" class="rr-user-dropdown__item">
                                    <span class="rr-user-dropdown__icon" style="background:#f0fdf4;color:#16a34a;">
                                        <i class="fa fa-kit-medical"></i>
                                    </span>

                                    <span class="rr-user-dropdown__label">
                                        <strong>First-Aid Guide</strong>
                                        <small>Emergency instructions</small>
                                    </span>
                                </a>

                                <div class="rr-user-dropdown__divider"></div>

                                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                    @csrf
                                    <button type="submit" class="rr-user-dropdown__item rr-user-dropdown__item--danger w-100"
                                        style="background:none;border:none;text-align:left;cursor:pointer;">
                                        <span class="rr-user-dropdown__icon" style="background:#fff0f1;color:var(--rr-primary);">
                                            <i class="fa fa-sign-out-alt"></i>
                                        </span>

                                        <span class="rr-user-dropdown__label">
                                            <strong>Logout</strong>
                                            <small>Sign out of your account</small>
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    @yield('content')

    {{-- Footer --}}
    <footer class="rr-footer">
        <div class="container" style="position:relative;">
            <div class="row g-5 mb-5">
                <div class="col-md-6 col-lg-4 rr-footer__col rr-footer__brand">
                    <a class="rr-logo d-inline-flex align-items-center mb-3" href="{{ route('home') }}">
                        <span class="rr-logo-mark">
                            <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue"
                                style="filter:brightness(0) invert(1);width:46px;">
                        </span>

                        <span class="rr-logo-text ms-2">
                            <strong style="color:#fff;">Rapid <span style="color:#fff;">Rescue</span></strong>
                            <small class="d-block" style="color:#fff;">Ambulance System</small>
                        </span>
                    </a>

                    <p>Trusted 24/7 emergency response. We bring rapid, professional care to your doorstep — whenever, wherever you need it.</p>

                    <div class="rr-footer__social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <div class="col-6 col-lg-2 rr-footer__col">
                    <h5>Explore</h5>
                    <a href="javascript:void(0)" data-scroll="features">Features</a>
                    <a href="javascript:void(0)" data-scroll="ambulances">Ambulances</a>
                    <a href="javascript:void(0)" data-scroll="services">Services</a>
                    <a href="javascript:void(0)" data-scroll="about-us">About</a>
                    <a href="javascript:void(0)" data-scroll="testimonials">Testimonials</a>
                    <a href="javascript:void(0)" data-scroll="faq">FAQ</a>
                </div>

                <div class="col-6 col-lg-2 rr-footer__col">
                    <h5>Support</h5>
                    <a href="/?s=contact-form" data-section="contact-form">Contact Us</a>
                    <a href="{{ route('first-aid.page') }}">First Aid Guide</a>
                    @guest('users')
                        <a href="{{ route('login') }}">Login</a>
                        <a href="{{ route('signup') }}">Signup</a>
                    @endguest
                    @auth('users')
                        <a href="{{ route('profile.grid') }}">My Account</a>
                        <a href="javascript:void(0)" onclick="window.location.href = window.routes.contactHistory">My Contact History</a>
                    @endauth
                    <a href="{{ route('privacy') }}">Privacy Policy</a>
                    <a href="{{ route('terms') }}">Terms of Service</a>
                </div>
                
                <div class="col-md-6 col-lg-4 rr-footer__col">
                    <h5>Get in Touch</h5>
                    <div class="rr-footer__contact-row">
                        <i class="fas fa-map-marker-alt"></i>
                        <span data-rr-ci="address">{{ $contactInfo->address ?? 'XYZ Corporate Office, DHA Phase 6, Karachi, Pakistan' }}</span>
                    </div>

                    <div class="rr-footer__contact-row">
                        <i class="fas fa-phone-alt"></i>
                        <span data-rr-ci="phone">{{ $contactInfo->phone ?? '+92 xxx xxxxxxx' }}</span>
                    </div>

                    <div class="rr-footer__contact-row">
                        <i class="fas fa-envelope"></i>
                        <span data-rr-ci="email">{{ $contactInfo->email ?? 'info@rapidrescue.com' }}</span>
                    </div>

                    <div class="rr-footer__contact-row">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Emergency Response</span>
                    </div>
                </div>
            </div>

            <div class="rr-copyright">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>&copy; {{ date('Y') }} <a href="https://daniyal-khan.com/">Daniyal Khan</a>. All rights
                        reserved.
                    </div>
                    <div>Designed for saving lives, every second of every day.</div>
                </div>
            </div>
        </div>
    </footer>

    <!-- BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>

    {{-- PUSHER JS (required for real-time WebSocket via Reverb) --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

    {{-- REVERB CONFIG (reuses the same public admin-dashboard channel/connection pattern) --}}
    <script>
        (function () {
            var key        = '{{ env("REVERB_APP_KEY") }}';
            var reverbPort = {{ (int) env('REVERB_PORT', 8080) }};
            var host       = window.location.hostname;
            var isLocalhost = (host === 'localhost' || host === '127.0.0.1' || host === '');

            window.reverbConfig = {
                key:        key,
                wsHost:     host,
                wsPort:     reverbPort,
                wssPort:    reverbPort,
                forceTLS:   !isLocalhost,
                transports: isLocalhost ? ['ws'] : ['ws', 'wss'],
            };
            
            if (!window.reverbConfig) return;

            window.pusher = new Pusher(window.reverbConfig.key, {
                wsHost: window.reverbConfig.wsHost,
                wsPort: window.reverbConfig.wsPort,
                wssPort: window.reverbConfig.wssPort,
                forceTLS: window.reverbConfig.forceTLS,
                enabledTransports: window.reverbConfig.transports,
                cluster: "mt1",
                disableStats: true,
                authEndpoint: "/broadcasting/auth",
                auth: {
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                    },
                },
            });

            if (!window.Pusher) return;

            window.channel = window.pusher.subscribe("admin-dashboard");
            window.initialBranches = @json(\App\Models\Admin\Branch::where('status', 1)->orderBy('id', 'asc')->get());
            window.initialFleetAmbs = @json(\App\Models\Admin\Ambulance::where('status', 1)->whereNotNull('card_title')->orderBy('type')->get());
         
            @auth('users')
            if (!window.contactUserChannel) {
                window.contactUserChannel = window.pusher.subscribe("private-contact.user.{{ Auth::guard('users')->id() }}");
            }
            if (!window.userRideChatChannel) {
                window.userRideChatChannel = window.pusher.subscribe("user.{{ Auth::guard('users')->id() }}");
            }
            @endauth
        })();
    </script>

    <!-- CUSTOM JS -->
    <script src="{{ asset('assets/user/js/script.js') }}"></script>

    {{-- REAL-TIME CONTENT SYNC (Services / FAQs / Testimonials) --}}
    <script src="{{ asset('assets/user/js/realtime.js') }}"></script>

    <script>
        @if (session('success'))
            showAlert('success', @json(session('success')));
        @elseif (session('error'))
            showAlert('error', @json(session('error')));
        @endif
    </script>

    @yield('extra_scripts')
    @stack('scripts')

</body>

</html>
