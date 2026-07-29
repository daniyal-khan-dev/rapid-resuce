let reqDetailMap          = null;
let reqDetailModalInstance = null;

// ── Admin modal live-tracking state (mirrors user tracking.js) ──
let _admTrackingData    = null;
let _admLrmRoute        = null;

// ── Online-driver markers on the modal map (one marker per online driver,
//    kept in sync in place — never recreated on real-time updates) ──
let _admDriverMarkers  = {};   // driverId -> L.Marker
let _admDriverData     = {};  // driverId -> latest known driver info
let _admSelectedDriverId = null;
let _admLastRouteDrawAt  = 0;
const _admROUTE_REDRAW_THROTTLE_MS = 3000;

// ── Dispatched-driver split-route tracking state ──────────────────────────
// Separate from the generic dispatch-panel driver pool above.
// Tracks the driver already assigned to the currently viewed request.
let _admDispatchedDriverId     = null;  // Driver ID for the open request's assigned driver
let _admDispatchedDriverMarker = null;  // Dedicated Leaflet marker for that driver
let _admFullRouteCoords        = null;  // Cached OSRM [lat,lng][] for driver→pickup leg
let _admRouteCompleted         = null;  // Grey polyline: already-traveled portion
let _admRouteRemaining         = null;  // Blue polyline: remaining portion
let _admPickupToHospLayer      = null;  // Green-dashed polyline: pickup→hospital
function getReqDetailModal() {
    if (!reqDetailModalInstance) {
        reqDetailModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('reqDetailModal'));
    }
    return reqDetailModalInstance;
}

/* ── Map icon builders ── */
function buildPersonIcon() {
    return L.divIcon({
        className: '',
        html: `<div style="background:#D72C42;border:3px solid #fff;border-radius:50%;width:38px;height:38px;
                    box-shadow:0 2px 14px rgba(215,44,66,0.55);display:flex;align-items:center;justify-content:center;">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="20" height="20">
                   <circle cx="12" cy="7" r="4"/>
                   <path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>
                 </svg>
               </div>`,
        iconSize: [38, 38], iconAnchor: [19, 19], popupAnchor: [0, -22],
    });
}

function buildHospitalIcon() {
    return L.divIcon({
        className: '',
        html: `<div style="background:#16a34a;border:3px solid #fff;border-radius:50%;width:34px;height:34px;
            box-shadow:0 2px 12px rgba(22,163,74,0.5);display:flex;align-items:center;justify-content:center;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18" height="18">
              <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 3a1 1 0 0 1
                1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>
            </svg></div>`,
        iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -20],
    });
}

function buildDriverIcon(highlighted) {
    const size = highlighted ? 40 : 32;
    const bg   = highlighted ? '#2563eb' : '#16a34a';
    const ring = highlighted ? '0 0 0 4px rgba(37,99,235,0.35), 0 2px 12px rgba(37,99,235,0.6)' : '0 2px 10px rgba(22,163,74,0.55)';
    return L.divIcon({
        className: highlighted ? 'adm-driver-marker adm-driver-marker--active' : 'adm-driver-marker',
        html: `<div style="background:${bg};border:3px solid #fff;border-radius:50%;width:${size}px;height:${size}px;
                    box-shadow:${ring};display:flex;align-items:center;justify-content:center;">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="${Math.round(size * 0.55)}" height="${Math.round(size * 0.55)}">
                   <path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13v5a1 1 0 0 1-1 1h-1a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H4a1 1 0 0 1-1-1v-5z"/>
                   <circle cx="7.5" cy="18" r="1.6" fill="${bg}"/>
                   <circle cx="16.5" cy="18" r="1.6" fill="${bg}"/>
                 </svg>
               </div>`,
        iconSize: [size, size], iconAnchor: [size / 2, size / 2], popupAnchor: [0, -size / 2],
    });
}

/* ── Driver popup content: name, id, status, availability, speed, last update, lat/lng ── */
const _admAvailLabelMap = { '1': 'Online', '2': 'Offline', '3': 'Busy' };
const _admStatusLabelMap = { '1': 'Active', '2': 'Inactive' };

function _admFormatUpdatedAt(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    } catch (e) { return '—'; }
}

