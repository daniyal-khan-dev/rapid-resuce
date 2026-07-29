function aprBuildParams() {
    var form = document.getElementById('aprFilterForm');
    var filters = {};
    ['search','status','driver_id','ambulance_id','date_filter','date_from','date_to'].forEach(function(n) {
        var el = form.elements[n];
        if (!el) return;
        var v = el.value;
        if (!v || v === '') return;
        if (n === 'date_filter' && v === 'all') return;
        filters[n] = v;
    });
    var params = new URLSearchParams();
    if (Object.keys(filters).length > 0) {
        params.set('q', btoa(JSON.stringify(filters)));
    }
    return params;
}

function aprNavigate() {
    var qs = aprBuildParams().toString();
    history.replaceState(null, '', location.pathname + (qs ? '#' + qs : ''));
    aprFetch(qs);
}

function aprFetch(qs) {
    var url = document.getElementById('aprFilterForm').action + (qs ? '?' + qs : '');
    var tableEl = document.getElementById('aprTableSection');
    if (tableEl) tableEl.style.opacity = '0.45';
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var ns = doc.getElementById('aprStatsSection');
            var nt = doc.getElementById('aprTableSection');
            if (ns && document.getElementById('aprStatsSection'))
                document.getElementById('aprStatsSection').outerHTML = ns.outerHTML;
            if (nt) {
                if (document.getElementById('aprTableSection'))
                    document.getElementById('aprTableSection').outerHTML = nt.outerHTML;
                try {
                    var d = document.getElementById('aprTableSection').getAttribute('data-rides');
                    if (d) _aprData = JSON.parse(d);
                } catch(e) {}
            }
        })
        .catch(function() {
            var t = document.getElementById('aprTableSection');
            if (t) t.style.opacity = '1';
        });
}
    
document.getElementById('aprFilterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    aprNavigate();
});

/* ── Intercept pagination link clicks → AJAX instead of full page reload ──────── */
document.addEventListener('click', function (e) {
    /* Only act on the past rides page */
    if (!document.getElementById('aprFilterForm')) return;
    var link = e.target.closest('.pgd-controls a.pgd-btn');
    if (!link) return;
    e.preventDefault();
    var href = link.getAttribute('href') || '';
    var pageMatch = href.match(/[?&]page=(\d+)/);
    var page = pageMatch ? pageMatch[1] : null;
    var params = aprBuildParams();
    if (page && page !== '1') params.set('page', page);
    aprFetch(params.toString());
});


/* Restore filters from URL hash on page load */
(function() {
    var hash = location.hash;
    if (hash && hash.length > 1) {
        var qs = hash.substring(1);
        var params = new URLSearchParams(qs);
        var q = params.get('q');
        if (q) {
            try {
                var decoded = JSON.parse(atob(q));
                var form = document.getElementById('aprFilterForm');
                Object.keys(decoded).forEach(function(k) {
                    var el = form.elements[k];
                    if (el) el.value = decoded[k];
                });
            } catch(e) {}
        }
        aprFetch(qs);
    }
})();

/* ── Date filter helpers ─────────────────────────────────────────────────────── */
function aprSetDateFilter(val) {
    document.getElementById('aprDateFilterHidden').value = val;
    if (val !== 'custom') {
        document.querySelectorAll('.apr-date-range input[type="date"]').forEach(function(i) {
            i.value = '';
        });
    }
    aprNavigate();
}

function aprToggleCustomRange() {
    var r = document.getElementById('aprCustomRange');
    r.style.display = (r.style.display === 'none') ? 'contents' : 'none';
}

/* ── HTML escape ─────────────────────────────────────────────────────────────── */
function aprEsc(s) {
    return String(s || '—').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
        '&quot;');
}

