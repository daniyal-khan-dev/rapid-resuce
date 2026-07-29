<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Driver Portal') — Rapid Rescue</title>

    {{-- FAVICONS --}}
    <link rel="icon" type="image/png" href="{{ asset('assets/user/img/logo/logo.png') }}">

    {{-- BOOTSTRAP CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    {{-- FONTAWESOME --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- GOOGLE FONTS --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- CUSTOM CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/driver/css/driver.css') }}">
    @stack('styles')
    <style>
        .pgd-scroll {
            overflow-y: auto;
            overflow-x: auto;
            max-height: 364px;
        }

        .pgd-scroll--list {
            max-height: 448px;
        }

        .pgd-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .pgd-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .pgd-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .12);
            border-radius: 3px;
        }

        .pgd-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #0e1728 !important;
            box-shadow: 0 1px 0 rgba(255, 255, 255, .07);
        }

        .pgd-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, .07);
            background: rgba(255, 255, 255, .02);
        }

        .pgd-info {
            font-size: .78rem;
            color: rgba(255, 255, 255, .40);
        }

        .pgd-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pgd-pages {
            font-size: .78rem;
            color: rgba(255, 255, 255, .40);
            min-width: 94px;
            text-align: center;
        }

        .pgd-btn {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .10);
            color: #e2e8f0;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            transition: background .15s, border-color .15s;
            line-height: 1.5;
            font-family: inherit;
        }

        .pgd-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, .12);
            border-color: rgba(255, 255, 255, .22);
        }

        .pgd-btn:disabled {
            opacity: .32;
            cursor: not-allowed;
        }

        /* ── Requests nav badge ─────────────────────────────────── */
        .dri-req-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ef4444;
            color: #fff;
            font-size: .60rem;
            font-weight: 700;
            min-width: 17px;
            height: 17px;
            border-radius: 9px;
            padding: 0 4px;
            margin-left: auto;
            line-height: 1;
            pointer-events: none;
            animation: driReqBadgePop .2s ease;
        }

        @keyframes driReqBadgePop {
            0% {
                transform: scale(.6);
                opacity: 0;
            }

            80% {
                transform: scale(1.15);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .dri-feedback-badge {
            background: #fbbf24;
            color: #1f2937;
        }
    </style>
</head>

<body>
    {{-- Success Toast --}}
    <div class="dri-toast dri-toast--success" id="driToastSuccess">
        <i class="fa fa-circle-check"></i>
        <span class="dri-toast-text"></span>
    </div>

    {{-- Error Toast --}}
    <div class="dri-toast dri-toast--error" id="driToastError">
        <i class="fa fa-circle-exclamation"></i>
        <span class="dri-toast-text"></span>
    </div>

    {{-- Sidebar overlay (mobile) --}}
    <div class="dri-sidebar-overlay" id="driSidebarOverlay" onclick="toggleSidebar()"></div>

    {{-- Sidebar --}}
    <aside class="dri-sidebar" id="driSidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('assets/user/img/logo/logo.png') }}" alt="Rapid Rescue">
            <div>
                <strong>Rapid Rescue</strong>
                <small>Driver Panel</small>
            </div>
            <button class="btn-sidebar-close" onclick="toggleSidebar()" aria-label="Close menu">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('driver.dashboard') }}"
                class="sidebar-nav-link {{ request()->routeIs('driver.dashboard') ? 'active' : '' }}">
                <i class="fa fa-gauge-high"></i> Dashboard
            </a>
            
            <div class="sidebar-section-label">My Work</div>
            @php
                $driver_id = Auth::guard('driver')->user()->id;
                $pendingEmergencyCount = \App\Models\User\EmergencyRequest::where('driver_id', $driver_id )->whereNotIn('status', ['6', '7'])->count();
                $driChatUnread = \App\Models\Chat\RideChatMessage::where('is_read_driver', false)->whereHas('emergencyRequest', function ($q) use ($driver_id) {$q->where('driver_id', $driver_id);})->distinct()->count('emergency_request_id');
                $unviewedDriverFeedbackCount = \App\Models\User\Feedback::whereNull('driver_viewed_at')
                    ->whereHas('request', function ($requestQuery) use ($driver_id) {
                        $requestQuery->where('driver_id', $driver_id);
                    })
                    ->count();
            @endphp

            <a href="{{ route('driver.requests.grid') }}"
               class="sidebar-nav-link {{ request()->routeIs('admin.emergency.grid') ? 'active' : '' }}">
                <i class="fa-solid fa-truck-medical"></i> Requests
                <span id="driReqNavBadge" class="dri-req-badge" style="display:{{ $pendingEmergencyCount > 0 ? 'inline-flex' : 'none' }};">{{ $pendingEmergencyCount }}</span>
            </a>

            <a href="{{ route('driver.past-rides') }}"
                class="sidebar-nav-link {{ request()->routeIs('driver.past-rides') ? 'active' : '' }}">
                <i class="fa fa-clock-rotate-left"></i> Past Rides
            </a>

            <a href="{{ route('driver.ride-chat.grid') }}"
                class="sidebar-nav-link {{ request()->routeIs('driver.ride-chat*') ? 'active' : '' }}">
                <i class="fa fa-comments"></i> Ride Chat
                <span id="driChatNavBadge" class="dri-req-badge" style="display:{{ $driChatUnread > 0 ? 'inline-flex' : 'none' }};background:#3b82f6;">{{ $driChatUnread > 99 ? '99+' : $driChatUnread }}</span>
            </a>

            <a href="{{ route('driver.feedback.grid') }}"
                class="sidebar-nav-link {{ request()->routeIs('driver.feedback.grid') ? 'active' : '' }}">
                <i class="fa fa-star"></i> Feedback
                <span id="driFeedbackNavBadge" class="dri-req-badge dri-feedback-badge"
                     style="display:{{ $unviewedDriverFeedbackCount > 0 ? 'inline-flex' : 'none' }};">{{ $unviewedDriverFeedbackCount }}</span>
            </a>
            
            <div class="sidebar-section-label">Account</div>
            <a href="{{ route('driver.profile.grid') }}"
                class="sidebar-nav-link {{ request()->routeIs('driver.profile') ? 'active' : '' }}">
                <i class="fa fa-circle-user"></i> My Profile
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-driver-info">
                <div class="sidebar-driver-avatar"><i class="fa fa-id-card"></i></div>
                <div>
                    <strong>{{ Auth::guard('driver')->user()->name }}</strong>
                    <small>{{ Auth::guard('driver')->user()->username }}</small>
                </div>
            </div>

            <form method="POST" action="{{ route('driver.logout') }}">
                @csrf
                <button type="submit" class="btn-sidebar-logout">
                    <i class="fa fa-right-from-bracket"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <main class="dri-main">
        <header class="dri-topbar">
            <button class="btn-menu-toggle" onclick="toggleSidebar()" aria-label="Toggle menu">
                <i class="fa fa-bars"></i>
            </button>
            <span class="dri-topbar__title">@yield('page_title', 'Dashboard')</span>

            <div class="d-flex align-items-center gap-2">
                <div class="dri-topbar__avatar"><i class="fa fa-id-card"></i></div>
                <div class="d-none d-md-block">
                    <div class="topbar-driver-name">{{ Auth::guard('driver')->user()->name }}</div>
                    <div class="topbar-driver-role">Driver</div>
                </div>
            </div>
        </header>

        <div class="dri-content">
            @yield('content')
        </div>

        {{-- Footer --}}
        <footer class="dri-footer">
            <span class="dri-footer-copy">
                &copy; {{ date('Y') }} <strong>Rapid Rescue</strong>. All rights reserved.
            </span>
            <span class="dri-footer-tagline">Ambulance Dispatch System</span>
        </footer>
    </main>

    {{-- BOOTSTRAP JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Pusher JS (for Reverb real-time) --}}
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    
    {{-- Reverb real-time init (mirrors admin layout) --}}
    <script>
        window.driDriverId = {{ Auth::guard('driver')->user()->id }};
        window.driLocationUpdateUrl = "{{ route('driver.location.update') }}";

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
            
        }());

        window.driverRoutes = {
            feedbackViewed: "{{ url('driver/feedback') }}",
        };
        window._rrUpdateDriverFeedbackBadge = function (count) {
            var badge = document.getElementById('driFeedbackNavBadge');
            if (!badge) return;

            var normalizedCount = Math.max(0, Number(count) || 0);
            badge.textContent = normalizedCount;
            badge.style.display = normalizedCount > 0 ? 'inline-flex' : 'none';
        };
    </script>

    {{-- CUSTOM JS --}}
    <script src="{{ asset('assets/driver/js/script.js') }}"></script>
    <script src="{{ asset('assets/driver/js/realtime.js') }}"></script>

    {{-- Flash session toasts --}}
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof driToastSuccess === 'function') driToastSuccess("{{ session('success') }}");
            });
        </script>
    @endif
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof driToastError === 'function') driToastError("{{ $errors->first() }}");
            });
        </script>
    @endif

    @stack('scripts')
    @stack('modals')
</body>

</html>
