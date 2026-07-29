let rcActiveId = null;
let rcActiveStatus = null;

// ── Search filter ─────────────────────────────────────────
function rcFilter() {
    const q = document.getElementById("rcSearch").value.toLowerCase().trim();
    const items = document.querySelectorAll("#rcList .msg-item");
    let visible = 0;
    items.forEach((el) => {
        const match = !q || el.dataset.search.includes(q);
        el.style.display = match ? "" : "none";
        if (match) visible++;
    });
    document.getElementById("rcNoResults").style.display =
        q && visible === 0 ? "" : "none";
}

// ── Load conversation ─────────────────────────────────────
function rcSelectConversation(id, el) {
    document
        .querySelectorAll("#rcList .msg-item")
        .forEach((i) => i.classList.remove("active"));
    el.classList.add("active");
    rcActiveId = id;

    document.getElementById("rcChatHeader").innerHTML =
        '<div style="color:rgba(255,255,255,0.35);font-size:0.85rem;"><i class="fa fa-spinner fa-spin me-2"></i>Loading…</div>';
    document.getElementById("rcChatBody").innerHTML =
        '<div class="chat-placeholder"><i class="fa fa-spinner fa-spin fa-lg opacity-50"></i></div>';
    document.getElementById("rcChatFooter").style.display = "none";

    fetch(`${rcRoute}/${id}/thread`, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "X-CSRF-TOKEN": rcCsrf,
        },
    })
        .then((r) => r.json())
        .then((data) => {
            rcActiveStatus = Number(data.request.status);
            rcRenderHeader(data.request);
            rcRenderMessages(data.messages);
            rcApplyChatState(rcActiveStatus);

            // Clear unread badge on list item
            const badge = el.querySelector(".msg-badge-unread");
            if (badge) badge.remove();
            el.classList.remove("unread");

            // Update sidebar nav badge
            rcUpdateNavBadge();
        })
        .catch(() => {
            document.getElementById("rcChatHeader").innerHTML =
                '<div style="color:#f87184;font-size:0.85rem;"><i class="fa fa-circle-exclamation me-2"></i>Failed to load conversation.</div>';
            document.getElementById("rcChatBody").innerHTML =
                '<div class="chat-placeholder"><i class="fa fa-circle-exclamation fa-2x opacity-25"></i><span>Could not load messages.</span></div>';
        });
}

/**
 * Show/hide the chat footer based on ride status.
 * Statuses 6 (Completed) and 7 (Cancelled) lock the chat.
 */
function rcApplyChatState(status) {
    var footer = document.getElementById("rcChatFooter");
    var closedMessages = {
        6: "This ride has been completed. This conversation is now closed and no further messages can be sent.",
        7: "This ride has been cancelled. This conversation has been closed and no further messages can be sent.",
    };

    if (closedMessages[status]) {
        footer.style.display = "";
        footer.innerHTML =
            '<div style="' +
            "padding:12px 16px;" +
            "background:rgba(107,114,128,0.12);" +
            "border-radius:8px;" +
            "border:1px solid rgba(107,114,128,0.25);" +
            "color:#9ca3af;" +
            "font-size:0.85rem;" +
            "text-align:center;" +
            "line-height:1.5;" +
            '">' +
            '<i class="fa fa-lock me-2" style="opacity:.7;"></i>' +
            escHtml(closedMessages[status]) +
            "</div>";
    } else {
        // Active ride — restore the textarea + send button if they were replaced
        var hasInput = footer.querySelector("#rcReplyInput");
        if (!hasInput) {
            footer.innerHTML =
                '<div style="display:flex;gap:8px;">' +
                '<textarea id="rcReplyInput" placeholder="Type your message…" rows="1"></textarea>' +
                '<button class="btn btn-primary px-3" id="rcSendBtn" title="Send message">' +
                '<i class="fa fa-paper-plane"></i>' +
                "</button>" +
                "</div>";
            document
                .getElementById("rcSendBtn")
                .addEventListener("click", rcSend);
            document
                .getElementById("rcReplyInput")
                .addEventListener("keydown", function (e) {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        rcSend();
                    }
                });
            document
                .getElementById("rcReplyInput")
                .addEventListener("input", function () {
                    if (this.value.trim()) {
                        _drvOnTyping();
                    } else {
                        _drvStopTyping();
                    }
                });
        }
        footer.style.display = "";
    }
}

