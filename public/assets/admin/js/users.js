function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── Filter ─────────────────────────────────────────────────────────────── */
function filterUserTable() {
    var search = (
        document.getElementById("searchUser").value || ""
    ).toLowerCase();
    var status = document.getElementById("filterUserStatus").value;
    var verified = document.getElementById("filterUserVerified").value;
    var noRes = document.getElementById("userNoResults");

    var matched = [];
    document
        .querySelectorAll("#userTable tbody tr.pgd-row")
        .forEach(function (row) {
            var matchSearch =
                !search || (row.dataset.search || "").includes(search);
            var matchStatus = !status || row.dataset.status === status;
            var matchVerified = !verified || row.dataset.verified === verified;
            if (matchSearch && matchStatus && matchVerified) matched.push(row);
        });

    if (noRes)
        noRes.style.display = matched.length === 0 ? "table-row" : "none";
    if (window.PGD) PGD.applyFilter("usr", matched);
}

/* ── View User ───────────────────────────────────────────────────────────── */
function viewUser(id) {
    var loader = document.getElementById("userViewLoader");
    var content = document.getElementById("userViewContent");
    loader.style.display = "block";
    content.style.display = "none";
    content.innerHTML = "";

    bootstrap.Modal.getOrCreateInstance(
        document.getElementById("userViewModal"),
    ).show();

    fetch(window.usersRoutes.show + "/" + id, {
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": getCsrf(),
        },
    })
        .then(function (r) {
            return r.json();
        })
        .then(function (res) {
            if (!res.success) {
                showAlert("error", "Could not load user details.");
                return;
            }
            var u = res.user;
            var d = u.details || {};
            var mc = u.medical_card || null;

            var statusHtml =
                u.status === "1"
                    ? '<span class="status-pill status-available">Active</span>'
                    : '<span class="status-pill status-maintenance">Suspended</span>';

            var html =
                '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">';

            /* ── Profile Card ── */
            html +=
                '<div class="card" style="grid-column:1/-1;padding:1.25rem;display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap;">';
            html += '<div style="width:64px;height:64px;border-radius:50%;background:rgba(215,44,66,0.1);border:2px solid rgba(215,44,66,0.25);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
            if (d.profile_picture && d.profile_picture !== 'default.jpg') {
                html += '<img src="/assets/user/img/users/' + escHtml(d.profile_picture) + '" style="width:100%;height:100%;object-fit:cover;" alt="">';
            } else {
                html += '<i class="fa fa-user" style="font-size:1.6rem;color:var(--adm-red);"></i>';
            }
            html += '</div>';
            html += '<div style="flex:1;min-width:0;">';
            html +=
                '<div style="font-size:1.1rem;font-weight:700;color:#f1f5f9;">' +
                (d.first_name ? d.first_name + " " + d.last_name : u.username) +
                "</div>";
            html +=
                '<div style="font-size:0.82rem;color:var(--adm-muted);margin-top:2px;">@' +
                u.username +
                " &nbsp;·&nbsp; Consumer #" +
                (d.consumer_no || "—") +
                "</div>";
            html +=
                '<div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">' +
                statusHtml;
            if (d.email_verified_at) {
                html +=
                    '<span class="status-pill status-available"><i class="fa fa-circle-check me-1" style="font-size:0.7rem;"></i>Email Verified</span>';
            } else {
                html +=
                    '<span class="status-pill status-offline"><i class="fa fa-circle-xmark me-1" style="font-size:0.7rem;"></i>Unverified</span>';
            }
            html += "</div></div>";
            html +=
                '<div style="font-size:0.78rem;color:var(--adm-muted);text-align:right;"><div>Joined</div><div style="color:#f1f5f9;font-weight:600;">' +
                (u.created_at || "—") +
                '</div><div style="margin-top:4px;">' +
                u.total_messages +
                " message(s)</div></div>";
            html += "</div>";

            /* ── Personal Info ── */
            html += detailSection("Personal Information", "fa-address-card", [
                [
                    "Full Name",
                    d.first_name ? d.first_name + " " + d.last_name : "—",
                ],
                ["Email", d.email || "—"],
                ["Phone", d.phone || "—"],
                ["Date of Birth", d.date_of_birth || "—"],
                ["Address", d.address || "—"],
            ]);

            /* ── Account Info ── */
            html += detailSection("Account Info", "fa-circle-info", [
                ["Username", u.username],
                ["Consumer No.", d.consumer_no || "—"],
                ["Email Verified", d.email_verified_at || "Not verified"],
                ["Account Status", u.status === "1" ? "Active" : "Suspended"],
                ["Registered On", u.created_at || "—"],
            ]);

            /* ── Medical Card ── */
            if (mc) {
                html +=
                    '<div class="card" style="grid-column:1/-1;padding:1.25rem;">';
                html +=
                    '<div style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--adm-muted);margin-bottom:1rem;display:flex;align-items:center;gap:6px;"><i class="fa fa-heart-pulse" style="color:var(--adm-red);"></i> Medical Card</div>';
                html +=
                    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:0.75rem 1.5rem;">';
                [
                    ["Blood Type", mc.blood_type || "—"],
                    ["Medical History", mc.medical_history || "—"],
                    ["Allergies", mc.allergies || "—"],
                    ["Medications", mc.medications || "—"],
                    ["Emergency Contact", mc.contact_name || "—"],
                    ["Relation", mc.relation || "—"],
                    ["Contact Phone", mc.contact_phone || "—"],
                ].forEach(function (item) {
                    html +=
                        '<div><div style="font-size:0.72rem;color:var(--adm-muted);margin-bottom:2px;">' +
                        item[0] +
                        "</div>";
                    html +=
                        '<div style="font-size:0.88rem;color:#f1f5f9;font-weight:500;">' +
                        item[1] +
                        "</div></div>";
                });
                html += "</div></div>";
            } else {
                html +=
                    '<div class="card" style="grid-column:1/-1;padding:1.25rem;text-align:center;color:var(--adm-muted);">';
                html +=
                    '<i class="fa fa-heart-pulse d-block mb-2 opacity-40" style="font-size:1.5rem;"></i>';
                html +=
                    '<span style="font-size:0.85rem;">No medical card on file.</span></div>';
            }

            html += "</div>";

            loader.style.display = "none";
            content.innerHTML = html;
            content.style.display = "block";
        })
        .catch(function () {
            showAlert("error", "Server error. Please try again.");
            loader.style.display = "none";
        });
}

