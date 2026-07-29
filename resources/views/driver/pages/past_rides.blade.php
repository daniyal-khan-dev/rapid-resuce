@extends('driver.layouts.driver')

@section('title', 'Past Rides')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-control.leaflet-control-attribution {
            display: none !important;
        }   
        
        /* ── Trip map ─────────────────────────────────────────────────── */
        .pr-trip-map-wrap {
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(255, 255, 255, .07);
        }

        .pr-trip-map {
            height: 320px;
            width: 100%;
            background: #1e293b;
        }

        .pr-trip-map-loader {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, .75);
            z-index: 999;
            border-radius: 10px;
        }

        .pr-trip-map-info {
            margin-top: 10px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            font-size: .75rem;
            color: rgba(255, 255, 255, .4);
        }

        .pr-trip-legend {
            margin-top: 10px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: .72rem;
            color: rgba(255, 255, 255, .45);
        }

        .pr-trip-legend span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ── Page header ─────────────────────────────────────────────── */
        .pr-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        .pr-header h2 {
            margin: 0 0 4px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: .01em;
        }

        .pr-header p {
            margin: 0;
            font-size: .82rem;
            color: rgba(255, 255, 255, .4);
        }

        /* ── Stats strip ─────────────────────────────────────────────── */
        .pr-stats {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .pr-stat-card {
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

        .pr-stat-card__icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .pr-stat-card__icon--green {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
        }

        .pr-stat-card__icon--red {
            background: rgba(239, 68, 68, .12);
            color: #ef4444;
        }

        .pr-stat-card__icon--blue {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        .pr-stat-card__val {
            font-size: 1.35rem;
            font-weight: 700;
            color: #f1f5f9;
            line-height: 1;
        }

        .pr-stat-card__lbl {
            font-size: .73rem;
            color: rgba(255, 255, 255, .38);
            margin-top: 3px;
        }

        /* ── Filter toolbar ──────────────────────────────────────────── */
        .pr-toolbar {
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .pr-search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .pr-search-wrap i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, .3);
            font-size: .8rem;
            pointer-events: none;
        }

        .pr-search-input {
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

        .pr-search-input::placeholder {
            color: rgba(255, 255, 255, .25);
        }

        .pr-search-input:focus {
            border-color: rgba(99, 102, 241, .5);
        }

        .pr-filter-select {
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

        .pr-filter-select option {
            background: #1e293b;
        }

        /* Date filter pill buttons */
        .pr-date-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .pr-date-btn {
            padding: 7px 14px;
            border-radius: 7px;
            border: 1px solid rgba(255, 255, 255, .1);
            background: rgba(255, 255, 255, .04);
            color: rgba(255, 255, 255, .5);
            font-size: .78rem;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }

        .pr-date-btn:hover {
            background: rgba(255, 255, 255, .08);
            color: #e2e8f0;
        }

        .pr-date-btn.active {
            background: rgba(99, 102, 241, .18);
            border-color: rgba(99, 102, 241, .4);
            color: #a5b4fc;
            font-weight: 600;
        }

        /* Custom date range row */
        .pr-date-range {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            width: 100%;
            margin-top: 4px;
        }

        .pr-date-range input[type="date"] {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            padding: 7px 10px;
            color: #e2e8f0;
            font-size: .8rem;
            outline: none;
            color-scheme: dark;
        }

        .pr-date-range label {
            font-size: .78rem;
            color: rgba(255, 255, 255, .35);
        }

        /* ── Table ───────────────────────────────────────────────────── */
        .pr-table-wrap {
            background: rgba(255, 255, 255, .025);
            border: 1px solid rgba(255, 255, 255, .06);
            border-radius: 12px;
            overflow: auto;
        }

        .pr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .pr-table th {
            background: rgba(255, 255, 255, .04);
            color: rgba(255, 255, 255, .38);
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 11px 14px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            white-space: nowrap;
        }

        .pr-table td {
            padding: 12px 14px;
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            vertical-align: middle;
        }

        .pr-table tbody tr:last-child td {
            border-bottom: none;
        }

        .pr-table tbody tr:hover td {
            background: rgba(255, 255, 255, .025);
        }

        .pr-table tbody tr.pr-new-row td {
            background: rgba(99, 102, 241, .05);
        }

        .pr-table td.pr-empty {
            text-align: center;
            padding: 48px 20px;
            color: rgba(255, 255, 255, .2);
            font-size: .85rem;
        }

        /* Status pills */
        .pr-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .pr-pill--completed {
            background: rgba(34, 197, 94, .12);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, .2);
        }

        .pr-pill--cancelled {
            background: rgba(239, 68, 68, .12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, .2);
        }

        /* Type badge */
        .pr-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: .7rem;
            font-weight: 600;
        }

        .pr-type-badge--emergency {
            background: rgba(239, 68, 68, .12);
            color: #f87171;
        }

        .pr-type-badge--non-emergency {
            background: rgba(59, 130, 246, .12);
            color: #60a5fa;
        }

        /* ── Pagination ──────────────────────────────────────────────── */
        .pr-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-top: 1px solid rgba(255, 255, 255, .05);
            flex-wrap: wrap;
            gap: 10px;
        }

        .pr-pag-info {
            font-size: .78rem;
            color: rgba(255, 255, 255, .3);
        }

        .pr-pag-links {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .pr-pag-links .page-link {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            color: rgba(255, 255, 255, .5);
            border-radius: 7px;
            padding: 5px 11px;
            font-size: .78rem;
            text-decoration: none;
            transition: all .15s;
        }

        .pr-pag-links .page-link:hover {
            background: rgba(255, 255, 255, .1);
            color: #e2e8f0;
        }

        .pr-pag-links .page-link.active {
            background: rgba(99, 102, 241, .25);
            border-color: rgba(99, 102, 241, .4);
            color: #a5b4fc;
            font-weight: 600;
        }

        .pr-pag-links .page-link.disabled {
            opacity: .3;
            pointer-events: none;
        }

        /* ── Live badge ──────────────────────────────────────────────── */
        .pr-live-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
            animation: prPulse 1.6s infinite;
            margin-right: 5px;
            vertical-align: middle;
        }

        @keyframes prPulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .4;
                transform: scale(.8)
            }
        }

        /* ── New row animation ───────────────────────────────────────── */
        @keyframes prNewRowSlide {
            from {
                opacity: 0;
                transform: translateY(-10px)
            }

            to {
                opacity: 1;
                transform: none
            }
        }

        .pr-new-row {
            animation: prNewRowSlide .45s ease both;
        }

        /* ── Detail modal ────────────────────────────────────────────── */
        .pr-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .pr-modal-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .pr-modal-field label {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(255, 255, 255, .35);
        }

        .pr-modal-field span {
            font-size: .85rem;
            color: #e2e8f0;
            word-break: break-word;
        }

        .pr-modal-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, .06);
            margin: 14px 0;
        }

        /* Resp table */
        @media (max-width: 768px) {

            .pr-table th:nth-child(3),
            .pr-table td:nth-child(3),
            .pr-table th:nth-child(4),
            .pr-table td:nth-child(4) {
                display: none;
            }
        }
    </style>
