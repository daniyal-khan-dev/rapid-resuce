/* ── Escaping / formatting helpers shared by the row builder below ── */
function _rrEsc(str) {
    var div = document.createElement('div');
    div.textContent = str === null || str === undefined ? '' : String(str);
    return div.innerHTML;
}

function _rrTruncate(str, len) {
    if (!str) return '—';
    str = String(str);
    return str.length > len ? str.slice(0, len) + '...' : str;
}

var _rrStatusMap = {
    '1': { label: 'Pending', cls: 'status-3' },
    '2': { label: 'Dispatched', cls: 'status-2' },
    '3': { label: 'En Route', cls: 'status-2' },
    '4': { label: 'Arrived', cls: 'status-1' },
    '5': { label: 'Transporting', cls: 'status-2' },
    '6': { label: 'Completed', cls: 'status-1' },
    '7': { label: 'Cancelled', cls: 'status-4' },
    '8': { label: 'Awaiting Acceptance', cls: 'status-2' },
};

/* ── Build a <tr> for a new Emergency Request, mirroring the Blade markup ── */
function _rrBuildRequestRow(r) {
    var typeHtml;
    if (String(r.type) === '1') {
        typeHtml = '<span class="status-pill status-4">Emergency</span>';
    } else if (String(r.type) === '2') {
        typeHtml = '<span class="status-pill status-3">Non-Emergency</span>';
    } else {
        typeHtml = '<span class="status-pill status-3">' + _rrEsc(r.type) + '</span>';
    }

    var st = _rrStatusMap[String(r.status)] || {
        label: r.status ? String(r.status).charAt(0).toUpperCase() + String(r.status).slice(1) : '—',
        cls: 'status-3',
    };

    var canDelete = !['6', '7'].includes(String(r.status));

    return (
        '<tr class="pgd-row" data-req-id="' + _rrEsc(r.id) + '">' +
            '<td class="ps-4"><span style="font-family:monospace;font-size:0.8rem;background:rgba(255,255,255,0.06);padding:3px 8px;border-radius:6px;color:rgba(255,255,255,0.75);white-space:nowrap;">' + _rrEsc(r.rreb_id || '—') + '</span></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);"><div>' + _rrEsc(r.user_name || 'Guest') + '</div><small class="adm-muted">' + _rrEsc(r.mobile_no) + '</small></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _rrEsc(_rrTruncate(r.hospital_name, 22)) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _rrEsc(_rrTruncate(r.pickup_address, 22)) + '</td>' +
            '<td>' + typeHtml + '</td>' +
            '<td data-status-cell><span class="status-pill ' + st.cls + '">' + _rrEsc(st.label) + '</span></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);" data-ambulance-cell>' + _rrEsc(r.ambulance || '—') + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);" data-driver-cell>' + _rrEsc(r.driver || '—') + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);white-space:nowrap;">' + _rrEsc(r.created_at) + '</td>' +
            '<td><div class="d-flex gap-1">' +
                '<button class="btn-adm-icon" title="View / Dispatch" onclick="viewRequest(' + Number(r.id) + ')"><i class="fa fa-eye"></i></button>' +
                (canDelete ? '<button class="btn-adm-icon btn-adm-icon--danger" title="Delete" onclick="deleteRequest(' + Number(r.id) + ')"><i class="fa fa-trash"></i></button>' : '') +
            '</div></td>' +
        '</tr>'
    );
}

window._rrEmergencyGridSeenCreated = window._rrEmergencyGridSeenCreated || new Set();
window._rrEmergencyGridSeenDeleted = window._rrEmergencyGridSeenDeleted || new Set();

