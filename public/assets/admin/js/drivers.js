var _driverEditId        = null;
var _driverUsernameTimer = null;
var _driverUsernameValid = null;

function previewDriverPhoto(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        var img  = document.getElementById('drvPhotoImg');
        var prev = document.getElementById('drvPhotoPrev');
        var clr  = document.getElementById('drvPhotoClear');
        img.src           = e.target.result;
        img.style.display = 'block';
        prev.style.display = 'none';
        if (clr) clr.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function clearDriverPhoto() {
    var img  = document.getElementById('drvPhotoImg');
    var prev = document.getElementById('drvPhotoPrev');
    var clr  = document.getElementById('drvPhotoClear');
    var file = document.getElementById('drv_photo');
    img.style.display  = 'none';
    prev.style.display = 'flex';
    if (clr) clr.style.display = 'none';
    if (file) file.value = '';
}

function _setDriverPhoto(url) {
    var img  = document.getElementById('drvPhotoImg');
    var prev = document.getElementById('drvPhotoPrev');
    var clr  = document.getElementById('drvPhotoClear');

    if (url) {
        const baseUrl = window.location.origin; 
        img.src = `${baseUrl}/assets/driver/img/${url}`;

        img.style.display = 'block';
        if (prev) prev.style.display = 'none';
        if (clr) clr.style.display = 'block';
    } else {
        img.src = '';
        img.style.display = 'none';

        if (prev) prev.style.display = 'flex';
        if (clr) clr.style.display = 'none';
    }
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

function checkDriverUsername() {
    var input    = document.getElementById('drv_username');
    var feedback = document.getElementById('driverUsernameFeedback');
    if (!input || !feedback) return;

    var val       = input.value.trim();
    var excludeId = _driverEditId;

    if (!val) {
        feedback.innerHTML = '';
        _driverUsernameValid = null;
        return;
    }

    if (!/^[a-z0-9_.]+$/.test(val)) {
        feedback.innerHTML = '<span style="color:#f87171;font-size:0.74rem;"><i class="fa fa-circle-xmark me-1"></i>Only lowercase letters (a-z), numbers, underscore (_) and dot (.) allowed.</span>';
        _driverUsernameValid = false;
        return;
    }

    feedback.innerHTML = '<span style="color:rgba(255,255,255,0.35);font-size:0.74rem;"><i class="fa fa-spinner fa-spin me-1"></i>Checking…</span>';

    clearTimeout(_driverUsernameTimer);
    _driverUsernameTimer = setTimeout(function () {
        var url = window.adminRoutes.checkDriverUsername + '?username=' + encodeURIComponent(val);
        if (excludeId) url += '&exclude_id=' + encodeURIComponent(excludeId);
        
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                _driverUsernameValid = data.available;
                if (data.available) {
                    feedback.innerHTML = '<span style="color:#86efac;font-size:0.74rem;"><i class="fa fa-circle-check me-1"></i>Username is available.</span>';
                } else {
                    feedback.innerHTML = '<span style="color:#f87171;font-size:0.74rem;"><i class="fa fa-circle-xmark me-1"></i>' + (data.message || 'Username is already taken.') + '</span>';
                }
            })
            .catch(function () {
                feedback.innerHTML = '';
                _driverUsernameValid = null;
            });
    }, 400);
}

function _resetDriverUsernameFeedback() {
    var feedback = document.getElementById('driverUsernameFeedback');
    if (feedback) feedback.innerHTML = '';
    _driverUsernameValid = null;
    clearTimeout(_driverUsernameTimer);
}

function openAddDriverModal() {
    _driverEditId = null;
    document.getElementById('driverModalTitle').innerHTML = '<span class="modal-title-icon"><i class="fa fa-id-card"></i></span> Add Driver';
    document.getElementById('driverForm').reset();
    clearDriverPhoto();
    document.getElementById('drvPasswordNote').style.display = 'none';
    document.getElementById('drvPwRequired').style.display   = 'inline';
    document.getElementById('drv_photo').required            = true;
    _resetDriverUsernameFeedback();
    _driverUsernameValid = null;

    var btn = document.getElementById('driverSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Driver'; }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('driverModal')).show();
}

