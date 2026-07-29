let _cMsgId = null;
let _cUserName = null;
const _csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";

let _admTyping = false;
let _admTypingTimer = null;

// ── Wire up existing list items ───────────────────────────────────────────────
document.querySelectorAll(".msg-item").forEach(function (item) {
    item.addEventListener("click", function () {
        const id = this.id.replace("msgItem", "");
        admSelectItem(this);
        admLoadThread(id);
    });
});

function admSelectItem(el) {
    document
        .querySelectorAll(".msg-item")
        .forEach((i) => i.classList.remove("active"));
    if (el) el.classList.add("active");
}

// ── Load thread from server
async function admLoadThread(id) {
    _cMsgId = id;

    const item = document.getElementById("msgItem" + id);
    if (item && item.classList.contains("unread")) {
        item.classList.remove("unread");
        item.querySelector(".msg-badge-unread")?.remove();
    }

    const body = document.getElementById("chatBody");
    const header = document.getElementById("chatHeader");
    if (body)
        body.innerHTML =
            '<div class="chat-placeholder"><i class="fa fa-spinner fa-spin fa-2x opacity-25"></i></div>';

    try {
        const resp = await fetch(`${window.route.cm}/${_cMsgId}/thread`, {
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": _csrf,
            },
        });
        if (!resp.ok) throw new Error("HTTP " + resp.status);
        const d = await resp.json();

        _cUserName = d.name || "User";

        // Build avatar HTML if profile picture exists
        const avatarHtml = d.profile_picture_url
            ? `<img src="${cEsc(d.profile_picture_url)}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">`
            : `<div style="width:36px;height:36px;border-radius:50%;background:rgba(215,44,66,0.15);color:#f87184;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.85rem;"><i class="fa fa-user"></i></div>`;

        // Header — with resolve button or resolved badge
        const resolvedBadge = d.is_resolved
            ? `<span style="font-size:0.78rem;background:rgba(34,197,94,0.1);color:#86efac;border:1.5px solid rgba(34,197,94,0.3);border-radius:20px;padding:3px 12px;font-weight:600;flex-shrink:0;"><i class="fa fa-circle-check"></i> Resolved</span>`
            : `<button id="resolveBtn" onclick="admResolveChat(${id})" style="font-size:0.78rem;background:rgba(34,197,94,0.12);color:#86efac;border:1.5px solid rgba(34,197,94,0.3);border-radius:20px;padding:3px 12px;font-weight:600;cursor:pointer;flex-shrink:0;"><i class="fa fa-circle-check"></i> Mark Resolved</button>`;
        if (header) {
            header.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        ${avatarHtml}
                        <div>
                            <div style="font-weight:700;color:#fff;font-size:0.95rem;">${cEsc(d.name)}${!d.is_user ? ' <span style="font-size:0.7rem;background:rgba(99,102,241,0.2);color:#a5b4fc;border-radius:20px;padding:2px 8px;border:1px solid rgba(99,102,241,0.3);margin-left:6px;">Guest</span>' : ""}</div>
                            <div style="font-size:0.78rem;color:var(--adm-muted);margin-top:2px;">${cEsc(d.email)} · ${cEsc(d.time)}</div>
                        </div>
                    </div>
                    ${resolvedBadge}
                </div>
                <div style="font-weight:600;color:rgba(255,255,255,0.85);font-size:0.87rem;margin-top:8px;">${cEsc(d.subject)}</div>`;
        }

        // Body
        if (body) {
            body.innerHTML = "";
            body.appendChild(cBubble("user", d.message, d.time, _cUserName));
            (d.replies || []).forEach(function (r) {
                body.appendChild(
                    cBubble(
                        r.sender_type,
                        r.message,
                        r.time,
                        r.sender_type === "user" ? _cUserName : "Operator",
                    ),
                );
            });
            body.scrollTop = body.scrollHeight;
        }

        // Footer
        const footer = document.getElementById("chatFooter");
        const resolvedNotice = document.getElementById("resolvedNotice");
        const replyInput = document.getElementById("replyInput");
        const sendBtn = document.getElementById("sendBtn");
        if (footer) footer.style.display = "flex";

        if (d.is_resolved) {
            if (resolvedNotice) resolvedNotice.style.display = "block";
            if (replyInput) replyInput.style.display = "none";
            if (sendBtn) sendBtn.style.display = "none";
        } else {
            if (resolvedNotice) resolvedNotice.style.display = "none";
            if (replyInput) replyInput.style.display = "block";
            if (sendBtn) sendBtn.style.display = "block";
        }

        // Guest notice
        const notice = document.getElementById("guestEmailNotice");
        const emailEl = document.getElementById("guestEmailAddress");
        if (!d.is_user) {
            if (emailEl) emailEl.textContent = d.email;
            if (notice) notice.style.display = "flex";
        } else {
            if (notice) notice.style.display = "none";
        }

        // Mark list item as read
        document.getElementById("msgItem" + id)?.classList.remove("unread");
    } catch (e) {
        if (body)
            body.innerHTML =
                '<div class="chat-placeholder">Error loading thread.</div>';
    }
}

// ── Build chat bubble with sender name ────────────────────────────────────────
function cBubble(senderType, message, time, senderName) {
    const isAdmin = senderType === "admin";
    const label = senderName || (isAdmin ? "Operator" : "User");
    const wrap = document.createElement("div");
    wrap.style.cssText = `display:flex;flex-direction:column;align-items:${isAdmin ? "flex-end" : "flex-start"};gap:2px;`;
    wrap.innerHTML = `
        <div style="font-size:0.7rem;font-weight:600;color:var(--adm-muted);padding:0 4px;">${cEsc(label)}</div>
        <div style="max-width:75%;background:${isAdmin ? "rgba(215,44,66,0.18)" : "rgba(255,255,255,0.06)"};
                border:1px solid ${isAdmin ? "rgba(215,44,66,0.25)" : "rgba(255,255,255,0.08)"};
                border-radius:12px;padding:10px 14px;font-size:0.88rem;
                color:${isAdmin ? "#fca5a5" : "rgba(255,255,255,0.85)"};">
            ${cEsc(message)}
        </div>
        <div style="font-size:0.68rem;color:var(--adm-muted);padding:0 4px;">${cEsc(time)}</div>`;
    return wrap;
}

function cEsc(s) {
    return String(s || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

// ── Send reply ────────────────────────────────────────────────────────────────
document.getElementById("sendBtn")?.addEventListener("click", admSendReply);
document.getElementById("replyInput")?.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        admSendReply();
    }
});

async function admSendReply() {
    if (!_cMsgId) return;
    const input = document.getElementById("replyInput");
    const msg = input?.value?.trim();
    if (!msg) return;

    const btn = document.getElementById("sendBtn");
    const origHTML = btn ? btn.innerHTML : "";
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    }

    clearTimeout(_admTypingTimer);
    _admTyping = false;

    try {
        const resp = await fetch(`${window.route.cm}/${_cMsgId}/reply`,
            {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": _csrf,
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
            if (data.reply?.id) _cSentReplyIds.add(data.reply.id);
            const body = document.getElementById("chatBody");
            if (body) {
                body.appendChild(
                    cBubble("admin", msg, data.reply.time, "Operator"),
                );
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

// ── Auto-resize + typing indicator
document.getElementById("replyInput")?.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = Math.min(this.scrollHeight, 100) + "px";

    if (!_cMsgId) return;
    clearTimeout(_admTypingTimer);
    if (!_admTyping) {
        _admTyping = true;
        fetch(`${window.route.cm}/${_cMsgId}/typing`, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": _csrf,
            },
        }).catch(function () {});
    }
    _admTypingTimer = setTimeout(function () {
        _admTyping = false;
    }, 2500);
});

// ── Resolve chat
async function admResolveChat(id) {
    const btn = document.getElementById("resolveBtn");
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Resolving…';
    }
    try {
        const resp = await fetch(`${window.route.cm}/${_cMsgId}/resolve`,  {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": _csrf,
                Accept: "application/json",
            },
        });
        const data = await resp.json();
        if (data.success) admMarkResolved(id);
    } catch (e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-circle-check"></i> Mark Resolved';
        }
    }
}

function admMarkResolved(id) {
    const btn = document.getElementById("resolveBtn");
    if (btn) {
        const badge = document.createElement("span");
        badge.style.cssText =
            "font-size:0.78rem;background:rgba(34,197,94,0.1);color:#86efac;border:1.5px solid rgba(34,197,94,0.3);border-radius:20px;padding:3px 12px;font-weight:600;flex-shrink:0;";
        badge.innerHTML = '<i class="fa fa-circle-check"></i> Resolved';
        btn.replaceWith(badge);
    }
    const replyInput = document.getElementById("replyInput");
    const sendBtn = document.getElementById("sendBtn");
    if (replyInput) replyInput.style.display = "none";
    if (sendBtn) sendBtn.style.display = "none";
    const notice = document.getElementById("resolvedNotice");
    if (notice) notice.style.display = "block";
    const listItem = document.getElementById("msgItem" + id);
    if (listItem) listItem.style.opacity = "0.65";
}

// ── Search / filter (existing UI hook)
document.getElementById("msgSearch")?.addEventListener("input", filterMessages);
document.querySelectorAll(".msg-filter-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
        document.querySelectorAll(".msg-filter-btn").forEach((b) => b.classList.remove("active"));
        this.classList.add("active");
        filterMessages();
    });
});

function filterMessages() {
    const q = (document.getElementById("msgSearch")?.value || "").toLowerCase();
    const active = document.querySelector(".msg-filter-btn.active")?.id || "filterAll";
    document.querySelectorAll(".msg-item").forEach(function (item) {
        const matchQ = !q || (item.getAttribute("data-search") || "").includes(q);
        const t = item.getAttribute("data-type") || "";
        const matchT = active === "filterAll" || (active === "filterUser" && t === "user") || (active === "filterGuest" && t === "guest");
        item.style.display = matchQ && matchT ? "" : "none";
    });
}

// ── Real-time (Contact Support) ─────────────────────────────────────────────
// Reuses the single "contact.admin" channel already subscribed once in
// admin.blade.php (window.contactAdminChannel) — no extra subscription here.
let _cUserTypingTimer = null;
// Tracks reply/resolve ids just sent from this tab so the echo we receive
// back on our own broadcast doesn't get appended/applied a second time.
const _cSentReplyIds = new Set();

(function () {
    const ch = window.contactAdminChannel;
    if (!ch) return;

    // New guest/user message submitted — insert at the top of the list
    // (matches server sort: unread first, then newest) and clear empty state.
    ch.bind("contact.submitted", function (data) {
        if (!data || !data.message_id || document.getElementById("msgItem" + data.message_id)) return;

        document.getElementById("msgEmptyState")?.style && (document.getElementById("msgEmptyState").style.display = "none");
        const list = document.getElementById("msgList");
        if (list) list.style.display = "";

        const isUser = !!data.user_id;
        const item = document.createElement("div");
        item.className = "msg-item unread";
        item.id = "msgItem" + data.message_id;
        item.setAttribute("data-search", String(data.name + " " + data.subject + " " + data.email).toLowerCase());
        item.setAttribute("data-type", isUser ? "user" : "guest");
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div style="min-width:0;">
                    <div class="fw-bold text-white fs-xs text-truncate">
                        ${cEsc(data.name)}
                        <span class="msg-badge-unread">new</span>
                        ${!isUser ? '<span class="msg-badge-guest" title="Submitted without an account — replies go via email">Guest</span>' : ""}
                    </div>
                    <div class="text-truncate" style="font-size:0.78rem;color:var(--adm-muted);">${cEsc(data.subject)}</div>
                </div>
                <div style="font-size:0.7rem;color:var(--adm-muted);white-space:nowrap;flex-shrink:0;">${cEsc(data.date_short)}</div>
            </div>
            <div class="mt-1 text-truncate" style="font-size:0.78rem;color:rgba(255,255,255,0.4);">${cEsc(data.message).slice(0, 55)}</div>`;
        item.addEventListener("click", function () {
            const id = this.id.replace("msgItem", "");
            admSelectItem(this);
            admLoadThread(id);
        });
        if (list) list.insertBefore(item, list.firstChild);
        filterMessages();
    });

    // User sent a reply — append live if that thread is open, otherwise flag unread.
    ch.bind("user.reply", function (data) {
        if (!data || !data.contact_message_id) return;
        const id = data.contact_message_id;

        if (String(_cMsgId) === String(id)) {
            const body = document.getElementById("chatBody");
            if (body) {
                body.appendChild(cBubble("user", data.message, data.time, _cUserName));
                body.scrollTop = body.scrollHeight;
            }
            hideUserTyping();
        } else {
            const item = document.getElementById("msgItem" + id);
            if (item && !item.classList.contains("unread")) {
                item.classList.add("unread");
                const nameLine = item.querySelector(".fw-bold");
                if (nameLine && !nameLine.querySelector(".msg-badge-reply")) {
                    const b = document.createElement("span");
                    b.className = "msg-badge-unread msg-badge-reply";
                    b.textContent = "Reply";
                    nameLine.appendChild(b);
                }
            }
        }
    });

    // User is typing in the currently open thread.
    ch.bind("user.typing", function (data) {
        if (!data || String(_cMsgId) !== String(data.contact_message_id)) return;
        const el = document.getElementById("userTypingIndicator");
        if (el) el.style.display = "flex";
        clearTimeout(_cUserTypingTimer);
        _cUserTypingTimer = setTimeout(hideUserTyping, 3000);
    });

    // An admin reply — reflect it in every OTHER tab/admin. The tab that sent
    // it already appended the bubble locally after the fetch resolved, so we
    // skip our own echo here to avoid a duplicate bubble.
    ch.bind("admin.reply", function (data) {
        if (!data) return;
        const wasSentByThisTab = data.reply_id && _cSentReplyIds.has(data.reply_id);
        if (wasSentByThisTab) _cSentReplyIds.delete(data.reply_id);
        if (wasSentByThisTab || String(_cMsgId) !== String(data.contact_message_id)) return;
        const body = document.getElementById("chatBody");
        if (body) {
            body.appendChild(cBubble("admin", data.message, data.time, "Operator"));
            body.scrollTop = body.scrollHeight;
        }
    });

    // Conversation resolved from another tab/admin — reflect it in this tab too.
    ch.bind("resolved", function (data) {
        if (!data || !data.message_id) return;
        if (String(_cMsgId) === String(data.message_id)) {
            admMarkResolved(data.message_id);
        } else {
            const listItem = document.getElementById("msgItem" + data.message_id);
            if (listItem) listItem.style.opacity = "0.65";
        }
    });
})();

function hideUserTyping() {
    clearTimeout(_cUserTypingTimer);
    const el = document.getElementById("userTypingIndicator");
    if (el) el.style.display = "none";
}