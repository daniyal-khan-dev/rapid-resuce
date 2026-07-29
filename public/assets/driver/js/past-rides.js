/* ── Hash-based navigation with AJAX result loading ─────────────────────────── */
function prBuildParams() {
    var form = document.getElementById("prFilterForm");
    var params = new URLSearchParams();
    ["search", "status", "date_filter", "date_from", "date_to"].forEach(
        function (n) {
            var el = form.elements[n];
            if (!el) return;
            var v = el.value;
            if (!v || v === "") return;
            if (n === "date_filter" && v === "all") return;
            params.set(n, v);
        },
    );
    return params;
}

function prNavigate() {
    var qs = prBuildParams().toString();
    history.replaceState(null, "", location.pathname + (qs ? "#" + qs : ""));
    prFetch(qs);
}

function prFetch(qs) {
    var url =
        document.getElementById("prFilterForm").action + (qs ? "?" + qs : "");
    var tableEl = document.getElementById("prTableSection");
    if (tableEl) tableEl.style.opacity = "0.45";
    fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then(function (r) {
            return r.text();
        })
        .then(function (html) {
            var doc = new DOMParser().parseFromString(html, "text/html");
            var ns = doc.getElementById("prStatsSection");
            var nt = doc.getElementById("prTableSection");
            if (ns && document.getElementById("prStatsSection"))
                document.getElementById("prStatsSection").outerHTML =
                    ns.outerHTML;
            if (nt) {
                if (document.getElementById("prTableSection"))
                    document.getElementById("prTableSection").outerHTML =
                        nt.outerHTML;
                try {
                    var d = document
                        .getElementById("prTableSection")
                        .getAttribute("data-rides");
                    if (d) _prData = JSON.parse(d);
                } catch (e) {}
                var cb = document.getElementById("prClearBtn");
                if (cb) cb.style.display = qs ? "" : "none";
            }
        })
        .catch(function () {
            var t = document.getElementById("prTableSection");
            if (t) t.style.opacity = "1";
        });
}

document
    .getElementById("prFilterForm")
    .addEventListener("submit", function (e) {
        e.preventDefault();
        prNavigate();
    });

/* Restore filters from URL hash on page load */
(function () {
    var hash = location.hash;
    if (hash && hash.length > 1) {
        var qs = hash.substring(1);
        var params = new URLSearchParams(qs);
        var form = document.getElementById("prFilterForm");
        params.forEach(function (v, k) {
            var el = form.elements[k];
            if (el) el.value = v;
        });
        prFetch(qs);
    }
})();

/* ── Date filter helper ──────────────────────────────────────────────────────── */
function prSetDateFilter(val) {
    document.getElementById("prDateFilterHidden").value = val;
    if (val !== "custom") {
        document
            .querySelectorAll('.pr-date-range input[type="date"]')
            .forEach(function (i) {
                i.value = "";
            });
    }
    prNavigate();
}

function prToggleCustomRange() {
    var r = document.getElementById("prCustomRange");
    if (r.style.display === "none") {
        r.style.display = "flex";
        r.querySelector('input[type="date"]').focus();
    } else {
        r.style.display = "none";
    }
}

