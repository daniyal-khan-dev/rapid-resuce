var _adminUsernameTimer = null;
var _adminUsernameValid = null;

function validateAdminPassword(input) {
    var val  = input.value;
    var wrap = document.getElementById('adminPwStrength');
    if (!wrap) return;
    wrap.style.display = val.length > 0 ? 'block' : 'none';

    var rules = {
        'pw-len':   val.length >= 7,
        'pw-alpha': /[A-Za-z]/.test(val),
        'pw-num':   /[0-9]/.test(val),
        'pw-spec':  /[@$!%*#?&^_\-]/.test(val),
    };

    Object.keys(rules).forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        var ok = rules[id];
        el.style.background  = ok ? 'rgba(34,197,94,0.12)'  : 'rgba(255,255,255,0.06)';
        el.style.borderColor = ok ? 'rgba(34,197,94,0.3)'   : 'rgba(255,255,255,0.10)';
        el.style.color       = ok ? '#86efac'                : 'rgba(255,255,255,0.4)';
        el.querySelector('i').className = ok ? 'fa fa-circle-check me-1' : 'fa fa-circle-xmark me-1';
    });
}

function adminPasswordIsValid(val) {
    return val.length >= 7 && /[A-Za-z]/.test(val) && /[0-9]/.test(val) && /[@$!%*#?&^_\-]/.test(val);
}

function resetPwStrength() {
    var wrap = document.getElementById('adminPwStrength');
    if (wrap) wrap.style.display = 'none';
    ['pw-len','pw-alpha','pw-num','pw-spec'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.background  = 'rgba(255,255,255,0.06)';
        el.style.borderColor = 'rgba(255,255,255,0.10)';
        el.style.color       = 'rgba(255,255,255,0.4)';
        el.querySelector('i').className = 'fa fa-circle-xmark me-1';
    });
}

function checkAdminUsername() {
    var input    = document.getElementById('admin_username');
    var feedback = document.getElementById('adminUsernameFeedback');
    if (!input || !feedback) return;

    var val       = input.value.trim();
    var excludeId = document.getElementById('admin_id').value;

    if (!val) {
        feedback.innerHTML = '';
        _adminUsernameValid = null;
        return;
    }

    if (!/^[a-z0-9_.]+$/.test(val)) {
        feedback.innerHTML = '<span style="color:#f87171;font-size:0.74rem;"><i class="fa fa-circle-xmark me-1"></i>Only lowercase letters (a-z), numbers, underscore (_) and dot (.) allowed.</span>';
        _adminUsernameValid = false;
        return;
    }

    feedback.innerHTML = '<span style="color:rgba(255,255,255,0.35);font-size:0.74rem;"><i class="fa fa-spinner fa-spin me-1"></i>Checking…</span>';

    clearTimeout(_adminUsernameTimer);
    _adminUsernameTimer = setTimeout(function () {
        var url = window.adminRoutes.checkAdminUsername + '?username=' + encodeURIComponent(val);
        if (excludeId) url += '&exclude_id=' + encodeURIComponent(excludeId);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                _adminUsernameValid = data.available;
                if (data.available) {
                    feedback.innerHTML = '<span style="color:#86efac;font-size:0.74rem;"><i class="fa fa-circle-check me-1"></i>Username is available.</span>';
                } else {
                    feedback.innerHTML = '<span style="color:#f87171;font-size:0.74rem;"><i class="fa fa-circle-xmark me-1"></i>' + (data.message || 'Username is already taken.') + '</span>';
                }
            })
            .catch(function () {
                feedback.innerHTML = '';
                _adminUsernameValid = null;
            });
    }, 400);
}

function _resetAdminUsernameFeedback() {
    var feedback = document.getElementById('adminUsernameFeedback');
    if (feedback) feedback.innerHTML = '';
    _adminUsernameValid = null;
    clearTimeout(_adminUsernameTimer);
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

function openAddAdminModal() {
    document.getElementById('adminModalTitle').innerHTML = '<span class="modal-title-icon"><i class="fa fa-user-shield"></i></span> Add Admin';
    document.getElementById('adminForm').reset();
    document.getElementById('admin_id').value = '';
    document.getElementById('adminPasswordNote').style.display = 'none';
    document.getElementById('admin_password').required = true;
    document.getElementById('admin_password_confirmation').required = true;
    resetPwStrength();
    _resetAdminUsernameFeedback();
    _adminUsernameValid = null;
    const btn = document.getElementById('adminSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Admin'; }
    _auditHide();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('adminModal')).show();
}

