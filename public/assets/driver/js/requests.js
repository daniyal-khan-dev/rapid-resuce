/* ─────────────────────────────────────────────────────────────────────────────
   Driver — Ride Detail View  (requests.js)
   Implements viewRequestDetail(id), live map, and status action panel.
───────────────────────────────────────────────────────────────────────────── */

/* ── Module state ─────────────────────────────────────────────────────────── */
var _driReqMap          = null;   // Leaflet map instance in the detail modal
var _driReqDriverMarker = null;   // Marker representing the driver (self)
var _driDriverToPickup  = null;   // (legacy ref, superseded by split layers below)
var _driPickupToHosp    = null;   // Green-dashed polyline: pickup → hospital
var _driCurrentReqData  = null;   // Request object currently displayed
var _driOpenReqId       = null;   // ID of the open request
var _driModalInst       = null;   // Bootstrap modal instance
var _driFullRouteCoords = null;   // Full OSRM coord array for driver→pickup (cached; no re-fetch on GPS update)
var _driRouteCompleted  = null;   // Grey polyline: portion already traveled
var _driRouteRemaining  = null;   // Blue polyline: portion still to travel

/* ── Helpers ─────────────────────────────────────────────────────────────── */
function _driEsc(s) {
    var d = document.createElement('div');
    d.textContent = (s === null || s === undefined) ? '' : String(s);
    return d.innerHTML;
}

function _driStatusLabel(s) {
    return {
        '1': 'Pending', '2': 'Dispatched', '3': 'En Route',
        '4': 'Arrived', '5': 'Transporting', '6': 'Completed',
        '7': 'Cancelled', '8': 'Awaiting Acceptance',
    }[String(s)] || String(s);
}

function _driStatusClass(s) {
    return {
        '1': 's1', '2': 's2', '3': 's3', '4': 's4',
        '5': 's5', '6': 's6', '7': 's7', '8': 's8',
    }[String(s)] || 's1';
}

function _driCsrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
}

/* ── Map icon factory ────────────────────────────────────────────────────── */
function _driMkIcon(bg, svgPath, size) {
    return L.divIcon({
        className: 'rm-div-icon',
        html: '<div style="background:' + bg + ';border:3px solid #fff;border-radius:50%;' +
              'width:' + size + 'px;height:' + size + 'px;' +
              'box-shadow:0 2px 12px rgba(0,0,0,.5);' +
              'display:flex;align-items:center;justify-content:center;">' +
              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" ' +
              'width="' + Math.round(size * 0.47) + '" height="' + Math.round(size * 0.47) + '">' +
              svgPath + '</svg></div>',
        iconSize:    [size, size],
        iconAnchor:  [size / 2, size / 2],
        popupAnchor: [0, -size / 2],
    });
}

var _SVG_AMB   = '<path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13v5a1 1 0 0 1-1 1h-1a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H4a1 1 0 0 1-1-1v-5z"/><circle cx="7.5" cy="18" r="1.6"/><circle cx="16.5" cy="18" r="1.6"/>';
var _SVG_PRSN  = '<circle cx="12" cy="7" r="4"/><path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>';
var _SVG_HOSP  = '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 3a1 1 0 0 1 1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>';

function _driIconDriver()   { return _driMkIcon('#2563eb', _SVG_AMB,  38); }
function _driIconPickup()   { return _driMkIcon('#ef4444', _SVG_PRSN, 34); }
function _driIconHospital() { return _driMkIcon('#16a34a', _SVG_HOSP, 34); }

/* ── Route drawing (OSRM) ────────────────────────────────────────────────── */

/**
 * Generic polyline fetcher — used for the Pickup→Hospital leg only.
 * Returns the raw [lat,lng] coordinate array via onDone so callers can
 * decide whether to draw a single layer or split it.
 */
function _driFetchPolyline(fromLng, fromLat, toLng, toLat, color, dashArray, onDone) {
    var url = 'https://router.project-osrm.org/route/v1/driving/' +
        fromLng + ',' + fromLat + ';' + toLng + ',' + toLat +
        '?overview=full&geometries=geojson';

    fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (d) {
            if (!d.routes || !d.routes[0] || !_driReqMap) return;
            var coords = d.routes[0].geometry.coordinates.map(function (c) {
                return [c[1], c[0]];
            });
            var poly = L.polyline(coords, {
                color: color, weight: 5, opacity: 0.85,
                lineJoin: 'round', dashArray: dashArray || null,
            }).addTo(_driReqMap);
            if (onDone) onDone(poly);
        })
        .catch(function () {});
}

/**
 * Fetch OSRM route coords for the Driver→Pickup leg and cache them.
 * Only called once per modal open; subsequent GPS updates re-use the cache.
 */
function _driFetchRouteCoords(fromLng, fromLat, toLng, toLat, onDone) {
    var url = 'https://router.project-osrm.org/route/v1/driving/' +
        fromLng + ',' + fromLat + ';' + toLng + ',' + toLat +
        '?overview=full&geometries=geojson';

    fetch(url)
        .then(function (res) { return res.json(); })
        .then(function (d) {
            if (!d.routes || !d.routes[0] || !_driReqMap) return;
            var coords = d.routes[0].geometry.coordinates.map(function (c) {
                return [c[1], c[0]];
            });
            if (onDone) onDone(coords);
        })
        .catch(function () {});
}

/**
 * Return the index of the vertex in coords[] closest to (lat, lng).
 * Uses squared distance — no need for sqrt since we only compare magnitudes.
 */
function _driNearestVertexIndex(coords, lat, lng) {
    var minDist = Infinity, idx = 0;
    for (var i = 0; i < coords.length; i++) {
        var dlat = coords[i][0] - lat;
        var dlng = coords[i][1] - lng;
        var dist = dlat * dlat + dlng * dlng;
        if (dist < minDist) { minDist = dist; idx = i; }
    }
    return idx;
}

/**
 * Split _driFullRouteCoords at the vertex nearest to the driver and
 * redraw the two segments in-place (no new OSRM request).
 *   completed [0..nearestIdx]  → grey   (#9ca3af)
 *   remaining [nearestIdx..end] → blue   (#2563eb)
 */
function _driRedrawRouteSplit(driverLat, driverLng) {
    if (!_driReqMap || !_driFullRouteCoords || _driFullRouteCoords.length < 2) return;

    var nearestIdx = _driNearestVertexIndex(_driFullRouteCoords, driverLat, driverLng);
    var completed  = _driFullRouteCoords.slice(0, nearestIdx + 1);
    var remaining  = _driFullRouteCoords.slice(nearestIdx);

    // Remove old segments (set/remove in one pass — no flicker)
    if (_driRouteCompleted) {
        try { _driReqMap.removeLayer(_driRouteCompleted); } catch (e) {}
        _driRouteCompleted = null;
    }
    if (_driRouteRemaining) {
        try { _driReqMap.removeLayer(_driRouteRemaining); } catch (e) {}
        _driRouteRemaining = null;
    }

    // Grey: already traveled
    if (completed.length >= 2) {
        _driRouteCompleted = L.polyline(completed, {
            color: '#9ca3af', weight: 5, opacity: 0.7, lineJoin: 'round',
        }).addTo(_driReqMap);
    }

    // Blue: still to travel
    if (remaining.length >= 2) {
        _driRouteRemaining = L.polyline(remaining, {
            color: '#2563eb', weight: 5, opacity: 0.85, lineJoin: 'round',
        }).addTo(_driReqMap);
    }
}

function _driDrawRoutes(driverLat, driverLng) {
    if (!_driReqMap || !_driCurrentReqData) return;
    var req     = _driCurrentReqData;
    var pickLat = req.pickup_lat,  pickLng = req.pickup_lng;
    var hospLat = req.hospital_lat, hospLng = req.hospital_lng;

    // Driver → Pickup: split into completed (grey) + remaining (blue)
    if (driverLat && driverLng && pickLat && pickLng) {
        if (_driFullRouteCoords) {
            // Route already cached — re-split only, no OSRM request
            _driRedrawRouteSplit(driverLat, driverLng);
        } else {
            // First call this modal open: fetch once, cache, then split
            _driFetchRouteCoords(driverLng, driverLat, pickLng, pickLat, function (coords) {
                _driFullRouteCoords = coords;
                _driRedrawRouteSplit(driverLat, driverLng);
            });
        }
    }

    // Green dashed: Pickup → Hospital (drawn once per modal open, never re-fetched)
    if (!_driPickupToHosp && pickLat && pickLng && hospLat && hospLng) {
        _driFetchPolyline(pickLng, pickLat, hospLng, hospLat, '#16a34a', '10,8', function (poly) {
            _driPickupToHosp = poly;
        });
    }
}

/* ── Map init ────────────────────────────────────────────────────────────── */
function _driInitMap(mapId, req, driverLat, driverLng) {
    if (typeof L === 'undefined') return;

    var mapEl = document.getElementById(mapId);
    if (!mapEl) return;

    var hasPick   = !!(req.pickup_lat  && req.pickup_lng);
    var hasHosp   = !!(req.hospital_lat && req.hospital_lng);
    var hasDriver = !!(driverLat && driverLng);

    if (!hasPick && !hasDriver) return;

    var center = hasPick ? [req.pickup_lat, req.pickup_lng] : [driverLat, driverLng];

    _driReqMap = L.map(mapEl, { zoomControl: true, attributionControl: false })
                  .setView(center, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors',
    }).addTo(_driReqMap);

    var bounds = [];

    // Driver marker
    if (hasDriver) {
        _driReqDriverMarker = L.marker([driverLat, driverLng], { icon: _driIconDriver() })
            .bindPopup('<div style="font-size:.82rem;min-width:130px;"><b style="color:#2563eb;">🚑 Your Position</b><br>' +
                '<small style="color:#9ca3af;">' + driverLat.toFixed(5) + ', ' + driverLng.toFixed(5) + '</small></div>')
            .addTo(_driReqMap);
        bounds.push([driverLat, driverLng]);
    }

    // Pickup marker
    if (hasPick) {
        L.marker([req.pickup_lat, req.pickup_lng], { icon: _driIconPickup() })
            .bindPopup('<div style="font-size:.82rem;min-width:130px;"><b style="color:#ef4444;">📍 Pickup</b><br>' +
                '<small style="color:#9ca3af;">' + _driEsc(req.pickup_address || '') + '</small></div>')
            .addTo(_driReqMap);
        bounds.push([req.pickup_lat, req.pickup_lng]);
    }

    // Hospital marker
    if (hasHosp) {
        L.marker([req.hospital_lat, req.hospital_lng], { icon: _driIconHospital() })
            .bindPopup('<div style="font-size:.82rem;min-width:130px;"><b style="color:#16a34a;">🏥 Hospital</b><br>' +
                '<small style="color:#9ca3af;">' + _driEsc(req.hospital_name || '') + '</small></div>')
            .addTo(_driReqMap);
        bounds.push([req.hospital_lat, req.hospital_lng]);
    }

    if (bounds.length > 1)      _driReqMap.fitBounds(bounds, { padding: [40, 40] });
    else if (bounds.length === 1) _driReqMap.setView(bounds[0], 15);

    _driReqMap.invalidateSize();

    // Draw routes after map settles
    setTimeout(function () { _driDrawRoutes(driverLat, driverLng); }, 400);
}

/* ── Action panel HTML ───────────────────────────────────────────────────── */
function _driBuildActionButtons(reqId, status) {
    var s    = String(status);
    var btns = '';

    if (s === '8') {
        btns += '<button class="req-act-btn enroute" onclick="_driDoAction(' + reqId + ',\'accept\')">' +
                '<i class="fa fa-check"></i> Accept</button>';
        btns += '<button class="req-act-btn cancel" onclick="_driShowRejectModal(' + reqId + ')" style="margin-left:auto;">' +
                '<i class="fa fa-xmark"></i> Reject</button>';
    } else if (s === '2') {
        /* Cancel is intentionally omitted at status 2 (post-accept) per UX policy */
        btns += '<button class="req-act-btn enroute" onclick="_driDoAction(' + reqId + ',\'en_route\')">' +
                '<i class="fa fa-route"></i> En Route to Pickup</button>';
    } else if (s === '3') {
        btns += '<button class="req-act-btn arrived" onclick="_driDoAction(' + reqId + ',\'arrived\')">' +
                '<i class="fa fa-map-pin"></i> Arrived at Pickup</button>';
        btns += '<button class="req-act-btn cancel" onclick="_driDoAction(' + reqId + ',\'cancel\')" style="margin-left:auto;">' +
                '<i class="fa fa-ban"></i> Cancel Ride</button>';
    } else if (s === '4') {
        btns += '<button class="req-act-btn transport" onclick="_driDoAction(' + reqId + ',\'transporting\')">' +
                '<i class="fa fa-person-walking-arrow-right"></i> Transporting Patient</button>';
        btns += '<button class="req-act-btn cancel" onclick="_driDoAction(' + reqId + ',\'cancel\')" style="margin-left:auto;">' +
                '<i class="fa fa-ban"></i> Cancel Ride</button>';
    } else if (s === '5') {
        btns += '<button class="req-act-btn complete" onclick="_driDoAction(' + reqId + ',\'complete\')">' +
                '<i class="fa fa-circle-check"></i> Complete Ride</button>';
    }

    return btns || '<span style="font-size:.8rem;color:rgba(255,255,255,.3);">No actions available for this status.</span>';
}

function _driBuildActionPanel(reqId, status) {
    var s = String(status);
    if (!['2', '3', '4', '5', '8'].includes(s)) return '';

    return '<div class="req-action-panel" id="driActionPanel_' + reqId + '">' +
        '<div class="req-action-panel__title"><i class="fa fa-sliders"></i> Update Ride Status</div>' +
        '<div class="req-action-btns" id="driActionBtns_' + reqId + '">' +
        _driBuildActionButtons(reqId, status) +
        '</div>' +
        '<div id="driActionMsg_' + reqId + '" style="display:none;margin-top:10px;font-size:.82rem;padding:8px 12px;border-radius:8px;"></div>' +
        '</div>';
}

