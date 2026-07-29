function driverFeedbackEscape(value) {
    return String(value ?? "—").replace(/[&<>"']/g, function (character) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
        }[character];
    });
}

function driverFeedbackStars(rating) {
    const score = Number(rating) || 0;
    return Array.from(
        {
            length: 5,
        },
        (_, index) =>
            '<i class="fa' + (index < score ? "s" : "r") + ' fa-star"></i>',
    ).join("");
}

function driverFeedbackMatchesSearch(item) {
    if (!driverFeedbackMeta.search) return true;
    return [
        item.booking_id,
        item.user_name,
        item.user_email,
        item.user_phone,
        item.message,
        item.hospital_name,
        item.pickup_address,
    ]
        .join(" ")
        .toLowerCase()
        .includes(driverFeedbackMeta.search);
}

function driverFeedbackCompare(a, b) {
    if (driverFeedbackMeta.sort === "oldest") {
        return String(a.created_at || "").localeCompare(
            String(b.created_at || ""),
        );
    }
    if (driverFeedbackMeta.sort === "rating_high") {
        return (
            Number(b.rating) - Number(a.rating) ||
            String(b.created_at || "").localeCompare(String(a.created_at || ""))
        );
    }
    if (driverFeedbackMeta.sort === "rating_low") {
        return (
            Number(a.rating) - Number(b.rating) ||
            String(b.created_at || "").localeCompare(String(a.created_at || ""))
        );
    }
    return String(b.created_at || "").localeCompare(String(a.created_at || ""));
}

function driverFeedbackRowHtml(item, index) {
    const booking = item.booking_url
        ? '<a href="' +
          driverFeedbackEscape(item.booking_url) +
          '" style="font-family:monospace;font-size:.78rem;color:#a5b4fc;text-decoration:none;">' +
          driverFeedbackEscape(item.booking_id) +
          "</a>"
        : '<span style="color:rgba(255,255,255,.3);">—</span>';

    return (
        '<td class="ps-4" style="color:rgba(255,255,255,.35);">' +
        index +
        "</td>" +
        "<td>" +
        booking +
        "</td>" +
        '<td><div style="color:#e2e8f0;">' +
        driverFeedbackEscape(item.user_name) +
        "</div>" +
        '<small style="color:rgba(255,255,255,.35);">' +
        driverFeedbackEscape(item.user_email) +
        "</small></td>" +
        '<td><span class="driver-feedback-stars">' +
        driverFeedbackStars(item.rating) +
        '<span style="color:rgba(255,255,255,.35);margin-left:4px;">' +
        driverFeedbackEscape(item.rating) +
        "/5</span></span></td>" +
        '<td style="max-width:260px;color:rgba(255,255,255,.5);">' +
        driverFeedbackEscape(
            String(item.message || "No written feedback provided.").length > 70
                ? String(item.message).slice(0, 69) + "…"
                : item.message,
        ) +
        "</td>" +
        '<td style="white-space:nowrap;color:rgba(255,255,255,.4);">' +
        driverFeedbackEscape(item.submitted_at) +
        "</td>" +
        '<td><button type="button" class="btn-dri-icon btn-dri-icon--primary" title="View Feedback"' +
        ' onclick="driverViewFeedback(' +
        Number(item.id) +
        ')"><i class="fa fa-eye"></i></button></td>'
    );
}

