@extends('admin.layouts.admin')
@section('title', 'Ambulances')
@section('page_title', 'Ambulance Management')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>Ambulances</h2>
            <p>Manage the Rapid Rescue fleet — including card info shown on the public site.</p>
        </div>

        <button class="btn-adm-primary" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add Ambulance
        </button>
    </div>

    <div class="adm-filter-row">
        <input type="text" class="form-control w-auto" id="searchAmb" placeholder="Search vehicle number…" oninput="filterTable()">
        <select class="form-select w-auto" id="filterStatus" onchange="filterTable()">
            <option value="">All Statuses</option>
            <option value="1">Available</option>
            <option value="2">On Call</option>
            <option value="3">Maintenance</option>
            <option value="4">Inactive</option>
        </select>
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $ambulances->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="ambTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Vehicle No.</th>
                        <th>Type</th>
                        <th>Equipment</th>
                        <th>Status</th>
                        <th>Assigned Driver</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ambulances as $amb)
                        <tr class="pgd-row" data-ambulance-id="{{ $amb->id }}" data-driver-id="{{ $amb->driver_id ?? '' }}" data-status="{{ $amb->status }}" data-vehicle="{{ strtolower($amb->vehicle_number) }}">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="adm-icon-preview" style="width:30px;height:30px;border-radius:7px;font-size:0.78rem;">
                                        <i class="fa fa-ambulance"></i>
                                    </div>
                                    <strong>{{ $amb->vehicle_number }}</strong>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill badge-type">
                                    @if ($amb->type == 1) BLS
                                    @elseif ($amb->type == 2) ALS
                                    @elseif ($amb->type == 3) CCT
                                    @elseif ($amb->type == 4) Neonatal
                                    @elseif ($amb->type == 5) AIR
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $amb->equipment_level == 2 ? 'badge-advanced' : 'badge-basic' }}">
                                    @if ($amb->equipment_level == 1) Basic
                                    @elseif ($amb->equipment_level == 2) Advanced
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="status-pill status-{{ $amb->status }}">
                                    @if ($amb->status == 1) Available
                                    @elseif ($amb->status == 2) On Job
                                    @elseif ($amb->status == 3) Maintenance
                                    @elseif ($amb->status == 4) Inactive
                                    @endif
                                </span>
                            </td>
                            <td class="fs-xs" style="color:var(--adm-muted);">
                                @if ($amb->driver)
                                    <span style="font-family:monospace;background:rgba(255,255,255,0.06);padding:2px 8px;border-radius:6px;font-size:0.82rem;">{{ $amb->driver->name }}</span>
                                @else
                                    <span style="color:rgba(255,255,255,0.25);">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button class="btn-adm-icon btn-adm-icon--edit" title="Edit"
                                        onclick="openEditModal({{ $amb->id }}, {{ json_encode($amb) }})">
                                        <i class="fa fa-pen"></i>
                                    </button>

                                    <button class="btn-adm-icon btn-adm-icon--danger" title="Delete"
                                        onclick="deleteAmbulance({{ $amb->id }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="ambNoResults" style="display:none;">
                        <td colspan="7" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No ambulances match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pgd-footer" style="{{ $ambulances->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="ambInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="ambPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="ambPages"></span>
                <button class="pgd-btn" id="ambNext">Next &#8594;</button>
            </div>
        </div>

        <div class="adm-empty" style="{{ $ambulances->count() ? 'display:none;' : '' }}">
            <i class="fa fa-ambulance"></i>
            <p>No ambulances added yet. Click "Add Ambulance" to get started.</p>
        </div>
    </div>

    {{-- Add / Edit Modal --}}
    <div class="modal fade" id="ambModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <span class="modal-title-icon"><i class="fa fa-ambulance"></i></span>
                        Add Ambulance
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="ambForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="amb_id" name="amb_id">

                        <div class="row g-3">
                            {{-- Card image --}}
                            <div class="col-12">
                                <label class="form-label">
                                    Card Image
                                    <span class="s-badge">(JPG/PNG/WebP · max 2 MB)</span>
                                </label>
                                <div class="adm-fleet-img-preview" id="ambImgPreview">
                                    <i class="fa fa-image fa-2x" style="opacity:0.2;"></i>
                                </div>
                                <input type="file" name="card_image" id="card_image" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp" onchange="previewAmbImage(this)">
                            </div>

                            {{-- Vehicle number --}}
                            <div class="col-md-6">
                                <label class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                                <input type="text" name="vehicle_number" id="vehicle_number" class="form-control" placeholder="e.g. RR-001" maxlength="20">
                            </div>

                            {{-- Type --}}
                            <div class="col-md-6">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select">
                                    <option value="0">Select type</option>
                                    <option value="1">BLS — Basic Life Support</option>
                                    <option value="2">ALS — Advanced Life Support</option>
                                    <option value="3">CCT — Critical Care Transport</option>
                                    <option value="4">Neonatal Transport</option>
                                    <option value="5">Air Ambulance</option>
                                </select>
                            </div>

                            {{-- Equipment level --}}
                            <div class="col-md-6">
                                <label class="form-label">Equipment Level <span class="text-danger">*</span></label>
                                <select name="equipment_level" id="equipment_level" class="form-select">
                                    <option value="0">Select level</option>
                                    <option value="1">Basic</option>
                                    <option value="2">Advanced</option>
                                </select>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" id="amb_status" class="form-select">
                                    <option value="0">Select status</option>
                                    <option value="1">Available</option>
                                    <option value="2">On Job</option>
                                    <option value="3">Maintenance</option>
                                    <option value="4">Inactive</option>
                                </select>
                            </div>

                            {{-- Assigned Driver --}}
                            <div class="col-md-12">
                                <label class="form-label">Assigned Driver
                                    <span style="color:var(--adm-muted);font-weight:500;text-transform:none;letter-spacing:0;">(active drivers only)</span>
                                </label>
                                <select name="driver_id" id="amb_driver_id" class="form-select">
                                    <option value="">No driver assigned</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }} — {{ $driver->phone }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Notes --}}
                            <div class="col-12">
                                <label class="form-label">Notes
                                    <span class="s-badge">(optional)</span>
                                </label>
                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any relevant notes…" maxlength="500" style="resize: none; height: 15vh;" oninput="allowAlphaNumericCommaDot(this); updateCharCount(this, 'notesCount')"></textarea>
                                <div style="text-align:right;font-size:0.74rem;color:var(--adm-muted);margin-top:4px;"><span id="notesCount">0</span>/500</div>
                            </div>

                            {{-- Fleet card section --}}
                            <div class="col-12 form-section-divider">
                                <i class="fa fa-credit-card"></i> Public Fleet Card Info
                            </div>

                            {{-- Card Title --}}
                            <div class="col-md-6">
                                <label class="form-label">Card Title
                                    <span class="text-danger">*</span>
                                    <span class="s-badge">(shown on site)</span>
                                </label>
                                <input type="text" name="card_title" id="card_title" class="form-control" placeholder="e.g. BLS Unit" maxlength="50" oninput="allowOnlyLetters(this)">
                            </div>

                            {{-- Rating --}}
                            <div class="col-md-6">
                                <label class="form-label">Rating
                                    <span class="text-danger">*</span>
                                    <span class="s-badge">(0–5)</span>
                                </label>
                                <input type="number" name="card_rating" id="card_rating" class="form-control" placeholder="4.5" min="0" max="5" step="0.1" oninput="limitRating(this)">
                            </div>

                            {{-- Card Description --}}
                            <div class="col-12">
                                <label class="form-label">Card Description
                                    <span class="text-danger">*</span>
                                    <span class="s-badge">(shown on site)</span>
                                </label>
                                <textarea name="card_description" id="card_description" class="form-control" rows="2" placeholder="Short description of this unit…" maxlength="500" oninput="allowAlphaNumericCommaDot(this); updateCharCount(this, 'cardDescCount')"></textarea>
                                <div style="text-align:right;font-size:0.74rem;color:var(--adm-muted);margin-top:4px;"><span id="cardDescCount">0</span>/500</div>
                            </div>

                            {{-- Features --}}
                            <div class="col-md-6">
                                <label class="form-label">Features
                                    <span class="text-danger">*</span>
                                    <span class="s-badge">(comma-separated)</span>
                                </label>
                                <input type="text" name="card_features" id="card_features" class="form-control" placeholder="Oxygen, Stretcher, First Aid" maxlength="50" oninput="allowAlphaNumericCommaDot(this)">
                            </div>

                            {{-- Trip Count --}}
                            <div class="col-md-6">
                                <label class="form-label">Trip Count</label>
                                <input type="number" name="card_trips" id="card_trips" class="form-control" placeholder="214" min="0">
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
                    <button type="button" class="btn btn-danger" id="ambSubmitBtn" onclick="saveAmbulance()">
                        <i class="fa fa-check me-1"></i> Save Ambulance
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window._rrPageModule = 'ambulance';
        if (window.PGD) {
            PGD.init({
                id: 'amb',
                sel: '#ambTable tbody tr.pgd-row',
                prevId: 'ambPrev',
                nextId: 'ambNext',
                infoId: 'ambInfo',
                pagesId: 'ambPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/ambulances.js') }}"></script>
@endpush
