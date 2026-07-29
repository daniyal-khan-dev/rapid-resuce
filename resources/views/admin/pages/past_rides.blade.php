@extends('admin.layouts.admin')
@section('title', 'Past Rides')
@section('page_title', 'Past Rides')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-control.leaflet-control-attribution {
            display: none !important;
        }   

        .apr-trip-map-wrap {
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 255, 255, .07);
        }

        .apr-trip-map {
            height: 340px;
            width: 100%;
            background: #1e293b;
        }

        .apr-trip-map-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, .75);
            z-index: 999;
            border-radius: 10px;
        }

        .apr-trip-map-info {
            margin-top: 10px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: .75rem;
            color: rgba(255, 255, 255, .4);
        }

        .apr-trip-legend {
            margin-top: 10px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: .72rem;
            color: rgba(255, 255, 255, .45);
        }

        .apr-trip-legend span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .apr-stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .apr-stat-card {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 160px;
            flex: 1;
        }

        .apr-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .apr-stat-icon--green {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
        }

        .apr-stat-icon--red {
            background: rgba(239, 68, 68, .12);
            color: #ef4444;
        }

        .apr-stat-icon--blue {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        .apr-stat-val {
            font-size: 1.35rem;
            font-weight: 700;
            color: #f1f5f9;
            line-height: 1;
        }

        .apr-stat-lbl {
            font-size: .72rem;
            color: rgba(255, 255, 255, .38);
            margin-top: 3px;
        }

        .apr-toolbar {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .apr-search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .apr-search-wrap i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, .3);
            font-size: .8rem;
            pointer-events: none;
        }

        .apr-search-input {
            width: 100%;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 8px 12px 8px 32px;
            color: #e2e8f0;
            font-size: .82rem;
            outline: none;
            transition: border-color .15s;
        }

        .apr-search-input::placeholder {
            color: rgba(255, 255, 255, .25);
        }

        .apr-search-input:focus {
            border-color: rgba(99, 102, 241, .5);
        }

        .apr-select {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 8px 12px;
            color: #e2e8f0;
            font-size: .82rem;
            outline: none;
            cursor: pointer;
            min-width: 140px;
        }

        .apr-select option {
            background: #1e293b;
        }

        .apr-date-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .apr-date-btn {
            padding: 7px 13px;
            border-radius: 7px;
            border: 1px solid rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .04);
            color: rgba(255, 255, 255, .5);
            font-size: .78rem;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }

        .apr-date-btn:hover {
            background: rgba(255, 255, 255, .08);
            color: #e2e8f0;
        }

        .apr-date-btn.active {
            background: rgba(99, 102, 241, .18);
            border-color: rgba(99, 102, 241, .4);
            color: #a5b4fc;
            font-weight: 600;
        }

        .apr-date-range {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
            margin-top: 4px;
        }

        .apr-date-range input[type="date"] {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 7px 10px;
            color: #e2e8f0;
            font-size: .8rem;
            outline: none;
            color-scheme: dark;
        }

        .apr-date-range label {
            font-size: .78rem;
            color: rgba(255, 255, 255, .35);
        }

        .apr-live-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            animation: aprPulse 1.6s infinite;
            margin-right: 4px;
            vertical-align: middle;
        }

        @keyframes aprPulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        @keyframes aprNewRow {
            from {
                opacity: 0;
                transform: translateY(-8px)
            }

            to {
                opacity: 1;
                transform: none
            }
        }

        .apr-new-row {
            animation: aprNewRow .4s ease both;
        }

        .apr-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .apr-detail-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .apr-detail-field label {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(255, 255, 255, .35);
        }

        .apr-detail-field span {
            font-size: .85rem;
            color: #e2e8f0;
            word-break: break-word;
        }

        .apr-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, .06);
            margin: 14px 0;
        }

        .apr-pill-completed {
            background: rgba(34, 197, 94, .1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, .2);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }

        .apr-pill-cancelled {
            background: rgba(239, 68, 68, .1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, .2);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .apr-search-wrap {
                min-width: unset;
            }
        }

        .table td{
            padding: 5px 7px;
        }
    </style>
@endpush