function _admBuildDriverPopupHtml(d) {
    const speedText = (d.speed === null || d.speed === undefined) ? '—' : `${d.speed} km/h`;
    return `
        <div style="min-width:180px;font-size:.8rem;line-height:1.5;">
            <div style="font-weight:700;margin-bottom:4px;">${_rrEsc(d.name || 'Driver')}</div>
            <div><span style="color:#94a3b8;">Driver ID:</span> ${_rrEsc(d.id)}</div>
            <div><span style="color:#94a3b8;">Status:</span> ${_rrEsc(_admStatusLabelMap[String(d.status)] || d.status || '—')}</div>
            <div><span style="color:#94a3b8;">Availability:</span> ${_rrEsc(_admAvailLabelMap[String(d.availability)] || d.availability || '—')}</div>
            <div><span style="color:#94a3b8;">Speed:</span> ${speedText}</div>
            <div><span style="color:#94a3b8;">Last Updated:</span> ${_admFormatUpdatedAt(d.updated_at)}</div>
            <div><span style="color:#94a3b8;">Lat:</span> ${d.lat !== undefined ? Number(d.lat).toFixed(5) : '—'}</div>
            <div><span style="color:#94a3b8;">Lng:</span> ${d.lng !== undefined ? Number(d.lng).toFixed(5) : '—'}</div>
        </div>`;
}

/* ── Create or move a driver marker in place (never recreates the map) ── */
function _admUpsertDriverMarker(d) {
    if (!reqDetailMap || !d || d.id === undefined) return;
    const lat = parseFloat(d.lat), lng = parseFloat(d.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    _admDriverData[d.id] = Object.assign({}, _admDriverData[d.id], d);

    const isSelected = String(_admSelectedDriverId) === String(d.id);
    let marker = _admDriverMarkers[d.id];

    if (!marker) {
        marker = L.marker([lat, lng], { icon: buildDriverIcon(isSelected) }).addTo(reqDetailMap);
        marker.on('click', function () { _admOnDriverSelect(d.id); });
        _admDriverMarkers[d.id] = marker;
    } else {
        marker.setLatLng([lat, lng]); /* smooth CSS-driven move (Leaflet animates marker position changes) */
    }

    marker.bindPopup(_admBuildDriverPopupHtml(_admDriverData[d.id]));
    if (marker.isPopupOpen()) marker.setPopupContent(_admBuildDriverPopupHtml(_admDriverData[d.id]));
}

function _admRemoveDriverMarker(id) {
    const marker = _admDriverMarkers[id];
    if (marker && reqDetailMap) {
        reqDetailMap.removeLayer(marker);
    }
    delete _admDriverMarkers[id];
    delete _admDriverData[id];
    if (String(_admSelectedDriverId) === String(id)) {
        _admSelectedDriverId = null;
        if (_admLrmRoute) {
            try {
                if (typeof _admLrmRoute.remove === 'function') reqDetailMap.removeControl(_admLrmRoute);
                else reqDetailMap.removeLayer(_admLrmRoute);
            } catch (e) {}
            _admLrmRoute = null;
        }
    }
}

/* Seed the map with every currently-online driver as soon as the modal opens. */
function _admInitOnlineDrivers(drivers) {
    if (!reqDetailMap) return;
    (drivers || []).forEach(function (d) {
        _admUpsertDriverMarker({
            id: d.id, name: d.name, status: d.status, availability: d.availability,
            lat: d.lat, lng: d.lng, updated_at: d.last_seen_at, speed: null,
        });
    });
}

/* ── Draw / update the live Driver → Pickup → Drop-off route ── */
function _admDrawDriverRoute(driverLat, driverLng, pickupLat, pickupLng, hospitalLat, hospitalLng, force) {
    if (!reqDetailMap) return;

    const now = Date.now();
    if (!force && now - _admLastRouteDrawAt < _admROUTE_REDRAW_THROTTLE_MS) return;
    _admLastRouteDrawAt = now;

    if (_admLrmRoute) {
        try {
            if (typeof _admLrmRoute.remove === 'function') reqDetailMap.removeControl(_admLrmRoute);
            else reqDetailMap.removeLayer(_admLrmRoute);
        } catch (e) {}
        _admLrmRoute = null;
    }

    const waypoints = [L.latLng(driverLat, driverLng), L.latLng(pickupLat, pickupLng)];
    if (hospitalLat && hospitalLng) waypoints.push(L.latLng(hospitalLat, hospitalLng));

    if (typeof L.Routing !== 'undefined') {
        _admLrmRoute = L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
            lineOptions: { styles: [{ color: '#2563eb', weight: 5, opacity: 0.85 }] },
            addWaypoints: false, draggableWaypoints: false,
            show: false, createMarker: () => null,
        }).addTo(reqDetailMap);
    } else {
        _admLrmRoute = L.polyline(waypoints, {
            color: '#2563eb', weight: 4, opacity: 0.8, dashArray: '10,8',
        }).addTo(reqDetailMap);
    }
}

