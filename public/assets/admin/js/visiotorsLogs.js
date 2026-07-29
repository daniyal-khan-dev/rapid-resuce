function switchYear(year) {
    document.querySelectorAll(".log-year-panel").forEach(function (p) {
        p.classList.remove("active");
    });
    var panel = document.getElementById("year-panel-" + year);
    if (panel) panel.classList.add("active");
}

function toggleMonth(key) {
    document.getElementById("month-body-" + key).classList.toggle("open");
}

function applyVFilter(monthKey, filter, btn) {
    document
        .querySelectorAll("#month-body-" + monthKey + " .log-filter-btn")
        .forEach(function (b) {
            b.classList.remove("active");
        });
    btn.classList.add("active");
    var rows = document.querySelectorAll(
        "#vlog-tbody-" + monthKey + " tr.vlog-row",
    );
    rows.forEach(function (row) {
        row.style.display =
            filter === "all" || row.dataset.device === filter ? "" : "none";
    });
    checkVNoData(monthKey);
}

function checkVNoData(monthKey) {
    var tbody = document.getElementById("vlog-tbody-" + monthKey);
    if (!tbody) return;
    var visible = Array.from(tbody.querySelectorAll("tr.vlog-row")).filter(
        function (r) {
            return r.style.display !== "none";
        },
    );
    var noData = tbody.querySelector(".vlog-no-data-row");
    if (visible.length === 0) {
        if (!noData) {
            noData = document.createElement("tr");
            noData.className = "vlog-no-data-row";
            noData.innerHTML =
                '<td colspan="7" class="log-no-data">No visitors found for this filter.</td>';
            tbody.appendChild(noData);
        }
    } else {
        if (noData) noData.remove();
    }
}

function filterVisitors() {
    var q = document.getElementById("searchVisitor").value.toLowerCase();
    var device = document.getElementById("filterDevice").value;
    document.querySelectorAll(".vlog-row").forEach(function (row) {
        var matchSearch = !q || row.dataset.search.includes(q);
        var matchDevice = !device || row.dataset.device === device;
        row.style.display = matchSearch && matchDevice ? "" : "none";
    });
}

