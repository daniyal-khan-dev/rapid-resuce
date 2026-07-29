@extends('admin.layouts.admin')
@section('title', 'Branches')
@section('page_title', 'Branches')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Branches</h2>
            <p>Manage the branch addresses shown on the homepage.</p>
        </div>

        <button class="btn-adm-primary" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add Branch
        </button>
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $items->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="branchTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Branch Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $branch)
                        <tr class="pgd-row">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="adm-icon-preview"
                                        style="width:30px;height:30px;border-radius:50%;font-size:0.75rem;flex-shrink:0;">
                                        <i class="fa fa-building"></i>
                                    </div>
                                    <strong>{{ $branch->name }}</strong>
                                </div>
                            </td>
                            <td style="color:var(--adm-muted);font-size:0.85rem;max-width:280px;">
                                {{ Str::limit($branch->address, 20) }}
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $branch->phone }}</td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $branch->email }}</td>
                            <td>
                                <span class="status-pill {{ $branch->status == '2' ? 'status-4' : 'status-1' }}">
                                    @if ($branch->status == 1)
                                        Active
                                    @elseif ($branch->status == 2)
                                        Inactive
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="Edit"
                                        onclick="openEditModal({{ $branch->id }}, {{ json_encode($branch) }})">
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    <button class="btn-adm-icon btn-adm-icon--danger" title="Delete"
                                        onclick="deleteItem({{ $branch->id }}, 'BRANCHES')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pgd-footer" style="{{ $items->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="branchInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="branchPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="branchPages"></span>
                <button class="pgd-btn" id="branchNext">Next &#8594;</button>
            </div>
        </div>
            
        <div class="adm-empty" style="{{ $items->count() ? 'display:none;' : '' }}">
            <i class="fa fa-building"></i>
            <p>No branches yet. Click "Add Branches" to get started.</p>
        </div>
    </div>

    <div class="modal fade" id="contentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <span class="modal-title-icon"><i class="fa fa-building"></i></span>
                        Add Branch
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="contentForm">
                        @csrf
                        <input type="hidden" id="item_id" name="item_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" id="svc_branch_name" name="svc_branch_name" class="form-control" placeholder="e.g. Main Branch" maxlength="30" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Address <span class="text-danger">*</span></label>
                                <input type="text" id="svc_branch_address" name="svc_branch_address" class="form-control" placeholder="Full address" maxlength="100" oninput="allowAlphaNumericCommaDot(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" id="svc_branch_phone" name="svc_branch_phone" class="form-control" placeholder="e.g. +92 300 1234567" maxlength="13" oninput="validatePakPhone(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" id="svc_branch_email" name="svc_branch_email" class="form-control" placeholder="e.g. branch@example.com" maxlength="100">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="svc_bstatus" id="svc_bstatus" class="form-select">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inctive</option>
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
                        <i class="fa fa-check me-1"></i> Save Branch
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.contentType = 'branch';
        window._rrPageModule = 'branch';
        window.pgdId = 'branch';
        window.contentRoutes = {
            store: window.adminRoutes.branchStore,
            update: window.adminRoutes.branchUpdate,
            del: window.adminRoutes.branchDelete,
        };
        if (window.PGD) {
            PGD.init({
                id: 'branch',
                sel: '#branchTable tbody tr.pgd-row',
                prevId: 'branchPrev',
                nextId: 'branchNext',
                infoId: 'branchInfo',
                pagesId: 'branchPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/content.js') }}"></script>
@endpush