/* ── Called when the admin selects (or clicks) a driver: focus + highlight
       the marker, open its popup, and draw the live route, without hiding
       any other online-driver markers ── */
function _admOnDriverSelect(driverId) {
    if (!reqDetailMap) return;

    const prevSelected = _admSelectedDriverId;
    _admSelectedDriverId = driverId ? String(driverId) : null;

    /* Un-highlight the previously selected marker */
    if (prevSelected && _admDriverMarkers[prevSelected]) {
        _admDriverMarkers[prevSelected].setIcon(buildDriverIcon(false));
    }

    if (!driverId) {
        if (_admLrmRoute) {
            try {
                if (typeof _admLrmRoute.remove === 'function') reqDetailMap.removeControl(_admLrmRoute);
                else reqDetailMap.removeLayer(_admLrmRoute);
            } catch (e) {}
            _admLrmRoute = null;
        }
        /* Restore the default pickup → hospital route for pending requests */
        _admApplySmartRoute(_admTrackingData, { forceRedraw: true });
        return;
    }

    const marker = _admDriverMarkers[driverId];
    if (!marker) return;

    marker.setIcon(buildDriverIcon(true));
    reqDetailMap.flyTo(marker.getLatLng(), Math.max(reqDetailMap.getZoom(), 15), { animate: true, duration: 0.8 });
    marker.openPopup();

    const d = _admDriverData[driverId];
    if (d && _admTrackingData) {
        _admDrawDriverRoute(
            d.lat, d.lng,
            _admTrackingData.pickupLat, _admTrackingData.pickupLng,
            _admTrackingData.hospitalLat, _admTrackingData.hospitalLng,
            true
        );
    }
}

/* Keep the dispatch panel's <select> and the map's marker selection in sync */
function _admOnDriverSelectChange(selectEl) {
    _admOnDriverSelect(selectEl.value || null);
}

/* ── Real-time hooks, called from emergency-realtime.js on the existing
       "drivers-update" Reverb channel (no new subscription/listener) ── */