function openEditAdminModal(id, data) {
    document.getElementById('adminModalTitle').innerHTML = '<span class="modal-title-icon"><i class="fa fa-user-shield"></i></span> Edit Admin';
    document.getElementById('admin_id').value        = id;
    document.getElementById('admin_name').value      = data.name     || '';
    document.getElementById('admin_username').value  = data.username || '';
    document.getElementById('admin_email').value     = data.email    || '';
    document.getElementById('admin_status').value    = data.status   || '1';
    document.getElementById('admin_password').value              = '';
    document.getElementById('admin_password_confirmation').value = '';
    document.getElementById('adminPasswordNote').style.display = 'block';
    document.getElementById('admin_password').required = false;
    document.getElementById('admin_password_confirmation').required = false;
    resetPwStrength();
    _resetAdminUsernameFeedback();
    _adminUsernameValid = true;
    const btn = document.getElementById('adminSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Admin'; }
    _auditShow(data);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('adminModal')).show();
}

function filterAdminTable() {
    const search = (document.getElementById('searchAdmin').value || '').toLowerCase();
    const status = document.getElementById('filterAdminStatus').value;
    var noResults = document.getElementById('adminNoResults');

    if (!search && !status) {
        if (noResults) noResults.style.display = 'none';
        if (window.PGD) PGD.applyFilter('adm', null);
        return;
    }

    var matched = [];
    document.querySelectorAll('#adminTable tbody tr.pgd-row').forEach(function (row) {
        const matchSearch = !search ||
            (row.dataset.name     || '').includes(search) ||
            (row.dataset.username || '').includes(search) ||
            (row.dataset.email    || '').includes(search);
        const matchStatus = !status || row.dataset.status === status;
        if (matchSearch && matchStatus) matched.push(row);
    });

    if (noResults) noResults.style.display = matched.length === 0 ? 'table-row' : 'none';
    if (window.PGD) PGD.applyFilter('adm', matched);
}

function deleteAdmin(id) {
    confirmAction('Are you sure you want to delete this admin account? This action cannot be undone.', function () {
        fetch(window.adminRoutes.adminsDelete + '/' + id, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('success', 'Admin account deleted successfully.');
            } else {
                showAlert('error', data.message || 'Delete failed.');
            }
        })
        .catch(function () { showAlert('error', 'Server error. Please try again.'); });
    });
}

function saveAdmin() {
    const id      = document.getElementById('admin_id').value;
    const isEdit  = !!id;
    const url     = isEdit ? window.adminRoutes.adminsUpdate + '/' + id : window.adminRoutes.adminsStore;
    const pwVal   = document.getElementById('admin_password').value;
    const pwFilled = !!pwVal;

    const usernameVal = (document.getElementById('admin_username').value || '').trim();
    if (!usernameVal) {
        showAlert('error', 'Username is required.');
        return;
    }
    if (!/^[a-z0-9_.]+$/.test(usernameVal)) {
        showAlert('error', 'Username may only contain lowercase letters (a-z), numbers, underscore (_) and dot (.).');
        return;
    }
    if (_adminUsernameValid === false) {
        showAlert('error', 'That username is already taken — please choose a different one.');
        return;
    }

    const fields = [
        { id: 'admin_name',  message: 'Name is required.',            maxLength: 100 },
        { id: 'admin_email', message: 'A valid email is required.' },
    ];

    if (!isEdit || pwFilled) {
        if (!isEdit && !pwFilled) {
            showAlert('error', 'Password is required.');
            return;
        }
        if (pwFilled && !adminPasswordIsValid(pwVal)) {
            showAlert('error', 'Password must be at least 7 characters and include a letter, a number, and a special character (@$!%*#?&^_-).');
            return;
        }
        const pwConf = document.getElementById('admin_password_confirmation').value;
        if (pwFilled && pwVal !== pwConf) {
            showAlert('error', 'Passwords do not match.');
            return;
        }
    }

    validateForm({
        formId: 'adminForm',
        fields: fields,
        btn:    'adminSubmitBtn',
        onSuccess: function () {
            submitFormData({
                formId: 'adminForm',
                url: url,
                successMessage: isEdit ? 'Admin updated successfully.' : 'Admin created successfully.',
                onSuccess: function () {
                    bootstrap.Modal.getInstance(document.getElementById('adminModal'))?.hide();
                },
                onError: function () {
                    const btn = document.getElementById('adminSubmitBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Admin'; }
                },
            });
        },
    });
}