@endpush

@php
    $_prPageData = collect($rides->items())
        ->map(function ($r) {
            return [
                'id' => $r->id,
                'rreb_id' => $r->rreb_id,
                'type' => $r->type,
                'status' => $r->status,
                'pickup_address' => $r->pickup_address,
                'hospital_name' => $r->hospital_name,
                'mobile_no' => $r->mobile_no,
                'ambulance_no' => $r->ambulance?->vehicle_number,
                'notes' => $r->notes,
                'completed_at' => $r->completed_at?->format('d M Y, h:i A'),
                'dispatched_at' => $r->dispatched_at?->format('d M Y, h:i A'),
                'created_at' => $r->created_at->format('d M Y, h:i A'),
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

    {{-- Page Header --}}
    <div class="pr-header">
        <div>
            <h2><i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;"></i>Past Rides</h2>
            <p>Completed and cancelled rides — full history with search and filters.</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:.75rem;color:rgba(255,255,255,.35);">
                <span class="pr-live-dot"></span>Real-time sync active
            </span>
            <a href="{{ route('driver.requests.grid') }}" class="btn-dri-secondary"
                style="padding:7px 16px;font-size:.8rem;text-decoration:none;">
                <i class="fa fa-truck-medical me-1"></i>Active Requests
            </a>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="pr-stats" id="prStatsSection">
        <div class="pr-stat-card">
            <div class="pr-stat-card__icon pr-stat-card__icon--blue">
                <i class="fa fa-list"></i>
            </div>
            <div>
                <div class="pr-stat-card__val">{{ $rides->total() }}</div>
                <div class="pr-stat-card__lbl">Total Rides</div>
            </div>
        </div>
        <div class="pr-stat-card">
            <div class="pr-stat-card__icon pr-stat-card__icon--green">
                <i class="fa fa-circle-check"></i>
            </div>
            <div>
                <div class="pr-stat-card__val" id="prStatCompleted">{{ $stats['completed'] }}</div>
                <div class="pr-stat-card__lbl">Completed Rides</div>
            </div>
        </div>
        <div class="pr-stat-card">
            <div class="pr-stat-card__icon pr-stat-card__icon--red">
                <i class="fa fa-ban"></i>
            </div>
            <div>
                <div class="pr-stat-card__val" id="prStatCancelled">{{ $stats['cancelled'] }}</div>
                <div class="pr-stat-card__lbl">Cancelled Rides</div>
            </div>
        </div>
    </div>

    {{-- Filter toolbar --}}
    <form id="prFilterForm" method="GET" action="{{ route('driver.past-rides') }}">
        <div class="pr-toolbar">
            {{-- Search --}}
            <div class="pr-search-wrap">
                <i class="fa fa-magnifying-glass"></i>
                <input type="text" name="search" value="{{ request('search') }}" class="pr-search-input"
                    placeholder="Search by ID, hospital, pickup, mobile…" autocomplete="off">
            </div>

            {{-- Status --}}
            <select name="status" class="pr-filter-select" onchange="prNavigate()">
                <option value="" {{ !request('status') ? 'selected' : '' }}>All Past Rides</option>
                <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Completed Only</option>
                <option value="7" {{ request('status') === '7' ? 'selected' : '' }}>Cancelled Only</option>
            </select>

            {{-- Date quick filters --}}
            <div class="pr-date-btns">
                <button type="button" class="pr-date-btn {{ request('date_filter', 'all') === 'all' ? 'active' : '' }}"
                    onclick="prSetDateFilter('all')">All Time</button>
                <button type="button" class="pr-date-btn {{ request('date_filter') === 'today' ? 'active' : '' }}"
                    onclick="prSetDateFilter('today')">Today</button>
                <button type="button" class="pr-date-btn {{ request('date_filter') === 'week' ? 'active' : '' }}"
                    onclick="prSetDateFilter('week')">This Week</button>
                <button type="button" class="pr-date-btn {{ request('date_filter') === 'month' ? 'active' : '' }}"
                    onclick="prSetDateFilter('month')">This Month</button>
            </div>
            <input type="hidden" name="date_filter" id="prDateFilterHidden" value="{{ request('date_filter', 'all') }}">

            {{-- Search button --}}
            <button type="submit" class="btn-dri-primary" style="padding:8px 18px;font-size:.8rem;">
                <i class="fa fa-magnifying-glass"></i> Search
            </button>

            <a href="{{ route('driver.past-rides') }}" id="prClearBtn" class="btn-dri-secondary"
                style="padding:8px 14px;font-size:.8rem;text-decoration:none;{{ request('search') || request('status') || (request('date_filter') && request('date_filter') !== 'all') || request('date_from') || request('date_to') ? '' : 'display:none;' }}">
                <i class="fa fa-xmark"></i> Clear
            </a>

            {{-- Custom date range --}}
            <div class="pr-date-range" id="prCustomRange"
                style="{{ request('date_from') || request('date_to') ? '' : 'display:none;' }}">
                <label>From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}">
                <label>To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}">
                <button type="submit" class="btn-dri-primary" style="padding:6px 14px;font-size:.78rem;">Apply</button>
            </div>

            {{-- Toggle custom range --}}
            <button type="button" id="prToggleRange"
                style="padding:7px 12px;font-size:.75rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:8px;color:rgba(255,255,255,.4);cursor:pointer;"
                onclick="prToggleCustomRange()">
                <i class="fa fa-calendar"></i> Custom Range
            </button>
        </div>
    </form>

    {{-- Table --}}
    <div class="pr-table-wrap" id="prTableSection" data-rides='@json($_prPageData)'>
        <table class="pr-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Type</th>
                    <th>Pickup</th>
                    <th>Hospital</th>
                    <th>Ambulance</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width:56px;"></th>
                </tr>
            </thead>
            <tbody id="prTbody">
                @forelse($rides as $ride)
                    <tr id="prRow_{{ $ride->id }}">
                        <td>
                            <span style="font-family:monospace;font-size:.78rem;color:#a5b4fc;font-weight:600;">
                                {{ $ride->rreb_id }}
                            </span>
                        </td>
                        <td>
                            @if ($ride->type == 1)
                                <span class="pr-type-badge pr-type-badge--emergency">Emergency</span>
                            @else
                                <span class="pr-type-badge pr-type-badge--non-emergency">Non-Emergency</span>
                            @endif
                        </td>
                        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="{{ $ride->pickup_address }}">
                            {{ Str::limit($ride->pickup_address, 32) ?: '—' }}
                        </td>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                            title="{{ $ride->hospital_name }}">
                            {{ Str::limit($ride->hospital_name, 28) ?: '—' }}
                        </td>
                        <td style="font-size:.78rem;color:rgba(255,255,255,.5);">
                            {{ $ride->ambulance?->vehicle_number ?? '—' }}
                        </td>
                        <td>
                            @if ($ride->status == 6)
                                <span class="pr-pill pr-pill--completed"><i
                                        class="fa fa-circle-check me-1"></i>Completed</span>
                            @else
                                <span class="pr-pill pr-pill--cancelled"><i class="fa fa-ban me-1"></i>Cancelled</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;color:rgba(255,255,255,.38);font-size:.77rem;">
                            {{ $ride->created_at->format('d M Y') }}
                        </td>
                        <td>
                            <button class="btn-dri-icon btn-dri-icon--primary" title="View Details"
                                onclick="prViewDetail({{ $ride->id }})">
                                <i class="fa fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr id="prEmptyRow">
                        <td colspan="8" class="pr-empty">
                            <i class="fa fa-inbox"
                                style="display:block;font-size:1.6rem;margin-bottom:10px;opacity:.2;"></i>
                            No past rides found.
                            @if (request('search') || request('status') || (request('date_filter') && request('date_filter') !== 'all'))
                                <br><small style="opacity:.6;">Try adjusting your filters.</small>
                            @else
                                <br><small style="opacity:.6;">Completed and cancelled rides will appear here.</small>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="pr-pagination">
            <div class="pr-pag-info">
                Showing {{ $rides->firstItem() }}–{{ $rides->lastItem() }} of {{ $rides->total() }} rides
            </div>
            <nav class="pr-pag-links">
                {{-- Previous --}}
                @if ($rides->onFirstPage())
                    <span class="page-link disabled"><i class="fa fa-chevron-left"></i></span>
                @else
                    <a href="{{ $rides->previousPageUrl() }}" class="page-link"><i
                            class="fa fa-chevron-left"></i></a>
                @endif

                {{-- Pages --}}
                @foreach ($rides->getUrlRange(max(1, $rides->currentPage() - 2), min($rides->lastPage(), $rides->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}"
                        class="page-link {{ $page == $rides->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                @endforeach

                {{-- Next --}}
                @if ($rides->hasMorePages())
                    <a href="{{ $rides->nextPageUrl() }}" class="page-link"><i class="fa fa-chevron-right"></i></a>
                @else
                    <span class="page-link disabled"><i class="fa fa-chevron-right"></i></span>
                @endif
            </nav>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        var _prData = @json($_prPageData);
    </script>
    <script src="{{ asset('assets/driver/js/past-rides.js') }}"></script>
@endpush

@push('modals')
    {{-- Past Ride Detail Modal --}}
    <div class="modal fade" id="prDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prModalTitle">
                        <i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;"></i>Ride Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="prModalBody">
                    <div style="text-align:center;padding:30px;">
                        <div class="dri-spinner"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.06);">
                    <button type="button" class="btn-dri-secondary" data-bs-dismiss="modal"
                        style="padding:8px 20px;font-size:.82rem;">
                        <i class="fa fa-xmark me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush
