(function () {
    'use strict';

    /* Only activate on pages that render the live map container */
    var mapEl = document.getElementById('driLiveMap');
    if (!mapEl) return;

    /* Guard: initialize once even if this script is somehow loaded twice */
    if (window._rrDriLiveMapBound) return;
    window._rrDriLiveMapBound = true;

    var map       = null;
    var marker    = null;
    var accCircle = null;
    var lastLat   = null;
    var lastLng   = null;

    var markerIconHtml =
        '<div style="width:18px;height:18px;background:#D72C42;border:3px solid #fff;border-radius:50%;' +
        'box-shadow:0 2px 10px rgba(215,44,66,0.6);"></div>';

    function buildMarkerIcon() {
        return L.divIcon({
            className: '',
            html: markerIconHtml,
            iconSize: [18, 18],
            iconAnchor: [9, 9],
        });
    }

    function initMap(lat, lng) {
        if (map) return; /* never recreate the map */

        map = L.map(mapEl, { zoomControl: true, attributionControl: true }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        marker = L.marker([lat, lng], { icon: buildMarkerIcon() }).addTo(map); /* only ever one marker */
        lastLat = lat;
        lastLng = lng;
    }

    function isValidCoord(lat, lng) {
        return typeof lat === 'number' && typeof lng === 'number' &&
            !isNaN(lat) && !isNaN(lng) &&
            lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
    }

    function updateAccuracyCircle(lat, lng, accuracy) {
        if (typeof accuracy !== 'number' || isNaN(accuracy) || accuracy <= 0) return;

        if (!accCircle) {
            accCircle = L.circle([lat, lng], {
                radius: accuracy,
                color: '#D72C42',
                weight: 1,
                fillColor: '#D72C42',
                fillOpacity: 0.08,
            }).addTo(map);
        } else {
            accCircle.setLatLng([lat, lng]);
            accCircle.setRadius(accuracy);
        }
    }

    function updateHeading(heading) {
        if (!marker || typeof heading !== 'number' || isNaN(heading)) return;
        var el = marker.getElement();
        if (!el) return;
        var inner = el.firstChild;
        if (inner && inner.style) {
            inner.style.transform = 'rotate(' + heading + 'deg)';
        }
    }

    function updateInfoText(lat, lng, updatedAt) {
        var coordsEl = document.getElementById('driMapCoords');
        if (coordsEl) coordsEl.textContent = lat.toFixed(5) + ',\u00A0\u00A0' + lng.toFixed(5);

        var updEl = document.getElementById('driMapLastUpdate');
        if (updEl) {
            var d;
            try { d = updatedAt ? new Date(updatedAt) : new Date(); } catch (e) { d = new Date(); }
            updEl.textContent = 'Updated ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }

    /* Central entry point: initializes the map on first fix, otherwise moves
       the existing marker only -- never recreates map/marker, and skips work
       entirely when the coordinates haven't actually changed. */
    function updatePosition(lat, lng, accuracy, heading, updatedAt) {
        lat = parseFloat(lat);
        lng = parseFloat(lng);
        if (!isValidCoord(lat, lng)) return;

        if (!map) {
            initMap(lat, lng);
        } else if (lat !== lastLat || lng !== lastLng) {
            marker.setLatLng([lat, lng]);
            map.panTo([lat, lng], { animate: true, duration: 0.8 });
            lastLat = lat;
            lastLng = lng;
        }

        updateAccuracyCircle(lat, lng, accuracy);
        updateHeading(heading);
        updateInfoText(lat, lng, updatedAt);
    }

    window._rrUpdateDriverMapPosition = updatePosition;

    /* Paint the map immediately from the server-rendered last-known fix */
    var initialLat = parseFloat(mapEl.getAttribute('data-lat'));
    var initialLng = parseFloat(mapEl.getAttribute('data-lng'));
    if (isValidCoord(initialLat, initialLng)) {
        updatePosition(initialLat, initialLng, null, null, null);
    }

    /* ── Push this driver's own live GPS to the server so it can rebroadcast
       it over Reverb (event-driven via watchPosition -- no setInterval/polling) ── */
    if (navigator.geolocation && window.driDriverId && window.driLocationUpdateUrl) {
        var lastSentAt = 0;
        var MIN_SEND_INTERVAL_MS = 4000;

        function sendLocationToServer(lat, lng, accuracy, heading) {
            var now = Date.now();
            if (now - lastSentAt < MIN_SEND_INTERVAL_MS) return;
            lastSentAt = now;

            fetch(window.driLocationUpdateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ lat: lat, lng: lng, accuracy: accuracy, heading: heading }),
                keepalive: true,
            }).catch(function () { /* offline / transient network error -- next fix will retry */ });
        }

        navigator.geolocation.watchPosition(
            function (pos) {
                var lat      = pos.coords.latitude;
                var lng      = pos.coords.longitude;
                var accuracy = pos.coords.accuracy;
                var heading  = pos.coords.heading;

                /* Paint locally right away; the Reverb echo of this same fix
                   will arrive shortly after and no-op since coords match. */
                updatePosition(lat, lng, accuracy, heading, new Date().toISOString());
                sendLocationToServer(lat, lng, accuracy, heading);
            },
            function () { /* permission denied / unavailable -- leave last known position on the map */ },
            { enableHighAccuracy: true, maximumAge: 5000, timeout: 20000 }
        );
    }
}());