/* ── Status update submission ────────────────────────────────────────────── */
function _driDoAction(reqId, action) {
    var msgEl  = document.getElementById('driActionMsg_' + reqId);
    var btnsEl = document.getElementById('driActionBtns_' + reqId);

    if (btnsEl) btnsEl.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
    if (msgEl) msgEl.style.display = 'none';

    fetch(window.driRequestBaseUrl + '/' + reqId + '/status', {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': _driCsrf(),
        },
        body: JSON.stringify({ action: action }),
    })
    .then(function (res) {
        return res.json().then(function (d) { return { ok: res.ok, data: d }; });
    })
    .then(function (res) {
        if (!res.ok || !res.data.success) {
            var msg = (res.data && res.data.message) ? res.data.message : 'Action failed.';
            if (msgEl) {
                msgEl.textContent = msg;
                msgEl.style.cssText = 'display:block;background:rgba(239,68,68,.1);color:#fca5a5;' +
                    'border:1px solid rgba(239,68,68,.2);margin-top:10px;font-size:.82rem;padding:8px 12px;border-radius:8px;';
            }
            if (btnsEl) btnsEl.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
            return;
        }

        var newStatus = String(res.data.status);
        if (_driCurrentReqData) _driCurrentReqData.status = newStatus;

        // Update status badge in the modal
        _driUpdateModalStatus(reqId, newStatus);

        // Update table row badge
        var rowBadge = document.getElementById('reqStatusBadge_' + reqId);
        if (rowBadge) {
            rowBadge.textContent = _driStatusLabel(newStatus);
            rowBadge.className   = 'status-pill ' + _driStatusClass(newStatus);
        }

        // Refresh action buttons
        if (btnsEl) {
            btnsEl.innerHTML = _driBuildActionButtons(reqId, newStatus);
        }

        // Hide action panel for terminal statuses
        if (['6', '7', '1'].includes(newStatus)) {
            var panel = document.getElementById('driActionPanel_' + reqId);
            if (panel) { panel.style.opacity = '0.4'; panel.style.pointerEvents = 'none'; }
        }

        // Reject: remove the row from the requests table, update counters, close modal
        if (action === 'reject') {
            /* Fade & remove the row from #driReqTableBody */
            var rejRow = document.getElementById('reqRow_' + reqId);
            if (rejRow) {
                rejRow.style.transition = 'opacity .35s, transform .35s';
                rejRow.style.opacity    = '0';
                rejRow.style.transform  = 'translateX(12px)';
                setTimeout(function () {
                    if (rejRow.parentNode) rejRow.remove();
                    /* Show empty-state row if the table is now empty */
                    var tbody = document.getElementById('driReqTableBody');
                    if (tbody && tbody.querySelectorAll('tr:not(.req-empty)').length === 0) {
                        var emptyTr = document.createElement('tr');
                        emptyTr.className = 'req-empty';
                        emptyTr.innerHTML =
                            '<td colspan="9">' +
                            '<i class="fa fa-inbox" style="display:block;font-size:1.6rem;margin-bottom:10px;opacity:.2;"></i>' +
                            'No requests found.' +
                            '</td>';
                        tbody.appendChild(emptyTr);
                    }
                }, 370);
            }

            /* Decrement sidebar nav badge */
            var navBadge = document.getElementById('driReqNavBadge');
            if (navBadge) {
                var nb = parseInt(navBadge.textContent, 10) || 0;
                if (nb > 1) {
                    navBadge.textContent = nb - 1;
                } else {
                    navBadge.textContent  = '0';
                    navBadge.style.display = 'none';
                }
            }

            /* Update stat cards on the requests page (Total Rides –1, Active –1) */
            document.querySelectorAll('.dri-stat-card').forEach(function (card) {
                var lbl = card.querySelector('.dri-stat-lbl');
                var val = card.querySelector('.dri-stat-val');
                if (!lbl || !val) return;
                var t = lbl.textContent.trim();
                if (t === 'Total Rides' || t === 'Pending') {
                    val.textContent = Math.max(0, (parseInt(val.textContent, 10) || 0) - 1);
                }
            });

            /* Update pagination info: –1 from visible count and total */
            var pgdInfoEl = document.querySelector('.pgd-info');
            if (pgdInfoEl) {
                pgdInfoEl.textContent = pgdInfoEl.textContent.replace(
                    /(\d+)\s*[–\-]\s*(\d+)\s+of\s+(\d+)/,
                    function (_, f, l, t) {
                        var newLast  = Math.max(parseInt(f, 10) - 1, parseInt(l, 10) - 1);
                        var newTotal = Math.max(0, parseInt(t, 10) - 1);
                        return newTotal === 0
                            ? '0\u20130 of 0'
                            : f + '\u2013' + newLast + ' of ' + newTotal;
                    }
                );
            }

            /* Decrement the "N Active" header pill */
            document.querySelectorAll('.status-pill.s2').forEach(function (el) {
                var m = el.textContent.match(/(\d+)/);
                if (m) {
                    var nv = Math.max(0, parseInt(m[0], 10) - 1);
                    el.textContent = el.textContent.replace(m[0], String(nv));
                }
            });

            /* Close the detail modal once the row has faded */
            if (_driModalInst) {
                setTimeout(function () { _driModalInst.hide(); }, 400);
            }
        }
    })
    .catch(function () {
        if (msgEl) {
            msgEl.textContent = 'Server error. Please try again.';
            msgEl.style.cssText = 'display:block;background:rgba(239,68,68,.1);color:#fca5a5;' +
                'border:1px solid rgba(239,68,68,.2);margin-top:10px;font-size:.82rem;padding:8px 12px;border-radius:8px;';
        }
        if (btnsEl) btnsEl.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
    });
}