(function () {
    'use strict';

    /* Only activate on the admins page */
    if (!document.getElementById('adminTable')) return;

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

    /* ── Detect the currently logged-in admin's ID from the DOM ─────────── */
    var _selfId = (function () {
        var disabled = document.querySelector('#adminTable tbody tr.pgd-row .btn-adm-icon--danger[disabled]');
        if (!disabled) return null;
        var row = disabled.closest('tr.pgd-row');
        if (!row) return null;
        var editBtn = row.querySelector('.btn-adm-icon--edit');
        if (!editBtn) return null;
        var m = (editBtn.getAttribute('onclick') || '').match(/openEditAdminModal\(\s*(\d+)/);
        return m ? parseInt(m[1], 10) : null;
    }());

    /* ── Tag existing Blade-rendered rows with data-id ───────────────────── */
    function _tagRows() {
        document.querySelectorAll('#adminTable tbody tr.pgd-row').forEach(function (row) {
            if (row.hasAttribute('data-id')) return;
            var editBtn = row.querySelector('.btn-adm-icon--edit');
            if (!editBtn) return;
            var m = (editBtn.getAttribute('onclick') || '').match(/openEditAdminModal\(\s*(\d+)/);
            if (m) row.setAttribute('data-id', m[1]);
        });
    }

    /* ── Renumber the # column ───────────────────────────────────────────── */
    function _renumber() {
        document.querySelectorAll('#adminTable tbody tr.pgd-row').forEach(function (row, i) {
            var cell = row.cells[0];
            if (cell) cell.textContent = i + 1;
        });
    }

    /* ── Show/hide empty-state and table wrapper ─────────────────────────── */
    function _syncEmpty() {
        var hasRows = document.querySelectorAll('#adminTable tbody tr.pgd-row').length > 0;
        var scroll  = document.querySelector('.pgd-scroll');
        var footer  = document.querySelector('.pgd-footer');
        var empty   = document.querySelector('.adm-empty');
        if (scroll) scroll.style.display = hasRows ? '' : 'none';
        if (footer) footer.style.display = hasRows ? '' : 'none';
        if (empty)  empty.style.display  = hasRows ? 'none' : '';
    }

    /* ── Refresh pagination ──────────────────────────────────────────────── */
    function _repaginate() {
        if (window.PGD) PGD.applyFilter('adm', null);
    }

    /* ── Name cell HTML (with optional "You" badge) ──────────────────────── */
    function _nameCell(d) {
        var isSelf = _selfId !== null && parseInt(d.id, 10) === _selfId;
        var badge  = isSelf
            ? '<span class="badge rounded-pill" style="font-size:0.65rem;background:rgba(215,44,66,0.15);color:var(--adm-red);border:1px solid rgba(215,44,66,0.25);">You</span>'
            : '';
        return '<div class="d-flex align-items-center gap-2">' +
               '<div class="adm-icon-preview" style="width:30px;height:30px;border-radius:50%;font-size:0.78rem;">' +
               '<i class="fa fa-user-shield"></i></div>' +
               '<strong>' + _esc(d.name) + '</strong>' + badge +
               '</div>';
    }

    /* ── Build a fresh <tr> for an admin record ──────────────────────────── */
    function _buildRow(d) {
        var isSelf = _selfId !== null && parseInt(d.id, 10) === _selfId;

        var tr = document.createElement('tr');
        tr.className = 'pgd-row';
        tr.setAttribute('data-id',       d.id);
        tr.setAttribute('data-status',   d.status);
        tr.setAttribute('data-name',     (d.name     || '').toLowerCase());
        tr.setAttribute('data-username', (d.username || '').toLowerCase());
        tr.setAttribute('data-email',    (d.email    || '').toLowerCase());

        var usernameHtml = d.username
            ? _esc(d.username)
            : '<span style="color:rgba(255,255,255,0.25);font-size:0.78rem;">—</span>';

        var deleteBtn = isSelf
            ? '<button class="btn-adm-icon btn-adm-icon--danger" title="Cannot delete your own account" disabled style="opacity:0.3;cursor:not-allowed;"><i class="fa fa-trash"></i></button>'
            : '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>';

        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
            '<td>' + _nameCell(d) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + usernameHtml + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.email) + '</td>' +
            '<td>' + _pill(d.status) + '</td>' +
            '<td><div class="d-flex gap-1">' +
                '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                deleteBtn +
            '</div></td>';

        tr.querySelector('.btn-adm-icon--edit').onclick = function () {
            openEditAdminModal(d.id, d);
        };
        if (!isSelf) {
            tr.querySelector('.btn-adm-icon--danger').onclick = function () {
                deleteAdmin(d.id);
            };
        }

        return tr;
    }

    /* ── CRUD handlers ───────────────────────────────────────────────────── */

    function _onCreated(d) {
        var tbody = document.querySelector('#adminTable tbody');
        if (!tbody) return;

        /* Guard against duplicates (e.g. two tabs open) */
        if (tbody.querySelector('tr.pgd-row[data-id="' + d.id + '"]')) return;

        var tr         = _buildRow(d);
        var noResults  = document.getElementById('adminNoResults');
        if (noResults) tbody.insertBefore(tr, noResults);
        else           tbody.appendChild(tr);

        _renumber();
        _syncEmpty();
        _repaginate();
    }

    function _onUpdated(d) {
        var existing = document.querySelector('#adminTable tbody tr.pgd-row[data-id="' + d.id + '"]');
        if (!existing) { _onCreated(d); return; }

        /* Refresh data attributes so search/filter still work */
        existing.setAttribute('data-status',   d.status);
        existing.setAttribute('data-name',     (d.name     || '').toLowerCase());
        existing.setAttribute('data-username', (d.username || '').toLowerCase());
        existing.setAttribute('data-email',    (d.email    || '').toLowerCase());

        /* Update cells in place — preserves row position */
        existing.cells[1].innerHTML = _nameCell(d);
        existing.cells[2].innerHTML = d.username
            ? _esc(d.username)
            : '<span style="color:rgba(255,255,255,0.25);font-size:0.78rem;">—</span>';
        existing.cells[3].textContent = d.email || '';
        existing.cells[4].innerHTML   = _pill(d.status);

        /* Refresh edit-button closure with fresh data */
        var editBtn = existing.querySelector('.btn-adm-icon--edit');
        if (editBtn) editBtn.onclick = function () { openEditAdminModal(d.id, d); };

        /* Re-evaluate any active search/filter so the updated row is correctly
           included or excluded in the current filtered view */
        _repaginate();
    }

    function _onDeleted(id) {
        var existing = document.querySelector('#adminTable tbody tr.pgd-row[data-id="' + id + '"]');
        if (!existing) return;
        existing.parentNode.removeChild(existing);
        _renumber();
        _syncEmpty();
        _repaginate();
    }

    /* ── Bootstrap ───────────────────────────────────────────────────────── */

    /* Tag Blade-rendered rows so they can be found by data-id */
    _tagRows();

    /* Subscribe once — guard prevents duplicate listeners on navigation */
    if (!window._rrAdminsBound && window.channel) {
        window._rrAdminsBound = true;
        window.channel.bind('admin-content-updated', function (event) {
            if (event.entity !== 'admins') return;
            if      (event.action === 'created') _onCreated(event.data);
            else if (event.action === 'updated') _onUpdated(event.data);
            else if (event.action === 'deleted') _onDeleted(event.data.id);
        });
    }
}());