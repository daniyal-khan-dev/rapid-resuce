@extends('admin.layouts.admin')
@section('title', 'Activity Logs')
@section('page_title', 'Activity Logs')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Activity Logs</h2>
            <p>Browse all system activity grouped by year and month.</p>
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

    @if ($grouped->isEmpty())
        <div class="card">
            <div class="adm-empty">
                <i class="fa fa-inbox"></i>
                <p>No logs recorded yet.</p>
            </div>
        </div>
    @else
        @foreach ($grouped as $year => $months)
            <div class="log-year-panel {{ $year == $currentYear ? 'active' : '' }}" id="year-panel-{{ $year }}">
                @foreach ($months->sortKeys() as $monthNum => $logs)
                    @php
                        $monthName = \Carbon\Carbon::create()->month($monthNum)->format('F');
                        $monthKey = $year . '-' . $monthNum;
                    @endphp
                    <div class="mb-3">
                        <button class="log-month-btn" onclick="toggleMonth('{{ $monthKey }}')">
                            <i class="fa fa-calendar-week" style="color:var(--adm-muted);"></i>
                            <span>{{ $monthName }} {{ $year }}</span>
                            <span class="log-count-badge">{{ $logs->count() }} logs</span>
                        </button>

                        <div class="log-month-body" id="month-body-{{ $monthKey }}">
                            <div class="d-flex flex-wrap gap-2 mb-3 px-2">
                                <button class="log-filter-btn active" onclick="applyFilter('{{ $monthKey }}', 'all', this)">All</button>
                                <button class="log-filter-btn" onclick="applyFilter('{{ $monthKey }}', 'week', this)">This Week</button>
                                <button class="log-filter-btn" onclick="applyFilter('{{ $monthKey }}', 'day', this)">Today</button>
                            </div>

                            <div class="table-responsive"
                                style="border-radius:var(--adm-radius-sm);border:1px solid var(--adm-border);max-height:360px;overflow-y:auto;">
                                <table class="table table-hover mb-0" style="font-size:0.855rem;">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">#</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>IP Address</th>
                                            <th>Date &amp; Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="log-tbody-{{ $monthKey }}">
                                        @foreach ($logs->sortByDesc('created_at') as $log)
                                            <tr data-date="{{ $log->created_at->format('Y-m-d') }}" data-week-start="{{ now()->startOfWeek()->format('Y-m-d') }}" data-week-end="{{ now()->endOfWeek()->format('Y-m-d') }}" data-today="{{ today()->format('Y-m-d') }}">
                                                <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="driver-avatar" style="width:28px;height:28px;font-size:0.75rem;">
                                                            {{ strtoupper(substr($log->username, 0, 1)) }}
                                                        </div>
                                                        <strong>{{ $log->username }}</strong>
                                                    </div>
                                                </td>
                                                <td style="color:var(--adm-text);">{{ $log->action }}</td>
                                                <td>
                                                    <code style="font-size:0.78rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:2px 7px;border-radius:5px;">
                                                        {{ $log->ip_address }}
                                                    </code>
                                                </td>
                                                <td class="fs-xs" style="color:var(--adm-muted);">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
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
    <script src="{{ asset('assets/admin/js/logs.js') }}"></script>
@endpush