function detailSection(title, icon, rows) {
    var html = '<div class="card" style="padding:1.25rem;">';
    html +=
        '<div style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--adm-muted);margin-bottom:1rem;display:flex;align-items:center;gap:6px;"><i class="fa ' +
        icon +
        '" style="color:var(--adm-red);"></i> ' +
        title +
        "</div>";
    html += '<div style="display:flex;flex-direction:column;gap:0.65rem;">';
    rows.forEach(function (row) {
        html +=
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;border-bottom:1px solid rgba(255,255,255,0.04);padding-bottom:0.5rem;">';
        html +=
            '<span style="font-size:0.78rem;color:var(--adm-muted);white-space:nowrap;">' +
            row[0] +
            "</span>";
        html +=
            '<span style="font-size:0.85rem;color:#f1f5f9;font-weight:500;text-align:right;word-break:break-all;">' +
            row[1] +
            "</span>";
        html += "</div>";
    });
    html += "</div></div>";
    return html;
}

// REAL TIME UPDATE REVERB
(function () {
    var seenCreated = new Set();

    window.channel.bind('user-changed', function (data) {
        /* ── New user registered ─────────────────────────────────────────── */
        if (data.action === 'created') {
            if (seenCreated.has(data.id)) return;
            seenCreated.add(data.id);
    
            ensureTable();
    
            var tbody = document.querySelector('#userTable tbody');
            if (!tbody) return;
    
            /* Skip if row already in DOM (server-rendered before event) */
            if (tbody.querySelector('tr[data-id="' + data.id + '"]')) return;
    
            /* Renumber existing rows */
            tbody.querySelectorAll('tr.pgd-row').forEach(function (row, i) {
                var cell = row.querySelector('td:first-child');
                if (cell) cell.textContent = i + 2;
            });
    
            var tr = buildRow(data, 1);
            tbody.insertBefore(tr, tbody.firstChild);
    
            /* Update "N Total" pill in page header */
            var pill = document.querySelector('.adm-page-header .status-pill');
            if (pill) {
                var n = (parseInt(pill.textContent) || 0) + 1;
                pill.textContent = n + ' Total';
            }
    
            /* Re-apply active filter + refresh PGD */
            if (typeof filterUserTable === 'function') filterUserTable();
        }

        /* ── User profile updated ────────────────────────────────────────── */
        if (data.action === 'updated') {
            var row = document.querySelector('#userTable tbody tr[data-id="' + data.id + '"]');
            if (!row) return;
    
            /* Update filter attributes */
            row.setAttribute('data-status',   data.status);
            row.setAttribute('data-verified', data.verified ? 'verified' : 'unverified');
            row.setAttribute('data-search',
                (data.name + ' ' + data.username + ' ' + (data.email || '')).toLowerCase());
    
            var cells = row.querySelectorAll('td');
            if (cells.length < 9) return;
    
            /* Avatar */
            var avatarBox = cells[1].querySelector('.adm-icon-preview');
            if (avatarBox) avatarBox.innerHTML = avatarHtml(data.profile_picture);
    
            /* Name */
            var nameEl = cells[1].querySelector('strong');
            if (nameEl) nameEl.textContent = data.name;
    
            /* Username */
            cells[2].textContent = data.username;
    
            /* Email */
            cells[3].textContent = data.email || '—';
    
            /* Phone */
            cells[4].textContent = data.phone || '—';
    
            /* Status pill */
            cells[5].innerHTML = statusPill(data.status);
    
            /* Verified pill */
            cells[6].innerHTML = verifiedPill(data.verified);
    
            /* Re-apply filter so status/verified filters stay accurate */
            if (typeof filterUserTable === 'function') filterUserTable();
        }
    });

    /* ── Helpers ─────────────────────────────────────────────────────── */
    function buildRow(data, num) {
        var tr = document.createElement('tr');
        tr.className = 'pgd-row';
        tr.setAttribute('data-id',       data.id);
        tr.setAttribute('data-status',   data.status);
        tr.setAttribute('data-verified', data.verified ? 'verified' : 'unverified');
        tr.setAttribute('data-search',
            (data.name + ' ' + data.username + ' ' + (data.email || '')).toLowerCase());

        tr.innerHTML =
            '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">' + num + '</td>' +
            '<td>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<div class="adm-icon-preview" style="width:32px;height:32px;border-radius:50%;font-size:0.8rem;overflow:hidden;padding:0;">' +
                        avatarHtml(data.profile_picture) +
                    '</div>' +
                    '<strong>' + esc(data.name) + '</strong>' +
                '</div>' +
            '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + esc(data.username) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + esc(data.email || '—') + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + esc(data.phone || '—') + '</td>' +
            '<td>' + statusPill(data.status) + '</td>' +
            '<td>' + verifiedPill(data.verified) + '</td>' +
            '<td class="fs-xs" style="color:var(--adm-muted);">' + esc(data.created_at || '') + '</td>' +
            '<td>' +
                '<button class="btn-adm-icon btn-adm-icon--edit" title="View Details" onclick="viewUser(' + data.id + ')">' +
                    '<i class="fa fa-eye"></i>' +
                '</button>' +
            '</td>';

        return tr;
    }

    function avatarHtml(pic) {
        if (pic && pic !== 'default.jpg') {
            return '<img src="/assets/user/img/users/' + esc(pic) + '?v=' + Date.now() +
                   '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="">';
        }
        return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">' +
               '<i class="fa fa-user" style="font-size:0.8rem;"></i></div>';
    }

    function statusPill(status) {
        return status === '1'
            ? '<span class="status-pill status-1">Active</span>'
            : '<span class="status-pill status-4">Suspended</span>';
    }

    function verifiedPill(verified) {
        return verified
            ? '<span class="status-pill status-1"><i class="fa fa-circle-check me-1" style="font-size:0.7rem;"></i>Verified</span>'
            : '<span class="status-pill status-4"><i class="fa fa-circle-xmark me-1" style="font-size:0.7rem;"></i>Unverified</span>';
    }

    /* Create the full table structure when page was empty */
    function ensureTable() {
        if (document.getElementById('userTable')) return;

        var card = document.querySelector('.card');
        if (!card) return;

        var emptyDiv = card.querySelector('.adm-empty');
        if (emptyDiv) emptyDiv.remove();

        var scrollDiv = document.createElement('div');
        scrollDiv.className = 'pgd-scroll';
        scrollDiv.innerHTML =
            '<table class="table table-hover mb-0" id="userTable">' +
                '<thead><tr>' +
                    '<th class="ps-4">#</th><th>Name</th><th>Username</th>' +
                    '<th>Email</th><th>Phone</th><th>Status</th>' +
                    '<th>Verified</th><th>Joined</th><th>Actions</th>' +
                '</tr></thead>' +
                '<tbody>' +
                    '<tr id="userNoResults" style="display:none;">' +
                        '<td colspan="9" class="text-center py-5" style="color:var(--adm-muted);">' +
                            '<i class="fa fa-search d-block mb-2 opacity-50"></i>' +
                            'No users match your search.' +
                        '</td>' +
                    '</tr>' +
                '</tbody>' +
            '</table>';

        var footerDiv = document.createElement('div');
        footerDiv.className = 'pgd-footer';
        footerDiv.innerHTML =
            '<span class="pgd-info" id="usrInfo"></span>' +
            '<div class="pgd-controls">' +
                '<button class="pgd-btn" id="usrPrev">&#8592; Prev</button>' +
                '<span class="pgd-pages" id="usrPages"></span>' +
                '<button class="pgd-btn" id="usrNext">Next &#8594;</button>' +
            '</div>';

        card.appendChild(scrollDiv);
        card.appendChild(footerDiv);

        if (window.PGD) {
            PGD.init({
                id: 'usr', sel: '#userTable tbody tr.pgd-row',
                prevId: 'usrPrev', nextId: 'usrNext',
                infoId: 'usrInfo', pagesId: 'usrPages',
                perPage: 10,
            });
        }
    }

    function esc(str) {
        return String(str || '')
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();