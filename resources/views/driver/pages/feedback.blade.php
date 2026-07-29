@extends('driver.layouts.driver')

@section('title', 'Feedback')
@section('page_title', 'Feedback')

@php
    $_driverFeedbackData = $feedback
        ->getCollection()
        ->map(function ($item) {
            $userDetails = $item->user?->details;
            $ride = $item->request;

            return [
                'id' => $item->id,
                'driver_id' => $ride?->driver_id,
                'driver_viewed' => $item->driver_viewed_at !== null,
                'booking_id' => $ride?->rreb_id ?? '—',
                'booking_url' => $ride ? route('driver.past-rides', ['search' => $ride->rreb_id]) : null,
                'user_name' =>
                    trim(($userDetails?->first_name ?? '') . ' ' . ($userDetails?->last_name ?? '')) ?:
                    ($item->name ?:
                    'Guest'),
                'user_email' => $item->user?->username ?? ($item->email ?? '—'),
                'user_phone' => $userDetails?->phone ?? ($ride?->mobile_no ?? '—'),
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

@push('styles')
    <style>
        body.modal-open {
            overflow: hidden !important;
        }

        .driver-feedback-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        .driver-feedback-header h2 {
            margin: 0 0 4px;
            font-size: 1.25rem;
            font-weight: 700;
            color: #f1f5f9;
        }

        .driver-feedback-header p {
            margin: 0;
            font-size: .82rem;
            color: rgba(255, 255, 255, .4);
        }

        .driver-feedback-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .driver-feedback-toolbar .form-control,
        .driver-feedback-toolbar .form-select {
            background: rgba(255, 255, 255, .05);
            border-color: rgba(255, 255, 255, .1);
            color: #e2e8f0;
            font-size: .82rem;
        }

        .driver-feedback-toolbar .form-control {
            min-width: 220px;
        }

        .driver-feedback-toolbar .form-select option {
            background: #0e1728;
        }

        .driver-feedback-table th {
            white-space: nowrap;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: rgba(255, 255, 255, .38);
        }

        .driver-feedback-table td {
            vertical-align: middle;
            font-size: .8rem;
        }

        .driver-feedback-empty {
            padding: 50px 20px !important;
            text-align: center;
            color: rgba(255, 255, 255, .28);
        }

        .driver-feedback-empty i {
            display: block;
            margin-bottom: 10px;
            font-size: 1.6rem;
            opacity: .45;
        }

        .driver-feedback-stars {
            white-space: nowrap;
            color: #fbbf24;
            font-size: .78rem;
        }

        .driver-feedback-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
        }

        .driver-feedback-modal-field label {
            display: block;
            margin-bottom: 4px;
            color: rgba(255, 255, 255, .35);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .driver-feedback-modal-field span {
            color: #e2e8f0;
            font-size: .85rem;
            word-break: break-word;
        }

        @media (max-width: 768px) {
            .driver-feedback-toolbar .form-control {
                min-width: 0;
                width: 100%;
            }
        }
        
        .modal-backdrop {
            position: relative !important;
        }
    </style>
@endpush

@section('content')
    <div class="driver-feedback-header">
        <div>
            <h2><i class="fa fa-star me-2" style="color:#fbbf24;font-size:1.1rem;"></i>Feedback</h2>
            <p>Feedback submitted for rides assigned to you.</p>
        </div>
        <span class="status-pill status-1" style="font-size:.8rem;">
            <span id="driverFeedbackHeaderTotal">{{ number_format($feedback->total()) }}</span> Total
        </span>
    </div>

    <form method="GET" action="{{ route('driver.feedback.grid') }}" class="driver-feedback-toolbar">
        <div class="position-relative flex-grow-1">
            <i class="fa fa-magnifying-glass position-absolute"
                style="left:12px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);font-size:.8rem;"></i>
            <input type="search" name="search" value="{{ request('search') }}" class="form-control ps-5"
                placeholder="Search booking, user, or feedback…" autocomplete="off">
        </div>
        <select name="sort" class="form-select w-auto" onchange="this.form.submit()">
            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest first</option>
            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            <option value="rating_high" {{ request('sort') === 'rating_high' ? 'selected' : '' }}>Highest rating</option>
            <option value="rating_low" {{ request('sort') === 'rating_low' ? 'selected' : '' }}>Lowest rating</option>
        </select>
        <button type="submit" class="btn-dri-primary" style="padding:8px 16px;font-size:.8rem;">
            <i class="fa fa-magnifying-glass me-1"></i> Search
        </button>
        @if (request()->filled('search'))
            <a href="{{ route('driver.feedback.grid', ['sort' => request('sort', 'latest')]) }}" class="btn-dri-secondary"
                style="padding:8px 16px;font-size:.8rem;text-decoration:none;">Clear</a>
        @endif
    </form>

    <div class="card">
        <div class="pgd-scroll" id="driverFeedbackTableScroll" style="{{ $feedback->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0 driver-feedback-table" id="driverFeedbackTable">
                <thead>
                    <tr>
                        <th class="ps-4">Sr. No.</th>
                        <th>Booking ID</th>
                        <th>User Name</th>
                        <th>Rating</th>
                        <th>Feedback</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="driverFeedbackBody">
                    @foreach ($feedback as $item)
                        @php
                            $userDetails = $item->user?->details;
                            $userName =
                                trim(($userDetails?->first_name ?? '') . ' ' . ($userDetails?->last_name ?? '')) ?:
                                ($item->name ?:
                                'Guest');
                        @endphp
                        <tr data-driver-feedback-id="{{ $item->id }}">
                            <td class="ps-4" style="color:rgba(255,255,255,.35);">
                                {{ ($feedback->firstItem() ?? 0) + $loop->index }}
                            </td>
                            <td>
                                @if ($item->request)
                                    <a href="{{ route('driver.past-rides', ['search' => $item->request->rreb_id]) }}"
                                        style="font-family:monospace;font-size:.78rem;color:#a5b4fc;text-decoration:none;">
                                        {{ $item->request->rreb_id }}
                                    </a>
                                @else
                                    <span style="color:rgba(255,255,255,.3);">—</span>
                                @endif
                            </td>
                            <td>
                                <div style="color:#e2e8f0;">{{ $userName }}</div>
                                <small
                                    style="color:rgba(255,255,255,.35);">{{ $item->user?->username ?? ($item->email ?? '—') }}</small>
                            </td>
                            <td>
                                <span class="driver-feedback-stars" title="{{ $item->rating }} out of 5">
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i class="fa{{ $star <= $item->rating ? 's' : 'r' }} fa-star"></i>
                                    @endfor
                                    <span style="color:rgba(255,255,255,.35);margin-left:4px;">{{ $item->rating }}/5</span>
                                </span>
                            </td>
                            <td style="max-width:260px;color:rgba(255,255,255,.5);">
                                {{ Str::limit($item->message ?: 'No written feedback provided.', 70) }}
                            </td>
                            <td style="white-space:nowrap;color:rgba(255,255,255,.4);">
                                {{ $item->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </td>
                            <td>
                                <button type="button" class="btn-dri-icon btn-dri-icon--primary" title="View Feedback"
                                    onclick="driverViewFeedback({{ $item->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pgd-footer" id="driverFeedbackPagination" style="{{ $feedback->count() ? '' : 'display:none;' }}">
            <div class="pgd-info">
                <span id="driverFeedbackPaginationInfo">
                    Showing {{ $feedback->firstItem() }}–{{ $feedback->lastItem() }} of {{ $feedback->total() }} feedback
                    entries
                </span>
            </div>
            <div class="pgd-controls">
                @if ($feedback->onFirstPage())
                    <button class="pgd-btn" id="driverFeedbackPrev" disabled>&#8592; Prev</button>
                @else
                    <a href="{{ $feedback->previousPageUrl() }}" id="driverFeedbackPrev" class="pgd-btn"
                        style="text-decoration:none;">&#8592; Prev</a>
                @endif
                <span class="pgd-pages" id="driverFeedbackPaginationPages">
                    Page {{ $feedback->currentPage() }} / {{ $feedback->lastPage() }}
                </span>
                @if ($feedback->hasMorePages())
                    <a href="{{ $feedback->nextPageUrl() }}" id="driverFeedbackNext" class="pgd-btn"
                        style="text-decoration:none;">Next &#8594;</a>
                @else
                    <button class="pgd-btn" id="driverFeedbackNext" disabled>Next &#8594;</button>
                @endif
            </div>
        </div>

        <div class="driver-feedback-empty" id="driverFeedbackEmptyRow" style="{{ $feedback->count() ? 'display:none;' : '' }}">
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

    <div class="modal fade" id="driverFeedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-star me-2" style="color:#fbbf24;"></i>Feedback Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="driverFeedbackModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn-dri-secondary" data-bs-dismiss="modal"
                        style="padding:8px 20px;font-size:.82rem;">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const driverFeedbackData = @json($_driverFeedbackData);
        const driverFeedbackSeenIds = new Set(driverFeedbackData.map(function(item) {
            return Number(item.id);
        }));

        const driverFeedbackMeta = {
            currentPage: {{ $feedback->currentPage() }},
            perPage: {{ $feedback->perPage() }},
            total: {{ $feedback->total() }},
            search: @json(strtolower(trim((string) request('search')))),
            sort: @json(request('sort', 'latest')),
        };
    </script>
    <script src="{{ asset('assets/driver/js/feedback.js') }}"></script>
@endpush
