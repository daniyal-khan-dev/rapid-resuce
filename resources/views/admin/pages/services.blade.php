@extends('admin.layouts.admin')
@section('title', 'Services')
@section('page_title', 'Content — Services')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Services</h2>
            <p>Manage the services listed on the public homepage.</p>
        </div>

        <button class="btn-adm-primary" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add Service
        </button>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchContent" placeholder="Search services…" oninput="filterContent()">
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $items->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="svcTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Icon</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr class="pgd-row">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="adm-icon-preview"><i class="{{ $item->icon }}"></i></div>
                            </td>
                            <td><strong>{{ $item->title }}</strong></td>
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
                                        onclick="deleteItem({{ $item->id }}, 'service')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="contentNoResults" style="display:none;">
                        <td colspan="7" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No services match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pgd-footer" style="{{ $items->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="svcInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="svcPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="svcPages"></span>
                <button class="pgd-btn" id="svcNext">Next &#8594;</button>
            </div>
        </div>
        <div class="adm-empty" style="{{ $items->count() ? 'display:none;' : '' }}">
            <i class="fa fa-briefcase-medical"></i>
            <p>No services yet. Click "Add Service" to get started.</p>
        </div>
    </div>

    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <span class="modal-title-icon"><i class="fa fa-briefcase-medical"></i></span>
                        Add Service
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="contentForm">
                        @csrf
                        <input type="hidden" id="item_id" name="item_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Font Awesome Icon Class <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="adm-icon-preview" id="iconPreview"><i class="fas fa-cogs"></i></div>
                                    <input type="text" name="svc_icon" id="svc_icon" class="form-control" maxlength="30" placeholder="fas fa-phone-alt" oninput="updateIconPreview(this.value)">
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="svc_title" id="svc_title" class="form-control" placeholder="Emergency Hotline" maxlength="50" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="svc_description" id="svc_description" class="form-control" maxlength="500" rows="3" placeholder="Short description of this service…" oninput="allowAlphaNumericCommaDot(this); updateCharCount(this, 'svcDescCount')"></textarea>
                                <div style="text-align:right;font-size:0.74rem;color:var(--adm-muted);margin-top:4px;"><span id="svcDescCount">0</span>/500</div>
                            </div>

                            <div class="col-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="svc_status" id="svc_status" class="form-select">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inacive</option>
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
                        <i class="fa fa-check me-1"></i> Save Service
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.contentType = 'service';
        window._rrPageModule = 'service';
        window.pgdId = 'svc';
        window.contentRoutes = {
            store: window.adminRoutes.servicesStore,
            update: window.adminRoutes.servicesUpdate,
            del: window.adminRoutes.servicesDelete,
        };
        if (window.PGD) {
            PGD.init({
                id: 'svc',
                sel: '#svcTable tbody tr.pgd-row',
                prevId: 'svcPrev',
                nextId: 'svcNext',
                infoId: 'svcInfo',
                pagesId: 'svcPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/content.js') }}"></script>
@endpush