function rcRenderHeader(req) {
    const statusMap = {
        1: { label: "Pending", cls: "status-locked" },
        2: { label: "Accepted", cls: "status-active" },
        3: { label: "En Route", cls: "status-active" },
        4: { label: "Arrived", cls: "status-active" },
        5: { label: "In Progress", cls: "status-active" },
        6: { label: "Completed", cls: "status-closed" },
        7: { label: "Cancelled", cls: "status-closed" },
        8: { label: "Awaiting Acceptance", cls: "status-closed" },
    };
    const s = statusMap[req.status] || {
        label: "Unknown",
        cls: "status-locked",
    };

    document.getElementById("rcChatHeader").innerHTML = `
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="fw-bold text-white" style="font-size:0.95rem;">
                            ${escHtml(req.rreb_id)}
                            <span class="ride-status-badge-1 ${s.cls}" style="margin-left:6px;">${s.label}</span>
                        </div>
                        <div class="d-flex gap-3 mt-1 flex-wrap">
                            <span style="color:rgba(255,255,255,0.4);font-size:0.78rem;">
                                <i class="fa fa-user me-1"></i>${escHtml(req.user_name)}
                            </span>
                        </div>
                        ${req.pickup_address ? `<div style="color:rgba(255,255,255,0.3);font-size:0.73rem;margin-top:3px;"><i class="fa fa-location-dot me-1"></i>${escHtml(req.pickup_address)}</div>` : ""}
                    </div>
                </div>`;
}

function rcRenderMessages(messages) {
    const body = document.getElementById("rcChatBody");

    if (!messages.length) {
        body.innerHTML =
            '<div class="chat-placeholder"><i class="fa fa-comments fa-2x opacity-25"></i><span>No messages in this conversation.</span></div>';
        return;
    }

    body.innerHTML = messages
        .map((m) => {
            const isDriver = m.sender_type === "driver";
            const isAdmin = m.sender_type === "admin";
            const bubbleCls = isDriver
                ? "bubble-driver"
                : isAdmin
                  ? "bubble-admin"
                  : "bubble-user";
            const align = isDriver
                ? "align-items:flex-end;"
                : "align-items:flex-start;";
            const metaAlign = isDriver ? "text-align:right;" : "";

            const icon = isDriver
                ? '<i class="fa fa-id-card" style="font-size:0.7rem;margin-right:3px;"></i>'
                : isAdmin
                  ? '<i class="fa fa-user-shield" style="font-size:0.7rem;margin-right:3px;"></i>'
                  : '<i class="fa fa-user" style="font-size:0.7rem;margin-right:3px;"></i>';

            return `<div style="display:flex;flex-direction:column;${align}">
                    <div class="chat-bubble ${bubbleCls}">${escHtml(m.message)}</div>
                    <div class="bubble-meta" style="${metaAlign}">
                        ${icon}${escHtml(m.sender_name)} &middot; ${escHtml(m.time)}
                    </div>
                </div>`;
        })
        .join("");

    body.scrollTop = body.scrollHeight;
}

// ── Driver typing indicator broadcast ─────────────────────────
var _drvTypingActive = false;
var _drvTypingStopTimer = null;
var _DRV_TYPING_STOP_MS = 2500;

function _drvBroadcastTyping(isTyping) {
    if (!rcActiveId) return;
    if (isTyping === _drvTypingActive) return;
    _drvTypingActive = isTyping;
    fetch(rcRoute + "/" + rcActiveId + "/typing", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": rcCsrf,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ typing: isTyping }),
    }).catch(function () {
        /* silent */
    });
}

function _drvOnTyping() {
    _drvBroadcastTyping(true);
    clearTimeout(_drvTypingStopTimer);
    _drvTypingStopTimer = setTimeout(function () {
        _drvBroadcastTyping(false);
    }, _DRV_TYPING_STOP_MS);
}

function _drvStopTyping() {
    clearTimeout(_drvTypingStopTimer);
    _drvBroadcastTyping(false);
}

document.getElementById("rcReplyInput").addEventListener("input", function () {
    if (this.value.trim()) {
        _drvOnTyping();
    } else {
        _drvStopTyping();
    }
});