/* ── HTML escape ─────────────────────────────────────────────────────────────── */
function prEsc(s) {
    return String(s || "—")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

/* ── View detail modal ───────────────────────────────────────────────────────── */
function prViewDetail(id) {
    var req = _prData.find(function (r) {
        return r.id === id;
    });
    if (!req) return;

    var statusLabel = req.status == "6" ? "Completed" : "Cancelled";
    var statusPill =
        req.status == "6"
            ? '<span class="pr-pill pr-pill--completed"><i class="fa fa-circle-check me-1"></i>Completed</span>'
            : '<span class="pr-pill pr-pill--cancelled"><i class="fa fa-ban me-1"></i>Cancelled</span>';
    var typePill =
        req.type == "1" || req.type === 1
            ? '<span class="pr-type-badge pr-type-badge--emergency">Emergency</span>'
            : '<span class="pr-type-badge pr-type-badge--non-emergency">Non-Emergency</span>';

    var html =
        '<div style="padding:4px 0;">' +
        '<div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-wrap:wrap;">' +
        typePill +
        " " +
        statusPill +
        '<span style="font-family:monospace;font-size:.9rem;color:#a5b4fc;margin-left:4px;">' +
        prEsc(req.rreb_id) +
        "</span>" +
        "</div>" +
        '<hr class="pr-modal-divider">' +
        '<div style="font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);margin-bottom:12px;">' +
        '<i class="fa fa-map-location-dot me-1" style="color:#818cf8;"></i> Trip Route' +
        "</div>" +
        '<div class="pr-trip-map-wrap">' +
        '<div id="prTripMap" class="pr-trip-map"></div>' +
        '<div id="prTripMapLoader" class="pr-trip-map-loader">' +
        '<div style="color:rgba(255,255,255,.35);font-size:.82rem;text-align:center;">' +
        '<i class="fa fa-spinner fa-spin" style="font-size:1.2rem;margin-bottom:8px;display:block;opacity:.5;"></i>Loading route…' +
        "</div>" +
        "</div>" +
        "</div>" +
        '<div class="pr-trip-legend">' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#6366f1;border:2px solid #fff;"></span>Driver start</span>' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#ef4444;border:2px solid #fff;"></span>Pickup</span>' +
        '<span><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#22c55e;border:2px solid #fff;"></span>Hospital</span>' +
        '<span><span style="display:inline-block;width:26px;height:3px;background:#94a3b8;border-radius:2px;vertical-align:middle;"></span>Completed route</span>' +
        "</div>" +
        '<div id="prTripMapInfo" class="pr-trip-map-info"></div>' +
        '<hr class="pr-modal-divider">' +
        '<div class="pr-modal-grid">' +
        '<div class="pr-modal-field"><label>Mobile No.</label><span>' +
        prEsc(req.mobile_no) +
        "</span></div>" +
        '<div class="pr-modal-field"><label>Ambulance</label><span>' +
        prEsc(req.ambulance_no) +
        "</span></div>" +
        '<div class="pr-modal-field" style="grid-column:1/-1"><label>Pickup Address</label><span>' +
        prEsc(req.pickup_address) +
        "</span></div>" +
        '<div class="pr-modal-field" style="grid-column:1/-1"><label>Destination Hospital</label><span>' +
        prEsc(req.hospital_name) +
        "</span></div>" +
        "</div>" +
        '<hr class="pr-modal-divider">' +
        '<div class="pr-modal-grid">' +
        '<div class="pr-modal-field"><label>Created At</label><span>' +
        prEsc(req.created_at) +
        "</span></div>" +
        '<div class="pr-modal-field"><label>Dispatched At</label><span>' +
        prEsc(req.dispatched_at) +
        "</span></div>" +
        '<div class="pr-modal-field"><label>' +
        statusLabel +
        " At</label><span>" +
        prEsc(req.completed_at) +
        "</span></div>" +
        "</div>";

    if (req.notes) {
        html +=
            '<hr class="pr-modal-divider">' +
            '<div class="pr-modal-field"><label>Notes</label>' +
            '<span style="white-space:pre-wrap;">' +
            prEsc(req.notes) +
            "</span></div>";
    }

    html += "</div>";

    document.getElementById("prModalTitle").innerHTML =
        '<i class="fa fa-clock-rotate-left me-2" style="color:#818cf8;"></i>Ride Details — ' +
        prEsc(req.rreb_id);
    document.getElementById("prModalBody").innerHTML = html;

    var modalEl = document.getElementById("prDetailModal");
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Destroy any previous Leaflet instance before opening a new modal
    if (_prTripMapInst) {
        try {
            _prTripMapInst.remove();
        } catch (ex) {}
        _prTripMapInst = null;
    }

    function _onPrShown() {
        modalEl.removeEventListener("shown.bs.modal", _onPrShown);
        _prTripMapInst = _prInitTripMap(
            "prTripMap",
            "prTripMapLoader",
            "prTripMapInfo",
            req,
        );
    }
    modalEl.addEventListener("shown.bs.modal", _onPrShown);

    modal.show();
}

/* ── Static trip route map ───────────────────────────────────────────────────── */
var _prTripMapInst = null;

function _prInitTripMap(mapId, loaderId, infoId, ride) {
    var mapEl = document.getElementById(mapId);
    var loader = document.getElementById(loaderId);
    var infoEl = document.getElementById(infoId);
    if (!mapEl || typeof L === "undefined") return null;

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
        mapEl.innerHTML =
            '<div style="display:flex;align-items:center;justify-content:center;height:100%;' +
            'color:rgba(255,255,255,.2);font-size:.82rem;">' +
            '<span><i class="fa fa-map-location-dot" style="margin-right:6px;opacity:.3;"></i>No location data saved for this ride.</span></div>';
        if (loader) loader.style.display = "none";
        return null;
    }

    var map = L.map(mapEl, {
        zoomControl: true,
        attributionControl: false,
    });
    // OpenStreetMap (standard bright map)
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    function _mkIcon(bg, svgPath) {
        return L.divIcon({
            className: "",
            html:
                '<div style="background:' +
                bg +
                ";border:3px solid #fff;border-radius:50%;" +
                "width:34px;height:34px;box-shadow:0 2px 12px rgba(0,0,0,.5);" +
                'display:flex;align-items:center;justify-content:center;">' +
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="15" height="15">' +
                svgPath +
                "</svg></div>",
            iconSize: [34, 34],
            iconAnchor: [17, 17],
            popupAnchor: [0, -20],
        });
    }

    var _svgAmb =
        '<path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>';
    var _svgPrsn =
        '<circle cx="12" cy="7" r="4"/><path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>';
    var _svgHosp =
        '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm-7 3a1 1 0 0 1 1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>';

    var bounds = [];
    if (hasAcc) {
        L.marker([accLat, accLng], {
            icon: _mkIcon("#6366f1", _svgAmb),
        })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:130px;"><b style="color:#6366f1;">🚑 Driver Start</b><br><small style="color:#9ca3af;">Accepted ride from here</small></div>',
            )
            .addTo(map);
        bounds.push([accLat, accLng]);
    }
    if (hasPick) {
        L.marker([pickLat, pickLng], {
            icon: _mkIcon("#ef4444", _svgPrsn),
        })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:130px;"><b style="color:#ef4444;">📍 Pickup</b><br><small style="color:#9ca3af;">' +
                    (ride.pickup_address || "") +
                    "</small></div>",
            )
            .addTo(map);
        bounds.push([pickLat, pickLng]);
    }
    if (hasHosp) {
        L.marker([hospLat, hospLng], {
            icon: _mkIcon("#22c55e", _svgHosp),
        })
            .bindPopup(
                '<div style="font-size:.82rem;min-width:130px;"><b style="color:#22c55e;">🏥 Hospital</b><br><small style="color:#9ca3af;">' +
                    (ride.hospital_name || "") +
                    "</small></div>",
            )
            .addTo(map);
        bounds.push([hospLat, hospLng]);
    }

    if (bounds.length === 1) {
        map.setView(bounds[0], 15);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, {
            padding: [50, 50],
        });
    }

    if (loader) loader.style.display = "none";

    // Fetch grey completed route from OSRM (static — no live tracking)
    var wps = [];
    if (hasAcc) wps.push(accLng + "," + accLat);
    if (hasPick) wps.push(pickLng + "," + pickLat);
    if (hasHosp) wps.push(hospLng + "," + hospLat);

    if (wps.length >= 2) {
        fetch(
            "https://router.project-osrm.org/route/v1/driving/" +
                wps.join(";") +
                "?overview=full&geometries=geojson",
        )
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.routes || !data.routes[0]) return;
                var route = data.routes[0];
                var coords = route.geometry.coordinates.map(function (c) {
                    return [c[1], c[0]];
                });
                L.polyline(coords, {
                    color: "#94a3b8",
                    weight: 4,
                    opacity: 0.85,
                    lineJoin: "round",
                }).addTo(map);
                map.fitBounds(coords, {
                    padding: [40, 40],
                });
                if (infoEl) {
                    var km = (route.distance / 1000).toFixed(1);
                    var mins = Math.round(route.duration / 60);
                    infoEl.innerHTML =
                        '<span><i class="fa fa-route" style="color:#818cf8;margin-right:4px;"></i>' +
                        km +
                        " km total route</span>" +
                        (mins > 0
                            ? '<span><i class="fa fa-clock" style="color:#60a5fa;margin-right:4px;"></i>~' +
                              mins +
                              " min estimated drive</span>"
                            : "");
                }
            })
            .catch(function () {});
    }

    return map;
}

