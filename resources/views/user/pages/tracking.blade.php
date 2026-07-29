@extends('user.layouts.user')
@section('title', 'Track Ambulance — Rapid Rescue')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/user/css/tracking.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-control.leaflet-control-attribution {
            display: none !important;
        }   
    </style>
@endpush

@section('content')
    <div class="rr-tracking-hero">
        <div class="container">
            <h1 style="color: white;"><i class="fas fa-map-marked-alt"></i> Real-Time Tracking</h1>
            <p>Emergency ID: <span style="text-transform: uppercase">{{ $req->rreb_id }}</span>
                &nbsp;·&nbsp;
                @if ($req->type === '1')
                    Emergency
                @elseif($req->type === '2')
                    non-emergency
                @endif
                &nbsp;·&nbsp;
                Submitted {{ $req->created_at->diffForHumans() }}
            </p>
        </div>
    </div>

    <div class="rr-tracking-wrap">
        <div class="container">
            <div class="row g-4">
                {{-- Map column --}}
                <div class="col-lg-8">
                    <div id="trackingMap"></div>
                    <div class="rr-refresh-btn mt-2">
                        <i class="fas fa-sync-alt"></i>
                        <i class="fas fa-circle"
                            style="color:#22c55e;font-size:.5rem;vertical-align:middle;animation:rrLivePulse 1.8s infinite;"></i>
                        Live real-time updates active &nbsp;|&nbsp; Last update:
                        <span id="lastTrackUpdate">just now</span>
                        <style>
                            @keyframes rrLivePulse {

                                0%,
                                100% {
                                    opacity: 1
                                }

                                50% {
                                    opacity: .25
                                }
                            }
                        </style>
                    </div>

                    {{-- Status timeline --}}
                    <div class="rr-status-timeline">
                        @php
                            $steps = ['pending', 'dispatched', 'en_route', 'arrived', 'transporting', 'completed'];
                            $statusToIdx = [
                                '1' => 0,
                                '8' => 0,
                                '2' => 1,
                                '3' => 2,
                                '4' => 3,
                                '5' => 4,
                                '6' => 5,
                            ];
                            $curIdx = $statusToIdx[$req->status] ?? -1;
                        @endphp

                        @foreach ($steps as $i => $step)
                            @php
                                $isCur = $i === $curIdx;
                                $isDone = $i < $curIdx;
                                $cls = $isCur ? 'active' : ($isDone ? 'done' : '');
                            @endphp

                            <div class="rr-tl-step {{ $cls }}">
                                <div class="rr-tl-dot">
                                    @if ($isDone)
                                        <i class="fa fa-check"></i>
                                    @elseif($isCur)
                                        <i class="fa fa-circle-dot"></i>
                                    @else
                                        <i class="fa fa-circle"></i>
                                    @endif
                                </div>
                                <span>{{ ucfirst(str_replace('_', ' ', $step)) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Info column --}}
                <div class="col-lg-4">
                    <div class="rr-tracking-status-big">
                        <h3 style="color: white;">
                            @php
                                $statusLabels = [
                                    '1' => '⏳ Awaiting Dispatch',
                                    '8' => '⏳ Awaiting Dispatch',
                                    '2' => '🚑 Ambulance Assigned',
                                    '3' => '🚨 En Route to You',
                                    '4' => '📍 Arrived at Scene',
                                    '5' => '🏥 Transporting',
                                    '6' => '✅ Trip Completed',
                                    '7' => '❌ Cancelled',
                                ];
                            @endphp
                            {{ $statusLabels[$req->status] ?? ucfirst($req->status) }}
                        </h3>

                        <p id="statusSubtext">
                            @if ($req->status === '1')
                                Your request has been received and is being reviewed.
                            @elseif($req->status === '2')
                                An ambulance has been assigned and will depart shortly.
                            @elseif($req->status === '3')
                                The ambulance is on its way to your location.
                            @elseif($req->status === '4')
                                The paramedic team has arrived at your pickup point.
                            @elseif($req->status === '5')
                                You are being transported to the hospital.
                            @elseif($req->status === '6')
                                Your trip has been completed. Thank you.
                            @elseif($req->status === '7')
                                Your trip has been cancelled. Thank you.
                            @else
                                Status updated.
                            @endif
                        </p>
                    </div>

                    <div class="rr-tracking-info">
                        <div class="rr-tracking-row">
                            <i class="fas fa-hashtag"></i>
                            <div><small>Emergency ID</small>
                                <div><strong style="text-transform: uppercase">{{ $req->rreb_id }}</strong></div>
                            </div>
                        </div>

                        <div class="rr-tracking-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <div><small>Pickup Address</small>
                                <div>{{ $req->pickup_address }}</div>
                            </div>
                        </div>

                        @if ($req->hospital_name)
                            <div class="rr-tracking-row">
                                <i class="fas fa-hospital"></i>
                                <div><small>Destination Hospital</small>
                                    <div>{{ $req->hospital_name }}</div>
                                </div>
                            </div>
                        @endif

                        <div class="rr-tracking-row">
                            <i class="fas fa-phone"></i>
                            <div><small>Contact Number</small>
                                <div>{{ $req->mobile_no }}</div>
                            </div>
                        </div>

                        <div id="rrDriverRow" class="rr-tracking-row" style="{{ ($req->status > 1 && $req->status < 7) ? '' : 'display:none;' }}">
                            <i class="fas fa-user"></i>
                            <div><small>Driver / Paramedic</small>
                                <div>
                                    <strong id="rrDriverName">{{ $req->driver?->name }}</strong><br><small id="rrDriverPhone">{{ $req->driver?->phone }}</small>
                                </div>
                            </div>
                        </div>
    
                        <div id="rrAmbulanceRow" class="rr-tracking-row" style="{{ ($req->status > 1 && $req->status < 7) ? '' : 'display:none;' }}">
                            <i class="fas fa-ambulance"></i>
                            <div><small>Ambulance</small>
                                <div><strong id="rrAmbulanceVehicle">{{ $req->ambulance?->vehicle_number }}</strong> — <span id="rrAmbulanceType">{{ $req->ambulance?->type }}</span></div>
                            </div>
                        </div>
    
                        <div id="rrDispatchedAtRow" class="rr-tracking-row" style="{{ ($req->status > 1 && $req->status < 7) ? '' : 'display:none;' }}">
                            <i class="fas fa-clock"></i>
                            <div><small>Dispatched At</small>
                                <div id="rrDispatchedAt">{{ $req->dispatched_at?->format('H:i, d M Y') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Cancel Ride — only shown while the ride is Pending (status 1) --}}
                    <div id="rrCancelRideWrap" class="mt-3"
                        @if ($req->status !== '1') style="display:none;" @endif>
                        <button id="rrCancelRideBtn" onclick="rrCancelRide()"
                            style="width:100%;padding:10px 16px;border-radius:10px;border:1.5px solid rgba(215,44,66,0.35);background:rgba(215,44,66,0.06);color:var(--rr-primary);font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;">
                            <i class="fas fa-ban"></i> Cancel Ride
                        </button>
                        <div id="rrCancelMsg"
                            style="display:none;margin-top:8px;font-size:.82rem;padding:8px 12px;border-radius:8px;"></div>
                    </div>

                    <div id="rrPendingNotice" class="rr-pending-notice mt-3"
                        @if ($req->status !== '1') style="display:none;" @endif>
                        <i class="fas fa-hourglass-half"></i>
                        <h5>Request Received</h5>
                        <p>Our team is reviewing your request. An ambulance will be assigned shortly.</p>
                    </div>

                    <div id="rrFeedbackCta" class="rr-feedback-cta mt-3"
                        @if ($req->status !== '6') style="display:none;" @endif>
                        @if ($alreadyRated)
                            <div
                                style="padding:10px 16px;border-radius:10px;background:rgba(34,197,94,0.08);color:#166534;border:1.5px solid rgba(34,197,94,0.25);font-size:.85rem;font-weight:600;text-align:center;">
                                <i class="fas fa-star" style="color:#f59e0b;"></i> You have already rated this ride.
                            </div>
                        @else
                            <button id="rrRateBtn" onclick="rrOpenRatingModal()"
                                style="width:100%;padding:10px 16px;border-radius:10px;border:none;background:var(--rr-grad-primary);color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:var(--rr-shadow-primary);">
                                <i class="fas fa-star"></i> Rate This Service
                            </button>
                        @endif
                    </div>

                    {{-- ── Rate This Service Modal ─────────────────────────────────── --}}
                    <div class="modal fade" id="rrRatingModal" tabindex="-1" aria-labelledby="rrRatingModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content"
                                style="border-radius:16px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);overflow:hidden;">

                                {{-- Header --}}
                                <div class="modal-header"
                                    style="background:var(--rr-grad-primary);border:none;padding:18px 24px;">
                                    <h5 class="modal-title" id="rrRatingModalLabel"
                                        style="color:#fff;font-weight:700;font-size:1rem;margin:0;">
                                        <i class="fas fa-star" style="margin-right:6px;"></i> Rate This Service
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                {{-- Body --}}
                                <div class="modal-body" style="padding:28px 24px 20px;">

                                    {{-- Star rating --}}
                                    <div style="text-align:center;margin-bottom:20px;">
                                        <p
                                            style="font-size:.82rem;color:var(--rr-text-muted);margin-bottom:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">
                                            Your Rating <span style="color:var(--rr-primary);">*</span>
                                        </p>
                                        <div id="rrStars"
                                            style="display:inline-flex;gap:6px;font-size:2rem;cursor:pointer;"
                                            role="group" aria-label="Star rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span class="rr-star" data-value="{{ $i }}"
                                                    style="color:#d1d5db;transition:color .15s,transform .1s;line-height:1;"
                                                    aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                                    &#9733;
                                                </span>
                                            @endfor
                                        </div>
                                        <div id="rrRatingError"
                                            style="display:none;color:var(--rr-primary);font-size:.78rem;margin-top:6px;font-weight:600;">
                                            Please select a star rating.
                                        </div>
                                    </div>

                                    {{-- Feedback textarea --}}
                                    <div>
                                        <label
                                            style="font-size:.82rem;font-weight:700;color:var(--rr-navy);margin-bottom:6px;display:block;">
                                            Feedback <span
                                                style="color:var(--rr-text-muted);font-weight:400;">(optional)</span>
                                        </label>
                                        <textarea id="rrRatingMessage" rows="4" placeholder="Share your experience..."
                                            style="width:100%;border:1.5px solid var(--rr-border);border-radius:10px;padding:10px 14px;font-size:.88rem;resize:vertical;outline:none;transition:border-color .2s;font-family:inherit;color:var(--rr-navy);"
                                            onfocus="this.style.borderColor='var(--rr-primary)'" onblur="this.style.borderColor='var(--rr-border)'"></textarea>
                                    </div>

                                    {{-- Submission message --}}
                                    <div id="rrRatingMsg"
                                        style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:.82rem;font-weight:600;">
                                    </div>
                                </div>

                                {{-- Footer --}}
                                <div class="modal-footer" style="border:none;padding:0 24px 22px;gap:10px;">
                                    <button type="button" data-bs-dismiss="modal"
                                        style="flex:1;padding:10px 16px;border-radius:10px;border:1.5px solid var(--rr-border);background:#fff;color:var(--rr-text-muted);font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;">
                                        Cancel
                                    </button>
                                    <button type="button" id="rrSubmitRatingBtn" onclick="rrSubmitRating()"
                                        style="flex:1;padding:10px 16px;border-radius:10px;border:none;background:var(--rr-grad-primary);color:#fff;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:var(--rr-shadow-primary);">
                                        <i class="fas fa-paper-plane"></i> Submit
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @php
		$rideChatUnreadCount = \App\Models\Chat\RideChatMessage::where('is_read_user', false)->distinct('emergency_request_id')->count('emergency_request_id');
    @endphp

    {{-- ── Floating Chat FAB Button ────────────────────────────────────── --}}
    <button class="rr-chat-fab {{ ($req->status > 1 && $req->status < 8) ? '' : 'disabled-button' }}" id="rrChatFab" title="Open Ride Chat" aria-label="Open Ride Chat" style="{{ Auth::guard('users')->check() ? '' : 'display:none;' }}" {{ ($req->status > 1 && $req->status < 8) ? '' : 'disabled' }}>
        <i class="fa fa-comments" id="rrChatFabIcon"></i>
        <span class="rr-chat-fab-badge" id="rrChatFabBadge">{{ $rideChatUnreadCount }}</span>
    </button>

    {{-- ── Ride Support Chat Box ───────────────────────────────────────── --}}
    <div class="rr-chatbox" id="rrChatBox" aria-label="Ride Support Chat" role="dialog" aria-modal="true">

        {{-- Header --}}
        <div class="rr-chatbox-header">
            <div class="rr-chatbox-header-info">
                <div class="rr-chatbox-avatar">
                    <i class="fa fa-headset"></i>
                </div>
                <div>
                    <div class="rr-chatbox-title">Ride Support</div>
                    <div class="rr-chatbox-subtitle">
                        <span class="rr-chatbox-online-dot"></span>Chat with Admin &amp; Driver
                    </div>
                </div>
            </div>
            <button class="rr-chatbox-close" id="rrChatClose" aria-label="Close chat">
                <i class="fa fa-xmark"></i>
            </button>
        </div>

        {{-- Messages --}}
        <div class="rr-chatbox-messages" id="rrChatMessages">
            <div id="rrChatLoading" style="text-align:center;padding:20px 0;font-size:.78rem;color:#94a3b8;">
                <i class="fa fa-circle-notch fa-spin"></i> Loading messages…
            </div>
        </div>

        {{-- Input --}}
        <div class="rr-chatbox-div">
            <div class="rr-chatbox-input-wrap" id="rrChatInputWrap" style="{{ ($req->status > 1 && $req->status < 5) ? '' : 'display:none;' }}">
                <textarea
                    class="rr-chatbox-input"
                    id="rrChatInput"
                    placeholder="Type your message…"
                    rows="1"
                    aria-label="Message input"
                ></textarea>
                <button class="rr-chatbox-send" id="rrChatSend" aria-label="Send message" title="Send">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
            
            {{-- Closed-chat status notice (shown for completed / cancelled rides) --}}
            <div id="rrChatStatusMsg" style="display:none;padding:14px 16px;font-size:.82rem;font-weight:500;line-height:1.5;border-top:1px solid rgba(0,0,0,.07);"></div>
            <div id="rrChatStatusMsg" style="display: block; padding: 14px 16px; font-size: 0.82rem; font-weight: 500; line-height: 1.5; border-top: 1px solid rgba(0, 0, 0, 0.07);">
                @if ($req->status == '6')
                <i class="fa fa-circle-check" style="color:#22c55e;margin-right:6px;background:rgba(34,197,94,.07);color:#166534;border-top:1px solid rgba(34,197,94,.18);"></i>
                <strong>This ride has been completed.</strong> Chat is now closed and no further messages can be sent.
                @endif
                @if ($req->status == '7')
                <i class="fa fa-circle-xmark" style="color:#ef4444;margin-right:6px;background:rgba(239,68,68,.07);color:#7f1d1d;border-top:1px solid rgba(239,68,68,.18);"></i>
                <strong>This ride has been cancelled.</strong> Chat is no longer available for new messages. If you need further assistance, please contact support.
                @endif
            </div>        
        </div>
        
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var REQ_ID = {{ $req->id }};

        @php
            $hasDriver = $req->driver && $req->driver_id && in_array($req->status, ['2', '3', '4', '5']);
            $driverLat = $hasDriver && $req->driver->lat ? (float) $req->driver->lat : null;
            $driverLng = $hasDriver && $req->driver->lng ? (float) $req->driver->lng : null;
            $pickupLat = $req->pickup_lat ? (float) $req->pickup_lat : null;
            $pickupLng = $req->pickup_lng ? (float) $req->pickup_lng : null;
            $hospLat = $req->hospital_lat ? (float) $req->hospital_lat : null;
            $hospLng = $req->hospital_lng ? (float) $req->hospital_lng : null;
        @endphp

        window.RR_CANCEL_URL = '{{ route('tracking.cancel', $req->id) }}';
        window.RR_CSRF_TOKEN = '{{ csrf_token() }}';
        window.RR_RATE_URL = '{{ route('tracking.rate', $req->id) }}';
        window.RR_CSRF = "{{ csrf_token() }}";

        @auth('users')
        window.RR_CHAT_MESSAGES_URL  = '{{ route('tracking.chat.messages', $req->id) }}';
        window.RR_CHAT_SEND_URL      = '{{ route('tracking.chat.send', $req->id) }}';
        window.RR_CHAT_MARK_READ_URL = '{{ route('tracking.chat.read', $req->id) }}';
        window.RR_CHAT_TYPING_URL    = '{{ route('tracking.chat.typing', $req->id) }}';
        @else
        window.RR_CHAT_MESSAGES_URL  = null;
        window.RR_CHAT_SEND_URL      = null;
        window.RR_CHAT_MARK_READ_URL = null;
        window.RR_CHAT_TYPING_URL    = null;
        @endauth
        
        window.TRACK_DATA = {
            pickupLat: {{ $pickupLat ?? 'null' }},
            pickupLng: {{ $pickupLng ?? 'null' }},
            pickupAddress: @json($req->pickup_address ?? ''),
            hospitalLat: {{ $hospLat ?? 'null' }},
            hospitalLng: {{ $hospLng ?? 'null' }},
            hospitalName: @json($req->hospital_name ?? ''),
            driverId: {{ $hasDriver ? $req->driver_id : 'null' }},
            driverLat: {{ $driverLat ?? 'null' }},
            driverLng: {{ $driverLng ?? 'null' }},
            driverName: @json($hasDriver ? $req->driver->name ?? '' : ''),
            status: '{{ $req->status }}',
        };
    </script>
    <script src="{{ asset('assets/user/js/tracking.js') }}"></script>
    <script src="{{ asset('assets/user/js/rideChat.js') }}"></script>
    <script src="{{ asset('assets/user/js/tracking-map.js') }}"></script>
@endpush