@php
    $_aprPageData = collect($rides->items())
        ->map(function ($r) {
            return [
                'id' => $r->id,
                'rreb_id' => $r->rreb_id,
                'type' => $r->type,
                'status' => $r->status,
                'pickup_address' => $r->pickup_address,
                'hospital_name' => $r->hospital_name,
                'mobile_no' => $r->mobile_no,
                'user_name' => $r->user?->details?->first_name ?? 'Guest',
                'ambulance_no' => $r->ambulance?->vehicle_number,
                'ambulance_type' => match((string)($r->ambulance?->type ?? '')) {
                    '1' => 'BLS — Basic Life Support',
                    '2' => 'ALS — Advanced Life Support',
                    '3' => 'CCT — Critical Care Transport',
                    '4' => 'Neonatal',
                    '5' => 'AIR Ambulance',
                    default => $r->ambulance?->type ?? null,
                },
                'driver_name' => $r->driver?->name,
                'driver_phone' => $r->driver?->phone,
                'notes' => $r->notes,
                'completed_at' => $r->completed_at?->format('l d M Y \a\t h:i A'),
                'dispatched_at' => $r->dispatched_at?->format('l d M Y \a\t h:i A'),
                'created_at' => $r->created_at->format('l d M Y \a\t h:i A'),
                'accepted_lat' => $r->accepted_lat ? (float) $r->accepted_lat : null,
                'accepted_lng' => $r->accepted_lng ? (float) $r->accepted_lng : null,
                'pickup_lat' => $r->pickup_lat ? (float) $r->pickup_lat : null,
                'pickup_lng' => $r->pickup_lng ? (float) $r->pickup_lng : null,
                'hospital_lat' => $r->hospital_lat ? (float) $r->hospital_lat : null,
                'hospital_lng' => $r->hospital_lng ? (float) $r->hospital_lng : null,
            ];
        })
        ->values()
        ->all();
@endphp

