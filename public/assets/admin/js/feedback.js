function feedbackMatchesSearch(item) {
    if (!feedbackMeta.search) return true;

    const searchable = [
        item.booking_id,
        item.user_name,
        item.user_email,
        item.user_phone,
        item.driver_name,
        item.driver_phone,
        item.message,
        item.hospital_name,
        item.pickup_address,
    ]
        .join(" ")
        .toLowerCase();

    return searchable.includes(feedbackMeta.search);
}

function feedbackEscape(value) {
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

function feedbackStars(rating) {
    const score = Number(rating) || 0;
    return Array.from(
        { length: 5 },
        (_, index) =>
            '<i class="fa' +
            (index < score ? "s" : "r") +
            ' fa-star" style="color:#fbbf24;margin-right:3px;"></i>',
    ).join("");
}

function feedbackDetailField(label, value) {
    return (
        '<div class="mb-3"><div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--adm-muted);font-weight:700;">' +
        feedbackEscape(label) +
        '</div><div style="font-size:.86rem;color:var(--adm-text);word-break:break-word;">' +
        feedbackEscape(value) +
        "</div></div>"
    );
}

function feedbackDetailLinkField(label, value, url) {
    return (
        '<div class="mb-3"><div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--adm-muted);font-weight:700;">' +
        feedbackEscape(label) +
        '</div><div style="font-size:.86rem;word-break:break-word;">' +
        '<a href="' +
        feedbackEscape(url) +
        '" style="color:#a5b4fc;text-decoration:none;font-family:monospace;">' +
        feedbackEscape(value) +
        ' <i class="fa fa-arrow-up-right-from-square" style="font-size:.7rem;"></i></a></div></div>'
    );
}

function feedbackTruncate(value, length) {
    const text = String(value ?? "—");
    return text.length > length ? text.slice(0, length - 1) + "…" : text;
}

function feedbackRowHtml(item, index) {
    const booking = item.booking_url
        ? '<a href="' +
          feedbackEscape(item.booking_url) +
          '" style="font-family:monospace;font-size:.8rem;background:rgba(129,140,248,.12);padding:4px 8px;border-radius:6px;color:#a5b4fc;text-decoration:none;white-space:nowrap;">' +
          feedbackEscape(item.booking_id) +
          "</a>"
        : '<span class="fs-xs" style="color:var(--adm-muted);">Booking unavailable</span>';

    return (
        '<td class="ps-4 fs-xs" style="color:var(--adm-muted);">' +
        index +
        "</td>" +
        "<td>" +
        booking +
        "</td>" +
        '<td class="fs-xs" style="color:var(--adm-text);"><div>' +
        feedbackEscape(item.user_name) +
        "</div>" +
        '<small style="color:var(--adm-muted);">' +
        feedbackEscape(item.user_email) +
        "</small></td>" +
        '<td class="fs-xs" style="color:var(--adm-muted);">' +
        feedbackEscape(item.driver_name) +
        "</td>" +
        '<td><span style="white-space:nowrap;color:#fbbf24;font-size:.8rem;">' +
        feedbackStars(item.rating) +
        '<span style="color:var(--adm-muted);margin-left:4px;">' +
        feedbackEscape(item.rating) +
        "/5</span></span></td>" +
        '<td class="fs-xs" style="color:var(--adm-muted);max-width:260px;">' +
        feedbackEscape(feedbackTruncate(item.message, 70)) +
        "</td>" +
        '<td class="fs-xs" style="color:var(--adm-muted);white-space:nowrap;">' +
        feedbackEscape(item.submitted_at) +
        "</td>" +
        '<td><button type="button" class="btn-adm-icon" title="View Feedback" onclick="viewFeedback(' +
        Number(item.id) +
        ')"><i class="fa fa-eye"></i></button></td>'
    );
}

function feedbackCompare(a, b) {
    if (feedbackMeta.sort === "oldest") {
        return String(a.created_at || "").localeCompare(
            String(b.created_at || ""),
        );
    }
    if (feedbackMeta.sort === "rating_high") {
        return (
            Number(b.rating) - Number(a.rating) ||
            String(b.created_at || "").localeCompare(String(a.created_at || ""))
        );
    }
    if (feedbackMeta.sort === "rating_low") {
        return (
            Number(a.rating) - Number(b.rating) ||
            String(b.created_at || "").localeCompare(String(a.created_at || ""))
        );
    }
    return String(b.created_at || "").localeCompare(String(a.created_at || ""));
}

function feedbackSetPagination(total) {
    const lastPage = Math.max(1, Math.ceil(total / feedbackMeta.perPage));
    const first = total
        ? (feedbackMeta.currentPage - 1) * feedbackMeta.perPage + 1
        : 0;
    const last = Math.min(
        feedbackMeta.currentPage * feedbackMeta.perPage,
        total,
    );
    const info = document.getElementById("feedbackPaginationInfo");
    const pages = document.getElementById("feedbackPaginationPages");
    const footer = document.getElementById("feedbackPagination");

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
            "Page " + feedbackMeta.currentPage + " / " + lastPage;
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
        "feedbackPrev",
        feedbackMeta.currentPage <= 1,
        feedbackMeta.currentPage - 1,
        "&#8592; Prev",
    );
    updateControl(
        "feedbackNext",
        feedbackMeta.currentPage >= lastPage,
        feedbackMeta.currentPage + 1,
        "Next &#8594;",
    );
}

