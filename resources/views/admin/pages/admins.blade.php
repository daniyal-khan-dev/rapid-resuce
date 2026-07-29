@extends('admin.layouts.admin')
@section('title', 'Admins')
@section('page_title', 'Admin Management')

@push('styles')
    <style>
        .pw-rule {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.4);
            transition: all .2s;
        }
    </style>
@endpush

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Admins</h2>
            <p>Create, update, and remove admin accounts for the Rapid Rescue panel.</p>
        </div>
        <button class="btn-adm-primary" onclick="openAddAdminModal()">
            <i class="fa fa-plus"></i> Add Admin
        </button>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchAdmin" placeholder="Search name, username or email…" oninput="filterAdminTable()">
        <select class="form-select w-auto" id="filterAdminStatus" onchange="filterAdminTable()">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="2">Inactive</option>
        </select>
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $admins->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="adminTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $adm)
                        <tr class="pgd-row" data-status="{{ $adm->status }}" data-name="{{ strtolower($adm->name) }}"
                            data-username="{{ strtolower($adm->username ?? '') }}"
                            data-email="{{ strtolower($adm->email) }}">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="adm-icon-preview"
                                        style="width:30px;height:30px;border-radius:50%;font-size:0.78rem;">
                                        <i class="fa fa-user-shield"></i>
                                    </div>
                                    <strong>{{ $adm->name }}</strong>
                                    @if ($adm->id === Auth::guard('admin')->id())
                                        <span class="badge rounded-pill"
                                            style="font-size:0.65rem;background:rgba(215,44,66,0.15);color:var(--adm-red);border:1px solid rgba(215,44,66,0.25);">You</span>
                                    @endif
                                </div>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                @if ($adm->username)
                                    {{ $adm->username }}
                                @else
                                    <span style="color:rgba(255,255,255,0.25);font-size:0.78rem;">—</span>
                                @endif
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $adm->email }}</td>
                            <td>
                                <span class="status-pill {{ $adm->status == '2' ? 'status-4' : 'status-1' }}">
                                    @if ($adm->status == 1)
                                        Active
                                    @elseif ($adm->status == 2)
                                        Inactive
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="Edit"
                                        onclick="openEditAdminModal({{ $adm->id }}, {{ json_encode(['name' => $adm->name, 'username' => $adm->username, 'email' => $adm->email, 'status' => $adm->status, 'added_by' => $adm->added_by, 'updated_by' => $adm->updated_by, 'created_at' => $adm->created_at, 'updated_at' => $adm->updated_at]) }})">
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    @if ($adm->id !== Auth::guard('admin')->id())
                                        <button class="btn-adm-icon btn-adm-icon--danger" title="Delete"
                                            onclick="deleteAdmin({{ $adm->id }})">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @else
                                        <button class="btn-adm-icon btn-adm-icon--danger"
                                            title="Cannot delete your own account" disabled
                                            style="opacity:0.3;cursor:not-allowed;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="adminNoResults" style="display:none;">
                        <td colspan="7" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No admins match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pgd-footer" style="{{ $admins->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="admInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="admPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="admPages"></span>
                <button class="pgd-btn" id="admNext">Next &#8594;</button>
            </div>
        </div>

        <div class="adm-empty" style="{{ $admins->count() ? 'display:none;' : '' }}">
            <i class="fa fa-user-shield"></i>
            <p>No admins found. Click "Add Admin" to create one.</p>
        </div>
    </div>

    <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalTitle">
                        <span class="modal-title-icon"><i class="fa fa-user-shield"></i></span>
                        Add Admin
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="adminForm">
                        @csrf
                        <input type="hidden" id="admin_id" name="admin_id">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="admin_name" id="admin_name" class="form-control"
                                    placeholder="e.g. John Smith" maxlength="20" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"
                                        style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);border-right:0;color:rgba(255,255,255,0.4);border-radius:14px 0 0 14px;padding:0 12px;font-size:0.9rem;">@</span>
                                    <input type="text" name="admin_username" id="admin_username" class="form-control"
                                        placeholder="e.g. john_admin" maxlength="30"
                                        style="border-left:0 !important;border-radius:0 14px 14px 0 !important;"
                                        oninput="this.value=this.value.replace(/[^a-z0-9_.]/g,''); checkAdminUsername()">
                                </div>
                                <div id="adminUsernameFeedback" style="margin-top:4px;min-height:18px;"></div>
                                <div style="font-size:0.72rem;color:var(--adm-muted);margin-top:2px;">Allowed: lowercase
                                    a–z, 0–9, underscore (_) and dot (.)</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" id="admin_email" class="form-control"
                                    maxlength="30" placeholder="admin@rapidrescue.com">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="admin_status" id="admin_status" class="form-select">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12 form-section-divider">
                                <i class="fa fa-lock"></i> Password
                            </div>

                            <div class="col-12" id="adminPasswordNote" style="display:none;">
                                <div
                                    style="font-size:0.8rem;color:var(--adm-muted);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:10px 14px;">
                                    <i class="fa fa-circle-info me-1"></i>
                                    Leave password fields blank to keep the current password.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Password
                                    <span class="text-danger" id="pwRequired">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" name="admin_password" id="admin_password"
                                        class="form-control" placeholder="e.g. Secret@7" minlength="7"
                                        oninput="validateAdminPassword(this)"
                                        style="border-right: 0 !important; border-radius: 14px 0 0 14px !important;">
                                    <button type="button" class="btn" onclick="togglePwEye('admin_password',this)"
                                        style="border:1px solid rgba(255,255,255,0.10);border-left: 0 !important;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);border-radius:0 14px 14px 0;padding:0 14px;">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <div id="adminPwStrength" style="margin-top:8px;display:none;">
                                    <div style="display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                                        <span class="pw-rule" id="pw-len">
                                            <i class="fa fa-circle-xmark me-1"></i>7+ chars
                                        </span>
                                        <span class="pw-rule" id="pw-alpha">
                                            <i class="fa fa-circle-xmark me-1"></i>Letter
                                        </span>
                                        <span class="pw-rule" id="pw-num">
                                            <i class="fa fa-circle-xmark me-1"></i>Number
                                        </span>
                                        <span class="pw-rule" id="pw-spec">
                                            <i class="fa fa-circle-xmark me-1"></i>Special
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" class="form-control" placeholder="Repeat password" style="border-right: 0 !important; border-radius: 14px 0 0 14px !important;">
                                    <button type="button" class="btn" onclick="togglePwEye('admin_password_confirmation',this)" style="border:1px solid rgba(255,255,255,0.10);border-left: 0 !important;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);border-radius:0 14px 14px 0;padding:0 14px;">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
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
                    <button type="button" class="btn btn-danger" id="adminSubmitBtn" onclick="saveAdmin()">
                        <i class="fa fa-check me-1"></i> Save Admin
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        if (window.PGD) {
            PGD.init({
                id: 'adm',
                sel: '#adminTable tbody tr.pgd-row',
                prevId: 'admPrev',
                nextId: 'admNext',
                infoId: 'admInfo',
                pagesId: 'admPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/admins.js') }}"></script>
@endpush
