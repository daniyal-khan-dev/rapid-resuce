/* CONTACT HISTORY — Real-time thread loading, user replies, Echo */
window._rrCurMsgId = null;
let _rrCurMsgId = null;

let _rrTyping = false;
let _rrTypingTimer = null;

// Alias so inline onclick="openContactThread(...)" works
function openContactThread(id, el) {
    rrLoadContactThread(id, el);
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".rr-msg-item").forEach(function (item) {
        item.addEventListener("click", function (evt) {
            if (evt._rrHandled) return;
            evt._rrHandled = true;
            rrLoadContactThread(this.dataset.id, this);
        });
    });

    // Send button
    document.getElementById("userSendBtn")?.addEventListener("click", rrSendUserReply);

    // Enter key in textarea
    document.getElementById("userReplyInput")?.addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            rrSendUserReply();
        }
    });

    // Auto-resize + typing indicator
    document.getElementById("userReplyInput")?.addEventListener("input", function () {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 100) + "px";

        if (!_rrCurMsgId) return;
        clearTimeout(_rrTypingTimer);
        if (!_rrTyping) {
            _rrTyping = true;
            fetch(
                window.routes.contactThread + "/" + _rrCurMsgId + "/typing",
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": window.routes.csrfToken,
                    },
                },
            ).catch(function () {});
        }
        _rrTypingTimer = setTimeout(function () {
            _rrTyping = false;
        }, 2500);
    });

    // Auto-open thread from ?thread= URL param
    const urlParams = new URLSearchParams(window.location.search);
    const threadId = urlParams.get("thread");
    if (threadId) {
        // Activate the Contact History tab
        var contactTab = document.getElementById("v-pills-contacts-tab");
        if (contactTab && typeof bootstrap !== "undefined") {
            new bootstrap.Tab(contactTab).show();
        }
        // Load the thread after tab animation settles
        setTimeout(function () {
            var item = document.getElementById("msgItem" + threadId);
            rrLoadContactThread(threadId, item || null);
        }, 200);
    }
});

// Load a thread from server
async function rrLoadContactThread(id, el) {
    _rrCurMsgId = id;
    window._rrCurMsgId = id;

    // Highlight selected item
    document
        .querySelectorAll(".rr-msg-item")
        .forEach((i) => (i.style.background = ""));
    if (el) el.style.background = "rgba(185,28,44,0.05)";

    // Show spinner
    const body = document.getElementById("contactThreadBody");
    if (body)
        body.innerHTML =
            '<div style="text-align:center;padding:40px;max-height: 60vh;color:var(--rr-text-muted);"><i class="fa fa-spinner fa-spin"></i></div>';

    try {
        const resp = await fetch(
            window.routes.contactThread + "/" + id + "/thread",
            {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": window.routes.csrfToken,
                },
            },
        );
        const d = await resp.json();

        // Thread body
        if (body) {
            body.innerHTML = "";
            body.appendChild(rrBubble("user", d.message, d.time, "You"));
            (d.replies || []).forEach((r) =>
                body.appendChild(
                    rrBubble(
                        r.sender_type,
                        r.message,
                        r.time,
                        r.sender_type === "admin" ? "Operator" : "You",
                    ),
                ),
            );
            body.scrollTop = body.scrollHeight;
        }

        // Show reply footer
        const footer = document.getElementById("contactReplyFooter");
        if (footer) footer.style.display = "block";

        // Resolved state
        if (d.is_resolved) {
            rrMarkResolved();
        } else {
            const rNotice = document.getElementById("rrResolvedNotice");
            const rInput = document.getElementById("userReplyInput");
            const rBtn = document.getElementById("userSendBtn");
            if (rNotice) rNotice.style.display = "none";
            if (rInput) {
                rInput.disabled = false;
                rInput.placeholder = "Type a follow-up message…";
            }
            if (rBtn) rBtn.disabled = false;
        }

        // Header — show resolved badge if applicable
        const header = document.getElementById("contactThreadHeader");
        if (header) {
            const resolvedTag = d.is_resolved
                ? ` <span style="font-size:0.72rem;background:rgba(34,197,94,0.1);color:#15803d;border:1px solid rgba(34,197,94,0.3);border-radius:20px;padding:2px 10px;font-weight:600;margin-left:8px;vertical-align:middle;"><i class="fa fa-circle-check"></i> Resolved</span>`
                : "";
            header.innerHTML = `<strong style="color:var(--rr-navy);font-size:0.95rem;">${rrHEsc(d.subject)}${resolvedTag}</strong>
                <div style="font-size:0.78rem;color:var(--rr-text-muted);margin-top:3px;">Sent ${rrHEsc(d.time)}</div>`;
        }

        // Clear unread badge
        document.getElementById("unreadBadge" + id)?.remove();
        const listItem = document.getElementById("msgItem" + id);
        if (listItem) listItem.style.borderLeft = "";
    } catch (e) {
        if (body)
            body.innerHTML =
                '<div style="text-align:center;padding:40px;color:var(--rr-text-muted);">Error loading thread.</div>';
    }
}