// ── Send message ──────────────────────────────────────────
document.getElementById("rcSendBtn").addEventListener("click", rcSend);
document
    .getElementById("rcReplyInput")
    .addEventListener("keydown", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            rcSend();
        }
    });

function rcSend() {
    if (!rcActiveId) return;

    const input = document.getElementById("rcReplyInput");
    const text = input.value.trim();
    if (!text) return;

    const btn = document.getElementById("rcSendBtn");
    btn.disabled = true;
    input.disabled = true;

    fetch(`${rcRoute}/${rcActiveId}/send`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": rcCsrf,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ message: text }),
    })
        .then((r) => r.json())
        .then((data) => {
            if (data.success) {
                _drvStopTyping();
                input.value = "";
                rcAppendMessage(data.message);

                // Update last-message preview in the list
                const listItem = document.getElementById("rcItem" + rcActiveId);
                if (listItem) {
                    const preview = listItem.querySelector(
                        '.text-truncate[style*="rgba(255,255,255,0.35)"]',
                    );
                    if (preview) {
                        const snippet =
                            text.length > 48 ? text.slice(0, 48) + "…" : text;
                        preview.innerHTML = `<span style="color:rgba(255,255,255,0.3);font-size:0.7rem;">Driver:</span> ${escHtml(snippet)}`;
                    }
                }
            } else {
                alert(data.message || "Failed to send message.");
            }
        })
        .catch(() => alert("Network error — message not sent."))
        .finally(() => {
            btn.disabled = false;
            input.disabled = false;
            input.focus();
        });
}

function rcAppendMessage(m) {
    const body = document.getElementById("rcChatBody");

    const placeholder = body.querySelector(".chat-placeholder");
    if (placeholder) placeholder.remove();

    const div = document.createElement("div");
    div.style.cssText =
        "display:flex;flex-direction:column;align-items:flex-end;";
    div.innerHTML = `
                <div class="chat-bubble bubble-driver">${escHtml(m.message)}</div>
                <div class="bubble-meta" style="text-align:right;">
                    <i class="fa fa-id-card" style="font-size:0.7rem;margin-right:3px;"></i>${escHtml(m.sender_name)} &middot; ${escHtml(m.time)}
                </div>`;
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
}

function rcUpdateNavBadge() {
    let total = 0;
    document.querySelectorAll("#rcList .msg-item").forEach((el) => {
        const badge = el.querySelector(".msg-badge-unread");
        if (badge) total += parseInt(badge.textContent) || 0;
    });
    const navBadge = document.getElementById("driChatNavBadge");
    if (navBadge) {
        navBadge.textContent = total > 99 ? "99+" : total;
        navBadge.style.display = total > 0 ? "inline-flex" : "none";
    }
}

function escHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

// ── Real-time: helpers ────────────────────────────────────────────
var _rcRtSeenIds = new Set();

var _rcRtStatusMap = {
    1: { label: "Pending", chat: "locked" },
    2: { label: "Accepted", chat: "active" },
    3: { label: "En Route", chat: "active" },
    4: { label: "Arrived", chat: "active" },
    5: { label: "In Progress", chat: "active" },
    6: { label: "Completed", chat: "closed" },
    7: { label: "Cancelled", chat: "closed" },
};

