function limitRating(input) {
    if (input.value > 5) input.value = 5;
    if (input.value < 0) input.value = 0;
}

function previewAmbImage(input) {
    const preview = document.getElementById('ambImgPreview');
    if (!preview) return;
    const file = input.files[0];
    if (!file) {
        preview.innerHTML = '<i class="fa fa-image fa-2x opacity-25"></i>';
        return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
        preview.innerHTML = '<img src="' + e.target.result + '" style="width:20%;height:100%;object-fit:cover;border-radius:10px;">';
    };
    reader.readAsDataURL(file);
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
    document.getElementById('modalTitle').textContent = 'Add Ambulance';
    document.getElementById('ambForm').reset();
    document.getElementById('amb_id').value = '';
    document.getElementById('amb_driver_id').value = '';
    const preview = document.getElementById('ambImgPreview');
    if (preview) preview.innerHTML = '<i class="fa fa-image fa-2x opacity-25"></i>';
    var nc = document.getElementById('notesCount'); if (nc) nc.textContent = '0';
    var dc = document.getElementById('cardDescCount'); if (dc) dc.textContent = '0';
    const btn = document.getElementById('ambSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Ambulance'; }
    _auditHide();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('ambModal')).show();
}

function openEditModal(id, data) {
    document.getElementById('modalTitle').textContent = 'Edit Ambulance';
    document.getElementById('amb_id').value            = id;
    document.getElementById('vehicle_number').value   = data.vehicle_number || '';
    document.getElementById('type').value             = data.type || '0';
    document.getElementById('equipment_level').value  = data.equipment_level || '0';
    document.getElementById('amb_status').value       = data.status || '0';
    document.getElementById('amb_driver_id').value    = data.driver_id || '';
    document.getElementById('notes').value            = data.notes || '';
    document.getElementById('card_title').value       = data.card_title || '';
    document.getElementById('card_description').value = data.card_description || '';
    document.getElementById('card_features').value    = data.card_features || '';
    document.getElementById('card_rating').value      = data.card_rating || '';
    document.getElementById('card_trips').value       = data.card_trips || '';

    const preview = document.getElementById('ambImgPreview');
    if (preview) {
        preview.innerHTML = data.card_image
            ? '<img src="/assets/admin/img/fleet/' + data.card_image + '" style="width:20%;height:100%;object-fit:cover;border-radius:10px;">'
            : '<i class="fa fa-image fa-2x opacity-25"></i>';
    }
    var nc = document.getElementById('notesCount'); if (nc) nc.textContent = (data.notes || '').length;
    var dc = document.getElementById('cardDescCount'); if (dc) dc.textContent = (data.card_description || '').length;
    const btn = document.getElementById('ambSubmitBtn');
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Ambulance'; }
    _auditShow(data);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('ambModal')).show();
}

function filterTable() {
    const search = document.getElementById('searchAmb').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    var noResults = document.getElementById('ambNoResults');
    if (!search && !status) {
        if (noResults) noResults.style.display = 'none';
        if (window.PGD) PGD.applyFilter('amb', null);
        return;
    }
    var matched = [];
    document.querySelectorAll('#ambTable tbody tr.pgd-row').forEach(function (row) {
        const matchSearch = !search || (row.dataset.vehicle || '').includes(search);
        const matchStatus = !status || String(row.dataset.status) === String(status);
        if (matchSearch && matchStatus) matched.push(row);
    });
    if (noResults) noResults.style.display = matched.length === 0 ? 'table-row' : 'none';
    if (window.PGD) PGD.applyFilter('amb', matched);
}

function deleteAmbulance(id) {
    confirmAction('Are you sure you want to delete this ambulance? This action cannot be undone.', function () {
        fetch(window.adminRoutes.ambulancesDelete + '/' + id, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('success', 'Ambulance deleted successfully.');
            } else {
                showAlert('error', data.message || 'Delete failed.');
            }
        })
        .catch(function () { showAlert('error', 'Server error. Please try again.'); });
    })
}

function saveAmbulance() {
    const id = document.getElementById('amb_id').value;
    const isEdit = !!id;
    const url = isEdit ? window.adminRoutes.ambulancesUpdate + '/' + id : window.adminRoutes.ambulancesStore;

    validateForm({
        formId: 'ambForm',
        fields: [
            { id: 'vehicle_number', message: 'Vehicle Number is required.', maxLength: 20 },
            { id: 'type', message: 'Please select a type.', skipIf: '0' },
            { id: 'equipment_level', message: 'Please select an equipment level.', skipIf: '0' },
            { id: 'amb_status', message: 'Please select a status.', skipIf: '0' },
            { id: 'card_title', message: 'Card Title is required.', maxLength: 20 },
            { id: 'card_rating', message: 'Rating is required.', max: 5 },
            { id: 'card_description', message: 'Card Description is required.', maxLength: 500 },
            { id: 'card_features', message: 'Card Features is required.', maxLength: 50 },
        ],
        btn: 'ambSubmitBtn',
        onSuccess: function () {
            submitFormData({
                formId: 'ambForm',
                url: url,
                successMessage: isEdit ? 'Ambulance updated successfully.' : 'Ambulance added successfully.',
                onSuccess: function (resData) {
                    bootstrap.Modal.getInstance(document.getElementById('ambModal'))?.hide();
                },
                onError: function () {
                    const btn = document.getElementById('ambSubmitBtn');
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check me-1"></i> Save Ambulance'; }
                },
            });
        },
    });
}