function driverFeedbackUpdatePagination(total) {
    const lastPage = Math.max(1, Math.ceil(total / driverFeedbackMeta.perPage));
    const first = total
        ? (driverFeedbackMeta.currentPage - 1) * driverFeedbackMeta.perPage + 1
        : 0;
    const last = Math.min(
        driverFeedbackMeta.currentPage * driverFeedbackMeta.perPage,
        total,
    );
    const info = document.getElementById("driverFeedbackPaginationInfo");
    const pages = document.getElementById("driverFeedbackPaginationPages");
    const footer = document.getElementById("driverFeedbackPagination");

    if (info)
        info.textContent = total
            ? "Showing " +
              first +
              "–" +
              last +
              " of " +
              total +
              " feedback entries"
            : "";
    if (pages)
        pages.textContent =
            "Page " + driverFeedbackMeta.currentPage + " / " + lastPage;
    if (footer) footer.style.display = total ? "" : "none";

    function updateControl(id, disabled, page, label) {
        const current = document.getElementById(id);
        if (!current) return;

        if (disabled) {
            if (current.tagName !== "BUTTON") {
                const button = document.createElement("button");
                button.id = id;
                button.className = current.className;
                button.disabled = true;
                button.innerHTML = label;
                current.replaceWith(button);
            } else {
                current.disabled = true;
            }
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set("page", page);
        if (current.tagName !== "A") {
            const link = document.createElement("a");
            link.id = id;
            link.className = current.className;
            link.style.textDecoration = "none";
            link.href = url.toString();
            link.innerHTML = label;
            current.replaceWith(link);
        } else {
            current.href = url.toString();
        }
    }

    updateControl(
        "driverFeedbackPrev",
        driverFeedbackMeta.currentPage <= 1,
        driverFeedbackMeta.currentPage - 1,
        "&#8592; Prev",
    );
    updateControl(
        "driverFeedbackNext",
        driverFeedbackMeta.currentPage >= lastPage,
        driverFeedbackMeta.currentPage + 1,
        "Next &#8594;",
    );
}

function driverFeedbackRender(items) {
    const body = document.getElementById("driverFeedbackBody");
    const table = document.getElementById("driverFeedbackTableScroll");
    const empty = document.getElementById("driverFeedbackEmptyRow");
    if (!body || !table) return;

    if (empty) empty.remove();
    body.querySelectorAll("tr[data-driver-feedback-id]").forEach(
        function (row) {
            row.remove();
        },
    );

    items.forEach(function (item, index) {
        const row = document.createElement("tr");
        row.dataset.driverFeedbackId = item.id;
        row.innerHTML = driverFeedbackRowHtml(
            item,
            (driverFeedbackMeta.currentPage - 1) * driverFeedbackMeta.perPage +
                index +
                1,
        );
        body.appendChild(row);
    });
    table.style.display = items.length ? "" : "none";
}

function driverFeedbackInsert(item) {
    if (
        !item ||
        !item.id ||
        String(item.driver_id) !== String(window.driDriverId) ||
        driverFeedbackSeenIds.has(Number(item.id)) ||
        !driverFeedbackMatchesSearch(item)
    )
        return;

    driverFeedbackSeenIds.add(Number(item.id));
    driverFeedbackMeta.total += 1;

    let shouldRender = driverFeedbackMeta.currentPage === 1;
    if (driverFeedbackMeta.sort === "oldest") {
        const lastPage = Math.max(
            1,
            Math.ceil(driverFeedbackMeta.total / driverFeedbackMeta.perPage),
        );
        shouldRender = driverFeedbackMeta.currentPage === lastPage;
    }
    if (shouldRender) {
        driverFeedbackData.push(item);
        driverFeedbackData.sort(driverFeedbackCompare);
        driverFeedbackRender(
            driverFeedbackData.slice(0, driverFeedbackMeta.perPage),
        );
    }

    const total = document.getElementById("driverFeedbackHeaderTotal");
    if (total) total.textContent = driverFeedbackMeta.total;
    driverFeedbackUpdatePagination(driverFeedbackMeta.total);
}

window._rrOnFeedbackCreated = function (payload) {
    driverFeedbackInsert(payload.feedback);
};

window._rrOnFeedbackViewed = function (payload) {
    const item = driverFeedbackData.find(function (feedback) {
        return Number(feedback.id) === Number(payload.feedback_id);
    });
    if (item) item.driver_viewed = true;
};

function driverFeedbackField(label, value) {
    return (
        '<div class="driver-feedback-modal-field"><label>' +
        driverFeedbackEscape(label) +
        "</label><span>" +
        driverFeedbackEscape(value) +
        "</span></div>"
    );
}

function driverViewFeedback(id) {
    const item = driverFeedbackData.find(function (feedback) {
        return Number(feedback.id) === Number(id);
    });
    if (!item) return;

    const booking = item.booking_url
        ? '<a href="' +
          driverFeedbackEscape(item.booking_url) +
          '" style="color:#a5b4fc;text-decoration:none;font-family:monospace;">' +
          driverFeedbackEscape(item.booking_id) +
          "</a>"
        : driverFeedbackEscape(item.booking_id);

    document.getElementById("driverFeedbackModalBody").innerHTML =
        '<div class="p-3 mb-4" style="border:1px solid rgba(251,191,36,.18);background:rgba(251,191,36,.06);border-radius:10px;">' +
        '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.4);font-weight:700;margin-bottom:7px;">Rating</div>' +
        '<div class="driver-feedback-stars" style="font-size:1rem;">' +
        driverFeedbackStars(item.rating) +
        '<span style="color:#e2e8f0;font-size:.82rem;margin-left:5px;">' +
        driverFeedbackEscape(item.rating) +
        " / 5</span></div></div>" +
        '<div class="driver-feedback-modal-grid">' +
        driverFeedbackField("Booking ID", item.booking_id) +
        driverFeedbackField("User Name", item.user_name) +
        driverFeedbackField("User Email", item.user_email) +
        driverFeedbackField("User Phone", item.user_phone) +
        driverFeedbackField("Submitted", item.submitted_at) +
        "</div>" +
        '<hr style="border-color:rgba(255,255,255,.08);">' +
        '<div class="driver-feedback-modal-grid">' +
        driverFeedbackField("Ride Type", item.ride_type) +
        driverFeedbackField("Status", item.status) +
        driverFeedbackField("Ambulance", item.ambulance) +
        driverFeedbackField("Hospital", item.hospital_name) +
        driverFeedbackField("Pickup Address", item.pickup_address) +
        driverFeedbackField("Requested", item.requested_at) +
        driverFeedbackField("Dispatched", item.dispatched_at) +
        driverFeedbackField("Completed", item.completed_at) +
        "</div>" +
        '<hr style="border-color:rgba(255,255,255,.08);">' +
        '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.4);font-weight:700;margin-bottom:8px;">Feedback</div>' +
        '<div style="white-space:pre-wrap;color:#e2e8f0;font-size:.9rem;line-height:1.65;background:rgba(255,255,255,.035);border-radius:8px;padding:12px;">' +
        driverFeedbackEscape(item.message) +
        "</div>";

    const modalElement = document.getElementById("driverFeedbackModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modalElement.addEventListener(
        "shown.bs.modal",
        function markDriverFeedbackAsViewed() {
            if (!item.driver_viewed) {
                fetch(
                    window.driverRoutes.feedbackViewed +
                        "/" +
                        item.id +
                        "/viewed",
                    {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]',
                                )?.content || "",
                            Accept: "application/json",
                        },
                    },
                )
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return {
                                ok: response.ok,
                                data: data,
                            };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data.success) return;

                        item.driver_viewed = true;
                        if (
                            typeof window._rrUpdateDriverFeedbackBadge ===
                            "function"
                        ) {
                            window._rrUpdateDriverFeedbackBadge(
                                result.data.driver_unviewed_count,
                            );
                        }
                    })
                    .catch(function () {
                        /* Keep the feedback unviewed if the request fails. */
                    });
            }
        },
        {
            once: true,
        },
    );

    modal.show();
}