/* ── View detail modal ───────────────────────────────────────────────────────── */
function aprViewDetail(id) {
    var d = _aprData.find(function(r) {
        return r.id === id;
    });
    if (!d) return;

    var statusHtml = d.status == '6' ?
        '<span class="apr-pill-completed"><i class="fa fa-circle-check me-1"></i>Completed</span>' :
        '<span class="apr-pill-cancelled"><i class="fa fa-ban me-1"></i>Cancelled</span>';
    var typeHtml = d.type == '1' ?
        '<span class="status-pill status-4">Emergency</span>' :
        '<span class="status-pill status-3">Non-Emergency</span>';

    var html = '<div>' +
        '<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;">' +
        typeHtml + ' ' + statusHtml +
        '<span style="font-family:monospace;font-size:.9rem;color:#a5b4fc;margin-left:4px;">' + aprEsc(d.rreb_id) +
        '</span>' +
        '</div>' +
        '<hr class="apr-divider">' +
        '<div style="font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);margin-bottom:12px;">' +
        '<i class="fa fa-map-location-dot me-1" style="color:#818cf8;"></i> Trip Route' +
        '</div>' +
        '<div class="apr-trip-map-wrap">' +
        '<div id="aprTripMap" class="apr-trip-map"></div>' +
        '<div id="aprTripMapLoader" class="apr-trip-map-loader">' +
        '<div style="color:rgba(255,255,255,.35);font-size:.82rem;text-align:center;">' +
        '<i class="fa fa-spinner fa-spin" style="font-size:1.2rem;margin-bottom:8px;display:block;opacity:.5;"></i>Loading route…' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="apr-trip-legend">' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#6366f1;border:2px solid #fff;"></span>Driver start</span>' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ef4444;border:2px solid #fff;"></span>Pickup</span>' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #fff;"></span>Hospital</span>' +
        '<span><span style="display:inline-block;width:26px;height:3px;background:#94a3b8;border-radius:2px;vertical-align:middle;"></span>Completed route</span>' +
        '</div>' +
        '<div id="aprTripMapInfo" class="apr-trip-map-info"></div>'+
        '<hr class="apr-divider">' +
        '<div style="margin-bottom:14px;font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);">Patient Details</div>' +
        '<div class="apr-detail-grid">' +
        '<div class="apr-detail-field"><label>Name</label><span>' + aprEsc(d.user_name) + '</span></div>' +
        '<div class="apr-detail-field"><label>Mobile No.</label><span>' + aprEsc(d.mobile_no) + '</span></div>' +
        '<div class="apr-detail-field"><label>Pickup Address</label><span>' + aprEsc(d
            .pickup_address) + '</span></div>' +
        '<div class="apr-detail-field"><label>Destination Hospital</label><span>' + aprEsc(
            d.hospital_name) + '</span></div>' +
        '</div>'+
        '<hr class="apr-divider">' +
        '<div style="margin-bottom:14px;font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);">Driver Details</div>' +
        '<div class="apr-detail-grid">' +
        '<div class="apr-detail-field"><label>Name</label><span>' + aprEsc(d.driver_name) + '</span></div>' +
        '<div class="apr-detail-field"><label>Phone</label><span>' + aprEsc(d.driver_phone) +
        '</span></div>' +
        '<div class="apr-detail-field"><label>Ambulance</label><span>' + aprEsc(d.ambulance_no) + '</span></div>' +
        '<div class="apr-detail-field"><label>Ambulance Type</label><span>' + aprEsc(d.ambulance_type) +
        '</span></div>' +
        '</div>'

        +
        '<hr class="apr-divider">' +
        '<div style="margin-bottom:14px;font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);">Timeline</div>' +
        '<div class="apr-detail-grid">' +
        '<div class="apr-detail-field"><label>Requested At</label><span>' + aprEsc(d.created_at) + '</span></div>' +
        '<div class="apr-detail-field"><label>Dispatched At</label><span>' + aprEsc(d.dispatched_at) +
        '</span></div>' +
        '<div class="apr-detail-field"><label>' + (d.status == '6' ? 'Completed At' : 'Cancelled At') +
        '</label><span>' + aprEsc(d.completed_at) + '</span></div>' +
        '</div>';

    if (d.notes) {
        html += '<hr class="apr-divider">' +
            '<div class="apr-detail-field"><label>Notes</label>' +
            '<span style="white-space:pre-wrap;">' + aprEsc(d.notes) + '</span></div>';
    }

    html += '</div>';


    document.getElementById('aprModalTitle').innerHTML = '<i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;"></i>Ride — ' + aprEsc(d.rreb_id);
    document.getElementById('aprModalBody').innerHTML = html;

    var aprModalEl = document.getElementById('aprDetailModal');

    // Destroy any previous Leaflet instance before opening new modal
    if (_aprTripMapInst) {
        try {
            _aprTripMapInst.remove();
        } catch (ex) {}
        _aprTripMapInst = null;
    }

    function _onAprShown() {
        aprModalEl.removeEventListener('shown.bs.modal', _onAprShown);
        _aprTripMapInst = _aprInitTripMap('aprTripMap', 'aprTripMapLoader', 'aprTripMapInfo', d);
    }
    aprModalEl.addEventListener('shown.bs.modal', _onAprShown);

    bootstrap.Modal.getOrCreateInstance(aprModalEl).show();
}