@section('content')
    {{-- Page header --}}
    <div class="adm-page-header">
        <div>
            <h2><i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;font-size:1.1rem;"></i>Past Rides</h2>
            <p>Completed and cancelled rides — full history with search and filters.</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-size:.75rem;color:rgba(255,255,255,.35);">
                <span class="apr-live-dot"></span>Real-time sync active
            </span>
            <a href="{{ route('admin.emergency.grid') }}"
                style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:#e2e8f0;font-size:.8rem;text-decoration:none;transition:background .15s;"
                onmouseover="this.style.background='rgba(255,255,255,.1)'"
                onmouseout="this.style.background='rgba(255,255,255,.06)'">
                <i class="fa fa-truck-medical"></i> Active Requests
            </a>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="apr-stats" id="aprStatsSection">
        <div class="apr-stat-card">
            <div class="apr-stat-icon apr-stat-icon--blue"><i class="fa fa-list-check"></i></div>
            <div>
                <div class="apr-stat-val" id="aprStatTotal">{{ $rides->total() }}</div>
                <div class="apr-stat-lbl">Total Rides</div>
            </div>
        </div>
        <div class="apr-stat-card">
            <div class="apr-stat-icon apr-stat-icon--green"><i class="fa fa-circle-check"></i></div>
            <div>
                <div class="apr-stat-val" id="aprStatCompleted">{{ $stats['completed'] }}</div>
                <div class="apr-stat-lbl">Completed Rides</div>
            </div>
        </div>
        <div class="apr-stat-card">
            <div class="apr-stat-icon apr-stat-icon--red"><i class="fa fa-ban"></i></div>
            <div>
                <div class="apr-stat-val" id="aprStatCancelled">{{ $stats['cancelled'] }}</div>
                <div class="apr-stat-lbl">Cancelled Rides</div>
            </div>
        </div>
    </div>

    {{-- Filter toolbar --}}
    <form id="aprFilterForm" method="GET" action="{{ route('admin.emergency.past-rides') }}">
        <div class="apr-toolbar">

            {{-- Search --}}
            <div class="apr-search-wrap">
                <i class="fa fa-magnifying-glass"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="apr-search-input"
                    placeholder="Search RREB ID, hospital, pickup, mobile…" autocomplete="off">
            </div>

            {{-- Status --}}
            <select name="status" class="apr-select" onchange="aprNavigate()">
                <option value="" {{ !request('status') ? 'selected' : '' }}>All Past Rides</option>
                <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Completed</option>
                <option value="7" {{ request('status') === '7' ? 'selected' : '' }}>Cancelled</option>
            </select>

            {{-- Driver --}}
            <select name="driver_id" class="apr-select" onchange="aprNavigate()">
                <option value="">All Drivers</option>
                @foreach ($allDrivers as $d)
                    <option value="{{ $d->id }}" {{ request('driver_id') == $d->id ? 'selected' : '' }}>
                        {{ $d->name }}
                    </option>
                @endforeach
            </select>

            {{-- Ambulance --}}
            <select name="ambulance_id" class="apr-select" onchange="aprNavigate()">
                <option value="">All Ambulances</option>
                @foreach ($allAmbulances as $a)
                    <option value="{{ $a->id }}" {{ request('ambulance_id') == $a->id ? 'selected' : '' }}>
                        {{ $a->vehicle_number }}
                    </option>
                @endforeach
            </select>

            {{-- Date quick filters --}}
            <div class="apr-date-btns">
                <button type="button" class="apr-date-btn {{ request('date_filter', 'all') === 'all' ? 'active' : '' }}"
                    onclick="aprSetDateFilter('all')">All Time</button>
                <button type="button" class="apr-date-btn {{ request('date_filter') === 'today' ? 'active' : '' }}"
                    onclick="aprSetDateFilter('today')">Today</button>
                <button type="button" class="apr-date-btn {{ request('date_filter') === 'week' ? 'active' : '' }}"
                    onclick="aprSetDateFilter('week')">This Week</button>
                <button type="button" class="apr-date-btn {{ request('date_filter') === 'month' ? 'active' : '' }}"
                    onclick="aprSetDateFilter('month')">This Month</button>
            </div>
            <input type="hidden" name="date_filter" id="aprDateFilterHidden" value="{{ request('date_filter', 'all') }}">

            {{-- Search button --}}
            <button type="submit" class="btn btn-primary btn-sm px-3" style="font-size:.8rem;">
                <i class="fa fa-magnifying-glass"></i> Search
            </button>


            {{-- Toggle custom range --}}
            <button type="button" id="aprToggleRange"
                style="padding:7px 12px;font-size:.75rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;color:rgba(255,255,255,.4);cursor:pointer;"
                onclick="aprToggleCustomRange()">
                <i class="fa fa-calendar"></i> Custom Range
            </button>

            {{-- Custom date range --}}
            <div class="apr-date-range" id="aprCustomRange"
                style="{{ request('date_from') || request('date_to') ? '' : 'display:none;' }}">
                <label>From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}">
                <label>To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}">
                <button type="submit" class="btn btn-primary btn-sm" style="font-size:.78rem;">Apply</button>
            </div>

        </div>
    </form>

    {{-- Table --}}
    <div class="card" id="aprTableSection" data-rides='@json($_aprPageData)'>
        <div class="pgd-scroll" style="{{ $rides->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="aprTable">
                <thead>
                    <tr>
                        <th>RREB ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Hospital</th>
                        <th>Pickup</th>
                        <th>Driver</th>
                        <th>Ambulance</th>
                        <th>Status</th>
                        <th>Completed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="aprTbody">
                    @foreach ($rides as $r)
                        <tr data-ride-id="{{ $r->id }}">
                            <td class="fs-xs">
                                <span
                                    style="font-family:monospace;font-size:.8rem;background:rgba(129,140,248,.12);padding:3px 8px;border-radius:6px;color:#a5b4fc;white-space:nowrap;">
                                    {{ $r->rreb_id ?? '—' }}
                                </span>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                <div>{{ $r->user?->details?->first_name ?? 'Guest' }}</div>
                            </td>
                            <td>
                                @if ($r->type === '1')
                                    <span class="status-pill status-4">Emergency</span>
                                @else
                                    <span class="status-pill status-3">Non-Emergency</span>
                                @endif
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                {{ Str::limit($r->hospital_name, 22) }}</td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                {{ Str::limit($r->pickup_address, 22) }}</td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                <div>{{ $r->driver?->name ?? '—' }}</div>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                {{ $r->ambulance?->vehicle_number ?? '—' }}</td>
                            <td class="fs-xs">
                                @if ($r->status === '6')
                                    <span class="apr-pill-completed"><i
                                            class="fa fa-circle-check me-1"></i>Completed</span>
                                @else
                                    <span class="apr-pill-cancelled"><i class="fa fa-ban me-1"></i>Cancelled</span>
                                @endif
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);white-space:nowrap;">
                                {{ $r->completed_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                <button class="btn-adm-icon" title="View Details"
                                    onclick="aprViewDetail({{ $r->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pgd-footer" style="{{ $rides->count() ? '' : 'display:none;' }}">
            <div class="pgd-info">
                Showing {{ $rides->firstItem() }}–{{ $rides->lastItem() }} of {{ $rides->total() }} rides
            </div>
            <div class="pgd-controls">
                @if ($rides->onFirstPage())
                    <button class="pgd-btn" disabled>← Prev</button>
                @else
                    <a href="{{ $rides->previousPageUrl() }}" class="pgd-btn" style="text-decoration:none;">←
                        Prev</a>
                @endif

                <span class="pgd-pages">Page {{ $rides->currentPage() }} / {{ $rides->lastPage() }}</span>

                @if ($rides->hasMorePages())
                    <a href="{{ $rides->nextPageUrl() }}" class="pgd-btn" style="text-decoration:none;">Next
                        →</a>
                @else
                    <button class="pgd-btn" disabled>Next →</button>
                @endif
            </div>
        </div>
        
        <div class="adm-empty" style="{{ $rides->count() ? 'display:none;' : '' }}">
            <i class="fa fa-inbox"></i>
            <p>No past rides found.
                @if (request()->anyFilled(['search', 'status', 'driver_id', 'ambulance_id', 'date_from', 'date_to']) ||
                        (request('date_filter') && request('date_filter') !== 'all'))
                    <br><small>Try adjusting your filters.</small>
                @else
                    <br><small>Completed and cancelled rides will appear here.</small>
                @endif
            </p>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="aprDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aprModalTitle">
                        <i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;"></i>Ride Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="aprModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-secondary"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.06);">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var _aprData = @json($_aprPageData);
    </script>
    <script src="{{ asset('assets/admin/js/past_ride.js') }}"></script>
@endpush