// REAL TIME UPDATE REVERB
(function () {
    var MONTH_NAMES = ['January','February','March','April','May','June', 'July','August','September','October','November','December'];
    var seenAt = new Set();

    window.pusher.subscribe('admin-dashboard').bind('visitor-log-created', function (data) {
        if (seenAt.has(data.created_at)) return;
        seenAt.add(data.created_at);

        var dt       = new Date(data.created_at);
        var year     = dt.getFullYear();
        var month    = dt.getMonth() + 1;
        var monthKey = year + '-' + month;

        ensurePanel(year, month, monthKey);
        prependRow(data, monthKey, dt);
        incrementBadge(monthKey);
        updateStatCards(data, dt);
    });

    /* ── Ensure year panel + month section exist ─────────────────────── */
    function ensurePanel(year, month, monthKey) {
        if (document.getElementById('vlog-tbody-' + monthKey)) return;

        var emptyCard = document.querySelector('.adm-empty');
        if (emptyCard) {
            var card = emptyCard.closest('.card');
            if (card) card.remove();
        }

        var yearPanel = document.getElementById('year-panel-' + year);
        if (!yearPanel) {
            yearPanel = document.createElement('div');
            yearPanel.className = 'log-year-panel active';
            yearPanel.id = 'year-panel-' + year;

            document.querySelectorAll('.log-year-panel').forEach(function (p) {
                p.classList.remove('active');
            });

            var dropdown = document.getElementById('yearDropdown');
            if (dropdown) {
                Array.from(dropdown.options).forEach(function (o) { o.selected = false; });
                var opt = document.createElement('option');
                opt.value = year; opt.text = year; opt.selected = true;
                dropdown.insertBefore(opt, dropdown.firstChild);
            }

            var panels = document.querySelectorAll('.log-year-panel');
            if (panels.length) {
                panels[panels.length - 1].after(yearPanel);
            } else {
                var filterRow = document.querySelector('.adm-filter-row');
                if (filterRow) filterRow.after(yearPanel);
                else document.querySelector('.adm-page-header').after(yearPanel);
            }
        }

        var mName   = MONTH_NAMES[month - 1];
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-3';
        wrapper.innerHTML =
            '<button class="log-month-btn" onclick="toggleMonth(\'' + monthKey + '\')">' +
                '<i class="fa fa-binoculars" style="color:var(--adm-muted);"></i>' +
                '<span>' + mName + ' ' + year + '</span>' +
                '<span class="log-count-badge">0 visitors</span>' +
            '</button>' +
            '<div class="log-month-body open" id="month-body-' + monthKey + '">' +
                '<div class="d-flex flex-wrap gap-2 mb-3 px-2">' +
                    '<button class="log-filter-btn active" onclick="applyVFilter(\'' + monthKey + '\',\'all\',this)">All</button>' +
                    '<button class="log-filter-btn" onclick="applyVFilter(\'' + monthKey + '\',\'mobile\',this)">Mobile</button>' +
                    '<button class="log-filter-btn" onclick="applyVFilter(\'' + monthKey + '\',\'desktop\',this)">Desktop</button>' +
                '</div>' +
                '<div class="table-responsive" style="border-radius:var(--adm-radius-sm);border:1px solid var(--adm-border);max-height:360px;overflow-y:auto;">' +
                    '<table class="table table-hover mb-0" style="font-size:0.855rem;">' +
                        '<thead><tr>' +
                            '<th class="ps-4">#</th>' +
                            '<th>IP Address</th>' +
                            '<th>Browser</th>' +
                            '<th>Platform</th>' +
                            '<th>Device</th>' +
                            '<th>Type</th>' +
                            '<th>Date &amp; Time</th>' +
                        '</tr></thead>' +
                        '<tbody id="vlog-tbody-' + monthKey + '"></tbody>' +
                    '</table>' +
                '</div>' +
            '</div>';
        yearPanel.insertBefore(wrapper, yearPanel.firstChild);
    }

    /* ── Prepend new visitor row ──────────────────────────────────────── */
    function prependRow(data, monthKey, dt) {
        var tbody = document.getElementById('vlog-tbody-' + monthKey);
        if (!tbody) return;

        var deviceType = data.is_mobile ? 'mobile' : 'desktop';
        var searchStr  = (
            (data.ip_address || '') + ' ' +
            (data.browser    || '') + ' ' +
            (data.platform   || '') + ' ' +
            (data.device     || '')
        ).toLowerCase();

        var existing = tbody.querySelectorAll('tr.vlog-row');
        existing.forEach(function (row, i) {
            var cell = row.querySelector('td:first-child');
            if (cell) cell.textContent = i + 2;
        });

        var bl = (data.browser || '').toLowerCase();
        var bIcon;
        if (bl.indexOf('chrome')  !== -1) bIcon = '<i class="fab fa-chrome" style="color:#fbbf24;"></i>';
        else if (bl.indexOf('firefox') !== -1) bIcon = '<i class="fab fa-firefox-browser" style="color:#f97316;"></i>';
        else if (bl.indexOf('safari')  !== -1) bIcon = '<i class="fab fa-safari" style="color:#60b3fb;"></i>';
        else bIcon = '<i class="fa fa-globe" style="color:var(--adm-muted);"></i>';

        var pill = data.is_mobile
            ? '<span class="status-pill" style="background:rgba(249,115,22,0.12);color:#fdba74;border:1px solid rgba(249,115,22,0.22);"><i class="fa fa-mobile-screen" style="font-size:0.65rem;margin-right:3px;"></i>Mobile</span>'
            : '<span class="status-pill" style="background:rgba(139,92,246,0.12);color:#c4b5fd;border:1px solid rgba(139,92,246,0.22);"><i class="fa fa-desktop" style="font-size:0.65rem;margin-right:3px;"></i>Desktop</span>';

        function pad(n) { return String(n).padStart(2,'0'); }
        var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var h    = dt.getHours();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        var formatted = pad(dt.getDate()) + ' ' + MONTHS[dt.getMonth()] + ' ' + dt.getFullYear() +
                        ', ' + pad(h) + ':' + pad(dt.getMinutes()) + ' ' + ampm;

        var tr = document.createElement('tr');
        tr.className = 'vlog-row';
        tr.setAttribute('data-device', deviceType);
        tr.setAttribute('data-search', searchStr);
        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">1</td>' +
            '<td><code style="font-size:0.80rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:3px 8px;border-radius:6px;border:1px solid rgba(59,130,246,0.15);">' +
                esc(data.ip_address || '') + '</code></td>' +
            '<td><span style="font-size:0.85rem;display:flex;align-items:center;gap:6px;">' + bIcon + ' ' + esc(data.browser || '—') + '</span></td>' +
            '<td style="color:var(--adm-text);">' + esc(data.platform || '—') + '</td>' +
            '<td style="color:var(--adm-muted);font-size:0.85rem;">' + esc(data.device || '—') + '</td>' +
            '<td>' + pill + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + formatted + '</td>';

        tbody.insertBefore(tr, tbody.firstChild);
    }

    /* ── Increment month visitor badge ───────────────────────────────── */
    function incrementBadge(monthKey) {
        var btns = document.querySelectorAll('.log-month-btn');
        for (var i = 0; i < btns.length; i++) {
            if ((btns[i].getAttribute('onclick') || '').indexOf(monthKey) !== -1) {
                var badge = btns[i].querySelector('.log-count-badge');
                if (badge) {
                    var n = (parseInt(badge.textContent) || 0) + 1;
                    badge.textContent = n + ' visitor' + (n !== 1 ? 's' : '');
                }
                break;
            }
        }
    }

    /* ── Update stat cards ───────────────────────────────────────────── */
    function updateStatCards(data, dt) {
        var totalEl = document.querySelector('.stat-card--blue .stat-value');
        if (totalEl) totalEl.textContent = fmtNum(parseNum(totalEl.textContent) + 1);

        var now     = new Date();
        var isToday = dt.getFullYear() === now.getFullYear() &&
                      dt.getMonth()    === now.getMonth()    &&
                      dt.getDate()     === now.getDate();
        if (isToday) {
            var todayEl = document.querySelector('.stat-card--green .stat-value');
            if (todayEl) todayEl.textContent = fmtNum(parseNum(todayEl.textContent) + 1);
        }

        if (data.is_mobile) {
            var mobileEl = document.querySelector('.stat-card--orange .stat-value');
            if (mobileEl) mobileEl.textContent = fmtNum(parseNum(mobileEl.textContent) + 1);
        } else {
            var deskEl = document.querySelector('.stat-card--purple .stat-value');
            if (deskEl) deskEl.textContent = fmtNum(parseNum(deskEl.textContent) + 1);
        }
    }

    function parseNum(str) { return parseInt(String(str).replace(/,/g,'')) || 0; }
    function fmtNum(n)     { return n.toLocaleString('en-US'); }

    function esc(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();