/* ── Static trip route map ───────────────────────────────────────────────────── */
var _aprTripMapInst = null;

function _aprInitTripMap(mapId, loaderId, infoId, ride) {
    var mapEl = document.getElementById(mapId);
    var loader = document.getElementById(loaderId);
    var infoEl = document.getElementById(infoId);
    if (!mapEl || typeof L === 'undefined') return null;

    var accLat = parseFloat(ride.accepted_lat);
    var accLng = parseFloat(ride.accepted_lng);
    var pickLat = parseFloat(ride.pickup_lat);
    var pickLng = parseFloat(ride.pickup_lng);
    var hospLat = parseFloat(ride.hospital_lat);
    var hospLng = parseFloat(ride.hospital_lng);

    var hasAcc = !isNaN(accLat) && !isNaN(accLng);
    var hasPick = !isNaN(pickLat) && !isNaN(pickLng);
    var hasHosp = !isNaN(hospLat) && !isNaN(hospLng);

    if (!hasAcc && !hasPick && !hasHosp) {
        mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;' +
            'color:rgba(255,255,255,.2);font-size:.82rem;">' +
            '<span><i class="fa fa-map-location-dot" style="margin-right:6px;opacity:.3;"></i>No location data saved for this ride.</span></div>';
        if (loader) loader.style.display = 'none';
        return null;
    }

    var map = L.map(mapEl, {
        zoomControl: true,
        attributionControl: false
    });
    // OpenStreetMap (standard bright map)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    function _mkIcon(bg, svgPath) {
        return L.divIcon({
            className: '',
            html: '<div style="background:' + bg + ';border:3px solid #fff;border-radius:50%;' +
                'width:34px;height:34px;box-shadow:0 2px 12px rgba(0,0,0,.5);' +
                'display:flex;align-items:center;justify-content:center;">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="15" height="15">' +
                svgPath + '</svg></div>',
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -20],
        });
    }

    var _svgAmb =
        '<path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>';
    var _svgPrsn = '<circle cx="12" cy="7" r="4"/><path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>';
    var _svgHosp =
        '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 3a1 1 0 0 1 1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>';

    var bounds = [];
    if (hasAcc) {
        L.marker([accLat, accLng], {
                icon: _mkIcon('#6366f1', _svgAmb)
            })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:140px;"><b style="color:#6366f1;">🚑 Driver Start</b><br><small style="color:#9ca3af;">Accepted ride from here</small></div>'
                )
            .addTo(map);
        bounds.push([accLat, accLng]);
    }
    if (hasPick) {
        L.marker([pickLat, pickLng], {
                icon: _mkIcon('#ef4444', _svgPrsn)
            })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:140px;"><b style="color:#ef4444;">📍 Pickup</b><br><small style="color:#9ca3af;">' +
                aprEsc(ride.pickup_address || '') + '</small></div>')
            .addTo(map);
        bounds.push([pickLat, pickLng]);
    }
    if (hasHosp) {
        L.marker([hospLat, hospLng], {
                icon: _mkIcon('#22c55e', _svgHosp)
            })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:140px;"><b style="color:#22c55e;">🏥 Hospital</b><br><small style="color:#9ca3af;">' +
                aprEsc(ride.hospital_name || '') + '</small></div>')
            .addTo(map);
        bounds.push([hospLat, hospLng]);
    }

    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, {
            padding: [50, 50]
        });
    }

    if (loader) loader.style.display = 'none';

    // Fetch grey completed route from OSRM (static — no live tracking)
    var wps = [];
    if (hasAcc) wps.push(accLng + ',' + accLat);
    if (hasPick) wps.push(pickLng + ',' + pickLat);
    if (hasHosp) wps.push(hospLng + ',' + hospLat);

    if (wps.length >= 2) {
        fetch('https://router.project-osrm.org/route/v1/driving/' + wps.join(';') +
                '?overview=full&geometries=geojson')
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (!data.routes || !data.routes[0]) return;
                var route = data.routes[0];
                var coords = route.geometry.coordinates.map(function(c) {
                    return [c[1], c[0]];
                });
                L.polyline(coords, {
                    color: '#94a3b8',
                    weight: 4,
                    opacity: 0.85,
                    lineJoin: 'round'
                }).addTo(map);
                map.fitBounds(coords, {
                    padding: [40, 40]
                });
                if (infoEl) {
                    var km = (route.distance / 1000).toFixed(1);
                    var mins = Math.round(route.duration / 60);
                    infoEl.innerHTML =
                        '<span><i class="fa fa-route" style="color:#818cf8;margin-right:4px;"></i>' + km +
                        ' km total route</span>' +
                        (mins > 0 ?
                            '<span><i class="fa fa-clock" style="color:#60a5fa;margin-right:4px;"></i>~' +
                            mins + ' min estimated drive</span>' : '');
                }
            })
            .catch(function() {});
    }

    return map;
}

