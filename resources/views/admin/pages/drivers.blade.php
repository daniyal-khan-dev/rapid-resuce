@extends('admin.layouts.admin')
@section('title', 'Drivers')
@section('page_title', 'Driver Management')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Drivers</h2>
            <p>Add, update, or remove driver accounts. Photo is required for all drivers.</p>
        </div>
        <button class="btn-adm-primary" onclick="openAddDriverModal()">
            <i class="fa fa-plus"></i> Add Driver
        </button>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchDrv" placeholder="Search by name, phone or email…" oninput="filterDriverTable()">
        <select class="form-select w-auto" id="filterDrvStatus" onchange="filterDriverTable()">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="2">Inactive</option>
        </select>
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $drivers->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="driverTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Driver</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>License No.</th>
                        <th>Status</th>
                        <th>Availability</th>
                        <th>Jobs Done</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drivers as $d)
                        <tr class="pgd-row" data-id="{{ $d->id }}" data-status="{{ $d->status }}" data-name="{{ strtolower($d->name) }}" data-email="{{ strtolower($d->email) }}" data-phone="{{ $d->phone }}">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="adm-icon-preview"
                                        style="width:36px;height:36px;border-radius:50%;overflow:hidden;font-size:0.85rem;flex-shrink:0;">
                                        @if ($d->photo && $d->photo !== 'default.jpg')
                                            <img src="{{ asset('assets/driver/img/' . $d->photo) }}" alt="{{ $d->name }}" style="width:100%;height:100%;object-fit:cover;">
                                        @else
                                            <i class="fa fa-user"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ $d->name }}</strong>
                                        <div class="fs-xs" style="color:var(--adm-muted);">{{ $d->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                {{ $d->username }}
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $d->phone }}</td>
                            <td class="fs-xs" style="color:var(--adm-muted);">{{ $d->license_no }}</td>
                            <td>
                                @php
                                    $statuses = [
                                        1 => ['class' => 'status-1', 'text' => 'Active'],
                                        2 => ['class' => 'status-4', 'text' => 'Inactive'],
                                    ];
                                    $status = $statuses[$d->status] ?? ['class' => '', 'text' => 'Unknown'];
                                @endphp
                                <span class="status-pill dri-rt-status {{ $status['class'] }}">
                                    {{ $status['text'] }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $availabilities = [
                                        1 => ['class' => 'status-1', 'text' => 'Online'],
                                        2 => ['class' => 'status-2', 'text' => 'On Duty'],
                                        3 => ['class' => 'status-4', 'text' => 'Offline'],
                                    ];
                                    $availability = $availabilities[$d->availability] ?? ['class' => '', 'text' => 'Unknown'];
                                @endphp
                                <span class="status-pill dri-rt-status {{ $availability['class'] }}">
                                    {{ $availability['text'] }}
                                </span>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                <span title="{{ $d->completed_jobs ?? 0 }} completed / {{ $d->total_jobs ?? 0 }} total">
                                    {{ $d->completed_jobs ?? 0 }}
                                    <span style="color:rgba(255,255,255,0.25);">/</span>
                                    {{ $d->total_jobs ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="Edit" onclick="openEditDriverModal({{ $d->id }}, {{ json_encode($d) }})" >
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    <button class="btn-adm-icon btn-adm-icon--danger" title="Delete"
                                        onclick="deleteDriver({{ $d->id }}, {{ json_encode($d->name) }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="driverNoResults" style="display:none;">
                        <td colspan="9" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No drivers match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pgd-footer" style="{{ $drivers->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="drvInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="drvPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="drvPages"></span>
                <button class="pgd-btn" id="drvNext">Next &#8594;</button>
            </div>
        </div>
        <div class="adm-empty" style="{{ $drivers->count() ? 'display:none;' : '' }}">
            <i class="fa fa-id-card"></i>
            <p>No drivers added yet. Click "Add Driver" to get started.</p>
        </div>
    </div>

    {{-- ── Driver Modal ─────────────────────────────────── --}}
    <div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="driverModalTitle">
                        <span class="modal-title-icon"><i class="fa fa-id-card"></i></span>
                        Add Driver
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="driverForm" enctype="multipart/form-data">
                        @csrf
                        <div class="text-center mb-4">
                            <div id="drvPhotoWrap" style="position:relative;display:inline-block;">
                                <div id="drvPhotoPrev" style="width:80px;height:80px;border-radius:50%;overflow:hidden;background:rgba(255,255,255,0.07);border:2px dashed rgba(255,255,255,0.18);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:border-color .2s;" onclick="document.getElementById('drv_photo').click()">
                                    <i class="fa fa-camera" style="font-size:1.4rem;color:rgba(255,255,255,0.35);"></i>
                                </div>
                                <img id="drvPhotoImg" style="display:none;width:80px;height:80px;border-radius:50%;object-fit:cover;cursor:pointer;border:2px solid rgba(255,255,255,0.18);" onclick="document.getElementById('drv_photo').click()" alt="Driver Photo">
                                <button type="button" id="drvPhotoClear" style="display:none;position:absolute;top:-4px;right:-4px;width:20px;height:20px;border-radius:50%;background:var(--adm-red);border:none;color:#fff;font-size:0.65rem;cursor:pointer;line-height:1;"onclick="clearDriverPhoto()">
                                    <i class="fa fa-xmark"></i>
                                </button>
                            </div>
                            <div style="font-size:0.74rem;color:var(--adm-muted);margin-top:8px;" id="drvPhotoHint">
                                Click to upload photo <span class="text-danger" id="drvPhotoRequired">*</span>
                            </div>
                            <input type="file" id="drv_photo" name="photo" accept="image/jpg,image/jpeg,image/png,image/webp" style="display:none;" onchange="previewDriverPhoto(this)">
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" id="drv_name" name="name" class="form-control" placeholder="e.g. Ali Raza" maxlength="50" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.10);border-right:0;color:rgba(255,255,255,0.4);border-radius:14px 0 0 14px;padding:0 12px;font-size:0.9rem;">@</span>
                                    <input type="text" id="drv_username" name="username" class="form-control" placeholder="e.g. ali_driver" maxlength="50" style="border-left:0 !important;border-radius:0 14px 14px 0 !important;" oninput="this.value=this.value.replace(/[^a-z0-9_.]/g,''); checkDriverUsername()">
                                </div>
                                <div style="font-size:0.72rem;color:var(--adm-muted);margin-top:2px;">Allowed: lowercase a–z, 0–9, underscore (_) and dot (.)</div>
                                <div id="driverUsernameFeedback" style="margin-top:4px;min-height:18px;"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" id="drv_email" name="email" class="form-control" placeholder="driver@rapidrescue.com" maxlength="80">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" id="drv_phone" name="phone" class="form-control" placeholder="03XXXXXXXXX" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,11)">
                                <div style="font-size:0.74rem;color:var(--adm-muted);margin-top:4px;">Pakistani format: 03XXXXXXXXX</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">License No. <span class="text-danger">*</span></label>
                                <input type="text" id="drv_license" name="license_no" class="form-control" placeholder="e.g. LHR-2024-001234" maxlength="30">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="drv_status" name="status" class="form-select">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12 form-section-divider">
                                <i class="fa fa-lock"></i> Password
                            </div>

                            <div class="col-12" id="drvPasswordNote" style="display:none;">
                                <div style="font-size:0.8rem;color:var(--adm-muted);background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:10px 14px;">
                                    <i class="fa fa-circle-info me-1"></i>
                                    Leave password blank to keep the current password.
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Password <span class="text-danger" id="drvPwRequired">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="drv_password" name="password" class="form-control" placeholder="Min. 6 characters" style="border-right:0 !important;border-radius:14px 0 0 14px !important;">
                                    <button type="button" class="btn" onclick="togglePwEye('drv_password', this)"
                                        style="border:1px solid rgba(255,255,255,0.10);border-left:0 !important;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);border-radius:0 14px 14px 0;padding:0 14px;">
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
                    <button type="button" class="btn btn-danger" id="driverSubmitBtn" onclick="saveDriver()">
                        <i class="fa fa-check me-1"></i> Save Driver
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
                id:      'drv',
                sel:     '#driverTable tbody tr.pgd-row',
                prevId:  'drvPrev',
                nextId:  'drvNext',
                infoId:  'drvInfo',
                pagesId: 'drvPages',
                perPage: 20,
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/drivers.js') }}"></script>
@endpush
