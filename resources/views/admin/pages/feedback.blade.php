@extends('admin.layouts.admin')
@section('title', 'Feedback')
@section('page_title', 'Feedback')

@php
    $_feedbackPageData = $feedback
        ->getCollection()
        ->map(function ($item) {
            $userDetails = $item->user?->details;
            $ride = $item->request;

            return [
                'id' => $item->id,
                'viewed' => $item->viewed_at !== null,
                'booking_id' => $ride?->rreb_id ?? '—',
                'booking_url' => $ride
                    ? route('admin.emergency.past-rides', ['search' => $ride->rreb_id])
                    : null,
                'user_name' => trim(($userDetails?->first_name ?? '') . ' ' . ($userDetails?->last_name ?? ''))
                    ?: ($item->name ?: 'Guest'),
                'user_email' => $item->user?->username ?? $item->email ?? '—',
                'user_phone' => $userDetails?->phone ?? $ride?->mobile_no ?? '—',
                'driver_name' => $ride?->driver?->name ?? '—',
                'driver_phone' => $ride?->driver?->phone ?? '—',
                'rating' => (int) $item->rating,
                'message' => $item->message ?: 'No written feedback provided.',
                'submitted_at' => $item->created_at?->format('d M Y, h:i A') ?? '—',
                 'created_at' => $item->created_at?->toIso8601String(),
                'ride_type' => match ((string) ($ride?->type ?? '')) {
                    '1' => 'Emergency',
                    '2' => 'Non-Emergency',
                    default => $ride?->type ?? '—',
                },
                'status' => match ((string) ($ride?->status ?? '')) {
                    '6' => 'Completed',
                    '7' => 'Cancelled',
                    default => $ride?->status ?? '—',
                },
                'hospital_name' => $ride?->hospital_name ?? '—',
                'pickup_address' => $ride?->pickup_address ?? '—',
                'ambulance' => $ride?->ambulance?->vehicle_number ?? '—',
                'requested_at' => $ride?->created_at?->format('d M Y, h:i A') ?? '—',
                'dispatched_at' => $ride?->dispatched_at?->format('d M Y, h:i A') ?? '—',
                'completed_at' => $ride?->completed_at?->format('d M Y, h:i A') ?? '—',
            ];
        })
        ->values()
        ->all();
@endphp

