/**
 * My Bookings — real-time status sync via existing Reverb/Pusher connection.
 *
 * For each booking row rendered in the My Bookings table we subscribe once to
 * the public `emergency.{id}` channel (same channel the Tracking page uses)
 * and patch the status badge in-place when the driver changes the ride status.
 *
 * Rules:
 *  - Reuses window.pusher — never creates a second connection.
 *  - Subscribes only once per page load (guard flag).
 *  - No polling, no AJAX, no setInterval.
 *  - Touches only the badge span and the row data-status attribute.
 */
(function () {
    'use strict';

    if (window.__rrBkRealtimeBound) return;
    window.__rrBkRealtimeBound = true;

    /* ── Status label & badge-style maps (mirror profile.blade.php $sBadge) ── */
    var STATUS_LABELS = {
        '1': 'Pending',
        '2': 'Dispatched',
        '3': 'On Way',
        '4': 'Arrived',
        '5': 'Transporting',
        '6': 'Completed',
        '7': 'Cancelled',
        '8': 'Pending',
    };

    var STATUS_STYLES = {
        '1': 'background:rgba(245,158,11,0.12);color:#b45309;',
        '2': 'background:rgba(59,130,246,0.12);color:#1d4ed8;',
        '3': 'background:rgba(139,92,246,0.12);color:#6d28d9;',
        '4': 'background:rgba(20,184,166,0.12);color:#0f766e;',
        '5': 'background:rgba(249,115,22,0.12);color:#c2410c;',
        '6': 'background:rgba(34,197,94,0.12);color:#166534;',
        '7': 'background:rgba(107,114,128,0.12);color:#374151;',
        '8': 'background:rgba(245,158,11,0.12);color:#b45309;',
    };

    var DEFAULT_STYLE = 'background:#f3f4f6;color:#374151;';

    /* ── Patch one booking row ── */
    function applyUpdate(reqId, status) {
        var s     = String(status);
        var badge = document.getElementById('rrBkBadge_' + reqId);
        var row   = document.getElementById('rrBkRow_'   + reqId);

        if (badge) {
            /* Preserve the fixed layout styles already on the element; only
               replace the colour-related portion that was inlined by Blade. */
            badge.style.cssText =
                'padding:3px 10px;border-radius:20px;font-size:0.73rem;font-weight:700;' +
                (STATUS_STYLES[s] || DEFAULT_STYLE);
            badge.textContent = STATUS_LABELS[s] || s;
        }

        if (row) {
            row.setAttribute('data-status', s);
        }
    }

    /* ── Subscribe to all booking rows present in the DOM ── */
    function bindBookings() {
        if (!window.pusher) { setTimeout(bindBookings, 200); return; }

        var rows = document.querySelectorAll('[id^="rrBkRow_"]');
        if (!rows.length) return; // not on My Bookings tab or no bookings

        rows.forEach(function (row) {
            /* Extract numeric ID from element id "rrBkRow_123" */
            var reqId = row.id.replace('rrBkRow_', '');
            if (!reqId) return;

            var channelName = 'emergency.' + reqId;

            /* Pusher deduplicates subscriptions to the same channel name,
               so calling subscribe() again is a safe no-op if already done
               (e.g. when both the Tracking page and My Bookings are open). */
            var ch = window.pusher.subscribe(channelName);

            ch.bind('emergency-request-status-changed', function (data) {
                if (String(data.id) === String(reqId)) {
                    applyUpdate(reqId, data.status);
                }
            });

            ch.bind('emergency-request-deleted', function (data) {
                if (String(data.id) === String(reqId)) {
                    removeBookingRow(reqId);
                }
            });
        });
    }

    /* ── Remove a deleted booking row and resequence Sr. No. ── */
    function removeBookingRow(reqId) {
        var row = document.getElementById('rrBkRow_' + reqId);
        if (!row) return;

        /* Fade the row out, then remove it from the DOM */
        row.style.transition = 'opacity .35s, transform .35s';
        row.style.opacity    = '0';
        row.style.transform  = 'translateX(12px)';

        setTimeout(function () {
            if (row.parentNode) row.remove();

            /* Re-number remaining rows — first <td> holds the Sr. No. */
            var remaining = document.querySelectorAll('[id^="rrBkRow_"]');
            remaining.forEach(function (r, idx) {
                var firstTd = r.querySelector('td');
                if (firstTd) firstTd.textContent = idx + 1;
            });

            /* If no rows remain, hide the table wrapper and reveal the empty state.
               Both elements are always present in the DOM (stable IDs from Blade). */
            if (remaining.length === 0) {
                var tableWrap = document.getElementById('rrBkTableWrap');
                var emptyState = document.getElementById('rrBkEmptyState');
                if (tableWrap)  tableWrap.style.display  = 'none';
                if (emptyState) emptyState.style.display = '';
            }
        }, 370);
    }


    bindBookings();

    /* ════════════════════════════════════════════════════════════════
     * NEW-BOOKING REAL-TIME INSERTION
     * Subscribe to the private per-user channel so that when this same
     * user submits a new emergency request in another tab, the new row
     * appears here immediately — no page reload, no polling.
     * ════════════════════════════════════════════════════════════════ */

    var STATUS_BADGE_INLINE = {
        '1': 'background:rgba(245,158,11,0.12);color:#b45309;',
        '2': 'background:rgba(59,130,246,0.12);color:#1d4ed8;',
        '3': 'background:rgba(139,92,246,0.12);color:#6d28d9;',
        '4': 'background:rgba(20,184,166,0.12);color:#0f766e;',
        '5': 'background:rgba(249,115,22,0.12);color:#c2410c;',
        '6': 'background:rgba(34,197,94,0.12);color:#166534;',
        '7': 'background:rgba(107,114,128,0.12);color:#374151;',
        '8': 'background:rgba(245,158,11,0.12);color:#b45309;',
    };

    /* Build a <tr> that matches the Blade output exactly */
    function buildBookingRow(data, srNo) {
        var s      = String(data.status || '1');
        var typeLabel = (String(data.type) === '1') ? 'Emergency' : 'non-emergency';
        var badgeStyle = STATUS_BADGE_INLINE[s] ||
                         'background:#f3f4f6;color:#374151;';
        var statusLabel = STATUS_LABELS[s] || 'Pending';

        /* Escape user-supplied text to prevent XSS */
        function esc(str) {
            return String(str || '')
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        var tr = document.createElement('tr');
        tr.id             = 'rrBkRow_' + data.id;
        tr.setAttribute('data-status', s);
        tr.style.cssText  = 'border-bottom:1px solid var(--rr-border);';

        tr.innerHTML =
            '<td style="padding:12px 14px;color:var(--rr-text-muted);">' + srNo + '</td>' +

            '<td style="padding:12px 14px;">' +
                '<span style="padding:3px 9px;border-radius:20px;font-size:0.73rem;font-weight:700;' +
                      'background:rgba(215,44,66,0.1);color:var(--rr-primary);">' +
                    esc(typeLabel) +
                '</span>' +
            '</td>' +

            '<td style="padding:12px 14px;max-width:180px;white-space:nowrap;' +
                       'overflow:hidden;text-overflow:ellipsis;">' +
                esc(data.pickup_address) +
            '</td>' +

            '<td style="padding:12px 14px;">' +
                '<span id="rrBkBadge_' + data.id + '" ' +
                      'style="padding:3px 10px;border-radius:20px;font-size:0.73rem;font-weight:700;' +
                             badgeStyle + '">' +
                    esc(statusLabel) +
                '</span>' +
            '</td>' +

            '<td style="padding:12px 14px;color:var(--rr-text-muted);white-space:nowrap;">' +
                esc(data.created_at) +
            '</td>' +

            '<td style="padding:12px 14px;">' +
                '<a href="' + esc(data.tracking_url) + '" ' +
                   'class="rr-btn rr-btn--primary" ' +
                   'style="padding:5px 12px;font-size:0.78rem;">' +
                    '<i class="fa fa-map-location-dot"></i> Track' +
                '</a>' +
            '</td>';

        return tr;
    }

    /* Re-number every Sr. No. cell sequentially (first <td> of each row) */
    function resequenceSerialNumbers() {
        var rows = document.querySelectorAll('[id^="rrBkRow_"]');
        rows.forEach(function (r, idx) {
            var firstTd = r.querySelector('td');
            if (firstTd) firstTd.textContent = idx + 1;
        });
    }

    /* Subscribe the newly inserted row to its per-request status channel */
    function bindNewRow(reqId) {
        if (!window.pusher) return;
        var channelName = 'emergency.' + reqId;
        var ch = window.pusher.subscribe(channelName);

        ch.bind('emergency-request-status-changed', function (data) {
            if (String(data.id) === String(reqId)) {
                applyUpdate(reqId, data.status);
            }
        });

        ch.bind('emergency-request-deleted', function (data) {
            if (String(data.id) === String(reqId)) {
                removeBookingRow(reqId);
            }
        });
    }

    /* Insert a new booking row into the table in real time */
    function insertNewBooking(data) {
        /* Guard: ignore if this row already exists (duplicate delivery) */
        if (document.getElementById('rrBkRow_' + data.id)) return;

        var tableWrap  = document.getElementById('rrBkTableWrap');
        var emptyState = document.getElementById('rrBkEmptyState');
        var tbody      = tableWrap ? tableWrap.querySelector('tbody') : null;
        if (!tbody) return;

        /* Show table, hide empty state if needed */
        if (emptyState && emptyState.style.display !== 'none') {
            emptyState.style.display = 'none';
        }
        if (tableWrap && tableWrap.style.display === 'none') {
            tableWrap.style.display = '';
        }

        /* Prepend (latest first) with Sr. No. = 1; existing rows shift down */
        var tr = buildBookingRow(data, 1);
        tbody.insertBefore(tr, tbody.firstChild);

        /* Re-number all rows after insertion */
        resequenceSerialNumbers();

        /* Briefly highlight the new row so the user notices it */
        tr.style.transition    = 'background .15s';
        tr.style.background    = 'rgba(215,44,66,0.07)';
        setTimeout(function () { tr.style.background = ''; }, 1400);

        /* Subscribe the new row to future driver status updates */
        bindNewRow(data.id);
    }

    /* Subscribe to the private per-user channel once pusher is ready */
    function bindNewBookingChannel() {
        if (!window.pusher) { setTimeout(bindNewBookingChannel, 200); return; }

        /* Read user ID from the data attribute on the table wrapper */
        var tableWrap = document.getElementById('rrBkTableWrap');
        if (!tableWrap) return; // not on the My Bookings page

        var userId = tableWrap.getAttribute('data-user-id');
        if (!userId) return;

        var channelName = 'private-user.bookings.' + userId;

        /* pusher.subscribe() is idempotent — safe to call even if already
           subscribed (e.g. back-navigation without a full page reload). */
        var ch = window.pusher.subscribe(channelName);

        ch.bind('new-booking-created', function (data) {
            insertNewBooking(data);
        });
    }

    bindNewBookingChannel();
})();
