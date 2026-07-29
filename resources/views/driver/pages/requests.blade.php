@extends('driver.layouts.driver')
@section('title', 'My Requests')
@section('page_title', 'My Requests')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .leaflet-control.leaflet-control-attribution {
            display: none !important;
        }   
        
        .req-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .req-table th {
            padding: 10px 16px;
            text-align: left;
            font-size: .71rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .35);
            text-transform: uppercase;
            letter-spacing: .05em;
            background: rgba(255, 255, 255, .03);
            border-bottom: 1px solid rgba(255, 255, 255, .06);
            white-space: nowrap;
        }

        .req-table td {
            padding: 12px 16px;
            color: rgba(255, 255, 255, .72);
            border-bottom: 1px solid rgba(255, 255, 255, .04);
            vertical-align: middle;
        }

        .req-table tbody tr:last-child td {
            border-bottom: none;
        }

        .req-table tbody tr:hover td {
            background: rgba(255, 255, 255, .03);
        }

        .req-table .mono {
            font-family: monospace;
            font-size: .78rem;
            background: rgba(255, 255, 255, .06);
            padding: 2px 7px;
            border-radius: 5px;
            color: rgba(255, 255, 255, .65);
        }

        .req-empty td {
            text-align: center;
            padding: 50px;
            color: rgba(255, 255, 255, .25);
            font-size: .85rem;
        }

        /* Detail modal */
        .req-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 22px;
        }

        @media (max-width:580px) {
            .req-detail-grid {
                grid-template-columns: 1fr;
            }
        }

        .req-detail-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .req-detail-item span {
            font-size: .69rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .35);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .req-detail-item strong {
            font-size: .88rem;
            color: #e2e8f0;
            font-weight: 600;
        }

        /* Spinner */
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

        /* Leaflet route panel override */
        .leaflet-routing-container {
            display: none !important;
        }

        /* ── Status Action Panel ────────────────────────────────────── */
        .req-action-panel {
            margin-top: 20px;
            padding: 16px 18px;
            background: rgba(255, 255, 255, .03);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 12px;
        }

        .req-action-panel__title {
            font-size: .68rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .3);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .req-action-panel__title i {
            color: rgba(255, 255, 255, .22);
        }

        .req-action-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
        }

        .req-act-btn {
            border: none;
            border-radius: 9px;
            padding: 9px 18px;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: opacity .15s, transform .1s, box-shadow .15s;
            line-height: 1.3;
        }

        .req-act-btn:hover:not(:disabled) {
            opacity: .88;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .35);
        }

        .req-act-btn:active:not(:disabled) {
            transform: scale(.97);
        }

        .req-act-btn:disabled {
            opacity: .38;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .req-act-btn.enroute {
            background: #2563eb;
            color: #fff;
        }

        .req-act-btn.arrived {
            background: #7c3aed;
            color: #fff;
        }

        .req-act-btn.transport {
            background: #d97706;
            color: #fff;
        }

        .req-act-btn.complete {
            background: #16a34a;
            color: #fff;
        }

        .req-act-btn.cancel {
            background: rgba(239, 68, 68, .12);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, .25);
        }

        .req-act-btn .btn-spin {
            width: 13px;
            height: 13px;
            border: 2px solid rgba(255, 255, 255, .25);
            border-top-color: #fff;
            border-radius: 50%;
            animation: driSpin .55s linear infinite;
            display: inline-block;
            flex-shrink: 0;
        }

        /* ── Transport Live Panel ────────────────────────────────────────────── */
        .transport-panel {
            margin-bottom: 14px;
            padding: 14px 16px;
            background: rgba(217, 119, 6, .07);
            border: 1px solid rgba(217, 119, 6, .22);
            border-radius: 12px;
        }

        .transport-panel__header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .transport-panel__dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #f59e0b;
            flex-shrink: 0;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, .4);
            animation: driLmPulse 1.8s infinite;
        }

        .transport-panel__title {
            font-size: .72rem;
            font-weight: 700;
            color: #f59e0b;
            letter-spacing: .05em;
        }

        .transport-panel__passenger {
            font-size: .77rem;
            color: rgba(255, 255, 255, .55);
            margin-left: auto;
            white-space: nowrap;
        }

        .transport-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .transport-stat {
            flex: 1;
            min-width: 90px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: 9px;
            padding: 10px 12px;
        }

        .transport-stat__lbl {
            font-size: .62rem;
            color: rgba(255, 255, 255, .28);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 4px;
        }

        .transport-stat__val {
            font-size: .95rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .transport-stat__val span {
            font-size: .65rem;
            font-weight: 400;
            color: rgba(255, 255, 255, .35);
        }

        /* ── Custom divIcon wrapper — suppresses Leaflet default white box ─────── */
        .rm-div-icon {
            background: none !important;
            border: none !important;
        }
    </style>
@endpush

@section('content')
    {{-- Page header --}}
    <div class="dri-page-header">
        <div>
            <h2>My Requests</h2>
            <p>Active requests assigned to you. Completed &amp; cancelled rides are in <a
                    href="{{ route('driver.past-rides') }}" style="color:#60a5fa;text-decoration:none;font-weight:600;">Past
                    Rides <i class="fa fa-arrow-right" style="font-size:.75em;"></i></a></p>
        </div>
    </div>

    {{-- Stats row --}}
    <div class="dri-stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));margin-bottom:20px;">
        <div class="dri-stat-card">
            <div class="dri-stat-icon blue"><i class="fa fa-ambulance"></i></div>
            <div class="dri-stat-val">{{ $stats['total'] }}</div>
            <div class="dri-stat-lbl">Total Rides</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-icon teal"><i class="fa fa-truck-medical"></i></div>
            <div class="dri-stat-val">{{ $stats['active'] }}</div>
            <div class="dri-stat-lbl">Active</div>
        </div>
        <div class="dri-stat-card">
            <div class="dri-stat-icon amber"><i class="fa fa-spinner"></i></div>
            <div class="dri-stat-val">{{ $stats['pending'] }}</div>
            <div class="dri-stat-lbl">Pending</div>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card" style="overflow:visible;">
        {{-- Toolbar --}}
        <form method="GET" action="{{ route('driver.requests.grid') }}" id="reqFilterForm">
            <div class="dri-toolbar">
                <div class="dri-search-wrap">
                    <i class="fa fa-magnifying-glass"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="dri-search-input"
                        placeholder="Search by ID, hospital, pickup…" autocomplete="off">
                </div>
                <select name="status" class="dri-filter-select"
                    onchange="document.getElementById('reqFilterForm').submit()">
                    <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>All Active</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Dispatched</option>
                    <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>En Route</option>
                    <option value="4" {{ request('status') === '4' ? 'selected' : '' }}>Arrived</option>
                    <option value="5" {{ request('status') === '5' ? 'selected' : '' }}>Transporting</option>
                </select>
                <button type="submit" class="btn-dri-primary" style="padding:7px 16px;font-size:.8rem;">
                    <i class="fa fa-magnifying-glass"></i> Search
                </button>
                @if (request('search') || (request('status') && request('status') !== 'all'))
                    <a href="{{ route('driver.requests.grid') }}" class="btn-dri-secondary"
                        style="padding:7px 14px;font-size:.8rem;text-decoration:none;">
                        <i class="fa fa-xmark me-1"></i> Clear
                    </a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="pgd-scroll pgd-scroll--list">
            <table class="req-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Type</th>
                        <th>Pickup Address</th>
                        <th>Hospital</th>
                        <th>Mobile</th>
                        <th>Ambulance</th>
                        <th>Status</th>
                        <th>Dispatched</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="driReqTableBody">
                    @forelse($requests as $r)
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
                        <tr id="reqRow_{{ $r->id }}">
                            <td><span class="mono">{{ $r->rreb_id ?? '#' . $r->id }}</span></td>
                            <td>
                                @if ($r->type === '1')
                                    <span class="status-pill emergency">Emergency</span>
                                @else
                                    <span class="status-pill non-emergency">Non-Emergency</span>
                                @endif
                            </td>
                            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ Str::limit($r->pickup_address, 32) }}
                            </td>
                            <td>{{ Str::limit($r->hospital_name, 22) }}</td>
                            <td style="white-space:nowrap;">{{ $r->mobile_no }}</td>
                            <td>{{ $r->ambulance?->vehicle_number ?? '—' }}</td>
                            <td><span class="status-pill {{ $sclass }}"
                                    id="reqStatusBadge_{{ $r->id }}">{{ $slabel }}</span></td>
                            <td style="white-space:nowrap;color:rgba(255,255,255,.38);font-size:.77rem;">
                                {{ $r->dispatched_at?->format('d M, H:i') ?? $r->created_at->format('d M, H:i') }}
                            </td>
                            <td>
                                <button class="btn-dri-icon btn-dri-icon--primary" title="View Details"
                                    onclick="viewRequestDetail({{ $r->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="req-empty">
                            <td colspan="9">
                                <i class="fa fa-inbox"
                                    style="display:block;font-size:1.6rem;margin-bottom:10px;opacity:.2;"></i>
                                No requests found.
                                @if (request('search') || request('status'))
                                    <br><a href="{{ route('driver.requests.grid') }}"
                                        style="color:rgba(255,255,255,.35);font-size:.78rem;">Clear filters</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pgd-footer">
            <div class="pgd-info">
                Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }} results
            </div>
            <div class="pgd-controls">
                @if ($requests->onFirstPage())
                    <button class="pgd-btn" disabled>← Prev</button>
                @else
                    <a href="{{ $requests->previousPageUrl() }}" class="pgd-btn">← Prev</a>
                @endif
                <span class="pgd-pages">Page {{ $requests->currentPage() }} / {{ $requests->lastPage() }}</span>
                @if ($requests->hasMorePages())
                    <a href="{{ $requests->nextPageUrl() }}" class="pgd-btn">Next →</a>
                @else
                    <button class="pgd-btn" disabled>Next →</button>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        window.driRequestBaseUrl = "{{ url('/driver/requests') }}";
    </script>
    <script src="{{ asset('assets/driver/js/requests.js') }}"></script>
