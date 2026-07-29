(function () {
    'use strict';

    /* ── Cancel Ride ─────────────────────────────────────────────────────── */
    window.rrCancelRide = function () {
        var btn   = document.getElementById('rrCancelRideBtn');
        var msgEl = document.getElementById('rrCancelMsg');
        if (btn) { btn.disabled = true; btn.textContent = 'Cancelling…'; }
        if (msgEl) msgEl.style.display = 'none';

        fetch(window.RR_CANCEL_URL, {
            method:  'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/json',
                'X-CSRF-TOKEN':  window.RR_CSRF_TOKEN,
            },
            body: JSON.stringify({}),
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            if (res.ok && res.data.success) {
                /* Success — hide button; real-time event will update the rest */
                var wrap = document.getElementById('rrCancelRideWrap');
                if (wrap) wrap.style.display = 'none';
                if (msgEl) {
                    msgEl.textContent = res.data.message || 'Ride cancelled.';
                    msgEl.style.cssText = 'display:block;background:rgba(34,197,94,.08);color:#166534;' +
                        'border:1px solid rgba(34,197,94,.2);margin-top:8px;font-size:.82rem;' +
                        'padding:8px 12px;border-radius:8px;';
                }
            } else {
                var msg = (res.data && res.data.message) ? res.data.message : 'Could not cancel ride.';
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-ban"></i> Cancel Ride'; }
                if (msgEl) {
                    msgEl.textContent = msg;
                    msgEl.style.cssText = 'display:block;background:rgba(215,44,66,.08);color:var(--rr-primary);' +
                        'border:1px solid rgba(215,44,66,.2);margin-top:8px;font-size:.82rem;' +
                        'padding:8px 12px;border-radius:8px;';
                }
            }
        })
        .catch(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-ban"></i> Cancel Ride'; }
            if (msgEl) {
                msgEl.textContent = 'Server error. Please try again.';
                msgEl.style.cssText = 'display:block;background:rgba(215,44,66,.08);color:var(--rr-primary);' +
                    'border:1px solid rgba(215,44,66,.2);margin-top:8px;font-size:.82rem;' +
                    'padding:8px 12px;border-radius:8px;';
            }
        });
    };

    var STATUS_LABELS = {
        '1': '⏳ Awaiting Dispatch',
        '2': '🚑 Ambulance Assigned',
        '8': '🚑 Ambulance Assigned',
        '3': '🚨 En Route to You',
        '4': '📍 Arrived at Scene',
        '5': '🏥 Transporting',
        '6': '✅ Trip Completed',
        '7': '❌ Cancelled'
    };

    var STATUS_SUBTEXT = {
        '1': 'Your request has been received and is being reviewed.',
        '2': 'An ambulance has been assigned and will depart shortly.',
        '3': 'The ambulance is on its way to your location.',
        '4': 'The paramedic team has arrived at your pickup point.',
        '5': 'You are being transported to the hospital.',
        '6': 'Your trip has been completed. Thank you.',
        '7': 'Your trip has been cancelled. Thank you.'
    };

    // Maps status code → zero-based timeline step index (matches Blade $statusToIdx)
    var STATUS_TO_IDX = { '1': 0, '2': 1, '8': 1, '3': 2, '4': 3, '5': 4, '6': 5 };

    function updateTracking(data) {
        var status = String(data.status);

        // 1. Status heading
        var h3 = document.querySelector('.rr-tracking-status-big h3');
        if (h3) h3.textContent = STATUS_LABELS[status] || status;

        // 2. Status subtext
        var subtext = document.getElementById('statusSubtext');
        if (subtext) subtext.textContent = STATUS_SUBTEXT[status] || 'Status updated.';

        // 3. Timeline — rebuild active/done/plain state without touching HTML structure
        var steps  = document.querySelectorAll('.rr-tl-step');
        var curIdx = (STATUS_TO_IDX[status] !== undefined) ? STATUS_TO_IDX[status] : -1;
        steps.forEach(function (step, i) {
            step.classList.remove('active', 'done');
            var dot = step.querySelector('.rr-tl-dot');
            if (!dot) return;
            if (i < curIdx) {
                step.classList.add('done');
                dot.innerHTML = '<i class="fa fa-check"></i>';
            } else if (i === curIdx) {
                step.classList.add('active');
                dot.innerHTML = '<i class="fa fa-circle-dot"></i>';
            } else {
                dot.innerHTML = '<i class="fa fa-circle"></i>';
            }
        });

        // 4. Pending notice (shown only while status === '1')
        var notice = document.getElementById('rrPendingNotice');
        if (notice) notice.style.display = (status === '1') ? '' : 'none';

        // 5. Feedback CTA (shown only when completed)
        var cta = document.getElementById('rrFeedbackCta');
        if (cta) cta.style.display = (status === '6') ? '' : 'none';

        // 6. Cancel Ride button (shown only while Pending)
        var cancelWrap = document.getElementById('rrCancelRideWrap');
        if (cancelWrap) cancelWrap.style.display = (status === '1') ? '' : 'none';
        
        // 7. Driver / Paramedic row — reveal and populate when data arrives
        if (data.driver_name) {
            var driverRow   = document.getElementById('rrDriverRow');
            var driverName  = document.getElementById('rrDriverName');
            var driverPhone = document.getElementById('rrDriverPhone');
            if (driverName)  driverName.textContent  = data.driver_name;
            if (driverPhone) driverPhone.textContent = data.driver_phone || '';
            if (driverRow)   driverRow.style.display  = '';
        }

        // 8. Ambulance row — reveal and populate when data arrives
        if (data.ambulance_vehicle_number) {
            var ambulanceRow     = document.getElementById('rrAmbulanceRow');
            var ambulanceVehicle = document.getElementById('rrAmbulanceVehicle');
            var ambulanceType    = document.getElementById('rrAmbulanceType');
            if (ambulanceVehicle) ambulanceVehicle.textContent = data.ambulance_vehicle_number;
            if (ambulanceType)    ambulanceType.textContent    = data.ambulance_type || '';
            if (ambulanceRow)     ambulanceRow.style.display   = '';
        }

        // 9. Dispatched At row — reveal and populate when data arrives
        if (data.dispatched_at) {
            var dispatchedRow  = document.getElementById('rrDispatchedAtRow');
            var dispatchedTime = document.getElementById('rrDispatchedAt');
            if (dispatchedTime && data.dispatched_at) {
                const date = new Date(data.dispatched_at);
            
                dispatchedTime.textContent = date.toLocaleString('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }).replace(',', '');
            }
            if (dispatchedRow)  dispatchedRow.style.display = '';
        }
        
        // 10. Last-update timestamp
        var ts = document.getElementById('lastTrackUpdate');
        if (ts) ts.textContent = 'just now';

        // 11. Enable/Disable Chat FAB (enabled only for status 2-7)
        var chatFab = document.getElementById('rrChatFab');
        if (chatFab) {
            if (parseInt(status) > 1 && parseInt(status) < 8) {
                chatFab.disabled = false;
                chatFab.classList.remove('disabled-button');
            } else {
                chatFab.disabled = true;
                chatFab.classList.add('disabled-button');
            }
        }

        // 12. Chat Input send button (Display/Hide only for status 2-7)
        var rrChatInputWrap = document.getElementById('rrChatInputWrap');
        if (rrChatInputWrap) {
            if (parseInt(status) > 1 && parseInt(status) < 6) {
                rrChatInputWrap.style.display = 'flex';
            } else {
                rrChatInputWrap.style.display = 'none ';
            }
        }

        // 13. Chat Input send button (Display/Hide and Display message on cancel and complete)
        var rrChatStatusMsg = document.getElementById('rrChatStatusMsg');
        if (rrChatStatusMsg) {
            if (parseInt(status) === 6 || parseInt(status) === 7) {
                rrChatStatusMsg.style.display = 'block';
                if(parseInt(status) == 6) {
                    rrChatStatusMsg.innerHTML = '<i class="fa fa-circle-check" style="color:#22c55e;margin-right:6px;background:rgba(34,197,94,.07);color:#166534;border-top:1px solid rgba(34,197,94,.18);"></i>' + '<strong>This ride has been completed.</strong> Chat is now closed and no further messages can be sent.';
                }
                if(parseInt(status) == 7) {
                    rrChatStatusMsg.innerHTML = '<i class="fa fa-circle-xmark" style="color:#ef4444;margin-right:6px;background:rgba(239,68,68,.07);color:#7f1d1d;border-top:1px solid rgba(239,68,68,.18);"></i>' + '<strong>This ride has been cancelled.</strong> Chat is no longer available for new messages. ' + 'If you need further assistance, please contact support.';
                }
            } else {
                rrChatStatusMsg.style.display = 'none ';
            }
        }
    }

    // Defer until window.pusher is initialised by the layout
    function bindChannel() {
        if (!window.pusher) { setTimeout(bindChannel, 200); return; }

        var ch = window.pusher.subscribe('emergency.' + REQ_ID);

        ch.bind('emergency-request-status-changed', function (data) {
            updateTracking(data);
        });

        /* If the admin deletes this ride, redirect the user away immediately */
        ch.bind('emergency-request-deleted', function (data) {
            if (String(data.id) === String(REQ_ID)) {
                window.location.href = '/my-bookings';
            }
        });
    }

    bindChannel();
})();