/* ── Real-time: insert a newly completed ride into the Past Rides grid ────────
   Reuses window.channel (admin-dashboard) — no new subscription.
   Only active on the past rides page; guards against duplicates.             */
(function () {
    if (!document.getElementById('aprFilterForm')) return;
    if (!window.channel) return;
    if (window._rrPastRidesBound) return;
    window._rrPastRidesBound = true;

    var _seenIds = new Set();

    function _esc(s) {
        var d = document.createElement('div');
        d.textContent = (s == null) ? '' : String(s);
        return d.innerHTML;
    }

    function _trunc(s, n) {
        if (!s) return '\u2014';
        s = String(s);
        return s.length > n ? s.slice(0, n) + '\u2026' : s;
    }

    function _buildRow(p) {
        var typeHtml = String(p.type) === '1'
            ? '<span class="status-pill status-4">Emergency</span>'
            : '<span class="status-pill status-3">Non-Emergency</span>';
        /* completed_at may be 'd M Y' (user cancel) or 'd M Y, H:i A' (driver complete) */
        var completedDate = p.completed_at ? String(p.completed_at).split(',')[0] : '\u2014';
        var statusPill = String(p.status) === '7'
            ? '<span class="apr-pill-cancelled"><i class="fa fa-ban me-1"></i>Cancelled</span>'
            : '<span class="apr-pill-completed"><i class="fa fa-circle-check me-1"></i>Completed</span>';
        return '<tr data-ride-id="' + _esc(p.id) + '">' +
            '<td class="fs-xs"><span style="font-family:monospace;font-size:.8rem;' +
                'background:rgba(129,140,248,.12);padding:3px 8px;border-radius:6px;' +
                'color:#a5b4fc;white-space:nowrap;">' + _esc(p.rreb_id || '\u2014') + '</span></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);"><div>' + _esc(p.user_name || 'Guest') + '</div></td>' +
            '<td>' + typeHtml + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(_trunc(p.hospital_name, 22)) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(_trunc(p.pickup_address, 22)) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);"><div>' + _esc(p.driver_name || '\u2014') + '</div></td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + _esc(p.ambulance_no || '\u2014') + '</td>' +
            '<td class="fs-xs">' + statusPill + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);white-space:nowrap;">' + _esc(completedDate) + '</td>' +
            '<td><button class="btn-adm-icon" title="View Details" ' +
                'onclick="aprViewDetail(' + Number(p.id) + ')">' +
                '<i class="fa fa-eye"></i></button></td>' +
        '</tr>';
    }

    window.channel.bind('emergency-request-status-changed', function (payload) {
        if (!payload || (payload.action !== 'complete' && payload.action !== 'cancel')) return;
        var id = payload.id;
        if (id === undefined || id === null) return;
        if (_seenIds.has(id)) return;
        _seenIds.add(id);

        /* Increment the Total and the appropriate Completed / Cancelled stat card */
        var statElTotal = document.getElementById('aprStatTotal');
        if (statElTotal) statElTotal.textContent = (parseInt(statElTotal.textContent, 10) || 0) + 1;
        if (String(payload.status) === '7') {
            var statElCancelled = document.getElementById('aprStatCancelled');
            if (statElCancelled) statElCancelled.textContent = (parseInt(statElCancelled.textContent, 10) || 0) + 1;
        } else {
            var statElCompleted = document.getElementById('aprStatCompleted');
            if (statElCompleted) statElCompleted.textContent = (parseInt(statElCompleted.textContent, 10) || 0) + 1;
        }

        /* Only splice into the grid when on page 1 with no active filters.
           Determine page 1 by checking whether the Prev button is disabled
           (server renders it as <button disabled> on page 1, <a> on later pages). */
        var prevEl  = document.querySelector('#aprTableSection .pgd-controls > *:first-child');
        var onPage1 = prevEl && prevEl.tagName === 'BUTTON' && prevEl.disabled;
        var noFilter = aprBuildParams().toString() === '';
        if (!onPage1 || !noFilter) return;

        var tbody = document.getElementById('aprTbody');
        if (!tbody) return;

        /* Never insert a duplicate row */
        if (tbody.querySelector('tr[data-ride-id="' + id + '"]')) return;

        /* Prepend — rides are sorted latest-first */
        tbody.insertAdjacentHTML('afterbegin', _buildRow(payload));

        /* Register in _aprData so the detail modal can find this record.
           Shape must match what $_aprPageData produces in the Blade view. */
        if (typeof _aprData !== 'undefined' && Array.isArray(_aprData)) {
            _aprData.unshift({
                id:             Number(payload.id),
                rreb_id:        payload.rreb_id        || '',
                type:           payload.type,
                status:         payload.status         || '6',
                pickup_address: payload.pickup_address || '',
                hospital_name:  payload.hospital_name  || '',
                mobile_no:      payload.mobile_no      || '',
                user_name:      payload.user_name      || 'Guest',
                ambulance_no:   payload.ambulance_no   || '',
                ambulance_type: payload.ambulance_type || null,
                driver_name:    payload.driver_name    || '',
                driver_phone:   payload.driver_phone   || '',
                notes:          payload.notes          || '',
                completed_at:   payload.completed_at   || '',
                dispatched_at:  payload.dispatched_at  || '',
                created_at:     payload.created_at     || '',
                accepted_lat:   payload.accepted_lat   != null ? payload.accepted_lat : null,
                accepted_lng:   payload.accepted_lng   != null ? payload.accepted_lng : null,
                pickup_lat:     payload.pickup_lat     != null ? payload.pickup_lat    : null,
                pickup_lng:     payload.pickup_lng     != null ? payload.pickup_lng    : null,
                hospital_lat:   payload.hospital_lat   != null ? payload.hospital_lat  : null,
                hospital_lng:   payload.hospital_lng   != null ? payload.hospital_lng  : null,
            });
        }

        /* Reveal table + footer and hide empty state if this was the first ride */
        var section = document.getElementById('aprTableSection');
        if (section) {
            var scroll = section.querySelector('.pgd-scroll');
            var footer = section.querySelector('.pgd-footer');
            var empty  = section.querySelector('.adm-empty');
            if (scroll) scroll.style.display = '';
            if (footer) footer.style.display = '';
            if (empty)  empty.style.display  = 'none';
        }

        /* Update the Showing X–Y of Z footer text */
        var infoEl = document.querySelector('#aprTableSection .pgd-info');
        if (infoEl) {
            var rowCount = tbody.querySelectorAll('tr[data-ride-id]').length;
            infoEl.textContent = 'Showing 1\u2013' + rowCount + ' of ' + rowCount + ' rides';
        }
    });
}());