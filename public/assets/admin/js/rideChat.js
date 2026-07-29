let rcActiveId = null;

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

function rcSelectConversation(id, el) {
    // Highlight active item
    document
        .querySelectorAll("#rcList .msg-item")
        .forEach((i) => i.classList.remove("active"));
    el.classList.add("active");
    rcActiveId = id;

    // Show loading state
    document.getElementById("rcChatHeader").innerHTML =
        '<div style="color:var(--adm-muted);font-size:0.85rem;"><i class="fa fa-spinner fa-spin me-2"></i>Loading…</div>';
    document.getElementById("rcChatBody").innerHTML =
        '<div class="chat-placeholder"><i class="fa fa-spinner fa-spin fa-lg opacity-50"></i></div>';
    document.getElementById("rcChatFooter").style.display = "none";

    fetch(`${rcRoute}/${id}/thread`, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
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

        // Update sidebar badge
        rcDecrementNavBadge(id);
    })
    .catch(() => {
        document.getElementById("rcChatHeader").innerHTML =
            '<div style="color:#f87184;font-size:0.85rem;"><i class="fa fa-circle-exclamation me-2"></i>Failed to load conversation.</div>';
        document.getElementById("rcChatBody").innerHTML =
            '<div class="chat-placeholder"><i class="fa fa-circle-exclamation fa-2x opacity-25"></i><span>Could not load messages.</span></div>';
    });
}

/**
 * Show or hide the chat footer based on ride status.
 * Statuses 6 (Completed) and 7 (Cancelled) lock the chat.
 * Exposed on window so rideChatRealtime.js can call it on live status changes.
 */