window._admUpdateDriverOnMap = function (d) {
    if (!reqDetailMap) return;
    const lat = parseFloat(d.lat), lng = parseFloat(d.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    /* ── Dispatched-driver live tracking ─────────────────────────────────────
       If this GPS event belongs to the driver already assigned to the open
       request, update their dedicated marker and redraw the split route.
       Return early so they are never treated as a generic dispatch-panel driver
       (dispatched drivers are availability=3/Busy and would otherwise be removed). */
    if (_admDispatchedDriverId && String(d.id) === String(_admDispatchedDriverId)) {
        const popup = `<div style="min-width:140px;font-size:.8rem;">
            <b style="color:#2563eb;">🚑 ${_rrEsc(d.name || 'Driver')}</b><br>
            <span style="color:#94a3b8;">Live location</span><br>
            <small style="color:#9ca3af;">${lat.toFixed(5)}, ${lng.toFixed(5)}</small>
        </div>`;
        if (!_admDispatchedDriverMarker) {
            _admDispatchedDriverMarker = L.marker([lat, lng], { icon: buildDriverIcon(true) })
                .bindPopup(popup)
                .addTo(reqDetailMap);
        } else {
            _admDispatchedDriverMarker.setLatLng([lat, lng]);
            _admDispatchedDriverMarker.setPopupContent(popup);
        }
        _admDrawDispatchedDriverRoute(lat, lng);
        return;
    }

    /* ── Generic online-driver markers (dispatch panel for pending requests) ── */
    const isEligible = String(d.status) === '1' && String(d.availability) === '1';
    if (!isEligible) {
        _admRemoveDriverMarker(d.id);
        return;
    }
    _admUpsertDriverMarker(d);

    /* If the moved driver is the one currently selected, keep the route in sync */
    if (String(_admSelectedDriverId) === String(d.id) && _admTrackingData) {
        _admDrawDriverRoute(
            d.lat, d.lng,
            _admTrackingData.pickupLat, _admTrackingData.pickupLng,
            _admTrackingData.hospitalLat, _admTrackingData.hospitalLng,
            false
        );
    }
};

window._admHandleDriverAvailabilityChange = function (d) {
    if (!reqDetailMap) return;
    const isEligible = String(d.status) === '1' && String(d.availability) === '1';
    if (isEligible) {
        _admUpsertDriverMarker({
            id: d.id, name: d.name, status: d.status, availability: d.availability,
            lat: d.lat, lng: d.lng, updated_at: d.updated_at || d.last_seen_at,
            speed: (_admDriverData[d.id] || {}).speed ?? null,
        });
    } else {
        _admRemoveDriverMarker(d.id);
    }
};

/* ── Status helpers ── */
const statusLabelMap = {
    '1': 'Pending', '2': 'Dispatched', '3': 'En Route',
    '4': 'Arrived', '5': 'Transporting', '6': 'Completed', '7': 'Cancelled',
    '8': 'Awaiting Acceptance',
};

function humanStatus(s) {
    return statusLabelMap[s] || (s ? s.replace(/_/g, ' ') : '—');
}

/* ── Smart route point selection (same logic as user tracking.js) ── */
function _admGetSmartRoutePoints(td) {
    const s           = String(td.status);
    const hasHospital = !!(td.hospitalLat && td.hospitalLng);

    if (s === '1') {
        if (hasHospital) return { from: [td.pickupLat, td.pickupLng], to: [td.hospitalLat, td.hospitalLng] };
        return null;
    }
    return null;
}

/* ── Draw / update live LRM route on the modal map ── */
function _admDrawLiveRoute(fromLat, fromLng, toLat, toLng) {
    if (!reqDetailMap) return;
    if (_admLrmRoute) {
        try {
            if (typeof _admLrmRoute.remove === 'function') reqDetailMap.removeControl(_admLrmRoute);
            else reqDetailMap.removeLayer(_admLrmRoute);
        } catch(e) {}
        _admLrmRoute = null;
    }
    if (typeof L.Routing !== 'undefined') {
        _admLrmRoute = L.Routing.control({
            waypoints: [L.latLng(fromLat, fromLng), L.latLng(toLat, toLng)],
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
            lineOptions: { styles: [{ color: '#1d4ed8', weight: 5, opacity: 0.80 }] },
            addWaypoints: false, draggableWaypoints: false,
            show: false, createMarker: () => null,
        }).addTo(reqDetailMap);
    } else {
        _admLrmRoute = L.polyline([[fromLat, fromLng], [toLat, toLng]], {
            color: '#1d4ed8', weight: 4, opacity: 0.75, dashArray: '10,8',
        }).addTo(reqDetailMap);
    }
}

/* ── Apply smart route (same throttle + deviation logic as user side) ────── */
function _admApplySmartRoute(td, opts) {
    if (!reqDetailMap) return;
    opts = opts || {};
    const pts = _admGetSmartRoutePoints(td);
    if (!pts) {
        if (_admLrmRoute) {
            try {
                if (typeof _admLrmRoute.remove === 'function') reqDetailMap.removeControl(_admLrmRoute);
                else reqDetailMap.removeLayer(_admLrmRoute);
            } catch(e) {}
            _admLrmRoute = null;
        }
        return;
    }
    let shouldRedraw = opts.forceRedraw;
    if (shouldRedraw) {
        _admDrawLiveRoute(pts.from[0], pts.from[1], pts.to[0], pts.to[1]);
    }
}

/* ── Dispatched-driver OSRM split-route helpers ──────────────────────────── */

/** Fetch OSRM route once and deliver the raw [lat,lng][] array to onDone. */
function _admFetchRouteCoords(fromLng, fromLat, toLng, toLat, onDone) {
    const url = 'https://router.project-osrm.org/route/v1/driving/' +
        fromLng + ',' + fromLat + ';' + toLng + ',' + toLat +
        '?overview=full&geometries=geojson';
    fetch(url)
        .then(r => r.json())
        .then(d => {
            if (!d.routes || !d.routes[0] || !reqDetailMap) return;
            const coords = d.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
            if (onDone) onDone(coords);
        })
        .catch(() => {});
}

/** Return the index of the vertex in coords[] nearest to (lat, lng). */
function _admNearestVertexIndex(coords, lat, lng) {
    let minDist = Infinity, idx = 0;
    for (let i = 0; i < coords.length; i++) {
        const dlat = coords[i][0] - lat;
        const dlng = coords[i][1] - lng;
        const dist = dlat * dlat + dlng * dlng;
        if (dist < minDist) { minDist = dist; idx = i; }
    }
    return idx;
}

/**
 * Split the cached driver→pickup route at the vertex nearest the driver,
 * then redraw two polylines in-place — no new OSRM request.
 *   [0..nearestIdx]   → grey  (#9ca3af) — already traveled
 *   [nearestIdx..end] → blue  (#2563eb) — still to travel
 */
function _admRedrawRouteSplit(driverLat, driverLng) {
    if (!reqDetailMap || !_admFullRouteCoords || _admFullRouteCoords.length < 2) return;

    const nearestIdx = _admNearestVertexIndex(_admFullRouteCoords, driverLat, driverLng);
    const completed  = _admFullRouteCoords.slice(0, nearestIdx + 1);
    const remaining  = _admFullRouteCoords.slice(nearestIdx);

    if (_admRouteCompleted) {
        try { reqDetailMap.removeLayer(_admRouteCompleted); } catch (e) {}
        _admRouteCompleted = null;
    }
    if (_admRouteRemaining) {
        try { reqDetailMap.removeLayer(_admRouteRemaining); } catch (e) {}
        _admRouteRemaining = null;
    }

    if (completed.length >= 2) {
        _admRouteCompleted = L.polyline(completed, {
            color: '#9ca3af', weight: 5, opacity: 0.7, lineJoin: 'round',
        }).addTo(reqDetailMap);
    }
    if (remaining.length >= 2) {
        _admRouteRemaining = L.polyline(remaining, {
            color: '#2563eb', weight: 5, opacity: 0.85, lineJoin: 'round',
        }).addTo(reqDetailMap);
    }
}

/**
 * Orchestrate split-route drawing for the dispatched driver.
 * First call per modal open: fetches OSRM and caches coords, then splits.
 * All subsequent calls: re-splits from cache — zero network requests.
 * Also draws the Pickup→Hospital green-dashed leg once on first call.
 */
function _admDrawDispatchedDriverRoute(driverLat, driverLng) {
    if (!reqDetailMap || !_admTrackingData) return;
    const { pickupLat, pickupLng, hospitalLat, hospitalLng } = _admTrackingData;
    if (!pickupLat || !pickupLng) return;

    if (_admFullRouteCoords) {
        /* Route cached — re-split only */
        _admRedrawRouteSplit(driverLat, driverLng);
    } else {
        /* First call: fetch driver→pickup, cache, then split */
        _admFetchRouteCoords(driverLng, driverLat, pickupLng, pickupLat, function (coords) {
            _admFullRouteCoords = coords;
            _admRedrawRouteSplit(driverLat, driverLng);
        });

        /* Pickup→Hospital green-dashed — drawn once per modal open */
        if (!_admPickupToHospLayer && hospitalLat && hospitalLng) {
            _admFetchRouteCoords(pickupLng, pickupLat, hospitalLng, hospitalLat, function (coords) {
                if (!reqDetailMap || coords.length < 2) return;
                _admPickupToHospLayer = L.polyline(coords, {
                    color: '#16a34a', weight: 5, opacity: 0.85,
                    lineJoin: 'round', dashArray: '10,8',
                }).addTo(reqDetailMap);
            });
        }
    }
}

/* ── Build dispatch panel HTML (shown only for Pending requests) ── */
function _buildDispatchPanel(reqId, reqStatus, availableAmbulDriver) {
    const isPending            = reqStatus === '1';
    const isAwaitingAcceptance = reqStatus === '8';

    // Driver options
    const drivers = window.reqDrivers || [];
    let driverOpts = '<option value="">— Select Driver —</option>';
    if (drivers.length) {
        drivers.forEach(function (d) {
            driverOpts += `<option value="${d.id}">${d.label}</option>`;
        });
    } else {
        driverOpts += '<option value="" disabled>No available drivers found</option>';
    }

    return isPending ? `
        <div class="adm-dispatch-panel" id="dispatchPanel_${reqId}">
            <div class="adm-dispatch-panel__title">
                <i class="fa fa-paper-plane"></i> Dispatch Driver
            </div>

            <div style="margin-bottom:12px;">
                <label class="adm-dispatch-label" for="dispDriver_${reqId}">Driver</label>
                <select class="adm-input" id="dispDriver_${reqId}" onchange="_admOnDriverSelectChange(this)">
                    ${driverOpts}
                </select>
                <span class="adm-field-error" id="dispDriverErr_${reqId}"></span>
            </div>

            <div style="margin-bottom:14px;">
                <label class="adm-dispatch-label" for="dispNotes_${reqId}">Notes for Driver <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea class="adm-input" rows="2" id="dispNotes_${reqId}" placeholder="Any special instructions for the driver…" maxlength="500"></textarea>
            </div>

            <div id="dispMsg_${reqId}" class="adm-form-msg" style="display:none;margin-bottom:10px;"></div>
            <div style="margin-top:14px;display:flex;justify-content:flex-end;">
                <button class="adm-btn adm-btn--primary" id="dispSubmitBtn_${reqId}" onclick="_submitDispatch(${reqId})">
                    <i class="fa fa-paper-plane"></i> Send Request
                </button>
            </div>
        </div>`
        : isAwaitingAcceptance ? `
        <div class="adm-dispatch-panel" style="border-color:rgba(251,191,36,.2);background:rgba(251,191,36,.04);">
            <div class="adm-dispatch-panel__title" style="color:#fbbf24;">
                <i class="fa fa-clock"></i> Awaiting Driver Acceptance
            </div>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0 4px;">
                <span style="font-size:1.4rem;animation:lmPulse 1.8s infinite;">⏳</span>
                <div>
                    <div style="font-size:.85rem;color:#e2e8f0;font-weight:600;">
                        ${availableAmbulDriver ?? 'Driver'} has been asked to accept this request.
                    </div>
                    <div style="font-size:.75rem;color:rgba(255,255,255,.35);margin-top:3px;">
                        The driver will Accept or Reject. Status updates automatically.
                    </div>
                </div>
            </div>
        </div>`
    : '' ;
}

/* ── Submit dispatch ("Send Request") ── */
function _submitDispatch(reqId) {
    const btn       = document.getElementById(`dispSubmitBtn_${reqId}`);
    const driverSel = document.getElementById(`dispDriver_${reqId}`);
    const notesEl   = document.getElementById(`dispNotes_${reqId}`);
    const driverErr = document.getElementById(`dispDriverErr_${reqId}`);
    const msgEl     = document.getElementById(`dispMsg_${reqId}`);

    if (driverErr) driverErr.textContent = '';
    if (msgEl) {
        msgEl.style.display = 'none';
        msgEl.textContent   = '';
        msgEl.className     = 'adm-form-msg';
    }

    const driverId = driverSel ? driverSel.value : '';
    if (!driverId) {
        if (driverErr) driverErr.textContent = 'Please select a driver.';
        return;
    }

    if (btn) {
        btn.disabled = true;
        if (!btn.dataset.origHtml) btn.dataset.origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending…';
    }

    fetch(`${window.adminRoutes.requestsDispatch}/${reqId}`, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({
            driver_id: driverId,
            notes:     notesEl ? notesEl.value : '',
        }),
    })
    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
    .then(function (res) {
        const data = res.data;
        if (!res.ok || !data.success) {
            const message = data.message || 'Failed to send dispatch request.';
            if (msgEl) {
                msgEl.textContent   = message;
                msgEl.className     = 'adm-form-msg error';
                msgEl.style.display = 'block';
            } else {
                showAlert('error', message);
            }
            return;
        }

        showAlert('success', data.message || 'Dispatch request sent. Awaiting driver acceptance.');
        getReqDetailModal().hide();

        /* Reflect the new status/ambulance/driver on the underlying grid row
           right away (the 'emergency-request-dispatched' broadcast keeps any
           other open admin tab in sync the same way). */
        const row = document.querySelector(`tr[data-req-id="${reqId}"]`);
        if (row && data.request) {
            const statusCell    = row.querySelector('[data-status-cell]');
            const driverCell    = row.querySelector('[data-driver-cell]');
            if (statusCell)    statusCell.innerHTML   = '<span class="status-pill status-2">' + humanStatus(data.request.status) + '</span>';
            if (driverCell)    driverCell.textContent    = data.request.driver ? data.request.driver.name : '—';
        }
    })
    .catch(function () {
        if (msgEl) {
            msgEl.textContent   = 'Server error. Please try again.';
            msgEl.className     = 'adm-form-msg error';
            msgEl.style.display = 'block';
        } else {
            showAlert('error', 'Server error. Please try again.');
        }
    })
    .finally(function () {
        if (btn) {
            btn.disabled  = false;
            btn.innerHTML = btn.dataset.origHtml || '<i class="fa fa-paper-plane"></i> Send Request';
        }
    });
}

