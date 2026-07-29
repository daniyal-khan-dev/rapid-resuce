@extends('admin.layouts.admin')
@section('title', 'FAQs')
@section('page_title', 'Content — FAQs')

@section('content')
    <div class="adm-page-header">
        <div>
            <h2>FAQs</h2>
            <p>Manage the frequently asked questions shown on the homepage.</p>
        </div>

        <button class="btn-adm-primary" onclick="openAddModal()">
            <i class="fa fa-plus"></i> Add FAQ
        </button>
    </div>

    <div class="card">
        <div class="pgd-scroll" style="{{ $items->count() ? '' : 'display:none;' }}">
            <table class="table table-hover mb-0" id="faqTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Question</th>
                        <th>Answer Preview</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr class="pgd-row">
                            <td class="ps-4 fs-xs" style="color:var(--adm-muted);">{{ $loop->iteration }}</td>
                            <td><strong>{{ Str::limit($item->question, 60) }}</strong></td>
                            <td class="fs-xs" style="color:var(--adm-muted);max-width:260px;">
                                {{ Str::limit($item->answer, 70) }}
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
                                        onclick="deleteItem({{ $item->id }}, 'FAQ')">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr id="contentNoResults" style="display:none;">
                        <td colspan="6" class="text-center py-5" style="color:var(--adm-muted);">
                            <i class="fa fa-search d-block mb-2 opacity-50"></i>
                            No FAQs match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pgd-footer" style="{{ $items->count() ? '' : 'display:none;' }}">
            <span class="pgd-info" id="faqInfo"></span>
            <div class="pgd-controls">
                <button class="pgd-btn" id="faqPrev">&#8592; Prev</button>
                <span class="pgd-pages" id="faqPages"></span>
                <button class="pgd-btn" id="faqNext">Next &#8594;</button>
            </div>
        </div>
        <div class="adm-empty" style="{{ $items->count() ? 'display:none;' : '' }}">
            <i class="fa fa-circle-question"></i>
            <p>No FAQs yet. Click "Add FAQ" to get started.</p>
        </div>
    </div>

    <div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <span class="modal-title-icon"><i class="fa fa-circle-question"></i></span>
                        Add FAQ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="contentForm">
                        @csrf
                        <input type="hidden" id="item_id" name="item_id">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Question <span class="text-danger">*</span></label>
                                <input type="text" name="svc_question" id="svc_question" class="form-control" placeholder="How do I request an ambulance?" maxlength="400" oninput="allowOnlyLetters(this)">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Answer <span class="text-danger">*</span></label>
                                <textarea name="svc_answer" id="svc_answer" class="form-control" rows="5" placeholder="Detailed answer…" maxlength="2000" oninput="allowAlphaNumericCommaDot(this); updateCharCount(this, 'svcAnswerCount')"></textarea>
                                <div style="text-align:right;font-size:0.74rem;color:var(--adm-muted);margin-top:4px;"><span id="svcAnswerCount">0</span>/2000</div>
                            </div>

                            <div class="col-6">
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
                        <i class="fa fa-check me-1"></i> Save FAQ
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.contentType = 'faq';
        window._rrPageModule = 'faq';
        window.pgdId = 'faq';
        window.contentRoutes = {
            store: window.adminRoutes.faqsStore,
            update: window.adminRoutes.faqsUpdate,
            del: window.adminRoutes.faqsDelete,
        };
        if (window.PGD) {
            PGD.init({
                id: 'faq',
                sel: '#faqTable tbody tr.pgd-row',
                prevId: 'faqPrev',
                nextId: 'faqNext',
                infoId: 'faqInfo',
                pagesId: 'faqPages',
                perPage: 20
            });
        }
    </script>
    <script src="{{ asset('assets/admin/js/content.js') }}"></script>
@endpush
