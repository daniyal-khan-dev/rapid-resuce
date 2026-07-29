/* ── Real-time: new request dispatched to this driver ──────────────────── */
window._rrOnNewRequest = function (r) {
    /* Increment the stat counters */
    function _inc(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = (parseInt(el.textContent, 10) || 0) + 1;
    }
    _inc("statTotal");
    _inc("statPending");
    _inc("statToday");

    /* Prepend a new row to the Recent Requests table */
    var tbody = document.getElementById("driHistoryBody");
    if (!tbody) return;

    /* Remove the empty-state placeholder row if present */
    var emptyRow = tbody.querySelector(".dri-empty-row");
    if (emptyRow) emptyRow.remove();

    /* Duplicate guard */
    if (tbody.querySelector('tr[data-req-id="' + r.id + '"]')) return;

    function _trunc(s, n) {
        if (!s) return "—";
        return s.length > n ? s.substring(0, n) + "…" : s;
    }

    var typeHtml =
        r.type === "1"
            ? '<span class="dri-type-badge emergency">Emergency</span>'
            : '<span class="dri-type-badge non-emergency">Non-Emergency</span>';

    var tr = document.createElement("tr");
    tr.setAttribute("data-req-id", r.id);
    tr.innerHTML =
        '<td><span class="mono">' +
        (r.rreb_id || "#" + r.id) +
        "</span></td>" +
        "<td>" +
        typeHtml +
        "</td>" +
        '<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' +
        _trunc(r.pickup_address, 30) +
        "</td>" +
        "<td>" +
        _trunc(r.hospital_name, 22) +
        "</td>" +
        "<td>" +
        (r.ambulance || "—") +
        "</td>" +
        '<td><span class="dri-status-badge s8">Awaiting Acceptance</span></td>' +
        '<td style="white-space:nowrap;color:rgba(255,255,255,.35);font-size:.77rem;">' +
        (r.created_at || "") +
        "</td>";

    /* Highlight the new row briefly */
    tr.style.transition = "background .6s";
    tr.style.background = "rgba(59,130,246,.12)";
    tbody.insertBefore(tr, tbody.firstChild);
    requestAnimationFrame(function () {
        setTimeout(function () {
            tr.style.background = "";
        }, 1200);
    });
};
