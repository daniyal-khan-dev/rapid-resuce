@extends('driver.layouts.driver')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        .leaflet-control.leaflet-control-attribution {
            display: none !important;
        }   
        
        /* ── Page header ─────────────────────────────── */
        .dri-dash-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        .dri-dash-header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #e2e8f0;
            margin: 0;
        }

        .dri-dash-header small {
            display: block;
            font-size: .78rem;
            color: rgba(255, 255, 255, .38);
            margin-top: 2px;
            font-weight: 400;
        }

        /* ── Availability toggle ─────────────────────── */
        .dri-avail-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 10px;
            padding: 8px 14px;
        }

        .dri-avail-label {
            font-size: .78rem;
            color: rgba(255, 255, 255, .45);
            font-weight: 500;
            white-space: nowrap;
        }

        .dri-avail-pill {
            display: flex;
            border-radius: 8px;
            overflow: hidden;
            gap: 4px;
        }

        .dri-avail-btn {
            border: none;
            background: rgba(255, 255, 255, .08);
            color: rgba(255, 255, 255, .45);
            font-size: .74rem;
            font-weight: 600;
            padding: 5px 13px;
            border-radius: 7px;
            cursor: pointer;
            transition: background .15s, color .15s;
            font-family: inherit;
        }

        .dri-avail-btn.active-online {
            background: #22c55e;
            color: #fff;
        }

        .dri-avail-btn.active-offline {
            background: #64748b;
            color: #fff;
        }

        .dri-avail-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .dri-avail-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #64748b;
            flex-shrink: 0;
            margin-top: 1px;
            transition: background .3s;
        }

        .dri-avail-status-dot.online {
            background: #22c55e;
            animation: driPulse 2s infinite;
        }

        .dri-avail-status-dot.offline {
            background: #64748b;
        }

        .dri-avail-status-dot.busy {
            background: #f59e0b;
            animation: driPulse 2s infinite;
        }

        @keyframes driPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 currentColor;
                opacity: 1;
            }

            50% {
                box-shadow: 0 0 0 4px transparent;
                opacity: .7;
            }
        }

        /* ── Stats cards ─────────────────────────────── */
        .dri-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .dri-stat-card {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .dri-stat-card__icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            margin-bottom: 8px;
        }

        .dri-stat-card__icon.blue {
            background: rgba(59, 130, 246, .15);
            color: #60a5fa;
        }

        .dri-stat-card__icon.green {
            background: rgba(34, 197, 94, .15);
            color: #4ade80;
        }

        .dri-stat-card__icon.amber {
            background: rgba(245, 158, 11, .15);
            color: #fbbf24;
        }

        .dri-stat-card__icon.red {
            background: rgba(239, 68, 68, .15);
            color: #f87171;
        }

        .dri-stat-card__icon.purple {
            background: rgba(139, 92, 246, .15);
            color: #c4b5fd;
        }

        .dri-stat-card__icon.teal {
            background: rgba(20, 184, 166, .15);
            color: #2dd4bf;
        }

        .dri-stat-card__val {
            font-size: 1.6rem;
            font-weight: 800;
            color: #e2e8f0;
            line-height: 1;
        }

        .dri-stat-card__lbl {
            font-size: .74rem;
            color: rgba(255, 255, 255, .38);
            font-weight: 500;
        }

        .dri-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: .75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: .03em;
        }

        .dri-status-badge.s1 {
            background: rgba(100, 116, 139, .2);
            color: #94a3b8;
        }

        .dri-status-badge.s2 {
            background: rgba(59, 130, 246, .2);
            color: #93c5fd;
        }

        .dri-status-badge.s3 {
            background: rgba(59, 130, 246, .25);
            color: #60a5fa;
        }

        .dri-status-badge.s4 {
            background: rgba(139, 92, 246, .2);
            color: #c4b5fd;
        }

        .dri-status-badge.s5 {
            background: rgba(245, 158, 11, .2);
            color: #fbbf24;
        }

        .dri-status-badge.s6 {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
        }

        .dri-status-badge.s7 {
            background: rgba(239, 68, 68, .2);
            color: #fca5a5;
        }

        .dri-status-badge.s8 {
            background: rgba(251, 191, 36, .18);
            color: #fbbf24;
        }

        .dri-type-badge {
            display: inline-block;
            font-size: .7rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 5px;
            letter-spacing: .04em;
        }

        .dri-type-badge.emergency {
            background: rgba(239, 68, 68, .18);
            color: #fca5a5;
        }

        .dri-type-badge.non-emergency {
            background: rgba(59, 130, 246, .15);
            color: #93c5fd;
        }

        /* ── Section heading ─────────────────────────── */
        .dri-section-hd {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .dri-section-hd h2 {
            font-size: .95rem;
            font-weight: 700;
            color: #e2e8f0;
            margin: 0;
        }

        /* ── History table ───────────────────────────── */
        .dri-table-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 13px;
            overflow: hidden;
        }

        .dri-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .dri-table th {
            padding: 10px 14px;
            text-align: left;
            font-size: .72rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .35);
            text-transform: uppercase;
            letter-spacing: .05em;
            background: rgba(255, 255, 255, .03);
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .dri-table td {
            padding: 11px 14px;
            color: rgba(255, 255, 255, .72);
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            vertical-align: middle;
        }

        .dri-table tbody tr:last-child td {
            border-bottom: none;
        }

        .dri-table tbody tr:hover td {
            background: rgba(255, 255, 255, .03);
        }

        .dri-table .mono {
            font-family: monospace;
            font-size: .78rem;
            background: rgba(255, 255, 255, .06);
            padding: 2px 7px;
            border-radius: 5px;
            color: rgba(255, 255, 255, .65);
        }

        .dri-empty-row td {
            text-align: center;
            padding: 32px;
            color: rgba(255, 255, 255, .25);
            font-size: .82rem;
        }

        /* ── Assignment card ─────────────────────────── */
        .dri-no-assignment {
            text-align: center;
            padding: 32px 20px;
            color: rgba(255, 255, 255, .28);
            font-size: .85rem;
        }

        .dri-assignment-card {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .dri-assignment-hd {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            padding: 16px 18px 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
        }

        .dri-assignment-hd__left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dri-assignment-hd__icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(239, 68, 68, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f87171;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .dri-assignment-hd__title {
            font-size: .92rem;
            font-weight: 700;
            color: #e2e8f0;
            font-family: monospace;
        }

        .dri-assignment-hd__sub {
            font-size: .74rem;
            color: rgba(255, 255, 255, .35);
            margin-top: 2px;
        }

        .dri-assignment-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            padding: 14px 18px;
        }

        .dri-detail-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
        }

        .dri-detail-row span {
            font-size: .68rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .32);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .dri-detail-row strong {
            font-size: .83rem;
            font-weight: 500;
            color: #e2e8f0;
        }

        .dri-assignment-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, .05);
        }

        .dri-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 9px;
            font-size: .82rem;
            font-weight: 700;
            font-family: inherit;
            border: none;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
        }

        .dri-action-btn:hover:not(:disabled) {
            opacity: .85;
            transform: translateY(-1px);
        }

        .dri-action-btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .dri-action-btn.accept {
            background: #16a34a;
            color: #fff;
        }

        .dri-action-btn.arrived {
            background: rgba(99, 102, 241, .3);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, .4);
        }

        .dri-action-btn.transport {
            background: rgba(245, 158, 11, .2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, .3);
        }

        .dri-action-btn.complete {
            background: rgba(34, 197, 94, .2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, .3);
        }

        .dri-action-btn.cancel {
            background: rgba(239, 68, 68, .12);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, .25);
        }

        /* ── Spinner ─────────────────────────────────── */
        .dri-spinner {
            width: 22px;
            height: 22px;
            border: 2px solid rgba(255, 255, 255, .12);
            border-top-color: #60a5fa;
            border-radius: 50%;
            animation: driSpin .6s linear infinite;
            display: inline-block;
        }

        @keyframes driSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Live indicator pulse ────────────────────── */
        .dri-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ef4444;
            animation: driPulse 1.4s ease-in-out infinite;
            display: inline-block;
            margin-right: 5px;
        }

        .dri-sidebar-notif-count {
            display: inline-block;
            background: #ef4444;
            color: #fff;
            border-radius: 10px;
            font-size: .65rem;
            font-weight: 700;
            padding: 1px 6px;
            margin-left: auto;
            line-height: 1.5;
        }
    </style>