function _driUpdateModalStatus(reqId, newStatus) {
    var badge = document.getElementById('driStatusBadge_' + reqId);
    if (badge) {
        badge.textContent = _driStatusLabel(newStatus);
        badge.className   = 'status-pill ' + _driStatusClass(newStatus);
    }
}

/* ── Reject confirmation ─────────────────────────────────────────────────── */
function _driShowRejectModal(reqId) {
    window._driPendingRejectId = reqId;
    var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('driRejectConfirmModal'));
    modal.show();
}

function driConfirmReject() {
    var reqId = window._driPendingRejectId;
    if (!reqId) return;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('driRejectConfirmModal')).hide();
    _driDoAction(reqId, 'reject');
}

/* ── Main: viewRequestDetail(id) ─────────────────────────────────────────── */
function viewRequestDetail(id) {
    _driOpenReqId      = id;
    _driCurrentReqData = null;

    // Tear down any existing map (removing the map also destroys all its layers)
    if (_driReqMap) { try { _driReqMap.remove(); } catch (e) {} _driReqMap = null; }
    _driReqDriverMarker = null;
    _driDriverToPickup  = null;
    _driPickupToHosp    = null;
    _driFullRouteCoords = null;   // Force re-fetch next open (driver may have moved)
    _driRouteCompleted  = null;
    _driRouteRemaining  = null;

    // Show loading
    var bodyEl  = document.getElementById('reqDetailBody');
    var titleEl = document.getElementById('reqModalTitle');
    if (bodyEl) bodyEl.innerHTML = '<div style="text-align:center;padding:36px 20px;">' +
        '<div class="dri-spinner"></div>' +
        '<p style="margin-top:14px;font-size:.82rem;color:rgba(255,255,255,.3);">Loading ride details…</p>' +
        '</div>';
    if (titleEl) titleEl.innerHTML =
        '<i class="fa fa-truck-medical me-2" style="color:#f87171;"></i>Request Details';

    if (!_driModalInst) {
        _driModalInst = bootstrap.Modal.getOrCreateInstance(document.getElementById('reqDetailModal'));
    }
    _driModalInst.show();

    fetch(window.driRequestBaseUrl + '/' + id, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': _driCsrf() },
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (!data.success) {
            if (bodyEl) bodyEl.innerHTML = '<p style="padding:24px 20px;color:#f87171;">Failed to load request details.</p>';
            return;
        }

        var req = data.request;
        _driCurrentReqData = req;
        _driOpenReqId      = req.id;

        var driverLat = req.driver_lat ? parseFloat(req.driver_lat) : null;
        var driverLng = req.driver_lng ? parseFloat(req.driver_lng) : null;
        var hasMap    = !!(req.pickup_lat && req.pickup_lng) || !!(driverLat && driverLng);

        if (titleEl) titleEl.innerHTML =
            '<i class="fa fa-truck-medical me-2" style="color:#f87171;"></i>' +
            'Ride — ' + _driEsc(req.rreb_id || ('#' + req.id));

        /* ── Build modal HTML ── */
        var mapLegend = hasMap ? (
            '<div style="display:flex;gap:14px;flex-wrap:wrap;font-size:.72rem;color:rgba(255,255,255,.4);' +
            'padding:9px 18px;border-bottom:1px solid rgba(255,255,255,.06);background:rgba(0,0,0,.12);">' +
            _driLegItem('#2563eb', 'Your Position') +
            _driLegItem('#ef4444', 'Pickup') +
            _driLegItem('#16a34a', 'Hospital') +
            '<span style="display:flex;align-items:center;gap:5px;">' +
              '<span style="display:inline-block;width:22px;height:3px;background:#2563eb;border-radius:2px;flex-shrink:0;"></span>' +
              'Driver→Pickup</span>' +
            '<span style="display:flex;align-items:center;gap:5px;">' +
              '<span style="display:inline-block;width:22px;height:0;border-top:3px dashed #16a34a;flex-shrink:0;"></span>' +
              'Pickup→Hospital</span>' +
            '</div>') : '';

        var sectionHdr = function(icon, title) {
            return '<div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;' +
                'color:rgba(255,255,255,.3);margin:18px 0 12px;border-top:1px solid rgba(255,255,255,.06);' +
                'padding-top:14px;display:flex;align-items:center;gap:6px;">' +
                '<i class="fa ' + icon + '"></i> ' + title + '</div>';
        };

        if (bodyEl) bodyEl.innerHTML =
            '<div class="adm-detail-body" style="padding:0;">' +

            // Map
            (hasMap ? '<div id="driLiveMap" style="height:310px;border-radius:0;overflow:hidden;"></div>' : '') +
            mapLegend +

            '<div style="padding:16px 18px 20px;">' +

            // Status & type badges
            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap;">' +
                '<span class="status-pill ' + (String(req.type) === '1' ? 'emergency' : 'non-emergency') + '">' +
                    (String(req.type) === '1' ? '🚨 Emergency' : 'Non-Emergency') + '</span>' +
                '<span class="status-pill ' + _driStatusClass(req.status) + '" id="driStatusBadge_' + req.id + '">' +
                    _driStatusLabel(req.status) + '</span>' +
                '<span style="font-family:monospace;font-size:.82rem;color:#a5b4fc;background:rgba(129,140,248,.1);' +
                    'padding:3px 10px;border-radius:20px;">' + _driEsc(req.rreb_id || '—') + '</span>' +
            '</div>' +

            // Patient Info
            sectionHdr('fa-user', 'Patient Information') +
            '<div class="req-detail-grid">' +
                _driCell('Mobile', req.mobile_no) +
                _driCell('Email', req.email || '—') +
                _driCell('Pickup Address', req.pickup_address || '—') +
                _driCell('Hospital', req.hospital_name || '—') +
            '</div>' +

            // Ambulance
            sectionHdr('fa-truck-medical', 'Ambulance') +
            '<div class="req-detail-grid">' +
                _driCell('Vehicle No.', req.ambulance_no || '—') +
                _driCell('Type', req.ambulance_type || '—') +
            '</div>' +

            // Timeline
            sectionHdr('fa-clock', 'Timeline') +
            '<div class="req-detail-grid">' +
                _driCell('Requested At', req.created_at) +
                _driCell('Dispatched At', req.dispatched_at || '—') +
                (req.completed_at ? _driCell('Completed At', req.completed_at) : '') +
            '</div>' +

            // Notes
            (req.notes ?
                sectionHdr('fa-note-sticky', 'Driver Notes') +
                '<div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);' +
                'border-radius:10px;padding:12px 14px;font-size:.83rem;color:rgba(255,255,255,.7);' +
                'line-height:1.55;white-space:pre-wrap;">' + _driEsc(req.notes) + '</div>'
                : '') +

            // Action panel
            _driBuildActionPanel(req.id, req.status) +

            '</div></div>';

        // Init map after DOM is painted
        if (hasMap) {
            setTimeout(function () { _driInitMap('driLiveMap', req, driverLat, driverLng); }, 120);
        }
    })
    .catch(function () {
        if (bodyEl) bodyEl.innerHTML =
            '<p style="padding:24px 20px;color:#f87171;">Server error. Please try again.</p>';
    });
}