/* ── Handle a freshly-broadcast Emergency Request without disturbing pagination/UI ── */
function _rrHandleNewEmergencyRequest(payload) {
    var table = document.getElementById('reqTable');
    if (!table || !payload || payload.id === undefined) return;

    /* Never process the same request twice (e.g. reconnect replay) */
    if (window._rrEmergencyGridSeenCreated.has(payload.id)) return;
    window._rrEmergencyGridSeenCreated.add(payload.id);

    /* Never insert a duplicate row (e.g. it's already there for any reason) */
    if (table.querySelector('tr[data-req-id="' + payload.id + '"]')) return;

    /* Keep the header total in sync regardless of which page is open */
    var totalEl  = document.getElementById('reqTotalCount');
    var newTotal = null;
    if (totalEl) {
        var currentTotal = parseInt(totalEl.textContent, 10);
        if (!isNaN(currentTotal)) {
            newTotal = currentTotal + 1;
            totalEl.textContent = newTotal + ' Total';
        }
    }

    /* Only splice the new row into the grid when viewing the first page --
       further pages are left untouched so their pagination stays correct. */
    var params      = new URLSearchParams(window.location.search);
    var currentPage = parseInt(params.get('page'), 10) || 1;
    if (currentPage !== 1) return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.insertAdjacentHTML('afterbegin', _rrBuildRequestRow(payload));

    /* Requests are sorted latest-first; keep the page size intact so the
       row that just fell off the end effectively belongs to the next page. */
    var perPage = parseInt(table.getAttribute('data-per-page'), 10) || 20;
    var rows    = tbody.querySelectorAll('tr.pgd-row');
    if (rows.length > perPage) {
        rows[rows.length - 1].remove();
    }

    /* Reveal the table/footer and hide the empty-state if this was the very first request */
    var wrap   = document.querySelector('.pgd-scroll');
    var footer = document.querySelector('.pgd-footer');
    var empty  = document.querySelector('.adm-empty');
    if (wrap && wrap.style.display === 'none') wrap.style.display = '';
    if (footer && footer.style.display === 'none') footer.style.display = '';
    if (empty && empty.style.display !== 'none') empty.style.display = 'none';

    /* Keep the "Showing X–Y of Z" footer text consistent */
    var infoEl = document.querySelector('.pgd-info');
    if (infoEl && newTotal !== null) {
        var shown = Math.min(tbody.querySelectorAll('tr.pgd-row').length, perPage);
        infoEl.textContent = 'Showing 1–' + shown + ' of ' + newTotal + ' requests';
    }
}

/* ── Handle a broadcast Emergency Request deletion ── */
function _rrHandleDeletedEmergencyRequest(payload) {
    var table = document.getElementById('reqTable');
    if (!table || !payload || payload.id === undefined) return;

    /* Never process the same deletion twice (e.g. reconnect replay) */
    if (window._rrEmergencyGridSeenDeleted.has(payload.id)) return;
    window._rrEmergencyGridSeenDeleted.add(payload.id);

    /* Keep the header total in sync regardless of which page is open */
    var totalEl  = document.getElementById('reqTotalCount');
    var newTotal = null;
    if (totalEl) {
        var currentTotal = parseInt(totalEl.textContent, 10);
        if (!isNaN(currentTotal)) {
            newTotal = Math.max(0, currentTotal - 1);
            totalEl.textContent = newTotal + ' Total';
        }
    }

    /* Remove the row only if it's actually rendered on the current page */
    var row = table.querySelector('tr[data-req-id="' + payload.id + '"]');
    if (row) row.remove();

    var tbody   = table.querySelector('tbody');
    var perPage = parseInt(table.getAttribute('data-per-page'), 10) || 20;

    /* Keep the "Showing X–Y of Z" footer text consistent */
    var infoEl = document.querySelector('.pgd-info');
    if (infoEl && newTotal !== null && tbody) {
        var shown = Math.min(tbody.querySelectorAll('tr.pgd-row').length, perPage);
        infoEl.textContent = newTotal === 0
            ? 'Showing 0 of 0 requests'
            : 'Showing 1–' + shown + ' of ' + newTotal + ' requests';
    }

    /* If that was the last remaining request, fall back to the empty state */
    if (newTotal === 0) {
        var wrap   = document.querySelector('.pgd-scroll');
        var footer = document.querySelector('.pgd-footer');
        var empty  = document.querySelector('.adm-empty');
        if (wrap) wrap.style.display = 'none';
        if (footer) footer.style.display = 'none';
        if (empty) empty.style.display = '';
    }
}

