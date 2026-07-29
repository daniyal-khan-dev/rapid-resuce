@extends('admin.layouts.admin')
@section('title', 'Testimonials')
@section('page_title', 'Content — Testimonials')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Testimonials</h2>
            <p>Manage the testimonials displayed on the public homepage.</p>
        </div>

        <button class="btn-adm-primary" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add Testimonial
        </button>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchContent" placeholder="Search testimonials…" oninput="filterContent()">
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $items->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="tstTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($items as $item)
                        <tr class="pgd-row">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="driver-avatar">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                                    <strong>{{ $item->name }}</strong>
                                </div>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $item->role ?: '—' }}</td>
                            <td>
                                <span class="adm-stars">
                                    @for ($i = 1; $i <= $item->rating; $i++)
                                        <i class="fa fa-star"></i>
                                    @endfor
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $item->status == '2' ? 'status-4' : 'status-1' }}">
                                    @if ($item->status == 1)
                                        Active
                                    @elseif ($item->status == 2)
                                        Inactive
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="Edit"
                                        onclick="openEditModal({{ $item->id }}, {{ json_encode($item) }})">
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    <button class="btn-adm-icon btn-adm-icon--danger" title="Delete"
                                        onclick="deleteItem({{ $item->id }}, 'testimonial')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="contentNoResults" style="display:none;">
                        <td colspan="8" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No testimonials match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pgd-footer" style="{{ $items->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="tstInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="tstPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="tstPages"></span>
                <button class="pgd-btn" id="tstNext">Next &#8594;</button>
            </div>
        </div>
        <div class="adm-empty" style="{{ $items->count() ? 'display:none;' : '' }}">
            <i class="fa fa-star"></i>
            <p>No testimonials yet. Click "Add Testimonial" to get started.</p>
        </div>
    </div>

    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <span class="modal-title-icon"><i class="fa fa-star"></i></span>
                        Add Testimonial
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="contentForm">
                        @csrf
                        <input type="hidden" id="item_id" name="item_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="svc_name" id="svc_name" class="form-control" placeholder="Sara Ahmed" maxlength="30" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Role / Location <span class="text-danger">*</span></label>
                                <input type="text" name="svc_role" id="svc_role" class="form-control" placeholder="Patient · Karachi" maxlength="30" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea name="svc_content" id="svc_content" class="form-control" rows="4" placeholder="What did they say…" maxlength="500" oninput="allowAlphaNumericCommaDot(this); updateCharCount(this, 'svcContentCount')"></textarea>
                                <div style="text-align:right;font-size:0.74rem;color:var(--adm-muted);margin-top:4px;"><span id="svcContentCount">0</span>/500</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rating (1–5) <span class="text-danger">*</span></label>
                                <select name="svc_rating" id="svc_rating" class="form-select">
                                    <option value="0">Select rating</option>
                                    <option value="5">★★★★★ — Excellent</option>
                                    <option value="4">★★★★☆ — Good</option>
                                    <option value="3">★★★☆☆ — Average</option>
                                    <option value="2">★★☆☆☆ — Poor</option>
                                    <option value="1">★☆☆☆☆ — Very Poor</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="svc_status" id="svc_status" class="form-select">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div id="auditTrail">
                        <div class="form-section-divider"><i class="fa fa-clock-rotate-left"></i> Record Info</div>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <div class="audit-label">Added By</div>
                                <div id="auditAddedBy" class="audit-data">—</div>
                            </div>
                            <div class="col-6">
                                <div class="audit-label">Added At</div>
                                <div id="auditAddedAt" class="audit-data">—</div>
                            </div>
                            <div class="col-6 audit-upd" style="display:none;">
                                <div class="audit-label">Updated By</div>
                                <div id="auditUpdatedBy" class="audit-data">—</div>
                            </div>
                            <div class="col-6 audit-upd" style="display:none;">
                                <div class="audit-label">Updated At</div>
                                <div id="auditUpdatedAt" class="audit-data">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="contentSubmitBtn" onclick="saveContent()">
                        <i class="fa fa-check me-1"></i> Save Testimonial
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.contentType = 'testimonial';
        window._rrPageModule = 'testimonial';
        window.pgdId = 'tst';
        window.contentRoutes = {
            store: window.adminRoutes.testimonialsStore,
            update: window.adminRoutes.testimonialsUpdate,
            del: window.adminRoutes.testimonialsDelete,
        };
        if (window.PGD) {
            PGD.init({
                id: 'tst',
                sel: '#tstTable tbody tr.pgd-row',
                prevId: 'tstPrev',
                nextId: 'tstNext',
                infoId: 'tstInfo',
                pagesId: 'tstPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/content.js') }}"></script>
@endpush
