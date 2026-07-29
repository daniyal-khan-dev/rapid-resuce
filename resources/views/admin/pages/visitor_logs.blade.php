@extends('admin.layouts.admin')
@section('title', 'Visitor Logs')
@section('page_title', 'Visitor Logs')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Visitor Logs</h2>
            <p>Guest visitors tracked and grouped by month.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 fs-xs fw-bold" style="color:var(--adm-muted);">
                <i class="fa fa-calendar-days" style="color:#f87184;margin-right:5px;"></i>Year:
            </label>
            <select class="form-select form-select-sm w-auto" id="yearDropdown" onchange="switchYear(this.value)">
                @foreach ($years as $yr)
                    <option value="{{ $yr }}" {{ $yr == $currentYear ? 'selected' : '' }}>{{ $yr }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--blue">
                <div class="stat-icon stat-icon--blue"><i class="fa fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Visitors</div>
                    <div class="stat-value">{{ number_format($total) }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--green">
                <div class="stat-icon stat-icon--green"><i class="fa fa-calendar-day"></i></div>
                <div>
                    <div class="stat-label">Today</div>
                    <div class="stat-value">{{ number_format($today) }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--orange">
                <div class="stat-icon stat-icon--orange"><i class="fa fa-mobile-screen"></i></div>
                <div>
                    <div class="stat-label">Mobile</div>
                    <div class="stat-value">{{ number_format($mobile) }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--purple">
                <div class="stat-icon stat-icon--purple"><i class="fa fa-desktop"></i></div>
                <div>
                    <div class="stat-label">Desktop</div>
                    <div class="stat-value">{{ number_format($total - $mobile) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Global search + device filter --}}
    <div class="adm-filter-row mb-3">
        <input type="text" class="form-control w-auto" id="searchVisitor" placeholder="Search IP, browser, platform…" oninput="filterVisitors()">
        <select class="form-select w-auto" id="filterDevice" onchange="filterVisitors()">
            <option value="">All Devices</option>
            <option value="mobile">Mobile</option>
            <option value="desktop">Desktop</option>
        </select>
    </div>

    @if ($grouped->isEmpty())
        <div class="card">
            <div class="adm-empty">
                <i class="fa fa-binoculars"></i>
                <p>No visitor logs yet. Make sure the visitor tracking middleware is active.</p>
            </div>
        </div>
    @else
        @foreach ($grouped as $year => $months)
            <div class="log-year-panel {{ $year == $currentYear ? 'active' : '' }}" id="year-panel-{{ $year }}">
                @foreach ($months->sortKeys() as $monthNum => $visitors)
                    @php
                        $monthName = \Carbon\Carbon::create()->month($monthNum)->format('F');
                        $monthKey = $year . '-' . $monthNum;
                    @endphp
                    <div class="mb-3">
                        <button class="log-month-btn" onclick="toggleMonth('{{ $monthKey }}')">
                            <i class="fa fa-binoculars" style="color:var(--adm-muted);"></i>
                            <span>{{ $monthName }} {{ $year }}</span>
                            <span class="log-count-badge">{{ $visitors->count() }} visitors</span>
                        </button>

                        <div class="log-month-body" id="month-body-{{ $monthKey }}">
                            <div class="d-flex flex-wrap gap-2 mb-3 px-2">
                                <button class="log-filter-btn active" onclick="applyVFilter('{{ $monthKey }}', 'all', this)">All</button>
                                <button class="log-filter-btn" onclick="applyVFilter('{{ $monthKey }}', 'mobile', this)">Mobile</button>
                                <button class="log-filter-btn" onclick="applyVFilter('{{ $monthKey }}', 'desktop', this)">Desktop</button>
                            </div>

                            <div class="table-responsive"
                                style="border-radius:var(--adm-radius-sm);border:1px solid var(--adm-border);max-height:360px;overflow-y:auto;">
                                <table class="table table-hover mb-0" style="font-size:0.855rem;">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>IP Address</th>
                                            <th>Browser</th>
                                            <th>Platform</th>
                                            <th>Device</th>
                                            <th>Type</th>
                                            <th>Date &amp; Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="vlog-tbody-{{ $monthKey }}">
                                        @foreach ($visitors->sortByDesc('created_at') as $log)
                                            <tr class="vlog-row" data-device="{{ $log->is_mobile ? 'mobile' : 'desktop' }}"
                                                data-search="{{ strtolower($log->ip_address . ' ' . $log->browser . ' ' . $log->platform . ' ' . $log->device) }}">
                                                <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                                                <td>
                                                    <code style="font-size:0.80rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:3px 8px;border-radius:6px;border:1px solid rgba(59,130,246,0.15);">
                                                        {{ $log->ip_address }}
                                                    </code>
                                                </td>
                                                <td>
                                                    <span
                                                        style="font-size:0.85rem;display:flex;align-items:center;gap:6px;">
                                                        @if (str_contains(strtolower($log->browser ?? ''), 'chrome'))
                                                            <i class="fab fa-chrome" style="color:#fbbf24;"></i>
                                                        @elseif(str_contains(strtolower($log->browser ?? ''), 'firefox'))
                                                            <i class="fab fa-firefox-browser" style="color:#f97316;"></i>
                                                        @elseif(str_contains(strtolower($log->browser ?? ''), 'safari'))
                                                            <i class="fab fa-safari" style="color:#60b3fb;"></i>
                                                        @else
                                                            <i class="fa fa-globe" style="color:var(--adm-muted);"></i>
                                                        @endif
                                                        {{ $log->browser ?? '—' }}
                                                    </span>
                                                </td>
                                                <td style="color:var(--adm-text);">{{ $log->platform ?? '—' }}</td>
                                                <td style="color:var(--adm-muted);font-size:0.85rem;">{{ $log->device ?? '—' }}</td>
                                                <td>
                                                    @if ($log->is_mobile)
                                                        <span class="status-pill"
                                                            style="background:rgba(249,115,22,0.12);color:#fdba74;border:1px solid rgba(249,115,22,0.22);">
                                                            <i class="fa fa-mobile-screen"
                                                                style="font-size:0.65rem;margin-right:3px;"></i>Mobile
                                                        </span>
                                                    @else
                                                        <span class="status-pill"
                                                            style="background:rgba(139,92,246,0.12);color:#c4b5fd;border:1px solid rgba(139,92,246,0.22);">
                                                            <i class="fa fa-desktop"
                                                                style="font-size:0.65rem;margin-right:3px;"></i>Desktop
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/admin/js/visiotorsLogs.js') }}"></script>
@endpush