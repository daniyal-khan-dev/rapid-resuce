/* ── Nominatim Place Autocomplete — Rapid Rescue */

const RRAutoComplete = (function () {
    const _timers = {};

    function debounce(key, fn, delay) {
        clearTimeout(_timers[key]);
        _timers[key] = setTimeout(fn, delay);
    }

    function getBox(boxId) {
        return document.getElementById(boxId);
    }

    function hideSuggestions(boxId) {
        const box = getBox(boxId);
        if (box) { box.innerHTML = ''; box.setAttribute('data-open', 'false'); }
    }

    function showSpinner(boxId) {
        const box = getBox(boxId);
        if (!box) return;
        box.innerHTML = `<div class="rr-sug-item rr-sug-state">
            <i class="fas fa-spinner fa-spin" style="color:var(--rr-primary)"></i>&nbsp; Searching places…
        </div>`;
        box.setAttribute('data-open', 'true');
    }

    function buildDisplayName(r) {
        const parts = (r.display_name || '').split(',');
        const main  = parts.slice(0, 2).join(',').trim();
        const sub   = parts.slice(2, 5).join(',').trim();
        return { main, sub };
    }

    function renderResults(boxId, results, onSelect) {
        const box = getBox(boxId);
        if (!box) return;

        if (!results || results.length === 0) {
            box.innerHTML = `<div class="rr-sug-item rr-sug-state">
                <i class="fas fa-circle-xmark" style="color:var(--rr-muted)"></i>&nbsp; No places found. Try a different search.
            </div>`;
            box.setAttribute('data-open', 'true');
            return;
        }

        box.innerHTML = results.map((r, idx) => {
            const { main, sub } = buildDisplayName(r);
            const typeIcon = getTypeIcon(r.type, r.class);
            return `<div class="rr-sug-item" data-idx="${idx}" tabindex="0"
                         data-lat="${r.lat}" data-lng="${r.lon}"
                         data-label="${encodeURIComponent(r.display_name)}">
                <i class="fas ${typeIcon} rr-sug-icon"></i>
                <div class="rr-sug-text">
                    <span class="rr-sug-main">${escHtml(main)}</span>
                    ${sub ? `<span class="rr-sug-sub">${escHtml(sub)}</span>` : ''}
                </div>
            </div>`;
        }).join('');

        box.setAttribute('data-open', 'true');

        box.querySelectorAll('.rr-sug-item[data-idx]').forEach(item => {
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                const lat   = this.dataset.lat;
                const lng   = this.dataset.lng;
                const label = decodeURIComponent(this.dataset.label);
                onSelect(lat, lng, label);
                hideSuggestions(boxId);
            });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                }
            });
        });
    }

    function getTypeIcon(type, cls) {
        const t = (type || cls || '').toLowerCase();
        if (/hospital|clinic|medical|health/.test(t)) return 'fa-hospital';
        if (/school|university|college/.test(t)) return 'fa-graduation-cap';
        if (/restaurant|cafe|food/.test(t)) return 'fa-utensils';
        if (/hotel|motel/.test(t)) return 'fa-bed';
        if (/park|garden/.test(t)) return 'fa-tree';
        if (/road|street|avenue|highway/.test(t)) return 'fa-road';
        if (/city|town|village/.test(t)) return 'fa-city';
        return 'fa-map-marker-alt';
    }

    function escHtml(s) {
        return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function fetchPlaces(query, boxId, onSelect) {
        if (!query || query.length < 2) { hideSuggestions(boxId); return; }
        showSpinner(boxId);
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=6&addressdetails=1`;
        fetch(url, { headers: { 'Accept': 'application/json', 'Accept-Language': 'en' } })
            .then(r => r.json())
            .then(data => renderResults(boxId, data, onSelect))
            .catch(() => hideSuggestions(boxId));
    }

    function fetchNearbyHospitals(boxId, onSelect) {
        if (!navigator.geolocation) return;
    
        showSpinner(boxId);
    
        navigator.geolocation.getCurrentPosition(function (position) {
    
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
    
            // Search hospitals within approximately 10km
            const viewbox =
                `${lng - 0.1},${lat + 0.1},${lng + 0.1},${lat - 0.1}`;
    
            const url =
                `https://nominatim.openstreetmap.org/search?` +
                `q=hospital` +
                `&format=json` +
                `&limit=8` +
                `&bounded=1` +
                `&viewbox=${viewbox}` +
                `&addressdetails=1`;
    
            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Accept-Language': 'en'
                }
            })
            .then(r => r.json())
            .then(data => renderResults(boxId, data, onSelect))
            .catch(() => hideSuggestions(boxId));
    
        }, function () {
            hideSuggestions(boxId);
        }, {
            enableHighAccuracy: true,
            timeout: 10000
        });
    }
    
    /**
     * Bind autocomplete to an input.
     * @param {object} cfg
     * @param {string} cfg.inputId   - text input id
     * @param {string} cfg.boxId     - suggestions container id
     * @param {string} cfg.latId     - hidden lat input id
     * @param {string} cfg.lngId     - hidden lng input id
     * @param {string} cfg.key       - unique debounce key
     * @param {number} [cfg.ms=350]  - debounce milliseconds
     */
    function bind(cfg) {
        const input = document.getElementById(cfg.inputId);
        const latEl = document.getElementById(cfg.latId);
        const lngEl = document.getElementById(cfg.lngId);
        if (!input) return;

        let _fromSelection = false;

        function onSelect(lat, lng, label) {
            _fromSelection = true;
            input.value    = label;
            if (latEl) latEl.value = lat;
            if (lngEl) lngEl.value = lng;
            clearError(cfg.inputId);
            // reset flag after event loop
            setTimeout(() => { _fromSelection = false; }, 0);
        }

        input.addEventListener('input', function () {
            if (_fromSelection) return;
            // Clear stored coords whenever user edits manually
            if (latEl) latEl.value = '';
            if (lngEl) lngEl.value = '';
            debounce(cfg.key, () => fetchPlaces(this.value.trim(), cfg.boxId, onSelect), cfg.ms ?? 350);
        });

        input.addEventListener('blur', function () {
            // Small delay so mousedown on a suggestion fires first
            setTimeout(() => hideSuggestions(cfg.boxId), 200);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideSuggestions(cfg.boxId);
        });

        input.addEventListener('focus', function () {

            // Show nearby hospitals immediately
            if (cfg.inputId === 'hospital_name' && this.value.trim() === '') {
                fetchNearbyHospitals(cfg.boxId, onSelect);
                return;
            }
        
            const box = getBox(cfg.boxId);
        
            if (box && box.children.length > 0) {
                box.setAttribute('data-open', 'true');
            }
        }); 
    }

    function clearError(inputId) {
        const errEl = document.getElementById(inputId + '_error');
        if (errEl) errEl.textContent = '';
        const input = document.getElementById(inputId);
        if (input) input.classList.remove('rr-input--error');
    }

    function showError(inputId, msg) {
        const errEl = document.getElementById(inputId + '_error');
        if (errEl) errEl.textContent = msg;
        const input = document.getElementById(inputId);
        if (input) input.classList.add('rr-input--error');
    }

    /**
     * Validate that an autocomplete field has a selected lat/lng.
     * Returns true if valid, false otherwise (and shows error).
     */
    function requireSelection(inputId, latId, msg) {
        const val  = (document.getElementById(inputId)?.value || '').trim();
        const lat  = (document.getElementById(latId)?.value || '').trim();
        if (!val) {
            showError(inputId, msg || 'This field is required.');
            return false;
        }
        if (!lat) {
            showError(inputId, msg || 'Please select a location from the suggestions.');
            return false;
        }
        clearError(inputId);
        return true;
    }

    return { bind, hideSuggestions, requireSelection, showError, clearError };
})();

/* ── Init on DOM ready */
document.addEventListener('DOMContentLoaded', function () {
    // Hospital Name
    RRAutoComplete.bind({
        inputId: 'hospital_name',
        boxId:   'hospitalSuggestions',
        latId:   'hospital_lat',
        lngId:   'hospital_lng',
        key:     'hospital',
        ms:      350,
    });

    // Pickup Location
    RRAutoComplete.bind({
        inputId: 'pickup_address',
        boxId:   'pickupSuggestions',
        latId:   'latitude',
        lngId:   'longitude',
        key:     'pickup',
        ms:      350,
    });
});
