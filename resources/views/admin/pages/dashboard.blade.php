@extends('admin.layouts.admin')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('styles')
    <style>
        .chart-range-btn {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all .15s;
        }

        .chart-range-btn:hover {
            background: rgba(255, 255, 255, 0.09);
            color: #f1f5f9;
        }

        .chart-range-btn--active {
            background: rgba(215, 44, 66, 0.15);
            border-color: rgba(215, 44, 66, 0.35);
            color: var(--adm-red);
        }

        .chart-zoom-btn {
            font-size: 0.72rem;
            padding: 5px 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all .15s;
        }

        .chart-zoom-btn:hover {
            background: rgba(255, 255, 255, 0.09);
            color: #f1f5f9;
        }
    </style>
@endpush

@section('content')
    {{-- Welcome row --}}
    <div class="adm-page-header">
        <div>
            <h1>Welcome back, {{ explode(' ', $admin->name)[0] }}.</h1>
            <p>Here's what's happening with Rapid Rescue today.</p>
        </div>
        <div class="adm-online-badge">
            <i class="fa fa-circle"></i> System Online
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card--blue">
                <div class="stat-icon stat-icon--blue"><i class="fa fa-ambulance"></i></div>
                <div>
                    <div class="stat-label">Total Ambulances</div>
                    <div class="stat-value">{{ $stats['ambulances'] }}</div>
                    <div class="stat-sub">Fleet registered</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card--green">
                <div class="stat-icon stat-icon--green"><i class="fa fa-circle-check"></i></div>
                <div>
                    <div class="stat-label">Available</div>
                    <div class="stat-value" id="admStatAvailable">{{ $stats['available_ambs'] }}</div>
                    <div class="stat-sub">Ready to dispatch</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card--red">
                <div class="stat-icon stat-icon--red"><i class="fa fa-phone-volume"></i></div>
                <div>
                    <div class="stat-label">On Call</div>
                    <div class="stat-value" id="admStatOnCall">{{ $stats['on_call_ambs'] }}</div>
                    <div class="stat-sub">Currently deployed</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card--orange">
                <div class="stat-icon stat-icon--orange"><i class="fa fa-wrench"></i></div>
                <div>
                    <div class="stat-label">In Maintenance</div>
                    <div class="stat-value">{{ $stats['maintenance'] }}</div>
                    <div class="stat-sub">Awaiting service</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Visitor Chart --}}
    <div class="card mb-3" style="padding:1.4rem 1.5rem 1rem;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center0 gap-2">
                <div class="adm-icon-preview">
                    <i class="fa fa-chart-area"></i>
                </div>

                <div>
                    <div style="font-weight:700;color:#f1f5f9;font-size:0.95rem;">Visitor Traffic</div>
                    <div style="font-size:0.75rem;color:var(--adm-muted);" id="chartSubtitle">Last 30 days</div>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="d-flex gap-1">
                    <button onclick="setChartRange(7)" id="btn7" class="chart-range-btn">7D</button>
                    <button onclick="setChartRange(30)" id="btn30" class="chart-range-btn chart-range-btn--active">30D</button>
                    <button onclick="setChartRange(90)" id="btn90" class="chart-range-btn">90D</button>
                </div>

                <div class="d-flex gap-1" style="border-left:1px solid rgba(255,255,255,0.08); padding-left:8px;">
                    <button onclick="zoomChart(-1)" class="chart-zoom-btn" title="Zoom Out"><i class="fa fa-minus"></i></button>
                    <button onclick="zoomChart(1)" class="chart-zoom-btn" title="Zoom In"><i class="fa fa-plus"></i></button>
                    <button onclick="resetZoom()" class="chart-zoom-btn" title="Reset"><i class="fa fa-arrows-rotate"></i></button>
                </div>
            </div>
        </div>

        <div style="position:relative; height:220px;">
            <canvas id="visitorChart"></canvas>
        </div>
    </div>

    {{-- Recent Logs --}}
    <div class="row g-3">
        {{-- Activity Logs --}}
        <div class="col-12 col-xl-6">
            <div class="card dash-log-card" id="activityLogsCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        <i class="fa fa-shield-halved" style="color:#f87184;"></i>
                        Recent Activity Logs
                    </h6>

                    <a href="{{ route('admin.logs') }}" class="btn-adm-ghost" style="padding:6px 14px;font-size:0.78rem;">
                        View All <i class="fa fa-arrow-right ms-1" style="font-size:0.7rem;"></i>
                    </a>
                </div>

                @if ($recentLogs->isEmpty())
                    <div class="adm-empty dash-log-empty" id="activityLogsEmpty">
                        <i class="fa fa-inbox"></i>
                        <p>No activity logs yet.</p>
                    </div>
                @else
                    <div class="dash-log-scroll" id="activityLogsScroll">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Time</th>
                                </tr>
                            </thead>

                            <tbody id="activityLogsTbody">
                                @foreach ($recentLogs as $log)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="driver-avatar">{{ strtoupper(substr($log->username, 0, 1)) }}</div>
                                                <strong style="font-size:0.82rem;">{{ $log->username }}</strong>
                                            </div>
                                        </td>
                                        <td
                                            style="color:var(--adm-text);font-size:0.82rem;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $log->action }}
                                        </td>
                                        <td class="text-muted fs-xs text-nowrap">{{ $log->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Visitor Logs --}}
        <div class="col-12 col-xl-6">
            <div class="card dash-log-card" id="visitorLogsCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        <i class="fa fa-binoculars" style="color:#818cf8;"></i>
                        Recent Visitor Logs
                    </h6>
                    <a href="{{ route('admin.visitor-logs') }}" class="btn-adm-ghost"
                        style="padding:6px 14px;font-size:0.78rem;">
                        View All <i class="fa fa-arrow-right ms-1" style="font-size:0.7rem;"></i>
                    </a>
                </div>

                @if ($recentVisitorLogs->isEmpty())
                    <div class="adm-empty dash-log-empty" id="visitorLogsEmpty">
                        <i class="fa fa-binoculars"></i>
                        <p>No visitor logs yet.</p>
                    </div>
                @else
                    <div class="dash-log-scroll" id="visitorLogsScroll">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">IP Address</th>
                                    <th>Browser</th>
                                    <th>Platform</th>
                                    <th>Device</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="visitorLogsTbody">
                                @foreach ($recentVisitorLogs as $vlog)
                                    <tr>
                                        <td class="ps-3">
                                            <code
                                                style="font-size:0.75rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:2px 6px;border-radius:5px;">
                                                {{ $vlog->ip_address }}
                                            </code>
                                        </td>
                                        <td style="font-size:0.82rem;max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $vlog->browser ?? '—' }}
                                        </td>
                                        <td style="font-size:0.82rem;">{{ $vlog->platform ?? '—' }}</td>
                                        <td>
                                            @if ($vlog->is_mobile)
                                                <span class="status-pill status-pill--orange"><i class="fa fa-mobile-screen-button me-1"></i>Mobile</span>
                                            @else
                                                <span class="status-pill status-pill--blue"><i class="fa fa-desktop me-1"></i>Desktop</span>
                                            @endif
                                        </td>
                                        <td class="text-muted fs-xs text-nowrap">{{ $vlog->created_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        window.dashboard = {
            visitorLabels:    @json(array_keys($visitorChart)),
            visitorData:      @json(array_values($visitorChart)),
            maxVisitorRows:   {{ max($recentVisitorLogs->count(), 10) }},
            maxActivityRows:  {{ max($recentLogs->count(), 10) }},
        };
    </script>
    <script src="{{ asset('assets/admin/js/dashboard.js') }}"></script>
@endpush