(function () {
    'use strict';

    /* Only activate on the ambulances page */
    if (!document.getElementById('amb_driver_id')) return;

    function _esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* Returns every driver <select> on the page (add + edit share one element) */
    function _selects() {
        return document.querySelectorAll('#amb_driver_id');
    }
    
    function _onDriverCreated(d) {
        if (parseInt(d.status, 10) !== 1) {
            return;
        }
        _selects().forEach(function (sel) {
            if (sel.querySelector('option[value="' + d.id + '"]')) return;
            var opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name + ' \u2014 ' + d.phone;
            sel.appendChild(opt);
        });
    }

    function _onDriverUpdated(d) {
        _selects().forEach(function (sel) {
            var opt = sel.querySelector('option[value="' + d.id + '"]');
    
            // Driver is inactive -> remove from dropdown
            if (parseInt(d.status, 10) !== 1) {
                if (opt) {
                    opt.remove();
                }
                return;
            }
    
            // Driver is active -> update or add
            if (opt) {
                opt.textContent = d.name + ' — ' + d.phone;
            } else {
                var newOpt = document.createElement('option');
                newOpt.value = d.id;
                newOpt.textContent = d.name + ' — ' + d.phone;
                sel.appendChild(newOpt);
            }
        });

        // ── Also update the Driver Name column in the ambulance grid ──────
        // Find every row that is assigned to this driver and patch cells[5].
        var safe = String(d.name == null ? '' : d.name)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        document.querySelectorAll('#ambTable tbody tr.pgd-row[data-driver-id="' + d.id + '"]').forEach(function (row) {
            if (row.cells[5]) {
                row.cells[5].innerHTML =
                    '<span style="font-family:monospace;background:rgba(255,255,255,0.06);padding:2px 8px;border-radius:6px;font-size:0.82rem;">' +
                    safe + '</span>';
            }
        });
    }
        
    function _onDriverDeleted(id) {
        _selects().forEach(function (sel) {
            var opt = sel.querySelector('option[value="' + id + '"]');
            if (!opt) return;
            /* If the deleted driver is currently selected, clear the selection */
            if (String(sel.value) === String(id)) sel.value = '';
            sel.removeChild(opt);
        });
    }

    /* Subscribe once — guard prevents duplicate listeners */
    if (!window._rrAmbDriverBound && window.pusher) {
        window._rrAmbDriverBound = true;
        var driverChannel = window.pusher.subscribe('drivers-update');
        driverChannel.bind('drivers-update', function (e) {
            if (e.entity !== 'driverAdminUpdated') return;
            if (e.action === 'created') _onDriverCreated(e.data);
            else if (e.action === 'updated') _onDriverUpdated(e.data);
            else if (e.action === 'deleted') _onDriverDeleted(e.data.id);
        });
    }
}());