function feedbackRenumberRows() {
    document
        .querySelectorAll("#feedbackTable tbody tr[data-feedback-id]")
        .forEach(function (row, index) {
            const cell = row.querySelector("td:first-child");
            if (cell)
                cell.textContent =
                    (feedbackMeta.currentPage - 1) * feedbackMeta.perPage +
                    index +
                    1;
        });
}

function feedbackInsert(item) {
    if (
        !item ||
        !item.id ||
        document.querySelector(
            '#feedbackTable tbody tr[data-feedback-id="' + item.id + '"]',
        )
    ) {
        return;
    }
    if (!feedbackMatchesSearch(item)) return;

    const tbody = document.querySelector("#feedbackTable tbody");
    const scroll = document.getElementById("feedbackTableScroll");
    const empty = document.querySelector(".adm-empty");
    if (!tbody || !scroll) return;

    const wasEmpty = !tbody.querySelector("tr[data-feedback-id]");
    let shouldInsert = feedbackMeta.currentPage === 1;

    if (feedbackMeta.sort === "oldest") {
        const newLastPage = Math.max(
            1,
            Math.ceil(feedbackMeta.total / feedbackMeta.perPage),
        );
        shouldInsert =
            feedbackMeta.currentPage === newLastPage &&
            tbody.querySelectorAll("tr[data-feedback-id]").length <
                feedbackMeta.perPage;
    }

    if (!shouldInsert) return;

    feedbackPageData.push(item);
    feedbackSeenIds.add(Number(item.id));
    feedbackPageData.sort(feedbackCompare);
    const visibleItems = feedbackPageData.slice(0, feedbackMeta.perPage);
    const existingRows = Array.from(
        tbody.querySelectorAll("tr[data-feedback-id]"),
    );
    existingRows.forEach(function (row) {
        row.remove();
    });
    visibleItems.forEach(function (entry, index) {
        const row = document.createElement("tr");
        row.dataset.feedbackId = entry.id;
        row.innerHTML = feedbackRowHtml(entry, index + 1);
        tbody.appendChild(row);
    });

    if (wasEmpty && empty) empty.style.display = "none";
    scroll.style.display = "";
    feedbackRenumberRows();
}

function feedbackUpdateTotals(total) {
    const header = document.getElementById("feedbackHeaderTotal");
    const stat = document.getElementById("feedbackStatTotal");
    if (header) header.textContent = Number(total) || 0;
    if (stat) stat.textContent = Number(total) || 0;
}

function feedbackUpdateAverageRating(averageRating) {
    const stat = document.getElementById("feedbackAverageRating");
    if (!stat) return;

    const rating = Number(averageRating);
    stat.firstChild.textContent =
        (Number.isFinite(rating) ? rating : 0).toFixed(1) + " ";
}