/* ── Small HTML helpers ───────────────────────────────────────────────────── */
function _driCell(label, value) {
    return '<div class="req-detail-item">' +
        '<span>' + label + '</span>' +
        '<strong>' + _driEsc(value) + '</strong>' +
        '</div>';
}

function _driLegItem(color, label) {
    return '<span style="display:flex;align-items:center;gap:5px;">' +
        '<span style="width:11px;height:11px;border-radius:50%;background:' + color + ';' +
        'border:2px solid #fff;display:inline-block;flex-shrink:0;"></span>' +
        label + '</span>';
}

/* ── Real-time: new request dispatched to this driver ────────────────────── */
window._rrOnNewRequest = function (r) {
    var tbody = document.getElementById('driReqTableBody');
    if (!tbody) return;

    /* Duplicate guard */
    if (document.getElementById('reqRow_' + r.id)) return;

    /* Detect active filters / search / pagination > 1 */
    var urlParams   = new URLSearchParams(window.location.search);
    var hasSearch   = urlParams.has('search') && urlParams.get('search') !== '';
    var hasStatus   = urlParams.has('status') && urlParams.get('status') !== '' && urlParams.get('status') !== 'all';
    var isPage2plus = urlParams.has('page')   && parseInt(urlParams.get('page'), 10) > 1;

    if (hasSearch || hasStatus || isPage2plus) {
        /* Can't insert into a filtered/paginated view — show a toast instead */
        if (typeof driToastSuccess === 'function') {
            driToastSuccess('New dispatch request assigned to you.');
        }
        return;
    }

    /* Remove the empty-state row if present */
    var emptyRow = tbody.querySelector('.req-empty');
    if (emptyRow) emptyRow.remove();

    /* Update the page-header stat counters by label text */
    function _driUpdStat(labelText, delta) {
        document.querySelectorAll('.dri-stat-card').forEach(function (card) {
            var lbl = card.querySelector('.dri-stat-lbl');
            var val = card.querySelector('.dri-stat-val');
            if (lbl && val && lbl.textContent.trim() === labelText) {
                val.textContent = (parseInt(val.textContent, 10) || 0) + delta;
            }
        });
    }
    _driUpdStat('Total Rides', 1);
    _driUpdStat('Pending',      1);

    /* Update the header badge counts (e.g. "3 Active") */
    document.querySelectorAll('.status-pill.s2').forEach(function (el) {
        var m = el.textContent.match(/(\d+)/);
        if (m) el.textContent = el.textContent.replace(m[0], parseInt(m[0], 10) + 1);
    });

    /* Build the new row */
    var sLabel = '8', sClass = 's8', sText = 'Awaiting Acceptance';
    var typeHtml = r.type === '1'
        ? '<span class="status-pill emergency">Emergency</span>'
        : '<span class="status-pill non-emergency">Non-Emergency</span>';

    function _trunc(s, n) {
        if (!s) return '—';
        return s.length > n ? s.substring(0, n) + '…' : s;
    }

    var tr = document.createElement('tr');
    tr.id = 'reqRow_' + r.id;
    tr.innerHTML =
        '<td><span class="mono">' + (r.rreb_id || '#' + r.id) + '</span></td>' +
        '<td>' + typeHtml + '</td>' +
        '<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + _trunc(r.pickup_address, 32) + '</td>' +
        '<td>' + _trunc(r.hospital_name, 22) + '</td>' +
        '<td style="white-space:nowrap;">' + (r.mobile_no || '—') + '</td>' +
        '<td>' + (r.ambulance || '—') + '</td>' +
        '<td><span class="status-pill ' + sClass + '" id="reqStatusBadge_' + r.id + '">' + sText + '</span></td>' +
        '<td style="white-space:nowrap;color:rgba(255,255,255,.38);font-size:.77rem;">' + (r.dispatched_at || r.created_at || '') + '</td>' +
        '<td><button class="btn-dri-icon btn-dri-icon--primary" title="View Details" onclick="viewRequestDetail(' + r.id + ')">' +
        '<i class="fa fa-eye"></i></button></td>';

    /* Flash highlight on the new row */
    tr.style.transition = 'background .6s';
    tr.style.background = 'rgba(59,130,246,.12)';
    tbody.insertBefore(tr, tbody.firstChild);
    requestAnimationFrame(function () {
        setTimeout(function () { tr.style.background = ''; }, 1200);
    });

    /* Update pagination info: +1 to the visible-last and total counts */
    var pgdInfo = document.querySelector('.pgd-info');
    if (pgdInfo) {
        pgdInfo.textContent = pgdInfo.textContent.replace(
            /(\d+)\s*[–\-]\s*(\d+)\s+of\s+(\d+)/,
            function (_, f, l, t) {
                return f + '\u2013' + (parseInt(l, 10) + 1) + ' of ' + (parseInt(t, 10) + 1);
            }
        );
    }
};

