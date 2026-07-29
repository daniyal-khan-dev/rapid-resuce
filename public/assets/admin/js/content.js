/* Content Management (Services / Testimonials / FAQs) */
function filterContent() {
    var q = (document.getElementById('searchContent') || {}).value;
    var noResults = document.getElementById('contentNoResults');
    if (!q) {
        if (noResults) noResults.style.display = 'none';
        if (window.PGD && window.pgdId) PGD.applyFilter(window.pgdId, null);
        return;
    }
    q = q.toLowerCase();
    var matched = [];
    document.querySelectorAll('table tbody tr.pgd-row').forEach(function(row) {
        if (row.textContent.toLowerCase().includes(q)) matched.push(row);
    });
    if (noResults) noResults.style.display = matched.length === 0 ? 'table-row' : 'none';
    if (window.PGD && window.pgdId) PGD.applyFilter(window.pgdId, matched);
}

function updateIconPreview(val) {
    const preview = document.getElementById('iconPreview');
    if (!preview) return;
    preview.innerHTML = '<i class="' + (val || 'fas fa-cogs') + '"></i>';
}

function _auditFmt(s) {
    if (!s) return '—';

    const d = new Date(s);
    if (isNaN(d.getTime())) return s;

    const date = d.toLocaleDateString('en-GB', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });

    const time = d.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    return `${date} at ${time}`;
}

function _auditShow(data) {
    var panel = document.getElementById('auditTrail');
    if (!panel) return;
    panel.style.display = 'block';
    document.getElementById('auditAddedBy').textContent = data.added_by || '—';
    document.getElementById('auditAddedAt').textContent = _auditFmt(data.created_at);
    var hasUpd = !!(data.updated_by);
    document.querySelectorAll('#auditTrail .audit-upd').forEach(function (el) {
        el.style.display = hasUpd ? '' : 'none';
    });
    if (hasUpd) {
        document.getElementById('auditUpdatedBy').textContent = data.updated_by;
        document.getElementById('auditUpdatedAt').textContent = _auditFmt(data.updated_at);
    }
}

function _auditHide() {
    var panel = document.getElementById('auditTrail');
    if (panel) panel.style.display = 'none';
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add ' + formatType(window.contentType);
    document.getElementById('contentForm').reset();
    document.getElementById('item_id').value = '';
    const ip = document.getElementById('iconPreview');
    if (ip) ip.innerHTML = '<i class="fas fa-cogs"></i>';
    var cc;
    cc = document.getElementById('svcDescCount');    if (cc) cc.textContent = '0';
    cc = document.getElementById('svcContentCount'); if (cc) cc.textContent = '0';
    cc = document.getElementById('svcAnswerCount');  if (cc) cc.textContent = '0';
    const btn = document.getElementById('contentSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save ' + formatType(window.contentType); }
    _auditHide();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('contentModal')).show();
}

function openEditModal(id, data) {
    document.getElementById('modalTitle').textContent = 'Edit ' + formatType(window.contentType);
    document.getElementById('item_id').value = id;

    setIfExists('svc_icon',           data.icon        || '');
    setIfExists('svc_title',          data.title       || '');
    setIfExists('svc_description',    data.description || '');
    setIfExists('svc_name',           data.name        || '');
    setIfExists('svc_role',           data.role        || '');
    setIfExists('svc_content',        data.content     || '');
    setIfExists('svc_rating',         data.rating !== undefined ? String(data.rating) : '5');
    setIfExists('svc_question',       data.question    || '');
    setIfExists('svc_answer',         data.answer      || '');
    setIfExists('svc_sort_order',     data.sort_order !== undefined ? data.sort_order : 0);
    setIfExists('svc_status',         data.status !== undefined ? String(data.status) : '1');
    setIfExists('svc_branch_name',    data.name        || '');
    setIfExists('svc_branch_address', data.address        || '');
    setIfExists('svc_branch_phone',   data.phone        || '');
    setIfExists('svc_branch_email',   data.email        || '');
    setIfExists('svc_bstatus',        data.status !== undefined ? String(data.status) : '1');

    if (data.icon) updateIconPreview(data.icon);

    var cc;
    cc = document.getElementById('svcDescCount');    if (cc) cc.textContent = (data.description || '').length;
    cc = document.getElementById('svcContentCount'); if (cc) cc.textContent = (data.content || '').length;
    cc = document.getElementById('svcAnswerCount');  if (cc) cc.textContent = (data.answer || '').length;

    const btn = document.getElementById('contentSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save ' + formatType(window.contentType); }
    _auditShow(data);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('contentModal')).show();
}