/* ── Handle a driver status change (accept / reject / en_route / arrived / transporting / complete / cancel) ── */
function _rrHandleStatusChanged(payload) {
    var table = document.getElementById('reqTable');
    if (!table || !payload || payload.id === undefined) return;

    var row    = table.querySelector('tr[data-req-id="' + payload.id + '"]');
    var action = String(payload.action || '');
    var status = String(payload.status || '');
    var st     = _rrStatusMap[status] || { label: humanStatusFallback(status), cls: 'status-3' };

    /* Terminal: complete (6) or cancel (7) — fade row out of the active grid */
    if (action === 'complete' || action === 'cancel') {
        if (row) {
            row.style.transition = 'opacity .45s';
            row.style.opacity    = '0';
            setTimeout(function () { if (row.parentNode) row.remove(); }, 460);
        }

        /* Keep header count accurate */
        var totalEl = document.getElementById('reqTotalCount');
        if (totalEl) {
            var cur = parseInt(totalEl.textContent, 10);
            if (!isNaN(cur) && cur > 0) {
                totalEl.textContent = (cur - 1) + ' Total';
            }
        }

        /* Update pgd-info footer and show empty state when last request leaves the grid */
        var newCount = totalEl ? Math.max(0, parseInt(totalEl.textContent, 10) || 0) : 0;
        var infoEl   = document.querySelector('.pgd-info');
        var tbody    = table.querySelector('tbody');
        if (infoEl && tbody) {
            var perPage     = parseInt(table.getAttribute('data-per-page'), 10) || 20;
            /* Row is still fading out — exclude it from the visible count */
            var visibleRows = Array.from(tbody.querySelectorAll('tr.pgd-row'))
                .filter(function (r) { return r !== row; }).length;
            infoEl.textContent = newCount === 0
                ? 'Showing 0 of 0 requests'
                : 'Showing 1\u2013' + Math.min(visibleRows, perPage) + ' of ' + newCount + ' requests';
        }
        if (newCount === 0) {
            var wrapEl   = document.querySelector('.pgd-scroll');
            var footerEl = document.querySelector('.pgd-footer');
            var emptyEl  = document.querySelector('.adm-empty');
            if (wrapEl)   wrapEl.style.display   = 'none';
            if (footerEl) footerEl.style.display = 'none';
            if (emptyEl)  emptyEl.style.display  = '';
        }
    } else if (row) {
        /* Non-terminal: update only the status cell (and optionally clear driver/ambulance on reject) */
        var statusCell = row.querySelector('[data-status-cell]');
        if (statusCell) {
            statusCell.innerHTML = '<span class="status-pill ' + st.cls + '">' + _rrEsc(st.label) + '</span>';
        }

        /* Reject returns request to Pending — clear driver & ambulance columns */
        if (action === 'reject') {
            var driverCell    = row.querySelector('[data-driver-cell]');
            var ambulanceCell = row.querySelector('[data-ambulance-cell]');
            if (driverCell)    driverCell.textContent    = '—';
            if (ambulanceCell) ambulanceCell.textContent = '—';
        }
    }

    /* Let emergency.js update the open modal if it belongs to this request */
    if (typeof window._admHandleRequestStatusChanged === 'function') {
        window._admHandleRequestStatusChanged(payload);
    }
}

/* ── Handle a broadcast Emergency Request dispatch (Send Request) ── */
function _rrHandleDispatchedEmergencyRequest(payload) {
    var table = document.getElementById('reqTable');
    if (!table || !payload || payload.id === undefined) return;

    var row = table.querySelector('tr[data-req-id="' + payload.id + '"]');
    if (!row) return;

    var st = _rrStatusMap[String(payload.status)] || { label: humanStatusFallback(payload.status), cls: 'status-3' };

    var statusCell    = row.querySelector('[data-status-cell]');
    var ambulanceCell    = row.querySelector('[data-ambulance-cell]');
    var driverCell    = row.querySelector('[data-driver-cell]');

    if (statusCell)    statusCell.innerHTML      = '<span class="status-pill ' + st.cls + '">' + _rrEsc(st.label) + '</span>';
    if (ambulanceCell)    ambulanceCell.textContent    = payload.ambulance ? payload.ambulance : '—';
    if (driverCell)    driverCell.textContent    = payload.driver ? payload.driver : '—';
}

function humanStatusFallback(s) {
    return s ? String(s).replace(/_/g, ' ') : '—';
}