// Build a chat bubble with sender name
function rrBubble(senderType, message, time, senderName) {
    const isAdmin = senderType === "admin";
    const label = senderName || (isAdmin ? "Operator" : "You");
    const wrap = document.createElement("div");
    wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isAdmin ? "flex-start" : "flex-end"};gap:2px;`;
    wrap.innerHTML = `
            <div style="font-size:0.7rem;font-weight:600;color:var(--rr-text-muted);padding:0 4px;">${rrHEsc(label)}</div>
            <div style="max-width:75%;background:${isAdmin ? "#f0fdf4" : "var(--rr-primary)"};
                    border:1px solid ${isAdmin ? "#d1fae5" : "transparent"};
                    border-radius:12px;padding:10px 14px;font-size:0.88rem;
                    color:${isAdmin ? "#065f46" : "#fff"};">${rrHEsc(message)}</div>
            <div style="font-size:0.68rem;color:var(--rr-text-light);padding:0 4px;">${rrHEsc(time)}</div>`;
    return wrap;
}

function rrHEsc(s) {
    return String(s || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

// Send a user reply
async function rrSendUserReply() {
    if (!_rrCurMsgId) return;
    const input = document.getElementById("userReplyInput");
    const msg = input?.value?.trim();
    if (!msg) return;

    const btn = document.getElementById("userSendBtn");
    const origHTML = btn ? btn.innerHTML : "";
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    }

    clearTimeout(_rrTypingTimer);
    _rrTyping = false;

    try {
        const resp = await fetch(
            window.routes.contactReply + "/" + _rrCurMsgId + "/reply",
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": window.routes.csrfToken,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    message: msg,
                }),
            },
        );
        const data = await resp.json();
        if (data.success) {
            input.value = "";
            input.style.height = "auto";
            if (data.reply?.id) _rrSentReplyIds.add(data.reply.id);
            const body = document.getElementById("contactThreadBody");
            if (body) {
                body.appendChild(rrBubble("user", msg, data.reply.time, "You"));
                body.scrollTop = body.scrollHeight;
            }
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHTML;
        }
    }
}

// ── Real-time (Contact Support) ─────────────────────────────────────────────
// Reuses the single private "contact.user.{id}" channel already subscribed
// once in user.blade.php (window.contactUserChannel) — no extra subscription here.
let _rrAdminTypingTimer = null;
// Tracks reply ids just sent from this tab so the echo we receive back on
// our own broadcast doesn't get appended a second time.
const _rrSentReplyIds = new Set();

(function () {
    const ch = window.contactUserChannel;
    if (!ch) return;

    // A brand-new message submitted (from this user, possibly another tab).
    ch.bind("contact.submitted", function (data) {
        if (!data || !data.message_id || document.getElementById("msgItem" + data.message_id)) return;

        document.getElementById("contactEmptyState")?.style && (document.getElementById("contactEmptyState").style.display = "none");
        const panel = document.getElementById("contactMsgListPanel");
        if (panel) panel.style.display = "flex";

        const list = document.getElementById("contactMsgList");
        if (!list) return;
        const item = document.createElement("div");
        item.className = "rr-msg-item";
        item.id = "msgItem" + data.message_id;
        item.setAttribute("data-id", data.message_id);
        item.setAttribute("data-subject", data.subject || "");
        item.setAttribute("data-message", data.message || "");
        item.setAttribute("data-date", data.time || "");
        item.setAttribute("onclick", "openContactThread(" + data.message_id + ", this)");
        item.style.cssText = "padding:14px 16px;border-bottom:1px solid var(--rr-border);cursor:pointer;transition:background .15s;";
        item.innerHTML = `
            <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                <strong style="font-size:0.88rem;color:var(--rr-navy);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${rrHEsc(data.subject)}</strong>
                <span style="font-size:0.7rem;color:var(--rr-text-light);flex-shrink:0;white-space:nowrap;">${rrHEsc(data.date_short)}</span>
            </div>
            <div style="font-size:0.78rem;color:var(--rr-text-muted);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${rrHEsc((data.message || "").slice(0, 45))}</div>`;
        item.addEventListener("click", function (evt) {
            if (evt._rrHandled) return;
            evt._rrHandled = true;
            rrLoadContactThread(this.dataset.id, this);
        });
        list.insertBefore(item, list.firstChild);
    });

    // This user's own reply, echoed back for sync across their other open
    // tabs. The tab that sent it already appended the bubble locally, so we
    // skip our own echo here to avoid a duplicate bubble.
    ch.bind("user.reply", function (data) {
        if (!data || !data.contact_message_id) return;
        const wasSentByThisTab = data.reply_id && _rrSentReplyIds.has(data.reply_id);
        if (wasSentByThisTab) _rrSentReplyIds.delete(data.reply_id);
        if (!wasSentByThisTab && String(_rrCurMsgId) === String(data.contact_message_id)) {
            const body = document.getElementById("contactThreadBody");
            if (body) {
                body.appendChild(rrBubble("user", data.message, data.time, "You"));
                body.scrollTop = body.scrollHeight;
            }
        }
    });

    // Admin replied — append live if this thread is open, otherwise flag unread.
    ch.bind("admin.reply", function (data) {
        if (!data || !data.contact_message_id) return;
        const id = data.contact_message_id;

        if (String(_rrCurMsgId) === String(id)) {
            const body = document.getElementById("contactThreadBody");
            if (body) {
                body.appendChild(rrBubble("admin", data.message, data.time, "Operator"));
                body.scrollTop = body.scrollHeight;
            }
            hideAdminTyping();
        } else {
            const item = document.getElementById("msgItem" + id);
            if (item && !document.getElementById("unreadBadge" + id)) {
                const badge = document.createElement("span");
                badge.id = "unreadBadge" + id;
                badge.style.cssText = "display:inline-flex;align-items:center;gap:4px;background:var(--rr-primary);color:#fff;font-size:0.62rem;font-weight:700;padding:2px 8px;border-radius:20px;margin-top:6px;letter-spacing:0.03em;";
                badge.innerHTML = '<i class="fa fa-circle" style="font-size:0.4rem;"></i>NEW REPLY';
                item.appendChild(badge);
                item.style.borderLeft = "3px solid var(--rr-primary)";
            }
        }
    });

    // Admin is typing in the currently open thread.
    ch.bind("admin.typing", function (data) {
        if (!data || String(_rrCurMsgId) !== String(data.contact_message_id)) return;
        const el = document.getElementById("rrAdminTypingIndicator");
        if (el) el.style.display = "flex";
        clearTimeout(_rrAdminTypingTimer);
        _rrAdminTypingTimer = setTimeout(hideAdminTyping, 3000);
    });

    // Conversation resolved from the Admin side (or another of this user's tabs).
    ch.bind("resolved", function (data) {
        if (!data || !data.message_id) return;
        if (String(_rrCurMsgId) === String(data.message_id)) {
            rrMarkResolved();
            const header = document.getElementById("contactThreadHeader");
            if (header && !header.querySelector(".fa-circle-check")) {
                const strong = header.querySelector("strong");
                if (strong) {
                    strong.insertAdjacentHTML(
                        "beforeend",
                        ' <span style="font-size:0.72rem;background:rgba(34,197,94,0.1);color:#15803d;border:1px solid rgba(34,197,94,0.3);border-radius:20px;padding:2px 10px;font-weight:600;margin-left:8px;vertical-align:middle;"><i class="fa fa-circle-check"></i> Resolved</span>',
                    );
                }
            }
        }
    });
})();

function hideAdminTyping() {
    clearTimeout(_rrAdminTypingTimer);
    const el = document.getElementById("rrAdminTypingIndicator");
    if (el) el.style.display = "none";
}

// Mark conversation as resolved
function rrMarkResolved() {
    const notice = document.getElementById("rrResolvedNotice");
    const input = document.getElementById("userReplyInput");
    const btn = document.getElementById("userSendBtn");
    const footer = document.getElementById("contactReplyFooter");
    const typing = document.getElementById("rrAdminTypingIndicator");

    if (footer) footer.style.display = "block";
    if (notice) notice.style.display = "block";
    if (input) input.style.display = "none";
    if (btn) btn.style.display = "none";
    if (typing) typing.style.display = "none";
}