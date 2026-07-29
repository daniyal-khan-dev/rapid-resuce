/* ── Ride Chat real-time handler (admin side) ─────────────────────────────
 *
 * Listens on the already-subscribed public "admin-dashboard" channel
 * (set up in admin.blade.php) for "ride-chat-message" events fired
 * whenever a user or driver sends a chat message.
 *
 * The nav-badge update is handled globally in admin.blade.php via
 * window.updateRideChatNavBadge / window.handleRideChatMessage so that
 * it works on every admin page. This file handles the ride-chat LIST
 * and CHAT-PANEL updates that are only relevant on the ride_chat page.
 *
 * Architecture mirrors emergency-realtime.js exactly.
 * ─────────────────────────────────────────────────────────────────────── */

(function () {
    'use strict';

    /* Only activate on the ride-chat page */
    if (!document.getElementById('rcList')) return;

    /* Guard: bind exactly once even if script is loaded twice */
    if (window._rrRideChatBound) return;
    if (!window.pusher || !window.channel) return;
    window._rrRideChatBound = true;

    /* ── Status helpers (mirrors blade $statusLabels) ──────────────────── */
    var _statusMap = {
        '1': { label: 'Pending',     chat: 'locked' },
        '2': { label: 'Accepted',    chat: 'active' },
        '3': { label: 'En Route',    chat: 'active' },
        '4': { label: 'Arrived',     chat: 'active' },
        '5': { label: 'In Progress', chat: 'active' },
        '6': { label: 'Completed',   chat: 'closed' },
        '7': { label: 'Cancelled',   chat: 'closed' },
        '8': { label: 'Awaiting Acceptance',   chat: 'closed' },
    };

    function _chatState(status) {
        return (_statusMap[String(status)] || { chat: 'locked' }).chat;
    }

    function _statusLabel(status) {
        return (_statusMap[String(status)] || { label: 'Unknown' }).label;
    }

    function _shortDate() {
        var now = new Date();
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return now.getDate() + ' ' + months[now.getMonth()];
    }

    function _ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    /* ── Build and prepend a brand-new conversation list item ─────────── */
    function _insertNewConversation(data) {
        var list  = document.getElementById('rcList');
        var empty = document.getElementById('rcEmptyState');
        if (!list) return;

        /* Show list, hide empty state */
        if (empty) empty.style.display = 'none';
        list.style.display = '';

        var msg        = data.message;
        var chatState  = _chatState(data.status);
        var statusLbl  = _statusLabel(data.status);
        var unread     = Number(data.request_unread_count) || 1;
        var searchStr  = escHtml(
            ((data.rreb_id || '') + ' ' + (data.user_name || '') + ' ' + (data.driver_name || '')).toLowerCase()
        );
        var preview    = escHtml(
            msg.message.length > 48 ? msg.message.slice(0, 48) + '\u2026' : msg.message
        );
        var driverLine = data.driver_name
            ? '&nbsp;&middot;&nbsp;<i class="fa fa-id-card" style="margin-right:3px;font-size:0.68rem;"></i>' + escHtml(data.driver_name)
            : '';

        var html =
            '<div class="msg-item unread" id="rcItem' + data.emergency_request_id + '"' +
                ' data-id="' + data.emergency_request_id + '"' +
                ' data-search="' + searchStr + '"' +
                ' onclick="rcSelectConversation(' + data.emergency_request_id + ', this)">' +

                '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<div style="min-width:0;">' +
                        '<div class="fw-bold text-white" style="font-size:0.82rem;">' +
                            escHtml(data.rreb_id) +
                            '<span class="msg-badge-unread">' + (unread > 99 ? '99+' : unread) + '</span>' +
                        '</div>' +
                        '<div class="text-truncate" style="font-size:0.76rem;color:var(--adm-muted);">' +
                            '<i class="fa fa-user" style="margin-right:3px;font-size:0.68rem;"></i>' +
                            escHtml(data.user_name || '\u2014') +
                            driverLine +
                        '</div>' +
                    '</div>' +
                    '<div style="flex-shrink:0;text-align:right;">' +
                        '<span class="ride-status-badge status-' + chatState + '">' + statusLbl + '</span>' +
                        '<div style="font-size:0.68rem;color:var(--adm-muted);margin-top:3px;">' + _shortDate() + '</div>' +
                    '</div>' +
                '</div>' +

                '<div class="mt-1 text-truncate" style="font-size:0.78rem;color:rgba(255,255,255,0.4);">' +
                    '<span style="color:var(--adm-muted);font-size:0.7rem;">' + escHtml(_ucfirst(msg.sender_type)) + ':</span> ' +
                    preview +
                '</div>' +
            '</div>';

        list.insertAdjacentHTML('afterbegin', html);
    }

    /* ── Update preview text and unread badge on an existing list item ── */
    function _updateConversationPreview(el, data) {
        var msg    = data.message;
        var reqId  = data.emergency_request_id;
        var unread = Number(data.request_unread_count) || 0;
        var isOpen = (typeof rcActiveId !== 'undefined' && rcActiveId === reqId);

        /* Update last-message preview */
        var preview = el.querySelector('.text-truncate[style*="rgba(255,255,255,0.4)"]');
        if (preview) {
            var text = msg.message.length > 48 ? msg.message.slice(0, 48) + '\u2026' : msg.message;
            preview.innerHTML =
                '<span style="color:var(--adm-muted);font-size:0.7rem;">' +
                escHtml(_ucfirst(msg.sender_type)) + ':</span> ' + escHtml(text);
        }

        /* Only mark as unread when the admin is NOT actively viewing it */
        if (!isOpen && unread > 0) {
            el.classList.add('unread');
            var badge = el.querySelector('.msg-badge-unread');
            if (badge) {
                badge.textContent = unread > 99 ? '99+' : unread;
            } else {
                var titleDiv = el.querySelector('.fw-bold.text-white');
                if (titleDiv) {
                    var newBadge = document.createElement('span');
                    newBadge.className = 'msg-badge-unread';
                    newBadge.textContent = unread > 99 ? '99+' : unread;
                    titleDiv.appendChild(newBadge);
                }
            }
        }

        /* Bubble conversation to top of list */
        var list = document.getElementById('rcList');
        if (list && el.parentNode === list) {
            list.insertBefore(el, list.firstChild);
        }
    }

    /* ── Typing indicator ───────────────────────────────────────────────── */
    var _typingTimers = {};

    function _showTypingIndicator(senderName) {
        var body = document.getElementById('rcChatBody');
        if (!body) return;
        var el = document.getElementById('rcTypingIndicator');
        if (!el) {
            el = document.createElement('div');
            el.id = 'rcTypingIndicator';
            el.style.cssText = 'padding:2px 0 6px;font-size:0.78rem;color:var(--adm-muted);font-style:italic;';
            el.innerHTML =
                '<span style="display:inline-flex;gap:3px;align-items:center;">' +
                    '<span class="rr-typing-dot"></span>' +
                    '<span class="rr-typing-dot"></span>' +
                    '<span class="rr-typing-dot"></span>' +
                '</span> ' +
                escHtml(senderName) + ' is typing\u2026';
            body.appendChild(el);
        }
        body.scrollTop = body.scrollHeight;
    }

    function _hideTypingIndicator() {
        var el = document.getElementById('rcTypingIndicator');
        if (el) el.remove();
    }

    /* ── Public typing handler — called by admin.blade.php channel binding ─ */
    window.handleRideChatTyping = function (data) {
        var reqId = data.emergency_request_id;

        clearTimeout(_typingTimers[reqId]);

        if (data.typing) {
            /* Only show the indicator when this conversation is active */
            if (typeof rcActiveId !== 'undefined' && rcActiveId === reqId) {
                _showTypingIndicator(data.sender_name);
            }
            /* Always set fallback auto-hide in case "stop" event is missed */
            _typingTimers[reqId] = setTimeout(function () {
                _hideTypingIndicator();
            }, 3500);
        } else {
            _hideTypingIndicator();
        }
    };

    /* ── Ride-status-changed handler: lock chat when completed/cancelled ── */
    window.channel.bind('emergency-request-status-changed', function (data) {
        var reqId  = data.id;
        var status = String(data.status);

        /* Update the sidebar list item's status badge */
        var listItem = document.getElementById('rcItem' + reqId);
        var listItem_1 = document.getElementById('rcChatHeader');
        if (listItem && listItem_1) {
            var badge = listItem.querySelector('.ride-status-badge');
            if (badge) {
                var info = _statusMap[status] || { label: 'Unknown', chat: 'locked' };
                badge.textContent = info.label;
                badge.className   = 'ride-status-badge status-' + info.chat;
            }
            var badge_1 = listItem_1.querySelector('.ride-status-badge-1');
            if (badge_1) {
                var info = _statusMap[status] || { label: 'Unknown', chat: 'locked' };
                badge_1.textContent = info.label;
                badge_1.className   = 'ride-status-badge-1 status-' + info.chat;
            }
        }

        /* If this is the currently open conversation, update the chat footer */
        if (typeof rcActiveId !== 'undefined' && rcActiveId === reqId) {
            rcActiveStatus = Number(status);
            if (typeof window.rcApplyChatState === 'function') {
                window.rcApplyChatState(Number(status));
            }
        }
    });

    /* ── Main handler — called by admin.blade.php channel binding ──────── */
    window.handleRideChatMessage = function (data) {
        var reqId       = data.emergency_request_id;
        var existingEl  = document.getElementById('rcItem' + reqId);

        if (!existingEl) {
            /* New conversation not yet in the list */
            _insertNewConversation(data);
        } else {
            /* Existing conversation: refresh preview + badge */
            _updateConversationPreview(existingEl, data);
        }

        /* If this conversation is currently open in the right pane, append
           the incoming message to the chat body immediately */
        if (typeof rcActiveId !== 'undefined' && rcActiveId === reqId) {
            if (typeof rcAppendMessage === 'function') {
                rcAppendMessage(data.message);
            }
        }
    };

}());