function openEditDriverModal(id, data) {
    _driverEditId = id;
    document.getElementById('driverModalTitle').innerHTML = '<span class="modal-title-icon"><i class="fa fa-id-card"></i></span> Edit Driver';

    document.getElementById('driverForm').reset();
    document.getElementById('drv_name').value     = data.name       || '';
    document.getElementById('drv_username').value = data.username   || '';
    document.getElementById('drv_email').value    = data.email      || '';
    document.getElementById('drv_phone').value    = data.phone      || '';
    document.getElementById('drv_license').value  = data.license_no || '';
    document.getElementById('drv_status').value   = data.status     || '0';
    document.getElementById('drv_password').value = '';
    document.getElementById('drv_photo').required = false;

    _setDriverPhoto(data.photo);
    _auditShow(data);
    
    document.getElementById('drvPasswordNote').style.display = 'block';
    document.getElementById('drvPwRequired').style.display   = 'none';

    _resetDriverUsernameFeedback();
    _driverUsernameValid = true;

    var btn = document.getElementById('driverSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Driver'; }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('driverModal')).show();
}

function filterDriverTable() {
    var search = (document.getElementById('searchDrv').value || '').toLowerCase();
    var status = document.getElementById('filterDrvStatus').value;
    var noRes  = document.getElementById('driverNoResults');

    if (!search && !status) {
        if (noRes) noRes.style.display = 'none';
        if (window.PGD) PGD.applyFilter('drv', null);
        return;
    }

    var matched = [];
    document.querySelectorAll('#driverTable tbody tr.pgd-row').forEach(function (row) {
        var ms = !search ||
            (row.dataset.name  || '').includes(search) ||
            (row.dataset.email || '').includes(search) ||
            (row.dataset.phone || '').includes(search);
        var mt = !status || String(row.dataset.status) === String(status);
        if (ms && mt) matched.push(row);
    });

    if (noRes) noRes.style.display = matched.length === 0 ? 'table-row' : 'none';
    if (window.PGD) PGD.applyFilter('drv', matched);
}

function deleteDriver(id, name) {
    confirmAction('Delete driver "' + name + '"? This cannot be undone.', function () {
        fetch(window.adminRoutes.driverDelete + '/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Accept': 'application/json'
            },
        })
        .then(function (r) {
            return r.json();
        })
        .then(function (data) {
            if (data.success) {
                showAlert('success', data.message || 'Driver removed successfully.');
            } else {
                showAlert('error', data.message || 'Delete failed.');
            }
        })
        .catch(function () {
            showAlert('error', 'Server error. Please try again.');
        });
    });
}

function saveDriver() {
    var isEdit = !!_driverEditId;
    var url    = isEdit
        ? window.adminRoutes.driverUpdate + '/' + _driverEditId
        : window.adminRoutes.driverStore;

    var usernameVal = (document.getElementById('drv_username').value || '').trim();
    if (!usernameVal) {
        showAlert('error', 'Username is required.');
        return;
    }
    if (!/^[a-z0-9_.]+$/.test(usernameVal)) {
        showAlert('error', 'Username may only contain lowercase letters (a-z), numbers, underscore (_) and dot (.).');
        return;
    }
    if (_driverUsernameValid === false) {
        showAlert('error', 'That username is already taken — please choose a different one.');
        return;
    }

    var phone = (document.getElementById('drv_phone').value || '').trim();
    if (!/^03[0-9]{9}$/.test(phone)) {
        showAlert('error', 'Enter a valid Pakistani phone number (03XXXXXXXXX).');
        return;
    }

    var pw = document.getElementById('drv_password').value;
    if (!isEdit && !pw) {
        showAlert('error', 'Password is required when adding a new driver.');
        return;
    }
    if (pw && pw.length < 6) {
        showAlert('error', 'Password must be at least 6 characters.');
        return;
    }

    var photoFile = document.getElementById('drv_photo');
    if (!isEdit && (!photoFile.files || !photoFile.files[0])) {
        showAlert('error', 'Driver photo is required.');
        return;
    }

    var fields = [
        { id: 'drv_name',    message: 'Full name is required.' },
        { id: 'drv_email',   message: 'Email address is required.', validate: 'email' },
        { id: 'drv_license', message: 'License number is required.' },
        { id: 'drv_status',  message: 'Please select a status.', skipIf: '0' },
    ];

    validateForm({
        formId: 'driverForm',
        fields: fields,
        btn:    'driverSubmitBtn',
        onSuccess: function () {
            submitFormData({
                formId:         'driverForm',
                url:            url,
                successMessage: isEdit ? 'Driver updated successfully.' : 'Driver added successfully.',
                onSuccess: function (resData) {
                    bootstrap.Modal.getInstance(document.getElementById('driverModal'))?.hide();
                },
                onError: function () {
                    var btn = document.getElementById('driverSubmitBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Driver'; }
                },
            });
        },
    });
}