/* ── Real-time: live driver position updates ─────────────────────────────── */
(function () {
    if (!window.pusher || !window.driDriverId) return;

    // Pusher deduplicates — safe to subscribe here even if realtime.js already did it
    var ch = window.pusher.subscribe('drivers-update');

    ch.bind('drivers-update', function (e) {
        // Only react to location updates for the logged-in driver
        if (!e || !e.data) return;
        if (e.entity !== 'driverLocationUpdated') return;
        if (String(e.data.id) !== String(window.driDriverId)) return;

        // Modal must be open with a map
        if (!_driReqMap) return;

        var lat = parseFloat(e.data.lat);
        var lng = parseFloat(e.data.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        var popup = '<div style="font-size:.82rem;min-width:130px;">' +
            '<b style="color:#2563eb;">🚑 Your Position</b><br>' +
            '<small style="color:#9ca3af;">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</small></div>';

        if (!_driReqDriverMarker) {
            // First GPS fix received while modal was already open — create the marker now
            _driReqDriverMarker = L.marker([lat, lng], { icon: _driIconDriver() })
                .bindPopup(popup)
                .addTo(_driReqMap);
        } else {
            _driReqDriverMarker.setLatLng([lat, lng]);
            _driReqDriverMarker.setPopupContent(popup);
        }

        // Redraw only the Driver → Pickup route segment
        _driDrawRoutes(lat, lng);
    });

    // ── GPS sender ────────────────────────────────────────────────────────────
    // live-map.js handles this on the dashboard (where #driLiveMap exists at load
    // time). On the requests page it bails early, so we send the GPS fix here
    // instead — same throttle logic, no duplicate watchPosition calls.
    if (navigator.geolocation && window.driLocationUpdateUrl) {
        var _reqLastSentAt  = 0;
        var _REQ_MIN_MS     = 4000;

        navigator.geolocation.watchPosition(
            function (pos) {
                var now = Date.now();
                if (now - _reqLastSentAt < _REQ_MIN_MS) return;
                _reqLastSentAt = now;

                fetch(window.driLocationUpdateUrl, {
                    method:   'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body:     JSON.stringify({
                        lat:      pos.coords.latitude,
                        lng:      pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                        heading:  pos.coords.heading,
                    }),
                    keepalive: true,
                }).catch(function () { /* transient error — next fix will retry */ });
            },
            function () { /* permission denied / unavailable */ },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );
    }
}());

/* ── Real-time: status update delivered via WebSocket (multi-tab / multi-device sync) ──
 * Called by realtime.js for every non-reject status change on this driver's personal channel.
 * Keeps the requests-page row badge and open modal in sync when another session fires the action.
 * Counter adjustments are NOT needed here for en_route (2→3) because both statuses
 * fall inside the Active bucket (whereNotIn 6,7,8); only badge/button UI is refreshed.
 * ─────────────────────────────────────────────────────────────────────────────────────── */