window.rcApplyChatState = function rcApplyChatState(status) {
    const footer  = document.getElementById("rcChatFooter");
    const closedMessages = {
        6: "This ride has been completed. This conversation is now closed and no further messages can be sent.",
        7: "This ride has been cancelled. This conversation has been closed and no further messages can be sent.",
    };

    if (closedMessages[status]) {
        // Hide the input/button, show the closed banner
        footer.style.display = "";
        footer.innerHTML =
            `<div style="` +
                `padding:12px 16px;` +
                `background:rgba(107,114,128,0.12);` +
                `border-radius:8px;` +
                `border:1px solid rgba(107,114,128,0.25);` +
                `color:#9ca3af;` +
                `font-size:0.85rem;` +
                `text-align:center;` +
                `line-height:1.5;` +
            `">` +
                `<i class="fa fa-lock me-2" style="opacity:.7;"></i>` +
                escHtml(closedMessages[status]) +
            `</div>`;
    } else {
        // Active ride — restore the textarea + send button if they were replaced
        const hasInput = footer.querySelector("#rcReplyInput");
        if (!hasInput) {
            footer.innerHTML =
                `<div style="display:flex;gap:8px;">` +
                    `<textarea id="rcReplyInput" placeholder="Type your reply…" rows="1"></textarea>` +
                    `<button class="btn btn-danger px-3" id="rcSendBtn" title="Send reply">` +
                        `<i class="fa fa-paper-plane"></i>` +
                    `</button>` +
                `</div>`;
            // Re-attach event listeners
            document.getElementById("rcSendBtn").addEventListener("click", rcSend);
            document.getElementById("rcReplyInput").addEventListener("keydown", function (e) {
                if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); rcSend(); }
            });
            document.getElementById("rcReplyInput").addEventListener("input", function () {
                if (this.value.trim()) { _rcOnAdminTyping(); } else { _rcStopTypingBroadcast(); }
            });
        }
        footer.style.display = "";
    }
};

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
    const driverLine = req.driver_name
        ? `<span style="color:var(--adm-muted);font-size:0.78rem;"><i class="fa fa-id-card me-1"></i>${escHtml(req.driver_name)}</span>`
        : `<span style="color:var(--adm-muted);font-size:0.78rem;opacity:.55;">No driver assigned</span>`;

    document.getElementById("rcChatHeader").innerHTML = `
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <div class="fw-bold text-white" style="font-size:0.95rem;">
                            ${escHtml(req.rreb_id)}
                            <span class="ride-status-badge-1 ${s.cls}" style="margin-left:6px;">${s.label}</span>
                        </div>
                        <div class="d-flex gap-3 mt-1 flex-wrap">
                            <span style="color:var(--adm-muted);font-size:0.78rem;"><i class="fa fa-user me-1"></i>${escHtml(req.user_name)}</span>
                            ${driverLine}
                        </div>
                        ${req.pickup_address ? `<div style="color:var(--adm-muted);font-size:0.73rem;margin-top:3px;"><i class="fa fa-location-dot me-1"></i>${escHtml(req.pickup_address)}</div>` : ""}
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
            const isAdmin = m.sender_type === "admin";
            const isDriver = m.sender_type === "driver";
            const bubbleCls = isAdmin
                ? "bubble-admin"
                : isDriver
                  ? "bubble-driver"
                  : "bubble-user";
            const align = isAdmin
                ? "align-items:flex-end;"
                : "align-items:flex-start;";

            const icon = isAdmin
                ? '<i class="fa fa-user-shield" style="font-size:0.7rem;margin-right:3px;"></i>'
                : isDriver
                  ? '<i class="fa fa-id-card" style="font-size:0.7rem;margin-right:3px;"></i>'
                  : '<i class="fa fa-user" style="font-size:0.7rem;margin-right:3px;"></i>';

            return `<div style="display:flex;flex-direction:column;${align}">
                    <div class="chat-bubble ${bubbleCls}">${escHtml(m.message)}</div>
                    <div class="bubble-meta" style="${isAdmin ? "text-align:right;" : ""}">
                        ${icon}${escHtml(m.sender_name)} &middot; ${escHtml(m.time)}
                    </div>
                </div>`;
        })
        .join("");

    // Scroll to latest
    body.scrollTop = body.scrollHeight;
}

// ── Admin typing indicator broadcast ─────────────────────
var _rcTypingActive    = false;
var _rcTypingStopTimer = null;
var _RC_TYPING_STOP_MS = 2500;

function _rcBroadcastTyping(isTyping) {
    if (!rcActiveId) return;
    if (isTyping === _rcTypingActive) return;
    _rcTypingActive = isTyping;
    fetch(rcRoute + "/" + rcActiveId + "/typing", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ typing: isTyping }),
    }).catch(function () { /* silent */ });
}

function _rcOnAdminTyping() {
    _rcBroadcastTyping(true);
    clearTimeout(_rcTypingStopTimer);
    _rcTypingStopTimer = setTimeout(function () {
        _rcBroadcastTyping(false);
    }, _RC_TYPING_STOP_MS);
}

function _rcStopTypingBroadcast() {
    clearTimeout(_rcTypingStopTimer);
    _rcBroadcastTyping(false);
}

document.getElementById("rcReplyInput").addEventListener("input", function () {
    if (this.value.trim()) {
        _rcOnAdminTyping();
    } else {
        _rcStopTypingBroadcast();
    }
});

// ── Send reply ────────────────────────────────────────────
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
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({ message: text }),
    })
        .then((r) => r.json())
        .then((data) => {
            if (data.success) {
                _rcStopTypingBroadcast();
                input.value = "";
                rcAppendMessage(data.message);

                // Update last-message preview in the list item
                const listItem = document.getElementById("rcItem" + rcActiveId);
                if (listItem) {
                    const preview = listItem.querySelector(
                        '.text-truncate[style*="rgba(255,255,255,0.4)"]',
                    );
                    if (preview) {
                        preview.innerHTML = `<span style="color:var(--adm-muted);font-size:0.7rem;">Admin:</span> ${escHtml(text.length > 48 ? text.slice(0, 48) + "…" : text)}`;
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

    // Remove placeholder and typing indicator if present
    const placeholder = body.querySelector(".chat-placeholder");
    if (placeholder) placeholder.remove();
    const typingEl = document.getElementById("rcTypingIndicator");
    if (typingEl) typingEl.remove();

    const isAdmin  = m.sender_type === "admin";
    const isDriver = m.sender_type === "driver";
    const bubbleCls = isAdmin ? "bubble-admin" : isDriver ? "bubble-driver" : "bubble-user";
    const align     = isAdmin ? "align-items:flex-end;" : "align-items:flex-start;";
    const icon      = isAdmin
        ? '<i class="fa fa-user-shield" style="font-size:0.7rem;margin-right:3px;"></i>'
        : isDriver
            ? '<i class="fa fa-id-card" style="font-size:0.7rem;margin-right:3px;"></i>'
            : '<i class="fa fa-user" style="font-size:0.7rem;margin-right:3px;"></i>';
    const metaAlign = isAdmin ? "text-align:right;" : "";

    const div = document.createElement("div");
    div.style.cssText = `display:flex;flex-direction:column;${align}`;
    div.innerHTML = `
                <div class="chat-bubble ${bubbleCls}">${escHtml(m.message)}</div>
                <div class="bubble-meta" style="${metaAlign}">
                    ${icon}${escHtml(m.sender_name)} &middot; ${escHtml(m.time)}
                </div>`;
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
}

function rcDecrementNavBadge(id) {
    // Recalculate from remaining unread items in list
    let total = 0;
    document.querySelectorAll("#rcList .msg-item").forEach((el) => {
        const badge = el.querySelector(".msg-badge-unread");
        if (badge) {
            const n = parseInt(badge.textContent) || 0;
            if (parseInt(el.dataset.id) !== id) total += n;
        }
    });
    const navBadge = document.getElementById("rideChatNavBadge");
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