function setIfExists(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}

function formatType(t) {
    const map = { service: 'Service', testimonial: 'Testimonial', faq: 'FAQ' };
    return map[t] || t;
}

function deleteItem(id, label) {
    confirmAction('Are you sure you want to delete this ' + label + '? This action cannot be undone.', function () {
        fetch(window.contentRoutes.del + '/' + id, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('success', label.charAt(0).toUpperCase() + label.slice(1) + ' deleted successfully.');
            } else {
                showAlert('error', data.message || 'Delete failed.');
            }
        })
        .catch(function () { showAlert('error', 'Server error. Please try again.'); });
    });
}

function saveContent() {
    const id     = document.getElementById('item_id').value;
    const isEdit = !!id;
    const url    = isEdit ? window.contentRoutes.update + '/' + id : window.contentRoutes.store;

    const fieldsByType = {
        service: [
            { id: 'svc_icon',        message: 'Icon class is required.',         maxLength: 30 },
            { id: 'svc_title',       message: 'Title is required.',              maxLength: 50 },
            { id: 'svc_description', message: 'Description is required.',        maxLength: 500 },
            { id: 'svc_status',      message: 'Please select a status.',         skipIf: '0' },
        ],
        testimonial: [
            { id: 'svc_name',    message: 'Name is required.',                   maxLength: 30 },
            { id: 'svc_role',    message: 'Role/Location is required.',          maxLength: 30 },
            { id: 'svc_content', message: 'Testimonial content is required.',    maxLength: 500 },
            { id: 'svc_rating',  message: 'Please select a rating.',             skipIf: '0' },
            { id: 'svc_status',  message: 'Please select a status.',             skipIf: '0' },
        ],
        faq: [
            { id: 'svc_question', message: 'Question is required.', maxLength: 400 },
            { id: 'svc_answer',   message: 'Answer is required.',   maxLength: 2000 },
            { id: 'svc_status',   message: 'Please select a status.', skipIf: '0' },
        ],
        branch: [
            { id: 'svc_branch_name', message: 'Branch Name is required.', maxLength: 30 },
            { id: 'svc_branch_address',   message: 'Address is required.',   maxLength: 100 },
            { id: 'svc_branch_phone',   message: 'Phone Number is required.',   maxLength: 13 },
            { id: 'svc_branch_email',   message: 'Eamil is required.',   maxLength: 100 },
            { id: 'svc_bstatus',   message: 'Please select a status.', skipIf: '0' },
        ],
    };

    const label = formatType(window.contentType);

    validateForm({
        formId: 'contentForm',
        fields: fieldsByType[window.contentType] || [],
        btn:    'contentSubmitBtn',
        onSuccess: function () {
            submitFormData({
                formId: 'contentForm',
                url: url,
                successMessage: isEdit ? label + ' updated successfully.' : label + ' added successfully.',
                onSuccess: function () {
                    bootstrap.Modal.getInstance(document.getElementById('contentModal'))?.hide();
                },
                onError: function () {
                    const btn = document.getElementById('contentSubmitBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save ' + label; }
                },
            });
        },
    });
}