window._rrOnRequestStatusChanged = function (payload) {
    if (!payload || payload.id === undefined) return;
    var action = String(payload.action || '');
    var status = String(payload.status  || '');
    var reqId  = payload.id;

    /* Skip reject — handled separately in realtime.js (row removal + counters) */
    if (action === 'reject') return;

    /* ── Accept: Pending –1, Active +1 ────────────────────────────────────── */
    if (action === 'accept') {
        /* Adjust stat cards (matched by label text) */
        document.querySelectorAll('.dri-stat-card').forEach(function (card) {
            var lbl = card.querySelector('.dri-stat-lbl');
            var val = card.querySelector('.dri-stat-val');
            if (!lbl || !val) return;
            var t = lbl.textContent.trim();
            if (t === 'Pending') {
                val.textContent = Math.max(0, (parseInt(val.textContent, 10) || 0) - 1);
            }
            if (t === 'Active') {
                val.textContent = Math.max(0, (parseInt(val.textContent, 10) || 0) + 1);
            }
        });

        /* Adjust the "N Active" header pill */
        document.querySelectorAll('.status-pill.s2').forEach(function (el) {
            var m = el.textContent.match(/(\d+)/);
            if (m) {
                var nv = Math.max(0, parseInt(m[0], 10) + 1);
                el.textContent = el.textContent.replace(m[0], String(nv));
            }
        });
    }

    /* Update the table row status badge */
    var rowBadge = document.getElementById('reqStatusBadge_' + reqId);
    if (rowBadge) {
        rowBadge.textContent = _driStatusLabel(status);
        rowBadge.className   = 'status-pill ' + _driStatusClass(status);
    }

    /* Update the open modal's status badge */
    _driUpdateModalStatus(reqId, status);

    /* Refresh action buttons in the open modal (guard: only if this request's modal is open) */
    var btnsEl = document.getElementById('driActionBtns_' + reqId);
    if (btnsEl && _driCurrentReqData && String(_driCurrentReqData.id) === String(reqId)) {
        if (_driCurrentReqData) _driCurrentReqData.status = status;
        btnsEl.innerHTML = _driBuildActionButtons(reqId, status);
    }

    /* Hide action panel for terminal statuses */
    if (['6', '7', '1'].includes(status)) {
        var panel = document.getElementById('driActionPanel_' + reqId);
        if (panel) { panel.style.opacity = '0.4'; panel.style.pointerEvents = 'none'; }
    }

    /* ── Complete: remove row from active grid, update counters, close modal ── */
    if (action === 'complete') {
        /* Fade & remove the row */
        var compRow = document.getElementById('reqRow_' + reqId);
        if (compRow) {
            compRow.style.transition = 'opacity .35s, transform .35s';
            compRow.style.opacity    = '0';
            compRow.style.transform  = 'translateX(12px)';
            setTimeout(function () {
                if (compRow.parentNode) compRow.remove();
                /* Show empty state if no active rows remain */
                var tbody = document.getElementById('driReqTableBody');
                if (tbody && tbody.querySelectorAll('tr:not(.req-empty)').length === 0) {
                    var emptyTr = document.createElement('tr');
                    emptyTr.className = 'req-empty';
                    emptyTr.innerHTML =
                        '<td colspan="9">' +
                        '<i class="fa fa-inbox" style="display:block;font-size:1.6rem;' +
                        'margin-bottom:10px;opacity:.2;"></i>No requests found.</td>';
                    tbody.appendChild(emptyTr);
                }
            }, 370);
        }

        /* Decrement Active and Total Rides stat cards */
        document.querySelectorAll('.dri-stat-card').forEach(function (card) {
            var lbl = card.querySelector('.dri-stat-lbl');
            var val = card.querySelector('.dri-stat-val');
            if (lbl && val) {
                var t = lbl.textContent.trim();
                if (t === 'Active' || t === 'Total Rides') {
                    val.textContent = Math.max(0, (parseInt(val.textContent, 10) || 0) - 1);
                }
            }
        });

        /* Decrement the "N Active" header pill */
        document.querySelectorAll('.status-pill.s2').forEach(function (el) {
            var m = el.textContent.match(/(\d+)/);
            if (m) {
                var nv = Math.max(0, parseInt(m[0], 10) - 1);
                el.textContent = el.textContent.replace(m[0], String(nv));
            }
        });

        /* Update pagination count */
        var pgdInfoEl = document.querySelector('.pgd-info');
        if (pgdInfoEl) {
            pgdInfoEl.textContent = pgdInfoEl.textContent.replace(
                /(\d+)\s*[–\-]\s*(\d+)\s+of\s+(\d+)/,
                function (_, f, l, t) {
                    var newLast  = Math.max(parseInt(f, 10) - 1, parseInt(l, 10) - 1);
                    var newTotal = Math.max(0, parseInt(t, 10) - 1);
                    return newTotal === 0
                        ? '0\u20130 of 0'
                        : f + '\u2013' + newLast + ' of ' + newTotal;
                }
            );
        }

        /* Close the detail modal once the row has faded */
        if (_driModalInst) {
            setTimeout(function () { _driModalInst.hide(); }, 400);
        }
    }
};