@endpush

@push('modals')
    {{-- Request Detail Modal — rendered at body level to avoid stacking-context conflicts --}}
    <div class="modal fade" id="reqDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reqModalTitle">
                        <i class="fa fa-truck-medical me-2" style="color:#f87171;"></i>Request Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reqDetailBody">
                    <div style="text-align:center;padding:30px;">
                        <div class="dri-spinner"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Reject Confirmation Modal ───────────────────────────────────────────── --}}
    <div class="modal fade" id="driRejectConfirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false" style="z-index: 2080 !important;background: #00000052;">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content"
                style="background:#1a2235;border:1px solid rgba(239,68,68,.3);border-radius:16px;overflow:hidden;">
                <div class="modal-body" style="padding:28px 24px 22px;">
                    <div style="text-align:center;margin-bottom:22px;">
                        <div
                            style="width:56px;height:56px;border-radius:50%;background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.2);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                            <i class="fa fa-triangle-exclamation" style="color:#ef4444;font-size:1.4rem;"></i>
                        </div>
                        <h6 style="color:#f1f5f9;font-weight:700;margin:0 0 10px;font-size:1rem;">Reject Dispatch Request?
                        </h6>
                        <p style="color:rgba(255,255,255,.45);font-size:.83rem;margin:0;line-height:1.55;">
                            Are you sure you want to reject this request?<br>
                            The admin will be notified and the request will return to <strong
                                style="color:rgba(255,255,255,.6);">Pending</strong>.
                        </p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <button type="button" class="btn w-50"
                            style="background:rgba(255,255,255,.05);color:#94a3b8;border:1px solid rgba(255,255,255,.1);border-radius:10px;font-size:.85rem;padding:10px 0;transition:background .2s;"
                            data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" onclick="driConfirmReject()" class="btn w-50"
                            style="background:rgba(239,68,68,.18);color:#f87171;border:1px solid rgba(239,68,68,.35);border-radius:10px;font-size:.85rem;padding:10px 0;font-weight:700;transition:background .2s;">
                            <i class="fa fa-xmark me-1"></i>Confirm Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush
