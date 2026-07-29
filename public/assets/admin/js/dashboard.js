(function () {
    var allLabels = window.dashboard.visitorLabels;
    var allData = window.dashboard.visitorData;
    var currentRange = 30;
    var zoomOffset = 0;
    var ctx = document.getElementById("visitorChart").getContext("2d");
    var gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, "rgba(215,44,66,0.30)");
    gradient.addColorStop(1, "rgba(215,44,66,0.00)");

    var chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: [],
            datasets: [
                {
                    label: "Visitors",
                    data: [],
                    borderColor: "rgba(215,44,66,0.85)",
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: "rgba(215,44,66,1)",
                    tension: 0.35,
                    fill: true,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: "index",
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    backgroundColor: "rgba(14,23,40,0.95)",
                    titleColor: "#f1f5f9",
                    bodyColor: "#94a3b8",
                    borderColor: "rgba(255,255,255,0.08)",
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        title: function (items) {
                            return formatLabel(items[0].label);
                        },
                        label: function (item) {
                            return (
                                " " +
                                item.raw +
                                " visitor" +
                                (item.raw !== 1 ? "s" : "")
                            );
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        color: "rgba(255,255,255,0.04)",
                    },
                    ticks: {
                        color: "rgba(255,255,255,0.35)",
                        font: {
                            size: 11,
                        },
                        maxTicksLimit: 10,
                        callback: function (val, idx) {
                            return formatShort(this.getLabelForValue(val));
                        },
                    },
                },
                y: {
                    grid: {
                        color: "rgba(255,255,255,0.05)",
                    },
                    ticks: {
                        color: "rgba(255,255,255,0.35)",
                        font: {
                            size: 11,
                        },
                        stepSize: 1,
                    },
                    beginAtZero: true,
                },
            },
        },
    });

    function formatLabel(dateStr) {
        var d = new Date(dateStr);
        return d.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        });
    }

    function formatShort(dateStr) {
        var d = new Date(dateStr);
        return d.toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
        });
    }

    function applyWindow() {
        var total = allLabels.length;
        var window = Math.min(currentRange, total);
        var maxOffset = total - window;
        zoomOffset = Math.max(0, Math.min(zoomOffset, maxOffset));

        var start = total - window - zoomOffset;
        var end = total - zoomOffset;
        chart.data.labels = allLabels.slice(start, end);
        chart.data.datasets[0].data = allData.slice(start, end);
        chart.update("none");

        var subtitle = document.getElementById("chartSubtitle");
        if (subtitle) {
            subtitle.textContent =
                window +
                " day" +
                (window !== 1 ? "s" : "") +
                (zoomOffset > 0 ? " (shifted)" : "");
        }
    }

    window.setChartRange = function (days) {
        currentRange = days;
        zoomOffset = 0;
        document.querySelectorAll(".chart-range-btn").forEach(function (b) {
            b.classList.remove("chart-range-btn--active");
        });
        var map = { 7: "btn7", 30: "btn30", 90: "btn90" };
        var btn = document.getElementById(map[days]);
        if (btn) btn.classList.add("chart-range-btn--active");
        applyWindow();
    };

    window.zoomChart = function (dir) {
        var step = dir === 1 ? -7 : 7;
        currentRange = Math.max(7, Math.min(90, currentRange + step));
        document.querySelectorAll(".chart-range-btn").forEach(function (b) {
            b.classList.remove("chart-range-btn--active");
        });
        applyWindow();
    };

    window.resetZoom = function () {
        setChartRange(30);
    };

    window.addVisitorChartToday = function () {
        var today = new Date().toISOString().split("T")[0];
        var idx = allLabels.indexOf(today);
        if (idx >= 0) {
            allData[idx]++;
        } else {
            allLabels.push(today);
            allData.push(1);
        }
        applyWindow();
    };

    applyWindow();

    
    // REAL TIME UPDATE REVERB
    var MAX_VISITOR_ROWS = window.dashboard.maxVisitorRows;
    var MAX_ACTIVITY_ROWS = window.dashboard.maxActivityRows;

    /* ── Visitor Logs ──────────────────────────────────────────────── */

    function ensureVisitorTable() {
        if (document.getElementById("visitorLogsTbody")) return;

        var emptyDiv = document.getElementById("visitorLogsEmpty");
        if (emptyDiv) emptyDiv.style.display = "none";

        var card = document.getElementById("visitorLogsCard");
        if (!card) return;

        var scrollDiv = document.createElement("div");
        scrollDiv.className = "dash-log-scroll";
        scrollDiv.id = "visitorLogsScroll";
        scrollDiv.innerHTML =
            '<table class="table table-hover mb-0">' +
            "<thead><tr>" +
            '<th class="ps-3">IP Address</th>' +
            "<th>Browser</th>" +
            "<th>Platform</th>" +
            "<th>Device</th>" +
            "<th>Time</th>" +
            "</tr></thead>" +
            '<tbody id="visitorLogsTbody"></tbody>' +
            "</table>";
        card.appendChild(scrollDiv);
    }

    function prependVisitorRow(data) {
        ensureVisitorTable();
        var tbody = document.getElementById("visitorLogsTbody");
        if (!tbody) return;

        var device = data.is_mobile
            ? '<span class="status-pill status-pill--orange"><i class="fa fa-mobile-screen-button me-1"></i>Mobile</span>'
            : '<span class="status-pill status-pill--blue"><i class="fa fa-desktop me-1"></i>Desktop</span>';

        var tr = document.createElement("tr");
        tr.innerHTML =
            '<td class="ps-3"><code style="font-size:0.75rem;color:#93c5fd;background:rgba(59,130,246,0.08);padding:2px 6px;border-radius:5px;">' +
            escHtml(data.ip_address || "") +
            "</code></td>" +
            '<td style="font-size:0.82rem;max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            escHtml(data.browser || "—") +
            "</td>" +
            '<td style="font-size:0.82rem;">' +
            escHtml(data.platform || "—") +
            "</td>" +
            "<td>" +
            device +
            "</td>" +
            '<td class="text-muted fs-xs text-nowrap">Just now</td>';

        tbody.insertBefore(tr, tbody.firstChild);

        while (tbody.rows.length > MAX_VISITOR_ROWS) {
            tbody.removeChild(tbody.lastChild);
        }
    }

    /* ── Activity Logs ─────────────────────────────────────────────── */
    function ensureActivityTable() {
        if (document.getElementById("activityLogsTbody")) return;

        var emptyDiv = document.getElementById("activityLogsEmpty");
        if (emptyDiv) emptyDiv.style.display = "none";

        var card = document.getElementById("activityLogsCard");
        if (!card) return;

        var scrollDiv = document.createElement("div");
        scrollDiv.className = "dash-log-scroll";
        scrollDiv.id = "activityLogsScroll";
        scrollDiv.innerHTML =
            '<table class="table table-hover mb-0">' +
            "<thead><tr>" +
            "<th>User</th>" +
            "<th>Action</th>" +
            "<th>Time</th>" +
            "</tr></thead>" +
            '<tbody id="activityLogsTbody"></tbody>' +
            "</table>";
        card.appendChild(scrollDiv);
    }

    function prependActivityRow(data) {
        ensureActivityTable();
        var tbody = document.getElementById("activityLogsTbody");
        if (!tbody) return;

        var initial = data.username
            ? data.username.charAt(0).toUpperCase()
            : "?";
        var tr = document.createElement("tr");
        tr.innerHTML =
            '<td><div class="d-flex align-items-center gap-2">' +
            '<div class="driver-avatar">' +
            escHtml(initial) +
            "</div>" +
            '<strong style="font-size:0.82rem;">' +
            escHtml(data.username || "") +
            "</strong>" +
            "</div></td>" +
            '<td style="color:var(--adm-text);font-size:0.82rem;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' +
            escHtml(data.action || "") +
            "</td>" +
            '<td class="text-muted fs-xs text-nowrap">Just now</td>';

        tbody.insertBefore(tr, tbody.firstChild);

        while (tbody.rows.length > MAX_ACTIVITY_ROWS) {
            tbody.removeChild(tbody.lastChild);
        }
    }

    /* ── Utility ───────────────────────────────────────────────────── */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    /* ── WebSocket connection ───────────────────────────────────────── */
    window.channel.bind("visitor-log-created", function (data) {
        prependVisitorRow(data);
        if (typeof window.addVisitorChartToday === "function") {
            window.addVisitorChartToday();
        }
    });

    window.channel.bind("log-history-created", function (data) {
        prependActivityRow(data);
    });

    /* ── Emergency ride status changes: keep ambulance stat cards in sync ── */
    window.channel.bind("emergency-request-status-changed", function (payload) {
        if (!payload || !payload.action) return;
        var action = String(payload.action);

        function adjAdm(id, delta) {
            var el = document.getElementById(id);
            if (el) el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
        }

        /* Accept: one more ambulance goes on-call, one fewer available */
        if (action === "accept") {
            adjAdm("admStatOnCall",    1);
            adjAdm("admStatAvailable", -1);
        }

        /* Complete / cancel / reject: ambulance freed up */
        if (action === "complete" || action === "cancel" || action === "reject") {
            adjAdm("admStatOnCall",    -1);
            adjAdm("admStatAvailable", 1);
        }
    });
})();