/* ── Real-time DOM updates via existing admin-dashboard channel ─────────── */
(function () {
    'use strict';

    /* Map server entity names → _rrPageModule values */
    var _entityModule = {
        service:     'service',
        testimonial: 'testimonial',
        faq:         'faq',
        branches:    'branch',
    };

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    function _esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function _pill(status) {
        var cls = status == 2 ? 'status-4' : 'status-1';
        var lbl = status == 1 ? 'Active' : 'Inactive';
        return '<span class="status-pill ' + cls + '">' + lbl + '</span>';
    }

    function _stars(rating) {
        var s = '';
        for (var i = 1; i <= rating; i++) s += '<i class="fa fa-star"></i>';
        return '<span class="adm-stars">' + s + '</span>';
    }

    function _trunc(str, len) {
        str = String(str == null ? '' : str);
        return str.length > len ? str.substring(0, len) + '…' : str;
    }

    /* ── Tag existing Blade-rendered rows with data-id ───────────────────── */
    function _tagExistingRows() {
        document.querySelectorAll('table tbody tr.pgd-row').forEach(function (row) {
            if (row.hasAttribute('data-id')) return;
            var editBtn = row.querySelector('.btn-adm-icon--edit');
            if (!editBtn) return;
            var match = (editBtn.getAttribute('onclick') || '').match(/openEditModal\(\s*(\d+)/);
            if (match) row.setAttribute('data-id', match[1]);
        });
    }

    /* ── Find tbody for current page ─────────────────────────────────────── */
    var _tbodyMap = {
        service:     '#svcTable tbody',
        faq:         '#faqTable tbody',
        testimonial: '#tstTable tbody',
        branch:      '#branchTable tbody',
    };

    function _tbody() {
        var sel = _tbodyMap[window._rrPageModule];
        return sel ? document.querySelector(sel) : null;
    }

    /* ── Renumber the # column for all pgd-rows ──────────────────────────── */
    function _renumber() {
        document.querySelectorAll('table tbody tr.pgd-row').forEach(function (row, i) {
            var cell = row.cells[0];
            if (cell) cell.textContent = i + 1;
        });
    }

    /* ── Show/hide empty-state and table wrapper ─────────────────────────── */
    function _syncEmpty() {
        var hasRows = document.querySelectorAll('table tbody tr.pgd-row').length > 0;
        var scroll = document.querySelector('.pgd-scroll');
        var footer = document.querySelector('.pgd-footer');
        var empty  = document.querySelector('.adm-empty');
        if (scroll) scroll.style.display = hasRows ? '' : 'none';
        if (footer) footer.style.display = hasRows ? '' : 'none';
        if (empty)  empty.style.display  = hasRows ? 'none' : '';
    }

    /* ── Refresh pagination (resets to page 1) ───────────────────────────── */
    function _repaginate() {
        if (window.PGD && window.pgdId) PGD.applyFilter(window.pgdId, null);
    }

    /* ── Build a new <tr> for a given entity ─────────────────────────────── */
    function _buildRow(d) {
        var module = window._rrPageModule;
        var tr = document.createElement('tr');
        tr.className = 'pgd-row';
        tr.setAttribute('data-id', d.id);

        if (module === 'service') {
            tr.innerHTML =
                '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
                '<td><div class="adm-icon-preview"><i class="' + _esc(d.icon) + '"></i></div></td>' +
                '<td><strong>' + _esc(d.title) + '</strong></td>' +
                '<td>' + _pill(d.status) + '</td>' +
                '<td><div class="d-flex gap-1">' +
                    '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                    '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
                '</div></td>';
            tr.querySelector('.btn-adm-icon--edit').onclick   = function () { openEditModal(d.id, d); };
            tr.querySelector('.btn-adm-icon--danger').onclick = function () { deleteItem(d.id, 'service'); };

        } else if (module === 'faq') {
            tr.innerHTML =
                '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
                '<td><strong>' + _esc(_trunc(d.question, 60)) + '</strong></td>' +
                '<td class="fs-xs" style="color:var(--adm-muted);max-width:260px;">' + _esc(_trunc(d.answer, 70)) + '</td>' +
                '<td>' + _pill(d.status) + '</td>' +
                '<td><div class="d-flex gap-1">' +
                    '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                    '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
                '</div></td>';
            tr.querySelector('.btn-adm-icon--edit').onclick   = function () { openEditModal(d.id, d); };
            tr.querySelector('.btn-adm-icon--danger').onclick = function () { deleteItem(d.id, 'FAQ'); };

        } else if (module === 'testimonial') {
            var initial = d.name ? d.name.charAt(0).toUpperCase() : '?';
            tr.innerHTML =
                '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
                '<td><div class="d-flex align-items-center gap-2">' +
                    '<div class="driver-avatar">' + _esc(initial) + '</div>' +
                    '<strong>' + _esc(d.name) + '</strong>' +
                '</div></td>' +
                '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.role || '—') + '</td>' +
                '<td>' + _stars(d.rating) + '</td>' +
                '<td>' + _pill(d.status) + '</td>' +
                '<td><div class="d-flex gap-1">' +
                    '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                    '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
                '</div></td>';
            tr.querySelector('.btn-adm-icon--edit').onclick   = function () { openEditModal(d.id, d); };
            tr.querySelector('.btn-adm-icon--danger').onclick = function () { deleteItem(d.id, 'testimonial'); };

        } else if (module === 'branch') {
            tr.innerHTML =
                '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
                '<td><div class="d-flex align-items-center gap-2">' +
                    '<div class="adm-icon-preview" style="width:30px;height:30px;border-radius:50%;font-size:0.75rem;flex-shrink:0;">' +
                        '<i class="fa fa-building"></i>' +
                    '</div>' +
                    '<strong>' + _esc(d.name) + '</strong>' +
                '</div></td>' +
                '<td style="color:var(--adm-muted);font-size:0.85rem;max-width:280px;">' + _esc(_trunc(d.address, 20)) + '</td>' +
                '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.phone) + '</td>' +
                '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.email) + '</td>' +
                '<td>' + _pill(d.status) + '</td>' +
                '<td><div class="d-flex gap-1">' +
                    '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                    '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
                '</div></td>';
            tr.querySelector('.btn-adm-icon--edit').onclick   = function () { openEditModal(d.id, d); };
            tr.querySelector('.btn-adm-icon--danger').onclick = function () { deleteItem(d.id, 'BRANCHES'); };
        }

        return tr;
    }

    /* ── CRUD handlers ───────────────────────────────────────────────────── */
    function onCreated(d) {
        var tbody = _tbody();
        if (!tbody) return;
        var tr = _buildRow(d);
        var noResults = tbody.querySelector('#contentNoResults');
        if (noResults) tbody.insertBefore(tr, noResults);
        else tbody.appendChild(tr);
        _renumber();
        _syncEmpty();
        _repaginate();
    }

    function onUpdated(d) {
        var existing = document.querySelector('table tbody tr.pgd-row[data-id="' + d.id + '"]');
        if (!existing) { onCreated(d); return; }

        var module = window._rrPageModule;

        if (module === 'service') {
            existing.cells[1].innerHTML = '<div class="adm-icon-preview"><i class="' + _esc(d.icon) + '"></i></div>';
            existing.cells[2].innerHTML = '<strong>' + _esc(d.title) + '</strong>';
            existing.cells[3].innerHTML = _pill(d.status);
        } else if (module === 'faq') {
            existing.cells[1].innerHTML = '<strong>' + _esc(_trunc(d.question, 60)) + '</strong>';
            existing.cells[2].textContent = _trunc(d.answer, 70);
            existing.cells[3].innerHTML = _pill(d.status);
        } else if (module === 'testimonial') {
            var initial = d.name ? d.name.charAt(0).toUpperCase() : '?';
            existing.cells[1].innerHTML =
                '<div class="d-flex align-items-center gap-2">' +
                '<div class="driver-avatar">' + _esc(initial) + '</div>' +
                '<strong>' + _esc(d.name) + '</strong></div>';
            existing.cells[2].textContent = d.role || '—';
            existing.cells[3].innerHTML   = _stars(d.rating);
            existing.cells[4].innerHTML   = _pill(d.status);
        } else if (module === 'branch') {
            existing.cells[1].innerHTML =
                '<div class="d-flex align-items-center gap-2">' +
                '<div class="adm-icon-preview" style="width:30px;height:30px;border-radius:50%;font-size:0.75rem;flex-shrink:0;">' +
                '<i class="fa fa-building"></i></div>' +
                '<strong>' + _esc(d.name) + '</strong></div>';
            existing.cells[2].textContent = _trunc(d.address, 20);
            existing.cells[3].textContent = d.phone;
            existing.cells[4].textContent = d.email;
            existing.cells[5].innerHTML   = _pill(d.status);
        }

        /* Refresh edit button closure with fresh data */
        var editBtn = existing.querySelector('.btn-adm-icon--edit');
        if (editBtn) editBtn.onclick = function () { openEditModal(d.id, d); };
    }

    function onDeleted(id) {
        var existing = document.querySelector('table tbody tr.pgd-row[data-id="' + id + '"]');
        if (!existing) return;
        existing.parentNode.removeChild(existing);
        _renumber();
        _syncEmpty();
        _repaginate();
    }

    /* ── Bootstrap ───────────────────────────────────────────────────────── */
    /* Only activate if we're on one of the four content pages */
    if (!window._rrPageModule || !_entityModule[window._rrPageModule] && window._rrPageModule !== 'branch') return;

    /* Tag existing Blade-rendered rows so we can find them by data-id */
    _tagExistingRows();

    /* Bind once to the shared channel — guard against duplicate registration */
    if (!window._rrContentBound && window.channel) {
        window._rrContentBound = true;
        window.channel.bind('admin-content-updated', function (event) {
            var module = _entityModule[event.entity] || event.entity;
            if (module !== window._rrPageModule) return;
            if (event.action === 'created')      onCreated(event.data);
            else if (event.action === 'updated') onUpdated(event.data);
            else if (event.action === 'deleted') onDeleted(event.data.id);
        });
    }
})();