/* ── viewRequest ── */
function viewRequest(id) {
    document.getElementById('reqDetailBody').innerHTML = '<div class="adm-loading"><i class="fa fa-spinner fa-spin"></i> Loading…</div>';
    getReqDetailModal().show();

    fetch(`${window.adminRoutes.requestsShow}/${id}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('reqDetailBody').innerHTML = '<p style="padding:20px;color:#f87171">Failed to load.</p>';
            return;
        }
        
        const r = data.request;
        const hasCoords = !!(r.pickup_lat && r.pickup_lng);

        document.getElementById('reqDetailBody').innerHTML = `
            <div class="adm-detail-body">
                ${hasCoords ? `
                <div id="reqDetailMap"></div>` : ''}
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>RREB ID</span><strong>${r.rreb_id || '—'}</strong></div>
                    <div class="adm-detail-cell"><span>Mobile</span><strong>${r.mobile_no}</strong></div>
                </div>
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>Type</span>
                        <strong>${r.type === '1' ? 'Emergency' : r.type === '2' ? 'Non-Emergency' : r.type}</strong>
                    </div>
                    <div class="adm-detail-cell"><span>Status</span><strong id="admModalReqStatus" data-req-id="${r.id}">${humanStatus(r.status)}</strong></div>
                </div>
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>Hospital</span><strong>${r.hospital_name || '—'}</strong></div>
                    <div class="adm-detail-cell"><span>Pickup Address</span><strong>${r.pickup_address || '—'}</strong></div>
                </div>
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>Driver</span>
                        <strong>${r.driver ? r.driver.name + ' (' + r.driver.phone + ')' : '—'}</strong>
                    </div>
                    <div class="adm-detail-cell"><span>Ambulance</span>
                        <strong>${r.ambulance ? r.ambulance.vehicle_number + ' — ' + r.ambulance.type : '—'}</strong>
                    </div>
                </div>
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>Coordinates</span>
                        <strong>${r.pickup_lat + ', ' + r.pickup_lng }</strong>
                    </div>
                    <div class="adm-detail-cell"><span>Dispatched At</span><strong>${r.dispatched_at || '—'}</strong></div>
                </div>
                <div class="adm-detail-pair">
                    <div class="adm-detail-cell"><span>Notes For Driver</span><strong>${r.notes || '—'}</strong></div>
                    <div class="adm-detail-cell"><span>Submitted</span><strong>${r.created_at}</strong></div>
                </div>
                ${_buildDispatchPanel(r.id, r.status, r.driver ? r.driver.name : null)}
            </div>
        `;

        if (hasCoords && typeof L !== 'undefined') {
            setTimeout(() => {
                if (reqDetailMap) { reqDetailMap.remove(); reqDetailMap = null; }

                /* Fresh modal open: reset the online-driver marker/selection state */
                _admDriverMarkers  = {};
                _admDriverData     = {};
                _admSelectedDriverId = null;
                _admLastRouteDrawAt  = 0;

                /* Reset dispatched-driver split-route state
                   (Leaflet layers are destroyed with the map above — just null the refs) */
                _admDispatchedDriverId     = null;
                _admDispatchedDriverMarker = null;
                _admFullRouteCoords        = null;
                _admRouteCompleted         = null;
                _admRouteRemaining         = null;
                _admPickupToHospLayer      = null;

                reqDetailMap = L.map('reqDetailMap').setView([r.pickup_lat, r.pickup_lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(reqDetailMap);

                const hasHospital     = r.hospital_lat && r.hospital_lng;
                const bounds          = [[r.pickup_lat, r.pickup_lng]];

                // ── Pickup marker (stored for smart-route hide/restore) ───────
                L.marker([r.pickup_lat, r.pickup_lng], { icon: buildPersonIcon() }).bindPopup('<b>User Pickup Location</b><br>' + r.pickup_address).addTo(reqDetailMap).openPopup();

                // ── Hospital marker ──────────────────────────────────────────
                if (hasHospital) {
                    L.marker([r.hospital_lat, r.hospital_lng], { icon: buildHospitalIcon() }).bindPopup('<b>Destination Hospital</b><br>' + (r.hospital_name || '')).addTo(reqDetailMap);
                    bounds.push([r.hospital_lat, r.hospital_lng]);
                }

                _admTrackingData = {
                    status:      r.status,
                    pickupLat:   parseFloat(r.pickup_lat),
                    pickupLng:   parseFloat(r.pickup_lng),
                    hospitalLat: hasHospital     ? parseFloat(r.hospital_lat) : null,
                    hospitalLng: hasHospital     ? parseFloat(r.hospital_lng) : null,
                };

                _admApplySmartRoute(_admTrackingData, { forceRedraw: true });

                /* Show every currently-online driver on the map right away —
                   the show() response's `drivers` list already carries full
                   lat/lng/status/availability, no extra request needed. */
                _admInitOnlineDrivers(data.drivers || []);

                /* ── Dispatched-driver live tracking ──────────────────────────
                   For active (non-pending) requests that already have a driver,
                   place their dedicated marker and draw the initial split route.
                   Pending/AwaitingAcceptance requests use the generic dispatch
                   panel driver pool above instead. */
                const isNonPending = !['1', '8'].includes(String(r.status));
                if (r.driver && isNonPending) {
                    _admDispatchedDriverId = r.driver.id;

                    const dLat = parseFloat(r.driver.lat);
                    const dLng = parseFloat(r.driver.lng);
                    if (!isNaN(dLat) && !isNaN(dLng) && dLat !== 0 && dLng !== 0) {
                        const dPopup = `<div style="min-width:140px;font-size:.8rem;">
                            <b style="color:#2563eb;">🚑 ${_rrEsc(r.driver.name || 'Driver')}</b><br>
                            <span style="color:#94a3b8;">Last known location</span><br>
                            <small style="color:#9ca3af;">${dLat.toFixed(5)}, ${dLng.toFixed(5)}</small>
                        </div>`;
                        _admDispatchedDriverMarker = L.marker([dLat, dLng], { icon: buildDriverIcon(true) })
                            .bindPopup(dPopup)
                            .addTo(reqDetailMap);
                        bounds.push([dLat, dLng]);
                        _admDrawDispatchedDriverRoute(dLat, dLng);
                    }
                }

                if (bounds.length > 1) reqDetailMap.fitBounds(bounds, { padding: [40, 40] });
                reqDetailMap.invalidateSize();

            }, 250);
        }
    })
    .catch(() => {
        document.getElementById('reqDetailBody').innerHTML = '<p style="padding:20px;color:#f87171">Server error.</p>';
    });
}

/* ── Real-time hook: called by emergency-realtime.js when driver changes request status ── */
window._admHandleRequestStatusChanged = function (payload) {
    /* Only act when the modal is open for this exact request */
    var modalStatus = document.querySelector('#admModalReqStatus[data-req-id="' + payload.id + '"]');
    if (!modalStatus) return;

    var action = String(payload.action || '');
    var status = String(payload.status || '');

    /* Update the status text inside the modal detail panel */
    modalStatus.textContent = humanStatus(status);

    /* If the driver accepted, flip the "Awaiting Acceptance" panel to a live-status notice */
    if (action === 'accept') {
        var awaitingPanel = document.querySelector('.adm-dispatch-panel');
        if (awaitingPanel) {
            awaitingPanel.innerHTML =
                '<div class="adm-dispatch-panel__title" style="color:#4ade80;">' +
                    '<i class="fa fa-circle-check"></i> Driver Accepted' +
                '</div>' +
                '<div style="font-size:.82rem;color:rgba(255,255,255,.55);padding:8px 0 4px;">' +
                    'The driver has accepted this request. Status is being updated in real time.' +
                '</div>';
        }
    }

    /* If the request is now terminal (completed / cancelled), dim the modal panel */
    if (action === 'complete' || action === 'cancel') {
        var panel = document.querySelector('.adm-dispatch-panel');
        if (panel) {
            var termLabel = action === 'complete' ? 'Completed' : 'Cancelled';
            var termColor = action === 'complete' ? '#4ade80' : '#f87171';
            panel.innerHTML =
                '<div class="adm-dispatch-panel__title" style="color:' + termColor + ';">' +
                    '<i class="fa ' + (action === 'complete' ? 'fa-circle-check' : 'fa-ban') + '"></i> Ride ' + termLabel +
                '</div>' +
                '<div style="font-size:.82rem;color:rgba(255,255,255,.45);padding:6px 0 2px;">' +
                    'This request has been ' + termLabel.toLowerCase() + '.' +
                    (payload.completed_at ? ' <span style="color:rgba(255,255,255,.3);">' + payload.completed_at + '</span>' : '') +
                '</div>';
        }

        /* Automatically close the modal when the ride is completed so admin is not
           left viewing a stale request. A short delay lets the status message render
           before the modal dismisses. */
        if (action === 'complete') {
            setTimeout(function () { getReqDetailModal().hide(); }, 1500);
        }
    }

    /* If the driver rejected, show a "returned to pending" notice */
    if (action === 'reject') {
        var rejectPanel = document.querySelector('.adm-dispatch-panel');
        if (rejectPanel) {
            rejectPanel.innerHTML =
                '<div class="adm-dispatch-panel__title" style="color:#fbbf24;">' +
                    '<i class="fa fa-rotate-left"></i> Driver Rejected' +
                '</div>' +
                '<div style="font-size:.82rem;color:rgba(255,255,255,.55);padding:8px 0 4px;">' +
                    'The driver rejected this request. It has been returned to Pending — close and reopen to dispatch a new driver.' +
                '</div>';
        }
    }
};

/* ── Delete ── */
function deleteRequest(id) {
    confirmAction('Are you sure you want to delete this emergency request? This action cannot be undone.', function () {
        fetch(window.adminRoutes.requestsDelete + '/' + id, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('success','Request deleted successfully.');
            } else {
                showAlert('error', data.message || 'Delete failed.');
            }
        })
        .catch(function () { showAlert('error', 'Server error. Please try again.'); });
    });
}