/* ── Real-time DOM updates for Ambulance table via admin-dashboard channel ── */
(function () {
    'use strict';

    /* Only activate on the ambulances page */
    if (!document.getElementById('ambTable')) return;

    function _esc(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    var _typeLabels  = { 1: 'BLS', 2: 'ALS', 3: 'CCT', 4: 'Neonatal', 5: 'AIR' };
    var _equipLabels = { 1: 'Basic', 2: 'Advanced' };
    var _statusLabels = { 1: 'Available', 2: 'On Job', 3: 'Maintenance', 4: 'Inactive' };

    function _statusPill(status) {
        return '<span class="status-pill status-' + status + '">' + (_statusLabels[status] || '—') + '</span>';
    }

    function _driverCell(d) {
        return d.driver_name ? '<span style="font-family:monospace;background:rgba(255,255,255,0.06);padding:2px 8px;border-radius:6px;font-size:0.82rem;">' + _esc(d.driver_name) + '</span>' : '<span style="color:rgba(255,255,255,0.25);">—</span>';
    }

    function _tbody() {
        return document.querySelector('#ambTable tbody');
    }

    function _renumber() {
        document.querySelectorAll('#ambTable tbody tr.pgd-row').forEach(function (row, i) {
            var cell = row.cells[0];
            if (cell) cell.textContent = i + 1;
        });
    }

    function _syncEmpty() {
        var hasRows = document.querySelectorAll('#ambTable tbody tr.pgd-row').length > 0;
        var scroll  = document.querySelector('.pgd-scroll');
        var footer  = document.querySelector('.pgd-footer');
        var empty   = document.querySelector('.adm-empty');
        if (scroll) scroll.style.display = hasRows ? '' : 'none';
        if (footer) footer.style.display = hasRows ? '' : 'none';
        if (empty)  empty.style.display  = hasRows ? 'none' : '';
    }

    function _repaginate() {
        if (window.PGD) PGD.applyFilter('amb', null);
    }

    function _buildRow(d) {
        var tr = document.createElement('tr');
        tr.className = 'pgd-row';
        tr.setAttribute('data-ambulance-id', d.id);
        tr.setAttribute('data-driver-id', d.driver_id || '');
        tr.setAttribute('data-status', d.status);
        tr.setAttribute('data-vehicle', (d.vehicle_number || '').toLowerCase());

        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">—</td>' +
            '<td><div class="d-flex align-items-center gap-2">' +
                '<div class="adm-icon-preview" style="width:30px;height:30px;border-radius:7px;font-size:0.78rem;"><i class="fa fa-ambulance"></i></div>' +
                '<strong>' + _esc(d.vehicle_number) + '</strong>' +
            '</div></td>' +
            '<td><span class="badge rounded-pill badge-type">' + (_typeLabels[d.type] || '—') + '</span></td>' +
            '<td><span class="badge rounded-pill ' + (d.equipment_level == 2 ? 'badge-advanced' : 'badge-basic') + '">' + (_equipLabels[d.equipment_level] || '—') + '</span></td>' +
            '<td>' + _statusPill(d.status) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _driverCell(d) + '</td>' +
            '<td><div class="d-flex gap-1">' +
                '<button class="btn-adm-icon btn-adm-icon--edit" title="Edit"><i class="fa fa-pen"></i></button>' +
                '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete"><i class="fa fa-trash"></i></button>' +
            '</div></td>'
        ;

        tr.querySelector('.btn-adm-icon--edit').onclick   = function () { openEditModal(d.id, d); };
        tr.querySelector('.btn-adm-icon--danger').onclick = function () { deleteAmbulance(d.id); };

        return tr;
    }

    function onCreated(d) {
        var tbody = _tbody();
        if (!tbody) return;
        var tr = _buildRow(d);
        var noResults = tbody.querySelector('#ambNoResults');
        if (noResults) tbody.insertBefore(tr, noResults);
        else tbody.appendChild(tr);
        _renumber();
        _syncEmpty();
        _repaginate();
    }

    function onUpdated(d) {
        var existing = document.querySelector('#ambTable tbody tr.pgd-row[data-ambulance-id="' + d.id + '"]');
        if (!existing) { onCreated(d); return; }

        existing.setAttribute('data-status', d.status);
        existing.setAttribute('data-vehicle', (d.vehicle_number || '').toLowerCase());

        existing.cells[1].innerHTML =
            '<div class="d-flex align-items-center gap-2">' +
            '<div class="adm-icon-preview" style="width:30px;height:30px;border-radius:7px;font-size:0.78rem;"><i class="fa fa-ambulance"></i></div>' +
            '<strong>' + _esc(d.vehicle_number) + '</strong></div>';
        existing.cells[2].innerHTML = '<span class="badge rounded-pill badge-type">' + (_typeLabels[d.type] || '—') + '</span>';
        existing.cells[3].innerHTML = '<span class="badge rounded-pill ' + (d.equipment_level == 2 ? 'badge-advanced' : 'badge-basic') + '">' + (_equipLabels[d.equipment_level] || '—') + '</span>';
        existing.cells[4].innerHTML = _statusPill(d.status);
        existing.cells[5].innerHTML = _driverCell(d);

        var editBtn = existing.querySelector('.btn-adm-icon--edit');
        if (editBtn) editBtn.onclick = function () { openEditModal(d.id, d); };
    }

    function onDeleted(id) {
        var existing = document.querySelector('#ambTable tbody tr.pgd-row[data-ambulance-id="' + id + '"]');
        if (!existing) return;
        existing.parentNode.removeChild(existing);
        _renumber();
        _syncEmpty();
        _repaginate();
    }

    /* Bind once — guard against duplicate registration */
    if (!window._rrAmbBound && window.channel) {
        window._rrAmbBound = true;
        window.channel.bind('admin-content-updated', function (event) {
            if (event.entity !== 'ambulance') return;
            if (event.action === 'created')      onCreated(event.data);
            else if (event.action === 'updated') onUpdated(event.data);
            else if (event.action === 'deleted') onDeleted(event.data.id);
        });
    }
}());
