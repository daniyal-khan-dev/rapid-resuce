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

function applyFilter(monthKey, filter, btn) {
    document
        .querySelectorAll("#month-body-" + monthKey + " .log-filter-btn")
        .forEach(function (b) {
            b.classList.remove("active");
        });
    btn.classList.add("active");
    var rows = document.querySelectorAll("#log-tbody-" + monthKey + " tr");
    rows.forEach(function (row) {
        if (row.classList.contains("log-no-data-row")) return;
        var date = row.dataset.date;
        var show = true;
        if (filter === "day") show = date === row.dataset.today;
        if (filter === "week")
            show = date >= row.dataset.weekStart && date <= row.dataset.weekEnd;
        row.style.display = show ? "" : "none";
    });
    var tbody = document.getElementById("log-tbody-" + monthKey);
    var visible = Array.from(
        tbody.querySelectorAll("tr:not(.log-no-data-row)"),
    ).filter(function (r) {
        return r.style.display !== "none";
    });
    var noData = tbody.querySelector(".log-no-data-row");
    if (visible.length === 0) {
        if (!noData) {
            noData = document.createElement("tr");
            noData.className = "log-no-data-row";
            noData.innerHTML =
                '<td colspan="5" class="log-no-data">No logs found for this period.</td>';
            tbody.appendChild(noData);
        }
    } else {
        if (noData) noData.remove();
    }
}

// REAL TIME UPDATE REVERB
(function () {
    var MONTH_NAMES = ['January','February','March','April','May','June', 'July','August','September','October','November','December'];
    var seenAt = new Set();
    
    window.pusher.subscribe('admin-dashboard').bind('log-history-created', function (data) {
        if (seenAt.has(data.created_at)) return;
        seenAt.add(data.created_at);
    
        var dt       = new Date(data.created_at);
        var year     = dt.getFullYear();
        var month    = dt.getMonth() + 1;
        var monthKey = year + '-' + month;
    
        ensurePanel(year, month, monthKey);
        prependRow(data, monthKey, dt);
        incrementBadge(monthKey);
    });
    
    /* ── Ensure year panel + month section exist ─────────────────────── */
    function ensurePanel(year, month, monthKey) {
        if (document.getElementById('log-tbody-' + monthKey)) return;
    
        /* Remove empty-state card if visible */
        var emptyCard = document.querySelector('.adm-empty');
        if (emptyCard) {
            var card = emptyCard.closest('.card');
            if (card) card.remove();
        }
    
        /* Ensure year panel exists */
        var yearPanel = document.getElementById('year-panel-' + year);
        if (!yearPanel) {
            yearPanel = document.createElement('div');
            yearPanel.className = 'log-year-panel active';
            yearPanel.id = 'year-panel-' + year;
    
            /* Deactivate other year panels */
            document.querySelectorAll('.log-year-panel').forEach(function (p) {
                p.classList.remove('active');
            });
    
            /* Add year to dropdown */
            var dropdown = document.getElementById('yearDropdown');
            if (dropdown) {
                Array.from(dropdown.options).forEach(function (o) { o.selected = false; });
                var opt = document.createElement('option');
                opt.value = year; opt.text = year; opt.selected = true;
                dropdown.insertBefore(opt, dropdown.firstChild);
            }
    
            /* Insert after last existing year panel or page header */
            var panels = document.querySelectorAll('.log-year-panel');
            var header = document.querySelector('.adm-page-header');
            if (panels.length) {
                panels[panels.length - 1].after(yearPanel);
            } else {
                header.after(yearPanel);
            }
        }
    
        /* Compute week boundaries */
        var now = new Date();
        var dow = (now.getDay() + 6) % 7;
        var weekStart = new Date(now); weekStart.setDate(now.getDate() - dow);
        var weekEnd   = new Date(weekStart); weekEnd.setDate(weekStart.getDate() + 6);
        function fmtDate(d) {
            return d.getFullYear() + '-' +
                   String(d.getMonth()+1).padStart(2,'0') + '-' +
                   String(d.getDate()).padStart(2,'0');
        }
    
        /* Build month section */
        var mName   = MONTH_NAMES[month - 1];
        var wrapper = document.createElement('div');
        wrapper.className = 'mb-3';
        wrapper.innerHTML =
            '<button class="log-month-btn" onclick="toggleMonth(\'' + monthKey + '\')">' +
                '<i class="fa fa-calendar-week" style="color:var(--adm-muted);"></i>' +
                '<span>' + mName + ' ' + year + '</span>' +
                '<span class="log-count-badge">0 logs</span>' +
            '</button>' +
            '<div class="log-month-body open" id="month-body-' + monthKey + '">' +
                '<div class="d-flex flex-wrap gap-2 mb-3 px-2">' +
                    '<button class="log-filter-btn active" onclick="applyFilter(\'' + monthKey + '\',\'all\',this)">All</button>' +
                    '<button class="log-filter-btn" onclick="applyFilter(\'' + monthKey + '\',\'week\',this)">This Week</button>' +
                    '<button class="log-filter-btn" onclick="applyFilter(\'' + monthKey + '\',\'day\',this)">Today</button>' +
                '</div>' +
                '<div class="table-responsive" style="border-radius:var(--adm-radius-sm);border:1px solid var(--adm-border);max-height:360px;overflow-y:auto;">' +
                    '<table class="table table-hover mb-0" style="font-size:0.855rem;">' +
                        '<thead><tr>' +
                            '<th class="ps-4">#</th>' +
                            '<th>User</th>' +
                            '<th>Action</th>' +
                            '<th>IP Address</th>' +
                            '<th>Date &amp; Time</th>' +
                        '</tr></thead>' +
                        '<tbody id="log-tbody-' + monthKey + '"></tbody>' +
                    '</table>' +
                '</div>' +
            '</div>';
        yearPanel.insertBefore(wrapper, yearPanel.firstChild);
    }
    
    /* ── Prepend new row ─────────────────────────────────────────────── */
    function prependRow(data, monthKey, dt) {
        var tbody = document.getElementById('log-tbody-' + monthKey);
        if (!tbody) return;
    
        /* Compute date attributes for filters */
        var now = new Date();
        var dow = (now.getDay() + 6) % 7;
        var weekStart = new Date(now); weekStart.setDate(now.getDate() - dow);
        var weekEnd   = new Date(weekStart); weekEnd.setDate(weekStart.getDate() + 6);
        function pad(n) { return String(n).padStart(2, '0'); }
        function fmtYMD(d) {
            return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
        }
    
        /* Format datetime: d M Y, H:i:s */
        var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var formatted = pad(dt.getDate()) + ' ' + MONTHS[dt.getMonth()] + ' ' + dt.getFullYear() +
                        ', ' + pad(dt.getHours()) + ':' + pad(dt.getMinutes()) + ':' + pad(dt.getSeconds());
    
        var initial = data.username ? data.username.charAt(0).toUpperCase() : '?';
    
        /* Shift existing row numbers */
        var existing = tbody.querySelectorAll('tr:not(.log-no-data-row)');
        existing.forEach(function (row, i) {
            var cell = row.querySelector('td:first-child');
            if (cell) cell.textContent = i + 2;
        });
    
        var tr = document.createElement('tr');
        tr.setAttribute('data-date',       fmtYMD(dt));
        tr.setAttribute('data-week-start',  fmtYMD(weekStart));
        tr.setAttribute('data-week-end',    fmtYMD(weekEnd));
        tr.setAttribute('data-today',       fmtYMD(now));
        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">1</td>' +
            '<td>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<div class="driver-avatar" style="width:28px;height:28px;font-size:0.75rem;">' + esc(initial) + '</div>' +
                    '<strong>' + esc(data.username || '') + '</strong>' +
                '</div>' +
            '</td>' +
            '<td style="color:var(--adm-text);">' + esc(data.action || '') + '</td>' +
            '<td>' +
                '<code style="font-size:0.78rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:2px 7px;border-radius:5px;">' +
                    esc(data.ip_address || '') +
                '</code>' +
            '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + formatted + '</td>';
    
        tbody.insertBefore(tr, tbody.firstChild);
    }
    
    /* ── Increment the month badge ───────────────────────────────────── */
    function incrementBadge(monthKey) {
        var btns = document.querySelectorAll('.log-month-btn');
        for (var i = 0; i < btns.length; i++) {
            if ((btns[i].getAttribute('onclick') || '').indexOf(monthKey) !== -1) {
                var badge = btns[i].querySelector('.log-count-badge');
                if (badge) {
                    var n = (parseInt(badge.textContent) || 0) + 1;
                    badge.textContent = n + ' log' + (n !== 1 ? 's' : '');
                }
                break;
            }
        }
    }
    
    function esc(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();