(function () {
    'use strict';

    /* Only activate on the drivers page */
    if (!document.getElementById('driverTable')) return;

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    function _esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function _statusPill(status) {
        var map = { 1: ['status-1', 'Active'], 2: ['status-4', 'Inactive'] };
        var p   = map[status] || ['', 'Unknown'];
        return '<span class="status-pill dri-rt-status ' + p[0] + '">' + p[1] + '</span>';
    }

    function _availPill(availability) {
        var map = { 1: ['status-1', 'Online'], 2: ['status-2', 'On Duty'], 3: ['status-4', 'Offline'] };
        var p   = map[availability] || ['', 'Unknown'];
        return '<span class="status-pill dri-rt-status ' + p[0] + '">' + p[1] + '</span>';
    }

    function _photoCell(d) {
        if (d.photo_url) {
            return '<img src="' + _esc(d.photo_url) + '" alt="' + _esc(d.name) + '" style="width:100%;height:100%;object-fit:cover;">';
        }
        return '<i class="fa fa-user"></i>';
    }

    /* ── Renumber the # column ───────────────────────────────────────────── */
    function _renumber() {
        document.querySelectorAll('#driverTable tbody tr.pgd-row').forEach(function (row, i) {
            var cell = row.cells[0];
            if (cell) cell.textContent = i + 1;
        });
    }

    /* ── Sync empty-state visibility ─────────────────────────────────────── */
    function _syncEmpty() {
        var hasRows = document.querySelectorAll('#driverTable tbody tr.pgd-row').length > 0;
        var scroll  = document.querySelector('.pgd-scroll');
        var footer  = document.querySelector('.pgd-footer');
        var empty   = document.querySelector('.adm-empty');
        if (scroll) scroll.style.display = hasRows ? '' : 'none';
        if (footer) footer.style.display = hasRows ? '' : 'none';
        if (empty)  empty.style.display  = hasRows ? 'none' : '';
    }

    /* ── Refresh pagination (resets to page 1) ───────────────────────────── */
    function _repaginate() {
        if (window.PGD && window.pgdId) PGD.applyFilter(window.pgdId, null);
    }

    /* ── Build a fresh <tr> for a driver record ──────────────────────────── */
    function _buildRow(d) {
        var tr = document.createElement('tr');
        tr.className = 'pgd-row';
        tr.setAttribute('data-id',     d.id);
        tr.setAttribute('data-status', d.status);
        tr.setAttribute('data-name',   (d.name  || '').toLowerCase());
        tr.setAttribute('data-email',  (d.email || '').toLowerCase());
        tr.setAttribute('data-phone',  d.phone  || '');

        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
            '<td><div class="d-flex align-items-center gap-2">' +
                '<div class="adm-icon-preview" style="width:36px;height:36px;border-radius:50%;overflow:hidden;font-size:0.85rem;flex-shrink:0;">' +
                    _photoCell(d) +
                '</div>' +
                '<div>' +
                    '<strong>' + _esc(d.name) + '</strong>' +
                    '<div class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.email) + '</div>' +
                '</div>' +
            '</div></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.username) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.phone) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.license_no) + '</td>' +
            '<td>' + _statusPill(d.status) + '</td>' +
            '<td>' + _availPill(d.availability) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' +
                '<span title="'+ _esc(d.completed_jobs) +' completed / '+ _esc(d.total_jobs) +' total">' +
                    _esc(d.completed_jobs) + '<span style="color:rgba(255,255,255,0.25);">/</span>' + _esc(d.total_jobs) +
                '</span>' +
            '</td>' +
            '<td><div class="d-flex gap-1">' +
                '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
            '</div></td>';

        tr.querySelector('.btn-adm-icon--edit').onclick = function () {
            openEditDriverModal(d.id, d);
        };
        tr.querySelector('.btn-adm-icon--danger').onclick = function () {
            deleteDriver(d.id, d.name);
        };

        return tr;
    }

    /* ── CRUD handlers ───────────────────────────────────────────────────── */
    function _onCreated(d) {
        var tbody = document.querySelector('#driverTable tbody');
        if (!tbody) return;

        /* Guard duplicates */
        if (tbody.querySelector('tr.pgd-row[data-id="' + d.id + '"]')) return;

        var tr = _buildRow(d);

        /* Insert at the top — backend uses latest()->get() (newest-first) */
        var firstRow = tbody.querySelector('tr.pgd-row');
        if (firstRow) {
            tbody.insertBefore(tr, firstRow);
        } else {
            var noResults = document.getElementById('driverNoResults');
            if (noResults) tbody.insertBefore(tr, noResults);
            else           tbody.appendChild(tr);
        }

        _renumber();
        _syncEmpty();
        filterDriverTable();
    }

    function _onUpdated(d) {
        var existing = document.querySelector('#driverTable tbody tr.pgd-row[data-id="' + d.id + '"]');
        if (!existing) { _onCreated(d); return; }

        /* Refresh searchable data attributes */
        existing.setAttribute('data-status', d.status);
        existing.setAttribute('data-name',   (d.name  || '').toLowerCase());
        existing.setAttribute('data-email',  (d.email || '').toLowerCase());
        existing.setAttribute('data-phone',  d.phone  || '');

        /* Update cells in place — preserves row position and jobs count */
        existing.cells[1].innerHTML =
            '<div class="d-flex align-items-center gap-2">' +
            '<div class="adm-icon-preview" style="width:36px;height:36px;border-radius:50%;overflow:hidden;font-size:0.85rem;flex-shrink:0;">' +
                _photoCell(d) +
            '</div>' +
            '<div>' +
                '<strong>' + _esc(d.name) + '</strong>' +
                '<div class="fs-xs" style="color:var(--adm-muted);">' + _esc(d.email) + '</div>' +
            '</div></div>';

        existing.cells[2].textContent = d.username   || '';
        existing.cells[3].textContent = d.phone      || '';
        existing.cells[4].textContent = d.license_no || '';
        existing.cells[5].innerHTML   = _statusPill(d.status);
        existing.cells[6].innerHTML   = _availPill(parseInt(d.availability, 10));

        /* Update Jobs Done only when the broadcast includes job counts
           (complete/cancel/reject payloads carry completed_jobs + total_jobs) */
        if (d.completed_jobs !== undefined && d.total_jobs !== undefined) {
            existing.cells[7].innerHTML =
                '<span title="' + _esc(d.completed_jobs) + ' completed / ' + _esc(d.total_jobs) + ' total">' +
                _esc(d.completed_jobs) + '<span style="color:rgba(255,255,255,0.25);">/</span>' + _esc(d.total_jobs) +
                '</span>';
        }

        /* Refresh action-button closures with fresh data */
        var editBtn = existing.querySelector('.btn-adm-icon--edit');
        if (editBtn) editBtn.onclick = function () { openEditDriverModal(d.id, d); };
        var delBtn = existing.querySelector('.btn-adm-icon--danger');
        if (delBtn) delBtn.onclick  = function () { deleteDriver(d.id, d.name); };

        /* Reapply current search/filter so updated row is correctly visible/hidden */
        filterDriverTable();
    }

    function _onDeleted(id) {
        var existing = document.querySelector('#driverTable tbody tr.pgd-row[data-id="' + id + '"]');
        if (!existing) return;
        existing.remove();
        _renumber();
        _syncEmpty();
        _repaginate();
    }
        
    /* Subscribe to the dedicated drivers channel once */
    if (!window._rrDriversBound && window.pusher) {
        window._rrDriversBound = true;
        var driverChannel = window.pusher.subscribe('drivers-update');
        driverChannel.bind('drivers-update', function (e) {
            if (e.entity !== 'driverAdminUpdated') return;
            if (e.action === 'created') _onCreated(e.data);
            else if (e.action === 'updated') _onUpdated(e.data);
            else if (e.action === 'deleted') _onDeleted(e.data.id);
        });
    }
}());