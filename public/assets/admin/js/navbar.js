(function () {
    var badge = document.getElementById("contactMsgNavBadge");
    if (!badge) return;

    function getCount() {
        return parseInt(badge.textContent, 10) || 0;
    }

    function setCount(n) {
        n = Math.max(0, n);
        badge.textContent = n > 99 ? "99+" : String(n);
        badge.style.display = n > 0 ? "inline-flex" : "none";
    }

    window.contactAdminChannel.bind("contact.submitted", function () {
        setCount(getCount() + 1);
    });

    window.contactAdminChannel.bind("resolved", function (data) {
        if (data) {
            setCount(getCount() - 1);
        }
    });
})();

/* Keep the Emergency Requests navbar badge synced in real time across every open Admin tab -- reuses window.channel, the already-subscribed "admin-dashboard" public channel. */
(function () {
    var badge = document.getElementById("emergencyReqBadge");
    if (!badge || !window.channel) return;

    window._rrEmergencyBadgeSeenCreated =
        window._rrEmergencyBadgeSeenCreated || new Set();
    window._rrEmergencyBadgeSeenDeleted =
        window._rrEmergencyBadgeSeenDeleted || new Set();

    function getCount() {
        var raw = badge.textContent.replace("+", "");
        return parseInt(raw, 10) || 0;
    }

    function setCount(n) {
        n = Math.max(0, n);
        badge.textContent = n > 99 ? "99+" : String(n);
        badge.style.display = n > 0 ? "inline-flex" : "none";
    }

    window.channel.bind("emergency-request-created", function (data) {
        var id = data && data.id;
        if (id === undefined || id === null) return;
        if (window._rrEmergencyBadgeSeenCreated.has(id)) return;
        window._rrEmergencyBadgeSeenCreated.add(id);
        setCount(getCount() + 1);
    });

    window.channel.bind("emergency-request-deleted", function (data) {
        var id = data && data.id;
        if (id === undefined || id === null) return;
        if (window._rrEmergencyBadgeSeenDeleted.has(id)) return;
        window._rrEmergencyBadgeSeenDeleted.add(id);
        setCount(getCount() - 1);
    });
    
    /* Decrement badge when a driver completes or cancels a ride — those requests
       leave the active queue and should no longer count against the badge total.
       Uses its own dedup Set to avoid double-counting with the deleted event. */
    window._rrEmergencyBadgeSeenCompleted =
        window._rrEmergencyBadgeSeenCompleted || new Set();

    window.channel.bind("emergency-request-status-changed", function (data) {
        var action = data && String(data.action || '');
        if (action !== 'complete' && action !== 'cancel') return;
        var id = data && data.id;
        if (id === undefined || id === null) return;
        if (window._rrEmergencyBadgeSeenCompleted.has(id)) return;
        window._rrEmergencyBadgeSeenCompleted.add(id);
        setCount(getCount() - 1);
    });
    
    window.updateRideChatNavBadge = function (count) {
        var badge = document.getElementById("rideChatNavBadge");
        if (!badge) return;
        var n = Math.max(0, Number(count) || 0);
        badge.textContent = n > 99 ? "99+" : n;
        badge.style.display = n > 0 ? "inline-flex" : "none";
    };
})();