@section('content')
    <div class="adm-page-header">
        <div>
            <h2><i class="fa fa-star me-2" style="color:#fbbf24;font-size:1.1rem;"></i>Feedback</h2>
            <p>User feedback submitted after completing an emergency ride.</p>
        </div>
        <span class="status-pill status-1" style="font-size:0.8rem;">
            <span id="feedbackHeaderTotal">{{ number_format($totalFeedback) }}</span> Total
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--blue">
                <div class="stat-icon stat-icon--blue"><i class="fa fa-comments"></i></div>
                <div>
                    <div class="stat-label">Total Feedback</div>
                    <div class="stat-value" id="feedbackStatTotal">{{ number_format($totalFeedback) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card--orange">
                <div class="stat-icon stat-icon--orange"><i class="fa fa-star"></i></div>
                <div>
                    <div class="stat-label">Average Rating</div>
                    <div class="stat-value" id="feedbackAverageRating">{{ number_format($averageRating, 1) }} <small style="font-size:.7rem;">/ 5</small></div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.feedback.grid') }}" class="adm-filter-row mb-3">
        <div class="position-relative flex-grow-1" style="min-width:220px;">
            <i class="fa fa-magnifying-glass position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--adm-muted);font-size:.8rem;"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control ps-5"
                placeholder="Search booking, user, driver, or feedback…" autocomplete="off">
        </div>
        <select name="sort" class="form-select w-auto" onchange="this.form.submit()">
            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest first</option>
            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            <option value="rating_high" {{ request('sort') === 'rating_high' ? 'selected' : '' }}>Highest rating</option>
            <option value="rating_low" {{ request('sort') === 'rating_low' ? 'selected' : '' }}>Lowest rating</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm px-3">
            <i class="fa fa-magnifying-glass me-1"></i> Search
        </button>
        @if (request()->filled('search'))
            <a href="{{ route('admin.feedback.grid', ['sort' => request('sort', 'latest')]) }}"
                class="btn btn-secondary btn-sm px-3">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="pgd-scroll" id="feedbackTableScroll" style="{{ $feedback->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="feedbackTable">
                <thead>
                    <tr>
                        <th class="ps-4">Sr. No.</th>
                        <th>Booking ID</th>
                        <th>User Name</th>
                        <th>Driver Name</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($feedback as $item)
                        @php
                            $userDetails = $item->user?->details;
                            $userName = trim(($userDetails?->first_name ?? '') . ' ' . ($userDetails?->last_name ?? ''))
                                ?: ($item->name ?: 'Guest');
                        @endphp
                        <tr data-feedback-id="{{ $item->id }}">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">
                                {{ ($feedback->firstItem() ?? 0) + $loop->index }}
                            </td>
                            <td>
                                @if ($item->request)
                                    <a href="{{ route('admin.emergency.past-rides', ['search' => $item->request->rreb_id]) }}"
                                        style="font-family:monospace;font-size:.8rem;background:rgba(129,140,248,.12);padding:4px 8px;border-radius:6px;color:#a5b4fc;text-decoration:none;white-space:nowrap;"
                                        title="View matching ride">
                                        {{ $item->request->rreb_id ?? '—' }}
                                    </a>
                                @else
                                    <span class="fs-xs" style="color:var(--adm-muted);">Booking unavailable</span>
                                @endif
                            </td>
                            <td class="fs-xs" style="color:var(--adm-text);">
                                <div>{{ $userName }}</div>
                                <small style="color:var(--adm-muted);">{{ $item->user?->username ?? $item->email ?? '—' }}</small>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                {{ $item->request?->driver?->name ?? '—' }}
                            </td>
                            <td>
                                <span style="white-space:nowrap;color:#fbbf24;font-size:.8rem;" title="{{ $item->rating }} out of 5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i class="fa{{ $star <= $item->rating ? 's' : 'r' }} fa-star"></i>
                                    @endfor
                                    <span style="color:var(--adm-muted);margin-left:4px;">{{ $item->rating }}/5</span>
                                </span>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);max-width:260px;">
                                {{ Str::limit($item->message ?: 'No written feedback provided.', 70) }}
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);white-space:nowrap;">
                                {{ $item->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </td>
                            <td>
                                <button type="button" class="btn-adm-icon" title="View Feedback"
                                    onclick="viewFeedback({{ $item->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pgd-footer" id="feedbackPagination" style="{{ $feedback->count() ? '' : 'display:none;' }}">
            <div class="pgd-info">
                <span id="feedbackPaginationInfo">Showing {{ $feedback->firstItem() }}–{{ $feedback->lastItem() }} of {{ $feedback->total() }} feedback entries</span>
            </div>
            <div class="pgd-controls">
                @if ($feedback->onFirstPage())
                    <button class="pgd-btn" id="feedbackPrev" disabled>&#8592; Prev</button>
                @else
                    <a href="{{ $feedback->previousPageUrl() }}" id="feedbackPrev" class="pgd-btn" style="text-decoration:none;">&#8592; Prev</a>
                @endif

                <span class="pgd-pages" id="feedbackPaginationPages">Page {{ $feedback->currentPage() }} / {{ $feedback->lastPage() }}</span>

                @if ($feedback->hasMorePages())
                    <a href="{{ $feedback->nextPageUrl() }}" id="feedbackNext" class="pgd-btn" style="text-decoration:none;">Next &#8594;</a>
                @else
                    <button class="pgd-btn" id="feedbackNext" disabled>Next &#8594;</button>
                @endif
            </div>
        </div>

        <div class="adm-empty" style="{{ $feedback->count() ? 'display:none;' : '' }}">
            <i class="fa fa-comments"></i>
            <p>No feedback found.
                @if (request()->filled('search'))
                    <br><small>Try adjusting your search.</small>
                @else
                    <br><small>User feedback will appear here after completed rides are rated.</small>
                @endif
            </p>
        </div>
    </div>

    <div class="modal fade" id="feedbackDetailModal" tabindex="-1" aria-labelledby="feedbackModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalTitle">
                        <span class="modal-title-icon"><i class="fa fa-star"></i></span> Feedback Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="feedbackDetailBody"></div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.06);">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const feedbackPageData = @json($_feedbackPageData);
        const feedbackSeenIds = new Set(feedbackPageData.map(function (item) {
            return Number(item.id);
        }));
        const feedbackMeta = {
            currentPage: {{ $feedback->currentPage() }},
            perPage: {{ $feedback->perPage() }},
            total: {{ $feedback->total() }},
            search: @json(strtolower(trim((string) request('search')))),
            sort: @json(request('sort', 'latest')),
        };
    </script>
    <script src="{{ asset('assets/admin/js/feedback.js') }}"></script>
@endpush