window.handleFeedbackCreated = function (data) {
    const item = data.feedback;
    const itemId = item ? Number(item.id) : 0;
    const alreadyKnown = !item || feedbackSeenIds.has(itemId);

    feedbackUpdateTotals(data.total_count);
    feedbackUpdateAverageRating(data.average_rating);
    if (alreadyKnown) return;

    feedbackSeenIds.add(itemId);
    feedbackMeta.total += feedbackMatchesSearch(item) ? 1 : 0;
    feedbackInsert(item);
    feedbackSetPagination(feedbackMeta.total);
};

function viewFeedback(id) {
    const item = feedbackPageData.find(
        (feedback) => Number(feedback.id) === Number(id),
    );
    if (!item) return;

    const bookingField = item.booking_url
        ? feedbackDetailLinkField(
              "Booking ID",
              item.booking_id,
              item.booking_url,
          )
        : feedbackDetailField("Booking ID", item.booking_id);

    document.getElementById("feedbackDetailBody").innerHTML =
        '<div class="p-3 mb-4" style="border:1px solid rgba(251,191,36,.18);background:rgba(251,191,36,.06);border-radius:10px;">' +
        '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--adm-muted);font-weight:700;margin-bottom:7px;">Rating</div>' +
        '<div style="font-size:1.05rem;">' +
        feedbackStars(item.rating) +
        '<span style="color:var(--adm-text);font-size:.82rem;margin-left:5px;">' +
        feedbackEscape(item.rating) +
        " / 5</span></div>" +
        "</div>" +
        '<div class="row">' +
        '<div class="col-md-6">' +
        feedbackDetailField("Booking ID", item.booking_id) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Submitted", item.submitted_at) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("User Name", item.user_name) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("User Email", item.user_email) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("User Phone", item.user_phone) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField(
            "Driver",
            item.driver_name +
                (item.driver_phone !== "—" ? " · " + item.driver_phone : ""),
        ) +
        "</div>" +
        "</div>" +
        '<hr style="border-color:rgba(255,255,255,.08);">' +
        '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--adm-muted);font-weight:700;margin-bottom:10px;">Ride Details</div>' +
        '<div class="row">' +
        '<div class="col-md-6">' +
        bookingField +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Ride Type", item.ride_type) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Status", item.status) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Ambulance", item.ambulance) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Hospital", item.hospital_name) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Pickup Address", item.pickup_address) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Requested", item.requested_at) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Dispatched", item.dispatched_at) +
        "</div>" +
        '<div class="col-md-6">' +
        feedbackDetailField("Completed", item.completed_at) +
        "</div>" +
        "</div>" +
        '<hr style="border-color:rgba(255,255,255,.08);">' +
        '<div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--adm-muted);font-weight:700;margin-bottom:8px;">Feedback</div>' +
        '<div style="white-space:pre-wrap;color:var(--adm-text);font-size:.9rem;line-height:1.65;background:rgba(255,255,255,.035);border-radius:8px;padding:12px;">' +
        feedbackEscape(item.message) +
        "</div>";

    const modalElement = document.getElementById("feedbackDetailModal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    modalElement.addEventListener(
        "shown.bs.modal",
        function markFeedbackAsViewed() {
            if (!item.viewed) {
                markFeedbackViewed(item);
            }
        },
        { once: true },
    );

    modal.show();
}

function markFeedbackViewed(item) {
    fetch(window.adminRoutes.feedbackViewed + "/" + item.id + "/viewed", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN":
                document.querySelector('meta[name="csrf-token"]')?.content ||
                "",
            Accept: "application/json",
        },
    })
        .then(function (response) {
            return response.json().then(function (data) {
                return { ok: response.ok, data: data };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data.success) return;

            item.viewed = true;
            if (typeof window.updateFeedbackNavBadge === "function") {
                window.updateFeedbackNavBadge(result.data.unviewed_count);
            }
        })
        .catch(function () {
            // The feedback remains unviewed if the mark request fails.
        });
}