function _rcRtChatState(status) {
    return (_rcRtStatusMap[String(status)] || { chat: "locked" }).chat;
}
function _rcRtStatusLabel(status) {
    return (_rcRtStatusMap[String(status)] || { label: "Unknown" }).label;
}
function _rcRtShortDate() {
    var now = new Date();
    var months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];
    return now.getDate() + " " + months[now.getMonth()];
}
function _rcRtUcfirst(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/* Append any message (user/admin/driver) to the open chat body */
function _rcAppendAnyMessage(m) {
    var body = document.getElementById("rcChatBody");
    if (!body) return;

    var placeholder = body.querySelector(".chat-placeholder");
    if (placeholder) placeholder.remove();

    // Clear typing indicator when a real message arrives
    var typingEl = document.getElementById("rcTypingIndicator");
    if (typingEl) typingEl.remove();

    var isDriver = m.sender_type === "driver";
    var isAdmin = m.sender_type === "admin";
    var bubbleCls = isDriver
        ? "bubble-driver"
        : isAdmin
          ? "bubble-admin"
          : "bubble-user";
    var align = isDriver ? "align-items:flex-end;" : "align-items:flex-start;";
    var metaAlign = isDriver ? "text-align:right;" : "";
    var icon = isDriver
        ? '<i class="fa fa-id-card" style="font-size:0.7rem;margin-right:3px;"></i>'
        : isAdmin
          ? '<i class="fa fa-user-shield" style="font-size:0.7rem;margin-right:3px;"></i>'
          : '<i class="fa fa-user" style="font-size:0.7rem;margin-right:3px;"></i>';

    var div = document.createElement("div");
    div.style.cssText = "display:flex;flex-direction:column;" + align;
    div.innerHTML =
        '<div class="chat-bubble ' +
        bubbleCls +
        '">' +
        escHtml(m.message) +
        "</div>" +
        '<div class="bubble-meta" style="' +
        metaAlign +
        '">' +
        icon +
        escHtml(m.sender_name) +
        " &middot; " +
        escHtml(m.time) +
        "</div>";
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
}

/* Insert a brand-new conversation item into the left list */
function _rcRtInsertConversation(data) {
    var list = document.getElementById("rcList");
    var empty = document.getElementById("rcEmptyState");
    if (!list) return;

    if (empty) empty.style.display = "none";
    list.style.display = "";

    var msg = data.message;
    var unread = Number(data.request_unread_count) || 1;
    var chatState = _rcRtChatState(data.status);
    var statusLbl = _rcRtStatusLabel(data.status);
    var searchStr = escHtml(
        ((data.rreb_id || "") + " " + (data.user_name || "")).toLowerCase(),
    );
    var preview = escHtml(
        msg.message.length > 48
            ? msg.message.slice(0, 48) + "\u2026"
            : msg.message,
    );

    var html =
        '<div class="msg-item unread" id="rcItem' +
        data.emergency_request_id +
        '"' +
        ' data-id="' +
        data.emergency_request_id +
        '"' +
        ' data-search="' +
        searchStr +
        '"' +
        ' onclick="rcSelectConversation(' +
        data.emergency_request_id +
        ', this)">' +
        '<div class="d-flex justify-content-between align-items-start gap-2">' +
        '<div style="min-width:0;">' +
        '<div class="fw-bold text-white" style="font-size:0.82rem;">' +
        escHtml(data.rreb_id) +
        '<span class="msg-badge-unread">' +
        (unread > 99 ? "99+" : unread) +
        "</span>" +
        "</div>" +
        '<div class="text-truncate" style="font-size:0.76rem;color:rgba(255,255,255,0.4);">' +
        '<i class="fa fa-user" style="margin-right:3px;font-size:0.68rem;"></i>' +
        escHtml(data.user_name || "\u2014") +
        "</div>" +
        "</div>" +
        '<div style="flex-shrink:0;text-align:right;">' +
        '<span class="ride-status-badge status-' +
        chatState +
        '">' +
        statusLbl +
        "</span>" +
        '<div style="font-size:0.68rem;color:rgba(255,255,255,0.35);margin-top:3px;">' +
        _rcRtShortDate() +
        "</div>" +
        "</div>" +
        "</div>" +
        '<div class="mt-1 text-truncate" style="font-size:0.78rem;color:rgba(255,255,255,0.35);">' +
        '<span style="color:rgba(255,255,255,0.3);font-size:0.7rem;">' +
        escHtml(_rcRtUcfirst(msg.sender_type)) +
        ":</span> " +
        preview +
        "</div>" +
        "</div>";

    list.insertAdjacentHTML("afterbegin", html);
}

/* Update preview text + unread badge on an existing list item */
function _rcRtUpdateConversation(el, data) {
    var msg = data.message;
    var reqId = data.emergency_request_id;
    var unread = Number(data.request_unread_count) || 0;
    var isOpen = rcActiveId === reqId;

    var preview = el.querySelector(
        '.text-truncate[style*="rgba(255,255,255,0.35)"]',
    );
    if (preview) {
        var text =
            msg.message.length > 48
                ? msg.message.slice(0, 48) + "\u2026"
                : msg.message;
        preview.innerHTML =
            '<span style="color:rgba(255,255,255,0.3);font-size:0.7rem;">' +
            escHtml(_rcRtUcfirst(msg.sender_type)) +
            ":</span> " +
            escHtml(text);
    }

    if (!isOpen && unread > 0) {
        el.classList.add("unread");
        var badge = el.querySelector(".msg-badge-unread");
        if (badge) {
            badge.textContent = unread > 99 ? "99+" : unread;
        } else {
            var titleDiv = el.querySelector(".fw-bold.text-white");
            if (titleDiv) {
                var newBadge = document.createElement("span");
                newBadge.className = "msg-badge-unread";
                newBadge.textContent = unread > 99 ? "99+" : unread;
                titleDiv.appendChild(newBadge);
            }
        }
    }

    /* Bubble conversation to top of list */
    var list = document.getElementById("rcList");
    if (list && el.parentNode === list) list.insertBefore(el, list.firstChild);
}

// ── Real-time: typing indicator ───────────────────────────────────
var _driTypingTimers = {};

function _showDriTypingIndicator(senderName) {
    var body = document.getElementById("rcChatBody");
    if (!body) return;
    var el = document.getElementById("rcTypingIndicator");
    if (!el) {
        el = document.createElement("div");
        el.id = "rcTypingIndicator";
        el.style.cssText =
            "padding:2px 0 6px;font-size:0.78rem;color:rgba(255,255,255,0.4);font-style:italic;";
        el.innerHTML =
            '<span style="display:inline-flex;gap:3px;align-items:center;">' +
            '<span class="rr-typing-dot"></span>' +
            '<span class="rr-typing-dot"></span>' +
            '<span class="rr-typing-dot"></span>' +
            "</span> " +
            escHtml(senderName) +
            " is typing\u2026";
        body.appendChild(el);
    }
    body.scrollTop = body.scrollHeight;
}

function _hideDriTypingIndicator() {
    var el = document.getElementById("rcTypingIndicator");
    if (el) el.remove();
}

window._rrOnRideChatTyping = function (data) {
    var reqId = data.emergency_request_id;

    clearTimeout(_driTypingTimers[reqId]);

    if (data.typing) {
        /* Only show when this conversation is open */
        if (rcActiveId === reqId) {
            _showDriTypingIndicator(data.sender_name);
        }
        /* Fallback auto-hide in case the stop event is missed */
        _driTypingTimers[reqId] = setTimeout(function () {
            _hideDriTypingIndicator();
        }, 3500);
    } else {
        _hideDriTypingIndicator();
    }
};

// ── Real-time: ride status change → lock chat if completed/cancelled ──
// realtime.js fires _rrOnRequestStatusChanged on every emergency-request-status-changed event.
window._rrOnRequestStatusChanged = function (payload) {
    var reqId = payload.id;
    var status = Number(payload.status);

    // Update the sidebar list item's status badge
    var listItem = document.getElementById("rcItem" + reqId);
    var listItem_1 = document.getElementById("rcChatHeader");
    if (listItem && listItem_1) {
        var badge = listItem.querySelector(".ride-status-badge");
        if (badge) {
            var info = _rcRtStatusMap[String(status)] || {
                label: "Unknown",
                chat: "locked",
            };
            badge.textContent = info.label;
            badge.className = "ride-status-badge status-" + info.chat;
        }
        var badge_1 = listItem_1.querySelector(".ride-status-badge-1");
        if (badge_1) {
            var info = _rcRtStatusMap[String(status)] || {
                label: "Unknown",
                chat: "locked",
            };
            badge_1.textContent = info.label;
            badge_1.className = "ride-status-badge-1 status-" + info.chat;
        }
    }

    // If this is the currently open conversation, update the footer immediately
    if (rcActiveId === reqId) {
        rcActiveStatus = status;
        rcApplyChatState(status);
    }
};

// ── Real-time: main handler (called by realtime.js channel binding) ──
// Defined here (not in an external file) so it closes over rcActiveId.
window._rrOnRideChatMessage = function (data) {
    var reqId = data.emergency_request_id;
    var msg = data.message;

    /* Deduplicate — guard against reconnect replays */
    if (msg && msg.id) {
        if (_rcRtSeenIds.has(msg.id)) return;
        _rcRtSeenIds.add(msg.id);
    }

    var existingEl = document.getElementById("rcItem" + reqId);
    if (!existingEl) {
        _rcRtInsertConversation(data);
    } else {
        _rcRtUpdateConversation(existingEl, data);
    }

    /* If this conversation is currently open, stream the message in */
    if (rcActiveId === reqId) {
        _rcAppendAnyMessage(msg);
    }
};
