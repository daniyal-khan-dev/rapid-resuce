(function () {
    var fab    = document.getElementById("rrChatFab");
    var box    = document.getElementById("rrChatBox");
    var close  = document.getElementById("rrChatClose");
    var msgs   = document.getElementById("rrChatMessages");
    var input  = document.getElementById("rrChatInput");
    var sendBtn = document.getElementById("rrChatSend");

    // ── FAB badge helpers ─────────────────────────────────────────────
    var badge = document.getElementById("rrChatFabBadge");

    function _fabBadgeCount() {
        var n = badge ? parseInt(badge.textContent, 10) : 0;
        return isNaN(n) ? 0 : n;
    }

    function updateFabBadge(n) {
        if (!badge) return;
        n = Math.max(0, Number(n) || 0);
        badge.textContent   = n > 99 ? "99+" : n;
        badge.style.display = n > 0 ? "inline-flex" : "none";
    }

    // Hide badge immediately if initial count is 0
    updateFabBadge(_fabBadgeCount());

    // ── Drain pending messages (arrived while chat was closed) ────────
    function _drainPending() {
        if (!msgs || _pendingMessages.length === 0) return;
        var drained = false;
        _pendingMessages.forEach(function (m) {
            if (m.id && _seenIds.has(m.id)) return;
            if (m.id) _seenIds.add(m.id);
            var placeholder = msgs.querySelector(".rr-chat-empty");
            if (placeholder) placeholder.remove();
            msgs.insertAdjacentHTML("beforeend", renderMessage(m));
            drained = true;
        });
        _pendingMessages = [];
        if (drained) scrollBottom();
    }

    // ── Open / close ─────────────────────────────────────────────────
    function openChat() {
        box.classList.add("rr-chatbox--open");
        fab.setAttribute("aria-expanded", "true");
        if (input) input.focus();

        // Render any messages that arrived while the chat was closed
        if (chatLoaded) {
            _drainPending();
        }

        if (msgs) msgs.scrollTop = msgs.scrollHeight;

        // Mark messages as read on the server every time the chat opens,
        // then clear the badge regardless of whether messages were loaded yet.
        if (window.RR_CHAT_MARK_READ_URL) {
            fetch(window.RR_CHAT_MARK_READ_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": window.RR_CSRF_TOKEN || window.RR_CSRF || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
            }).then(function () {
                updateFabBadge(0);
            }).catch(function () { /* silent */ });
        }
    }

    function closeChat() {
        box.classList.remove("rr-chatbox--open");
        fab.setAttribute("aria-expanded", "false");
        _stopTypingBroadcast();
    }

    function toggleChat() {
        if (box.classList.contains("rr-chatbox--open")) {
            closeChat();
        } else {
            openChat();
        }
    }

    if (fab) fab.addEventListener("click", toggleChat);
    if (close) close.addEventListener("click", closeChat);

    // Auto-grow textarea
    if (input) {
        input.addEventListener("input", function () {
            this.style.height = "auto";
            this.style.height = Math.min(this.scrollHeight, 100) + "px";
        });
    }

    // Close on Escape
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && box && box.classList.contains("rr-chatbox--open")) {
            closeChat();
        }
    });

    if (!window.RR_CHAT_MESSAGES_URL) return; // guest — chat disabled

    var chatLoaded = false;

    // ── Seen-ID set (dedup for reconnect replays) ─────────────────────
    var _seenIds = new Set();

    // ── Queue for messages that arrive while the chat box is closed ───
    var _pendingMessages = [];

    // ── Helpers ───────────────────────────────────────────────────────
    function scrollBottom() {
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderMessage(m) {
        var isUser  = m.sender_type === "user";
        var isAdmin = m.sender_type === "admin";

        if (isUser) {
            return (
                '<div class="rr-chatbox-msg rr-chatbox-msg--right">' +
                    '<div class="rr-chatbox-msg-body">' +
                        '<div class="rr-chatbox-msg-bubble">' + escHtml(m.message) + "</div>" +
                        '<div class="rr-chatbox-msg-time">' + escHtml(m.time) + "</div>" +
                    "</div>" +
                "</div>"
            );
        }

        var avatarClass = isAdmin ? "rr-chatbox-msg-avatar--admin" : "rr-chatbox-msg-avatar--driver";
        var icon        = isAdmin ? "fa-user-shield" : "fa-truck-medical";

        return (
            '<div class="rr-chatbox-msg rr-chatbox-msg--left">' +
                '<div class="rr-chatbox-msg-avatar ' + avatarClass + '">' +
                    '<i class="fa ' + icon + '"></i>' +
                "</div>" +
                '<div class="rr-chatbox-msg-body">' +
                    '<div class="rr-chatbox-msg-sender">' + escHtml(m.sender_name) + "</div>" +
                    '<div class="rr-chatbox-msg-bubble">' + escHtml(m.message) + "</div>" +
                    '<div class="rr-chatbox-msg-time">' + escHtml(m.time) + "</div>" +
                "</div>" +
            "</div>"
        );
    }

    // ── Load messages ─────────────────────────────────────────────────
    function loadMessages() {
        fetch(window.RR_CHAT_MESSAGES_URL, {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                var html = "";
                if (data.messages.length === 0) {
                    html = '<div class="rr-chat-empty" style="text-align:center;padding:20px 0;font-size:.78rem;color:#94a3b8;">No messages yet. Say hello!</div>';
                } else {
                    data.messages.forEach(function (m) {
                        html += renderMessage(m);
                        if (m.id) _seenIds.add(m.id);
                    });
                }
                msgs.innerHTML = html;
                chatLoaded = true;

                // Render messages that arrived via Reverb while loading
                _drainPending();
                scrollBottom();

                // Messages have been marked as read server-side (messages() does this).
                updateFabBadge(0);
            })
            .catch(function () {
                msgs.innerHTML = '<div style="text-align:center;padding:20px 0;font-size:.78rem;color:#94a3b8;">Could not load messages.</div>';
            });
    }

    // Load when chat opens for the first time
    if (fab) {
        fab.addEventListener("click", function () {
            if (!chatLoaded && box && box.classList.contains("rr-chatbox--open")) {
                loadMessages();
            }
        });
    }

    // Also load immediately if already open on page load
    if (box && box.classList.contains("rr-chatbox--open")) {
        loadMessages();
    }

    // ── Typing indicator broadcast ────────────────────────────────────
    var _typingActive    = false;
    var _typingStopTimer = null;
    var _TYPING_STOP_MS  = 2500;

    function _broadcastTyping(isTyping) {
        if (!window.RR_CHAT_TYPING_URL) return;
        // Avoid redundant broadcasts (started already / stopped already)
        if (isTyping === _typingActive) return;
        _typingActive = isTyping;
        fetch(window.RR_CHAT_TYPING_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": window.RR_CSRF_TOKEN || window.RR_CSRF || "",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ typing: isTyping }),
        }).catch(function () { /* silent */ });
    }

    function _onUserTyping() {
        // Throttle: only fire "started" once; restart the stop-timer on each key
        _broadcastTyping(true);
        clearTimeout(_typingStopTimer);
        _typingStopTimer = setTimeout(function () {
            _broadcastTyping(false);
        }, _TYPING_STOP_MS);
    }

    function _stopTypingBroadcast() {
        clearTimeout(_typingStopTimer);
        _broadcastTyping(false);
    }

    if (input) {
        input.addEventListener("input", function () {
            if (input.value.trim()) {
                _onUserTyping();
            } else {
                // Input cleared — stop typing
                _stopTypingBroadcast();
            }
        });
    }

    // ── Send message ──────────────────────────────────────────────────
    function sendMessage() {
        var text = input ? input.value.trim() : "";
        if (!text) return;

        sendBtn.disabled = true;

        fetch(window.RR_CHAT_SEND_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": window.RR_CSRF_TOKEN || window.RR_CSRF,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ message: text }),
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    _stopTypingBroadcast();

                    var placeholder = msgs.querySelector(".rr-chat-empty");
                    if (placeholder) placeholder.remove();

                    if (data.message && data.message.id) _seenIds.add(data.message.id);
                    msgs.insertAdjacentHTML("beforeend", renderMessage(data.message));
                    input.value = "";
                    input.style.height = "auto";
                    scrollBottom();

                    if (!chatLoaded) chatLoaded = true;
                }
            })
            .catch(function () { /* silent — keep input text */ })
            .finally(function () {
                sendBtn.disabled = false;
                if (input) input.focus();
            });
    }

    if (sendBtn) sendBtn.addEventListener("click", sendMessage);

    if (input) {
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // ── Real-time: bind to user personal channel ──────────────────────
    // ── Typing indicator (shown when admin is typing) ─────────────────
    var _userTypingTimer = null;

    function _showAdminTypingIndicator(senderName) {
        if (!msgs) return;
        var isOpen = box && box.classList.contains("rr-chatbox--open");
        if (!isOpen) return;
        var el = document.getElementById("rrAdminTypingIndicator");
        if (!el) {
            el = document.createElement("div");
            el.id = "rrAdminTypingIndicator";
            el.style.cssText = "padding:4px 0 2px;font-size:0.76rem;color:#94a3b8;font-style:italic;";
            el.innerHTML =
                '<span style="display:inline-flex;gap:3px;align-items:center;">' +
                    '<span class="rr-typing-dot"></span>' +
                    '<span class="rr-typing-dot"></span>' +
                    '<span class="rr-typing-dot"></span>' +
                "</span> " +
                escHtml(senderName) + " is typing\u2026";
            msgs.appendChild(el);
        }
        msgs.scrollTop = msgs.scrollHeight;
    }

    function _hideAdminTypingIndicator() {
        var el = document.getElementById("rrAdminTypingIndicator");
        if (el) el.remove();
    }

    function bindRideChatChannel(ch) {
        ch.bind("ride-chat-message", function (data) {
            if (!data || !data.message) return;

            // Only handle events for this specific request
            if (String(data.emergency_request_id) !== String(window.REQ_ID)) return;

            var m = data.message;

            // Deduplicate — guard against reconnect replays
            if (m.id && _seenIds.has(m.id)) return;
            if (m.id) _seenIds.add(m.id);

            var isOpen = box && box.classList.contains("rr-chatbox--open");

            if (isOpen && chatLoaded) {
                // Chat is open — stream the message in and mark as read immediately
                var placeholder = msgs ? msgs.querySelector(".rr-chat-empty") : null;
                if (placeholder) placeholder.remove();

                // Remove typing indicator before appending the real message
                _hideAdminTypingIndicator();

                msgs.insertAdjacentHTML("beforeend", renderMessage(m));
                scrollBottom();

                // Tell the server this message is now read
                if (window.RR_CHAT_MARK_READ_URL) {
                    fetch(window.RR_CHAT_MARK_READ_URL, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Accept: "application/json",
                            "X-CSRF-TOKEN": window.RR_CSRF_TOKEN || window.RR_CSRF || "",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    }).catch(function () { /* silent */ });
                }
            } else {
                // Chat is closed (or not yet loaded) — queue the message so it
                // can be rendered when the user reopens the chat box.
                _pendingMessages.push(m);

                // Update the FAB badge with the authoritative count
                var unread = typeof data.request_unread_count !== "undefined"
                    ? Number(data.request_unread_count)
                    : _fabBadgeCount() + 1;
                updateFabBadge(unread);
            }
        });

        ch.bind("ride-chat-typing", function (data) {
            if (!data) return;
            // Only handle events for this specific request
            if (String(data.emergency_request_id) !== String(window.REQ_ID)) return;

            clearTimeout(_userTypingTimer);

            if (data.typing) {
                _showAdminTypingIndicator(data.sender_name);
                // Fallback auto-hide in case the stop event is missed
                _userTypingTimer = setTimeout(function () {
                    _hideAdminTypingIndicator();
                }, 3500);
            } else {
                _hideAdminTypingIndicator();
            }
        });
    }

    // Channel may already exist (layout initialises it before this script runs)
    if (window.userRideChatChannel) {
        bindRideChatChannel(window.userRideChatChannel);
    } else {
        // Wait briefly for the layout Pusher init to complete
        var _tries = 0;
        var _waitId = setInterval(function () {
            _tries++;
            if (window.userRideChatChannel || _tries > 50) {
                clearInterval(_waitId);
                if (window.userRideChatChannel) {
                    bindRideChatChannel(window.userRideChatChannel);
                }
            }
        }, 100);
    }
})();