(function () {
    "use strict";

    var selectedRating = 0;
    var RR_RATE_URL = window.RR_RATE_URL;
    var RR_CSRF = window.RR_CSRF;

    /* ── Open modal (Bootstrap 5) ── */
    window.rrOpenRatingModal = function () {
        rrResetModal();
        var el = document.getElementById("rrRatingModal");
        if (el && window.bootstrap) {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    };

    /* ── Star hover + click ── */
    function initStars() {
        var container = document.getElementById("rrStars");
        if (!container) return;

        var stars = container.querySelectorAll(".rr-star");

        function paint(upTo) {
            stars.forEach(function (s, idx) {
                s.style.color = idx < upTo ? "#f59e0b" : "#d1d5db";
                s.style.transform = idx < upTo ? "scale(1.15)" : "scale(1)";
            });
        }

        stars.forEach(function (star) {
            star.addEventListener("mouseenter", function () {
                paint(parseInt(star.getAttribute("data-value"), 10));
            });
            star.addEventListener("mouseleave", function () {
                paint(selectedRating);
            });
            star.addEventListener("click", function () {
                selectedRating = parseInt(star.getAttribute("data-value"), 10);
                paint(selectedRating);
                document.getElementById("rrRatingError").style.display = "none";
            });
        });
    }

    /* ── Reset modal to initial state ── */
    function rrResetModal() {
        selectedRating = 0;
        var stars = document.querySelectorAll(".rr-star");
        stars.forEach(function (s) {
            s.style.color = "#d1d5db";
            s.style.transform = "scale(1)";
        });
        var msg = document.getElementById("rrRatingMessage");
        if (msg) msg.value = "";
        document.getElementById("rrRatingError").style.display = "none";
        document.getElementById("rrRatingMsg").style.display = "none";
        var btn = document.getElementById("rrSubmitRatingBtn");
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
        }
    }

    /* ── Submit ── */
    window.rrSubmitRating = function () {
        if (selectedRating === 0) {
            document.getElementById("rrRatingError").style.display = "block";
            return;
        }

        var btn = document.getElementById("rrSubmitRatingBtn");
        var msg = document.getElementById("rrRatingMessage");

        if (btn) {
            btn.disabled = true;
            btn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Submitting…';
        }

        fetch(RR_RATE_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": RR_CSRF,
            },
            body: JSON.stringify({
                rating: selectedRating,
                message: msg ? msg.value.trim() : "",
            }),
        })
            .then(function (r) {
                return r.json().then(function (d) {
                    return { ok: r.ok, data: d };
                });
            })
            .then(function (res) {
                var msgBox = document.getElementById("rrRatingMsg");
                if (res.ok && res.data.success) {
                    /* Success: show message, close modal after 1.4 s, swap button */
                    if (msgBox) {
                        msgBox.textContent =
                            res.data.message || "Thank you for your feedback!";
                        msgBox.style.cssText =
                            "display:block;background:rgba(34,197,94,.08);color:#166534;border:1px solid rgba(34,197,94,.25);margin-top:12px;font-size:.82rem;font-weight:600;padding:10px 14px;border-radius:8px;";
                    }
                    setTimeout(function () {
                        var el = document.getElementById("rrRatingModal");
                        if (el && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(el).hide();
                        }
                        /* Replace button with "Already Rated" notice — no page reload */
                        var rateBtn = document.getElementById("rrRateBtn");
                        if (rateBtn) {
                            var notice = document.createElement("div");
                            notice.style.cssText =
                                "padding:10px 16px;border-radius:10px;background:rgba(34,197,94,0.08);color:#166534;border:1.5px solid rgba(34,197,94,0.25);font-size:.85rem;font-weight:600;text-align:center;";
                            notice.innerHTML =
                                '<i class="fas fa-star" style="color:#f59e0b;"></i> You have already rated this ride.';
                            rateBtn.replaceWith(notice);
                        }
                    }, 1400);
                } else {
                    var errMsg =
                        res.data && res.data.message
                            ? res.data.message
                            : "Submission failed. Please try again.";
                    if (msgBox) {
                        msgBox.textContent = errMsg;
                        msgBox.style.cssText =
                            "display:block;background:rgba(215,44,66,.08);color:var(--rr-primary);border:1px solid rgba(215,44,66,.2);margin-top:12px;font-size:.82rem;font-weight:600;padding:10px 14px;border-radius:8px;";
                    }
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<i class="fas fa-paper-plane"></i> Submit';
                    }
                }
            })
            .catch(function () {
                var msgBox = document.getElementById("rrRatingMsg");
                if (msgBox) {
                    msgBox.textContent = "Server error. Please try again.";
                    msgBox.style.cssText =
                        "display:block;background:rgba(215,44,66,.08);color:var(--rr-primary);border:1px solid rgba(215,44,66,.2);margin-top:12px;font-size:.82rem;font-weight:600;padding:10px 14px;border-radius:8px;";
                }
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit';
                }
            });
    };

    /* Reset modal state when Bootstrap closes it */
    var modalEl = document.getElementById("rrRatingModal");
    if (modalEl) {
        modalEl.addEventListener("hidden.bs.modal", rrResetModal);
    }

    initStars();
})();