/* ── Real-time: completed ride inserted into Past Rides ──────────────────────
 * Called by realtime.js when the driver broadcasts a 'complete' action.
 * Inserts a new row at the top of #prTbody (unless filters/pagination prevent a
 * safe insert), updates stat cards, and keeps _prData in sync for the detail modal.
 * ─────────────────────────────────────────────────────────────────────────── */
window._rrOnRideCompleted = function (payload) {
    if (!payload || !payload.id) return;

    /* Duplicate guard */
    if (document.getElementById('prRow_' + payload.id)) return;

    /* Read active filter state from the form (hash-nav keeps form values in sync) */
    var form         = document.getElementById('prFilterForm');
    var hasSearch    = !!(form && form.elements['search'] && form.elements['search'].value);
    var statusFilter = (form && form.elements['status'])      ? form.elements['status'].value      : '';
    var dateFilter   = (form && form.elements['date_filter']) ? form.elements['date_filter'].value  : 'all';
    var dateFrom     = (form && form.elements['date_from'])   ? form.elements['date_from'].value    : '';
    var dateTo       = (form && form.elements['date_to'])     ? form.elements['date_to'].value      : '';
    var hasDateFilter = (dateFilter && dateFilter !== 'all') || !!dateFrom || !!dateTo;

    /* If filtered to Cancelled Only, a completed ride is out of scope — update stats only */
    if (statusFilter === '7') {
        var scOnly = document.getElementById('prStatCompleted');
        if (scOnly) scOnly.textContent = Math.max(0, (parseInt(scOnly.textContent, 10) || 0) + 1);
        return;
    }

    /* Detect page > 1 via the active pagination link */
    var activePageEl = document.querySelector('.pr-pag-links .page-link.active');
    var isPage2plus  = !!(activePageEl && parseInt(activePageEl.textContent, 10) > 1);

    /* Row insertion is safe only when: no search, no date filter, on page 1 */
    var canInsert = !hasSearch && !hasDateFilter && !isPage2plus;
    var tbody     = document.getElementById('prTbody');

    if (canInsert && tbody) {
        /* Remove empty-state placeholder if present */
        var emptyRow = document.getElementById('prEmptyRow');
        if (emptyRow) emptyRow.remove();

        /* Build new row HTML */
        function _trunc(s, n) { return s && s.length > n ? s.substring(0, n) + '\u2026' : (s || '\u2014'); }

        var typeHtml = (String(payload.type) === '1')
            ? '<span class="pr-type-badge pr-type-badge--emergency">Emergency</span>'
            : '<span class="pr-type-badge pr-type-badge--non-emergency">Non-Emergency</span>';

        /* "17 Jul 2026, 04:30 PM" -> "17 Jul 2026" */
        var dateDisplay = (payload.created_at || '').split(',')[0] || '\u2014';

        var tr      = document.createElement('tr');
        tr.id       = 'prRow_' + payload.id;
        tr.className = 'pr-new-row';
        tr.innerHTML =
            '<td><span style="font-family:monospace;font-size:.78rem;color:#a5b4fc;font-weight:600;">' +
                prEsc(payload.rreb_id || '') + '</span></td>' +
            '<td>' + typeHtml + '</td>' +
            '<td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' +
                prEsc(payload.pickup_address) + '">' + prEsc(_trunc(payload.pickup_address, 32)) + '</td>' +
            '<td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' +
                prEsc(payload.hospital_name) + '">' + prEsc(_trunc(payload.hospital_name, 28)) + '</td>' +
            '<td style="font-size:.78rem;color:rgba(255,255,255,.5);">' + prEsc(payload.ambulance_no || '\u2014') + '</td>' +
            '<td><span class="pr-pill pr-pill--completed"><i class="fa fa-circle-check me-1"></i>Completed</span></td>' +
            '<td style="white-space:nowrap;color:rgba(255,255,255,.38);font-size:.77rem;">' + prEsc(dateDisplay) + '</td>' +
            '<td><button class="btn-dri-icon btn-dri-icon--primary" title="View Details" ' +
                'onclick="prViewDetail(' + payload.id + ')"><i class="fa fa-eye"></i></button></td>';

        tbody.insertBefore(tr, tbody.firstChild);

        /* Register in _prData so the detail modal can read it */
        if (typeof _prData !== 'undefined' && Array.isArray(_prData)) {
            _prData.unshift({
                id:             payload.id,
                rreb_id:        payload.rreb_id        || '',
                type:           payload.type,
                status:         '6',
                pickup_address: payload.pickup_address || '',
                hospital_name:  payload.hospital_name  || '',
                mobile_no:      payload.mobile_no      || '',
                ambulance_no:   payload.ambulance_no   || '',
                notes:          payload.notes          || '',
                completed_at:   payload.completed_at   || '',
                dispatched_at:  payload.dispatched_at  || '',
                created_at:     payload.created_at     || '',
                accepted_lat:   payload.accepted_lat   || null,
                accepted_lng:   payload.accepted_lng   || null,
                pickup_lat:     payload.pickup_lat     || null,
                pickup_lng:     payload.pickup_lng     || null,
                hospital_lat:   payload.hospital_lat   || null,
                hospital_lng:   payload.hospital_lng   || null,
            });
        }

        /* Update pagination info: +1 to last-item and total */
        var pgInfo = document.querySelector('.pr-pag-info');
        if (pgInfo) {
            pgInfo.textContent = pgInfo.textContent.replace(
                /(\d+)\s*[–\-]\s*(\d+)\s+of\s+(\d+)/,
                function (_, f, l, t) {
                    return f + '\u2013' + (parseInt(l, 10) + 1) + ' of ' + (parseInt(t, 10) + 1);
                }
            );
        }
    }

    /* Always update stat cards regardless of filter / pagination state */
    var statCompleted = document.getElementById('prStatCompleted');
    if (statCompleted) {
        statCompleted.textContent = Math.max(0, (parseInt(statCompleted.textContent, 10) || 0) + 1);
    }

    /* Total Rides card (no ID — match by label text) */
    document.querySelectorAll('.pr-stat-card').forEach(function (card) {
        var lbl = card.querySelector('.pr-stat-card__lbl');
        var val = card.querySelector('.pr-stat-card__val');
        if (lbl && val && lbl.textContent.trim() === 'Total Rides') {
            val.textContent = Math.max(0, (parseInt(val.textContent, 10) || 0) + 1);
        }
    });
};
