(function () {
    'use strict';

    /* Requires: window.driDriverId, window.pusher (both set by driver layout) */
    if (!window.driDriverId || !window.pusher) return;
    if (window._rrDriverPortalBound) return;
    window._rrDriverPortalBound = true;

    var driverChannel = window.pusher.subscribe('drivers-update');

    /* ── Personal dispatch channel: receives new requests assigned to this driver ── */
    var driPersonalCh = window.pusher.subscribe('driver.' + window.driDriverId);

    /* ── Personal channel: driver's own status changes (covers dashboard when open in another tab) ── */
    driPersonalCh.bind('emergency-request-status-changed', function (payload) {
        if (!payload || payload.id === undefined) return;

        var action = String(payload.action || '');
        var status = String(payload.status  || '');

        /* Status label/class mirrors dashboard.blade.php $smap */
        var _sLabel = {
            '1':'Pending','2':'Dispatched','3':'En Route','4':'Arrived',
            '5':'Transporting','6':'Completed','7':'Cancelled','8':'Awaiting Acceptance'
        };
        var _sCls = {
            '1':'s1','2':'s2','3':'s3','4':'s4','5':'s5','6':'s6','7':'s7','8':'s8'
        };

        /* Helper: adjust a named stat counter */
        function _adj(id, delta) {
            var el = document.getElementById(id);
            if (el) el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
        }

        /* ── Dashboard history table: update status badge for every non-reject action ── */
        var histRow = document.querySelector(
            '#driHistoryBody tr[data-req-id="' + payload.id + '"]'
        );
        if (histRow && action !== 'reject') {
            var badge = histRow.querySelector('.dri-status-badge');
            if (badge) {
                badge.textContent = _sLabel[status] || status;
                badge.className   = 'dri-status-badge ' + (_sCls[status] || 's1');
            }
        }

        /* ── Reject: remove row, update counters, show empty-state ── */
        if (action === 'reject') {
            if (histRow) {
                histRow.style.transition = 'opacity .35s';
                histRow.style.opacity    = '0';
                setTimeout(function () {
                    if (histRow.parentNode) histRow.remove();
                    /* Show empty-state if no data rows remain */
                    var hBody = document.getElementById('driHistoryBody');
                    if (hBody && !hBody.querySelector('tr:not(.dri-empty-row)')) {
                        var emptyTr = document.createElement('tr');
                        emptyTr.className = 'dri-empty-row';
                        emptyTr.innerHTML =
                            '<td colspan="7">' +
                            '<i class="fa fa-inbox" style="display:block;font-size:1.4rem;' +
                            'margin-bottom:8px;opacity:.2;"></i>No requests yet.</td>';
                        hBody.appendChild(emptyTr);
                    }
                }, 360);
            }
            _adj('statTotal',   -1);
            _adj('statPending', -1);
            _adj('statToday',   -1);

            /* Sidebar nav badge */
            var navBadge = document.getElementById('driReqNavBadge');
            if (navBadge) {
                var nb = parseInt(navBadge.textContent, 10) || 0;
                if (nb > 1) { navBadge.textContent = nb - 1; }
                else        { navBadge.textContent = '0'; navBadge.style.display = 'none'; }
            }
        }

        /* ── Accept: Pending –1, Active +1 ── */
        if (action === 'accept') {
            _adj('statPending', -1);
            _adj('statActive',  +1);
        }

        /* ── Complete: Active –1, Completed +1, nav badge –1 ── */
        if (action === 'complete') {
            _adj('statActive',    -1);
            _adj('statCompleted', +1);

            /* Sidebar nav badge (counts active/pending requests) */
            var navBadge = document.getElementById('driReqNavBadge');
            if (navBadge) {
                var nb = parseInt(navBadge.textContent, 10) || 0;
                if (nb > 1) { navBadge.textContent = nb - 1; }
                else        { navBadge.textContent = '0'; navBadge.style.display = 'none'; }
            }
        }

        /* ── Page-specific hook for extensibility ── */
        if (typeof window._rrOnRequestStatusChanged === 'function') {
            window._rrOnRequestStatusChanged(payload);
        }

        /* ── Past-rides page hook: insert completed ride in real time ── */
        if (action === 'complete' && typeof window._rrOnRideCompleted === 'function') {
            window._rrOnRideCompleted(payload);
        }
    });

    driPersonalCh.bind('emergency-request-dispatched', function (payload) {
        /* Increment the nav badge in the sidebar */
        var badge = document.getElementById('driReqNavBadge');
        if (badge) {
            var cur = parseInt(badge.textContent, 10) || 0;
            badge.textContent = cur + 1;
            badge.style.display = 'inline-flex';
        }

        /* Delegate page-specific UI updates to whichever page defines this hook */
        if (typeof window._rrOnNewRequest === 'function') {
            window._rrOnNewRequest(payload);
        }
    });

    /* ── Personal channel: feedback submitted for this driver's ride ── */
    driPersonalCh.bind('feedback-created', function (payload) {
        if (!payload || !payload.feedback) return;
        if (String(payload.feedback.driver_id) !== String(window.driDriverId)) return;

        if (typeof window._rrOnFeedbackCreated === 'function') {
            window._rrOnFeedbackCreated(payload);
        }
    });
    
    /* ── Personal channel: user typing indicator ── */
    driPersonalCh.bind('ride-chat-typing', function (payload) {
        if (!payload) return;
        if (typeof window._rrOnRideChatTyping === 'function') {
            window._rrOnRideChatTyping(payload);
        }
    });

    /* ── Personal channel: new ride chat message (from user or admin) ── */
    driPersonalCh.bind('ride-chat-message', function (payload) {
        if (!payload) return;

        /* Update the Ride Chat nav badge on every driver page */
        if (typeof payload.total_unread_count !== 'undefined') {
            var badge = document.getElementById('driChatNavBadge');
            if (badge) {
                var n = Math.max(0, Number(payload.total_unread_count) || 0);
                badge.textContent  = n > 99 ? '99+' : n;
                badge.style.display = n > 0 ? 'inline-flex' : 'none';
            }
        }

        /* Delegate page-level UI (list + chat panel) to the ride-chat page */
        if (typeof window._rrOnRideChatMessage === 'function') {
            window._rrOnRideChatMessage(payload);
        }
    });
    
    driverChannel.bind('drivers-update', function (e) {
        /* Only react to events that concern the currently logged-in driver */
        if (!e || !e.data || String(e.data.id) !== String(window.driDriverId)) return;

        /* ── Live GPS position -> update the dashboard's Leaflet map ────── */
        if (e.entity === 'driverLocationUpdated') {
            if (typeof window._rrUpdateDriverMapPosition === 'function') {
                window._rrUpdateDriverMapPosition(e.data.lat, e.data.lng, e.data.accuracy, e.data.heading, e.data.updated_at);
            }
            return;
        }

        if (e.entity !== 'driverAdminUpdated') return;
        if (e.action !== 'created' && e.action !== 'updated') return;

        var d = e.data;

        /* ── Topbar driver name ─────────────────────────────────────────── */
        document.querySelectorAll('.topbar-driver-name').forEach(function (el) {
            if (d.name) el.textContent = d.name;
        });

        document.querySelectorAll('.dash-name').forEach(function (el) {
            if (d.name) el.textContent = d.name;
        });

        /* ── Sidebar footer: name + username ────────────────────────────── */
        var sidebarInfo = document.querySelector('.sidebar-driver-info');
        if (sidebarInfo) {
            var sNameEl = sidebarInfo.querySelector('strong');
            var sUserEl = sidebarInfo.querySelector('small');
            if (sNameEl && d.name)     sNameEl.textContent = d.name;
            if (sUserEl && d.username) sUserEl.textContent = d.username;
        }

        /* ── Profile card (profile page) ────────────────────────────────── */
        var profileName = document.querySelector('.dri-profile-name');
        if (profileName && d.name) profileName.textContent = d.name;

        /* Meta rows (icon-matched) */
        document.querySelectorAll('.dri-profile-meta-row').forEach(function (row) {
            var icon = row.querySelector('i');
            var span = row.querySelector('span');
            if (!icon || !span) return;
            if (icon.classList.contains('fa-at')       && d.username)   span.textContent = d.username;
            if (icon.classList.contains('fa-envelope') && d.email)      span.textContent = d.email;
            if (icon.classList.contains('fa-phone')    && d.phone)      span.textContent = d.phone;
            if (icon.classList.contains('fa-id-badge') && d.license_no) span.textContent = 'License: ' + d.license_no;
        });

        /* Edit-profile form inputs (name + phone are editable by driver) */
        var nameInput = document.querySelector('input[name="name"]');
        if (nameInput && d.name) nameInput.value = d.name;

        var phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput && d.phone) phoneInput.value = d.phone;

        /* Read-only inputs (username, email, license) — matched by label text */
        document.querySelectorAll('.dri-form-input[readonly]').forEach(function (input) {
            var group = input.closest('.dri-form-group');
            if (!group) return;
            var label = group.querySelector('label');
            if (!label) return;
            var lbl = label.textContent.trim().toLowerCase();
            if (lbl === 'username'       && d.username)   input.value = d.username;
            if (lbl === 'email address'  && d.email)      input.value = d.email;
            if (lbl === 'license number' && d.license_no) input.value = d.license_no;
        });

        /* ── Status badges ──────────────────────────────────────────────── */
        if (d.status) {
            var statusLabels = { '1': 'Online', '2': 'Offline', '3': 'Busy' };
            var statusClass  = { '1': 'online',  '2': 'offline',  '3': 'busy' };
            var newLabel = statusLabels[String(d.status)] || d.status;
            var newClass = statusClass[String(d.status)]  || 'offline';

            document.querySelectorAll('.dri-profile-status').forEach(function (badge) {
                ['online', 'offline', 'busy'].forEach(function (c) { badge.classList.remove(c); });
                badge.classList.add(newClass);
                badge.innerHTML =
                    '<span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> ' +
                    newLabel;
            });

            /* ── Dashboard: availability dot ─────────────────────────────── */
            var availDot = document.getElementById('driAvailDot');
            if (availDot) {
                ['online', 'offline', 'busy'].forEach(function (c) { availDot.classList.remove(c); });
                availDot.classList.add(newClass);
            }

            /* ── Dashboard: welcome-text availability line ───────────────── */
            var availText = document.getElementById('driAvailText');
            if (availText) {
                var colorMap = { '1': '#4ade80', '2': '#94a3b8', '3': '#fbbf24' };
                var wordMap  = { '1': 'Online',  '2': 'Offline', '3': 'Busy' };
                var trailer  = String(d.status) === '1' ? ' and available.' : '.';
                availText.innerHTML =
                    'You are currently <span style="color:' +
                    (colorMap[String(d.status)] || '#94a3b8') +
                    ';font-weight:600;">' +
                    (wordMap[String(d.status)]  || 'Unknown') +
                    '</span>' + trailer;
            }

            /* ── Dashboard: Online / Offline toggle buttons ──────────────── */
            var onlineBtn  = document.getElementById('driAvailOnline');
            var offlineBtn = document.getElementById('driAvailOffline');
            if (onlineBtn && offlineBtn) {
                var isBusy = String(d.status) === '3';
                onlineBtn.disabled  = isBusy;
                offlineBtn.disabled = isBusy;
                onlineBtn.classList.toggle('active-online',  String(d.status) === '1');
                onlineBtn.classList.toggle('active-offline', false);
                offlineBtn.classList.toggle('active-offline', String(d.status) === '2');
                offlineBtn.classList.toggle('active-online',  false);
            }
        }
    });

}());