@endpush

@section('content')
    {{-- Page header --}}
    <div class="dri-dash-header">
        <div>
            <h1>Welcome back, <span class="dash-name">{{ explode(' ', $driver->name)[0] }}</span>.</h1>
            <small id="driAvailText">
                @if ($driver->status === '1')
                    You are currently <span style="color:#4ade80;font-weight:600;">Online</span> and available.
                @elseif($driver->status === '2')
                    You are currently <span style="color:#94a3b8;font-weight:600;">Offline</span>.
                @else
                    You are currently <span style="color:#fbbf24;font-weight:600;">Busy</span> on an assignment.
                @endif
            </small>
        </div>

        {{-- Availability toggle --}}
        <div class="dri-avail-wrap" id="driAvailWrap">
            <span class="dri-avail-label">Availability</span>
            <div id="driAvailDot"
                class="dri-avail-status-dot {{ $driver->status === '1' ? 'online' : ($driver->status === '3' ? 'busy' : 'offline') }}">
            </div>
            <div class="dri-avail-pill">
                <button id="driAvailOnline" class="dri-avail-btn {{ $driver->status === '1' ? 'active-online' : '' }}"
                    onclick="driSetAvailability('1')" {{ $driver->status === '3' ? 'disabled' : '' }}>
                    Online
                </button>
                <button id="driAvailOffline" class="dri-avail-btn {{ $driver->status === '2' ? 'active-offline' : '' }}"
                    onclick="driSetAvailability('2')" {{ $driver->status === '3' ? 'disabled' : '' }}>
                    Offline
                </button>
            </div>
        </div>
    </div>

    {{-- Stats row —  6 cards --}}
    <div class="dri-stats-grid">
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon blue"><i class="fa fa-ambulance"></i></div>
            <div class="dri-stat-card__val" id="statTotal">{{ $total }}</div>
            <div class="dri-stat-card__lbl">Total Rides</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon amber"><i class="fa fa-truck-medical"></i></div>
            <div class="dri-stat-card__val" id="statActive">{{ $active }}</div>
            <div class="dri-stat-card__lbl">Active</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon purple"><i class="fa fa-spinner"></i></div>
            <div class="dri-stat-card__val" id="statPending">{{ $pending }}</div>
            <div class="dri-stat-card__lbl">Pending</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon green"><i class="fa fa-circle-check"></i></div>
            <div class="dri-stat-card__val" id="statCompleted">{{ $completed }}</div>
            <div class="dri-stat-card__lbl">Completed</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon red"><i class="fa fa-ban"></i></div>
            <div class="dri-stat-card__val" id="statCancelled">{{ $cancelled }}</div>
            <div class="dri-stat-card__lbl">Cancelled</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-card__icon teal"><i class="fa fa-calendar-day"></i></div>
            <div class="dri-stat-card__val" id="statToday">{{ $today }}</div>
            <div class="dri-stat-card__lbl">Today</div>
        </div>
    </div>

    {{-- ── Live Position Map ──────────────────────────────────────────────── --}}
    <div class="dri-section-hd" style="margin-top:6px;">
        <h2>
            <span class="dri-live-dot" style="margin-right:6px;"></span>
            My Live Position
        </h2>
        <span id="driMapLastUpdate" style="font-size:.74rem;color:rgba(255,255,255,.3);">
            Waiting for GPS…
        </span>
    </div>
    <div class="dri-table-card" style="overflow:hidden;margin-bottom:22px;">
        <div id="driLiveMap" class="driLiveMap" data-lat="{{ $driver->lat ?? '' }}" data-lng="{{ $driver->lng ?? '' }}" style="width:100%;height:280px;background:#0e1728;"></div>

        <div
            style="padding:8px 14px;border-top:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <i class="fa fa-location-dot" style="color:#D72C42;font-size:.8rem;flex-shrink:0;"></i>
            <span id="driMapCoords" style="font-size:.75rem;color:rgba(255,255,255,.35);">
                @if ($driver->lat && $driver->lng)
                    {{ number_format((float) $driver->lat, 5) }},&nbsp;{{ number_format((float) $driver->lng, 5) }}
                @else
                    No location recorded yet
                @endif
            </span>
            <span style="margin-left:auto;font-size:.7rem;color:rgba(255,255,255,.18);">
                <i class="fa fa-circle"
                    style="font-size:.45rem;vertical-align:middle;color:#22c55e;animation:driPulse 2s infinite;"></i>
                GPS auto-updates in real time
            </span>
        </div>
    </div>

    {{-- Recent Request History --}}
    <div class="dri-section-hd">
        <h2><i class="fa fa-clock-rotate-left me-2" style="opacity:.5;font-size:.85rem;"></i>Recent Requests</h2>
        <a href=""
            style="font-size:.78rem;color:rgba(255,255,255,.35);text-decoration:none;font-weight:600;">
            View all <i class="fa fa-arrow-right ms-1" style="font-size:.7rem;"></i>
        </a>
    </div>
    <div class="dri-table-card">
        <div class="pgd-scroll pgd-scroll--list">
            <table class="dri-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Type</th>
                        <th>Pickup Address</th>
                        <th>Hospital</th>
                        <th>Ambulance</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="driHistoryBody">
                    @forelse($history as $r)
                        @php
                            $smap = [
                                '1' => ['Pending', 's1'],
                                '2' => ['Dispatched', 's2'],
                                '3' => ['En Route', 's3'],
                                '4' => ['Arrived', 's4'],
                                '5' => ['Transporting', 's5'],
                                '6' => ['Completed', 's6'],
                                '7' => ['Cancelled', 's7'],
                                '8' => ['Awaiting Acceptance', 's8'],
                            ];
                            [$slabel, $sclass] = $smap[$r->status] ?? [ucfirst($r->status), 's1'];
                        @endphp
                        <tr data-req-id="{{ $r->id }}">
                            <td><span class="mono">{{ $r->rreb_id ?? '#' . $r->id }}</span></td>
                            <td>
                                @if ($r->type === '1')
                                    <span class="dri-type-badge emergency">Emergency</span>
                                @else
                                    <span class="dri-type-badge non-emergency">Non-Emergency</span>
                                @endif
                            </td>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ Str::limit($r->pickup_address, 30) }}</td>
                            <td>{{ Str::limit($r->hospital_name, 22) }}</td>
                            <td>{{ $r->ambulance?->vehicle_number ?? '—' }}</td>
                            <td><span class="dri-status-badge {{ $sclass }}">{{ $slabel }}</span></td>
                            <td style="white-space:nowrap;color:rgba(255,255,255,.35);font-size:.77rem;">
                                {{ $r->created_at->format('d M, H:i') }}</td>
                        </tr>
                    @empty
                        <tr class="dri-empty-row">
                            <td colspan="7">
                                <i class="fa fa-inbox"
                                    style="display:block;font-size:1.4rem;margin-bottom:8px;opacity:.2;"></i>
                                No requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="{{ asset('assets/driver/js/live-map.js') }}"></script>
    <script src="{{ asset('assets/driver/js/dashboard.js') }}"></script>
@endpush