(function () {
    'use strict';

    /* Only activate on the emergency requests page */
    if (!document.getElementById('reqTable')) return;

    /* Guard: only bind once even if script is somehow loaded twice */
    if (window._rrEmergencyDriverBound) return;
    if (!window.pusher) return;
    window._rrEmergencyDriverBound = true;

    /* Reuse the already-subscribed public "admin-dashboard" channel (see
       admin.blade.php) for new Emergency Request notifications -- no new
       channel/subscription is created, and this binds exactly once. */
    if (window.channel && !window._rrEmergencyRequestBound) {
        window._rrEmergencyRequestBound = true;
        window.channel.bind('emergency-request-created',        _rrHandleNewEmergencyRequest);
        window.channel.bind('emergency-request-deleted',        _rrHandleDeletedEmergencyRequest);
        window.channel.bind('emergency-request-dispatched',     _rrHandleDispatchedEmergencyRequest);
        window.channel.bind('emergency-request-status-changed', _rrHandleStatusChanged);
    }

    /* Reuse / subscribe to the same public channel (Pusher deduplicates) */
    var driverChannel = window.pusher.subscribe('drivers-update');

    driverChannel.bind('drivers-update', function (e) {
        /* ── Live map: driver moved -- update marker + route in place ──── */
        if (e.entity === 'driverLocationUpdated') {
            if (typeof window._admUpdateDriverOnMap === 'function') {
                window._admUpdateDriverOnMap(e.data);
            }
            return;
        }

        if (e.entity !== 'driverAdminUpdated') return;

        /* ── Live map: driver went online/offline -- show/hide its marker ── */
        if (typeof window._admHandleDriverAvailabilityChange === 'function') {
            window._admHandleDriverAvailabilityChange(e.data);
        }

        var d          = e.data;
        var isEligible = (String(d.status) === '1' && String(d.availability) === '1');

        /* ── 1. Keep window.reqDrivers in sync ──────────────────────────── */
        var drivers = window.reqDrivers || [];
        var idx     = drivers.findIndex(function (dr) { return String(dr.id) === String(d.id); });

        if (e.action === 'created' || e.action === 'updated') {
            var entry = { id: d.id, label: d.name + ' — ' + d.phone };
            if (isEligible) {
                if (idx === -1) drivers.push(entry);
                else            drivers[idx] = entry;
            } else {
                if (idx !== -1) drivers.splice(idx, 1);
            }
        } else if (e.action === 'deleted') {
            if (idx !== -1) drivers.splice(idx, 1);
        }
        window.reqDrivers = drivers;

        /* ── 2. Patch the open dispatch panel's driver select ───────────── */
        /* The select ID is dispDriver_<reqId>; query generically */
        var sel = document.querySelector('[id^="dispDriver_"]');
        if (!sel) return; /* no dispatch panel in DOM (modal closed / non-pending) */

        var savedVal = sel.value; /* preserve current selection */

        if (e.action === 'created' || e.action === 'updated') {
            var existing = sel.querySelector('option[value="' + d.id + '"]');

            if (isEligible) {
                if (existing) {
                    /* Just update the label */
                    existing.textContent = d.name + ' — ' + d.phone;
                } else {
                    /* Insert a new option */
                    var opt      = document.createElement('option');
                    opt.value    = d.id;
                    opt.textContent = d.name + ' — ' + d.phone;
                    sel.appendChild(opt);

                    /* Remove the "No available drivers" disabled placeholder if present */
                    var placeholder = sel.querySelector('option[value=""][disabled]');
                    if (placeholder) placeholder.remove();
                }
            } else {
                /* Driver no longer meets criteria — remove from dropdown */
                if (existing) existing.remove();
            }

        } else if (e.action === 'deleted') {
            var toRemove = sel.querySelector('option[value="' + d.id + '"]');
            if (toRemove) toRemove.remove();
        }

        /* Restore selection when the chosen option still exists */
        if (savedVal && sel.querySelector('option[value="' + savedVal + '"]')) {
            sel.value = savedVal;
        } else {
            sel.value = '';
        }

        /* Re-add the "No available drivers" hint if the list is now empty */
        var validOpts = sel.querySelectorAll('option[value]:not([value=""])');
        if (validOpts.length === 0 && !sel.querySelector('option[value=""][disabled]')) {
            var noneOpt      = document.createElement('option');
            noneOpt.value    = '';
            noneOpt.disabled = true;
            noneOpt.textContent = 'No available drivers found';
            sel.appendChild(noneOpt);
        }
    });

}());
