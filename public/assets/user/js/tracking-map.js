/**
 * User Tracking Page — live map with real-time driver tracking.
 *
 * Mirrors the admin Emergency Request modal map exactly:
 *   - Before driver assignment : Pickup → Hospital route (green dashed)
 *   - After driver assignment  : Driver → Pickup (blue split) + Pickup → Hospital (green dashed)
 *   - On every GPS update      : driver marker moves, traveled portion turns grey
 *
 * Reuses window.pusher (initialized by user.blade.php layout).
 * Subscribes to:
 *   • emergency.{REQ_ID}  — status changes (same channel as tracking.js; Pusher deduplicates)
 *   • drivers-update      — live GPS pings from the driver
 *
 * No polling. No AJAX. No page reload. No duplicate connections.
 */
(function () {
    'use strict';

    /* ── State ─────────────────────────────────────────────────────────── */
    var map               = null;
    var pickupMarker      = null;
    var hospitalMarker    = null;
    var driverMarker      = null;
    var pickupToHospLayer = null;   // green-dashed pickup → hospital polyline
    var fullRouteCoords   = null;   // cached OSRM driver → pickup coord array
    var routeCompleted    = null;   // grey polyline: already-traveled segment
    var routeRemaining    = null;   // blue polyline: remaining segment
    var dispatchedDriverId = null;
    var driversChannel    = null;

    /* ── Icon builders (identical to admin emergency.js) ──────────────── */
    function buildPersonIcon() {
        return L.divIcon({
            className: '',
            html: '<div style="background:#D72C42;border:3px solid #fff;border-radius:50%;width:38px;height:38px;' +
                  'box-shadow:0 2px 14px rgba(215,44,66,0.55);display:flex;align-items:center;justify-content:center;">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="20" height="20">' +
                  '<circle cx="12" cy="7" r="4"/>' +
                  '<path d="M12 14c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4z"/>' +
                  '</svg></div>',
            iconSize: [38, 38], iconAnchor: [19, 19], popupAnchor: [0, -22],
        });
    }

    function buildHospitalIcon() {
        return L.divIcon({
            className: '',
            html: '<div style="background:#16a34a;border:3px solid #fff;border-radius:50%;width:34px;height:34px;' +
                  'box-shadow:0 2px 12px rgba(22,163,74,0.5);display:flex;align-items:center;justify-content:center;">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18" height="18">' +
                  '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z' +
                  'm-7 3a1 1 0 0 1 1 1v3h3a1 1 0 0 1 0 2h-3v3a1 1 0 0 1-2 0v-3H8a1 1 0 0 1 0-2h3V7a1 1 0 0 1 1-1z"/>' +
                  '</svg></div>',
            iconSize: [34, 34], iconAnchor: [17, 17], popupAnchor: [0, -20],
        });
    }

    function buildDriverIcon() {
        return L.divIcon({
            className: 'rr-driver-marker',
            html: '<div style="background:#2563eb;border:3px solid #fff;border-radius:50%;width:40px;height:40px;' +
                  'box-shadow:0 0 0 4px rgba(37,99,235,0.35),0 2px 12px rgba(37,99,235,0.6);' +
                  'display:flex;align-items:center;justify-content:center;">' +
                  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="22" height="22">' +
                  '<path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13v5a1 1 0 0 1-1 1h-1' +
                  'a2 2 0 1 1-4 0H9a2 2 0 1 1-4 0H4a1 1 0 0 1-1-1v-5z"/>' +
                  '</svg></div>',
            iconSize: [40, 40], iconAnchor: [20, 20], popupAnchor: [0, -24],
        });
    }

    /* ── OSRM helpers ─────────────────────────────────────────────────── */
    function fetchRouteCoords(fromLng, fromLat, toLng, toLat, onDone) {
        var url = 'https://router.project-osrm.org/route/v1/driving/' +
            fromLng + ',' + fromLat + ';' + toLng + ',' + toLat +
            '?overview=full&geometries=geojson';
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.routes || !d.routes[0] || !map) return;
                var coords = d.routes[0].geometry.coordinates.map(function (c) { return [c[1], c[0]]; });
                if (onDone) onDone(coords);
            })
            .catch(function () {});
    }

    function nearestVertexIndex(coords, lat, lng) {
        var minDist = Infinity, idx = 0;
        for (var i = 0; i < coords.length; i++) {
            var dlat = coords[i][0] - lat;
            var dlng = coords[i][1] - lng;
            var dist = dlat * dlat + dlng * dlng;
            if (dist < minDist) { minDist = dist; idx = i; }
        }
        return idx;
    }

    /* ── Layer helpers ────────────────────────────────────────────────── */
    function removeLayer(layer) {
        if (layer && map) { try { map.removeLayer(layer); } catch (e) {} }
        return null;
    }

    /* ── Route drawing ────────────────────────────────────────────────── */
    function drawPickupToHospital() {
        var td = window.TRACK_DATA;
        if (!td.hospitalLat || !td.hospitalLng || pickupToHospLayer) return;

        fetchRouteCoords(td.pickupLng, td.pickupLat, td.hospitalLng, td.hospitalLat, function (coords) {
            if (!map || coords.length < 2) return;
            pickupToHospLayer = L.polyline(coords, {
                color: '#16a34a', weight: 5, opacity: 0.85,
                lineJoin: 'round', dashArray: '10,8',
            }).addTo(map);
        });
    }

    function redrawRouteSplit(driverLat, driverLng) {
        if (!map || !fullRouteCoords || fullRouteCoords.length < 2) return;

        var nearestIdx = nearestVertexIndex(fullRouteCoords, driverLat, driverLng);
        var completed  = fullRouteCoords.slice(0, nearestIdx + 1);
        var remaining  = fullRouteCoords.slice(nearestIdx);

        routeCompleted = removeLayer(routeCompleted);
        routeRemaining = removeLayer(routeRemaining);

        if (completed.length >= 2) {
            routeCompleted = L.polyline(completed, {
                color: '#9ca3af', weight: 5, opacity: 0.7, lineJoin: 'round',
            }).addTo(map);
        }
        if (remaining.length >= 2) {
            routeRemaining = L.polyline(remaining, {
                color: '#2563eb', weight: 5, opacity: 0.85, lineJoin: 'round',
            }).addTo(map);
        }
    }

    function drawDispatchedDriverRoute(driverLat, driverLng) {
        if (!map) return;
        var td = window.TRACK_DATA;

        if (fullRouteCoords) {
            /* Route cached — re-split in place, zero network requests */
            redrawRouteSplit(driverLat, driverLng);
        } else {
            /* First call: fetch driver → pickup from OSRM, cache, then split */
            fetchRouteCoords(driverLng, driverLat, td.pickupLng, td.pickupLat, function (coords) {
                fullRouteCoords = coords;
                redrawRouteSplit(driverLat, driverLng);
            });

            /* Pickup → Hospital green-dashed leg — drawn once */
            drawPickupToHospital();
        }
    }

    /* ── Driver marker ────────────────────────────────────────────────── */
    function placeOrMoveDriver(lat, lng, name) {
        var popup = '<div style="min-width:140px;font-size:.8rem;">' +
            '<b style="color:#2563eb;">🚑 ' + (name || 'Ambulance') + '</b><br>' +
            '<span style="color:#94a3b8;">Live location</span>' +
            '</div>';

        if (!driverMarker) {
            driverMarker = L.marker([lat, lng], { icon: buildDriverIcon() })
                .bindPopup(popup)
                .addTo(map);
        } else {
            driverMarker.setLatLng([lat, lng]);
            driverMarker.setPopupContent(popup);
        }
    }

    function clearDriver() {
        driverMarker      = removeLayer(driverMarker);
        routeCompleted    = removeLayer(routeCompleted);
        routeRemaining    = removeLayer(routeRemaining);
        pickupToHospLayer = removeLayer(pickupToHospLayer);
        fullRouteCoords   = null;
        dispatchedDriverId = null;
    }

    /* ── Map initialisation ───────────────────────────────────────────── */
    function initMap() {
        var el = document.getElementById('trackingMap');
        if (!el || map) return;

        var td = window.TRACK_DATA;
        if (!td || !td.pickupLat || !td.pickupLng) return;

        map = L.map('trackingMap', { zoomControl: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        var bounds = [[td.pickupLat, td.pickupLng]];

        /* Pickup marker */
        pickupMarker = L.marker([td.pickupLat, td.pickupLng], { icon: buildPersonIcon() })
            .bindPopup('<b>Pickup Location</b><br>' + (td.pickupAddress || ''))
            .addTo(map);

        /* Hospital marker */
        if (td.hospitalLat && td.hospitalLng) {
            hospitalMarker = L.marker([td.hospitalLat, td.hospitalLng], { icon: buildHospitalIcon() })
                .bindPopup('<b>Destination Hospital</b><br>' + (td.hospitalName || ''))
                .addTo(map);
            bounds.push([td.hospitalLat, td.hospitalLng]);
        }

        /* Initial pickup → hospital route (always shown) */
        drawPickupToHospital();

        /* If driver already assigned and ride is active, show driver + split route */
        var status = String(td.status);
        var isActive = status === '2' || status === '3' || status === '4' || status === '5';
        if (td.driverId && isActive && td.driverLat && td.driverLng) {
            dispatchedDriverId = String(td.driverId);
            placeOrMoveDriver(td.driverLat, td.driverLng, td.driverName);
            drawDispatchedDriverRoute(td.driverLat, td.driverLng);
            bounds.push([td.driverLat, td.driverLng]);
        }

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [40, 40] });
        } else {
            map.setView([td.pickupLat, td.pickupLng], 14);
        }

        map.invalidateSize();
        bindRealtime();
    }

    /* ── Real-time bindings ───────────────────────────────────────────── */
    function bindRealtime() {
        if (!window.pusher) { setTimeout(bindRealtime, 200); return; }

        /* emergency.{REQ_ID} — ride status changes.
           Pusher deduplicates the subscription; tracking.js already subscribes
           to this channel, so this is a safe second bind on the same object. */
        var emergencyCh = window.pusher.subscribe('emergency.' + REQ_ID);
        emergencyCh.bind('emergency-request-status-changed', onStatusChanged);

        /* drivers-update — live GPS from the driver.
           The user layout does not subscribe to this channel, so we do it here. */
        driversChannel = window.pusher.subscribe('drivers-update');
        driversChannel.bind('drivers-update', function (e) {
            if (e && e.entity === 'driverLocationUpdated') {
                onDriverLocationUpdated(e.data !== undefined ? e.data : e);
            }
        });
    }

    function onStatusChanged(data) {
        var action = String(data.action || '');

        if (action === 'accept') {
            /* Payload now includes driver_id + accepted lat/lng (extended in controller).
               Set dispatchedDriverId so subsequent GPS pings are routed correctly. */
            if (data.driver_id) {
                dispatchedDriverId = String(data.driver_id);
            }
            var lat = parseFloat(data.driver_lat);
            var lng = parseFloat(data.driver_lng);
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                fullRouteCoords = null; // force fresh OSRM fetch from accept position
                placeOrMoveDriver(lat, lng, null);
                drawDispatchedDriverRoute(lat, lng);

                /* Re-fit map to show driver + pickup + hospital */
                if (map) {
                    var td = window.TRACK_DATA;
                    var b  = [[td.pickupLat, td.pickupLng]];
                    if (td.hospitalLat && td.hospitalLng) b.push([td.hospitalLat, td.hospitalLng]);
                    b.push([lat, lng]);
                    map.fitBounds(b, { padding: [40, 40] });
                }
            }
        }

        if (action === 'complete' || action === 'cancel' || action === 'reject') {
            clearDriver();
            /* Restore the simple pickup → hospital route */
            drawPickupToHospital();
        }
    }

    function onDriverLocationUpdated(d) {
        if (!dispatchedDriverId || String(d.id) !== dispatchedDriverId) return;
        var lat = parseFloat(d.lat), lng = parseFloat(d.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        placeOrMoveDriver(lat, lng, d.name || null);
        drawDispatchedDriverRoute(lat, lng);

        var ts = document.getElementById('lastTrackUpdate');
        if (ts) ts.textContent = 'just now';
    }

    /* ── Bootstrap: wait for Leaflet + TRACK_DATA ─────────────────────── */
    function waitAndInit() {
        if (typeof L === 'undefined' || !window.TRACK_DATA || !window.TRACK_DATA.pickupLat) {
            setTimeout(waitAndInit, 100);
            return;
        }
        initMap();
    }

    waitAndInit();
})();
