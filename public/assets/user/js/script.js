(function () {
    "use strict";

    /* 1. PAGE LOADER */
    function hideLoader() {
        var loader = document.getElementById("rr-loader");
        if (loader) {
            loader.classList.add("rr-loader--hidden");
            setTimeout(function () {
                loader.style.display = "none";
            }, 600);
        }
    }
    if (document.readyState === "complete") {
        hideLoader();
    } else {
        window.addEventListener("load", hideLoader);
    }

    /* 2. BACK TO TOP */
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $(".back-to-top").addClass("rr-visible");
        } else {
            $(".back-to-top").removeClass("rr-visible");
        }
    });
    $(".back-to-top").click(function () {
        $("html, body").animate({ scrollTop: 0 }, 800, "swing");
        return false;
    });

    /* 3. STICKY NAVBAR */
    var nav = document.getElementById("rrNav");
    var topbar = document.getElementById("rrTopbar");

    function positionNav() {
        if (!nav || !topbar) return;
        if (
            window.innerWidth >= 992 &&
            !nav.classList.contains("rr-nav--stuck")
        ) {
            nav.style.top = "50px";
        }
    }
    function handleNavStick() {
        if (!nav) return;
        var threshold = topbar ? topbar.offsetHeight : 50;
        if (window.scrollY > threshold) {
            nav.classList.add("rr-nav--stuck");
            if (window.innerWidth >= 992) nav.style.top = "";
        } else {
            nav.classList.remove("rr-nav--stuck");
            positionNav();
        }
    }
    window.addEventListener("scroll", handleNavStick, { passive: true });
    window.addEventListener(
        "resize",
        function () {
            positionNav();
            handleNavStick();
        },
        { passive: true },
    );
    positionNav();
    handleNavStick();

    /* 4. COUNTER ANIMATION */
    var counters = document.querySelectorAll(".rr-counter");
    var countersStarted = false;
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    function startCounters() {
        if (countersStarted) return;
        countersStarted = true;
        counters.forEach(function (el) {
            var target = parseInt(el.getAttribute("data-counter"), 10);
            var start = performance.now();
            (function step(now) {
                var p = Math.min((now - start) / 2200, 1);
                el.textContent = Math.floor(
                    easeOutCubic(p) * target,
                ).toLocaleString();
                if (p < 1) requestAnimationFrame(step);
                else el.textContent = target.toLocaleString();
            })(start);
        });
    }
    var statsSection = document.getElementById("stats");
    if (statsSection && "IntersectionObserver" in window) {
        new IntersectionObserver(
            function (entries, obs) {
                if (entries[0].isIntersecting) {
                    startCounters();
                    obs.disconnect();
                }
            },
            { threshold: 0.3 },
        ).observe(statsSection);
    }

    /* 5. CUSTOM SLIDERS — true infinite loop */
    var rrSliders = {};

    function rrGetPerView(id) {
        var w = window.innerWidth;
        if (id === "fleetSlider") return w >= 992 ? 4 : w >= 576 ? 2 : 1;
        if (id === "reviewsSlider") return w >= 992 ? 3 : w >= 576 ? 2 : 1;
        return 1;
    }

    function rrInitSlider(id) {
        var wrap = document.getElementById(id);
        if (!wrap) {
            return;
        }
        var track = wrap.querySelector(".rr-slider-track");
        var origSlides = Array.from(track.children);
        var n = origSlides.length;
        var dotsWrap = document.getElementById(id.replace("Slider", "Dots"));
        var controls = wrap.nextElementSibling;
        var btnPrev = controls ? controls.querySelector(".prev") : null;
        var btnNext = controls ? controls.querySelector(".next") : null;

        rrSliders[id] = {
            wrap: wrap,
            track: track,
            origSlides: origSlides,
            n: n,
            dotsWrap: dotsWrap,
            btnPrev: btnPrev,
            btnNext: btnNext,
            index: 0,
            dots: [],
            busy: false,
            pv: 0,
            cloneCount: 0,
        };

        rrBuildInfinite(id);
        rrBuildDots(id);

        if (btnPrev)
            btnPrev.onclick = function () {
                rrMove(id, -1);
            };
        if (btnNext)
            btnNext.onclick = function () {
                rrMove(id, 1);
            };

        track.addEventListener("transitionend", function (e) {
            if (e.propertyName !== "transform") return;
            rrAfterTransition(id);
        });
    }

    function rrBuildInfinite(id) {
        var s = rrSliders[id];
        var pv = rrGetPerView(id);
        s.pv = pv;
        var n = s.n;
        var pct = 100 / pv;

        /* remove old clones */
        Array.from(s.track.querySelectorAll(".rr-clone")).forEach(function (c) {
            c.remove();
        });
        s.origSlides.forEach(function (sl) {
            sl.style.width = pct + "%";
        });

        /* append n clones at end (copy of originals in order) */
        for (var i = 0; i < n; i++) {
            var cl = s.origSlides[i].cloneNode(true);
            cl.classList.add("rr-clone");
            cl.style.width = pct + "%";
            s.track.appendChild(cl);
        }
        /* prepend n clones at start (copy of originals in reverse) */
        for (var i = n - 1; i >= 0; i--) {
            var cl = s.origSlides[i].cloneNode(true);
            cl.classList.add("rr-clone");
            cl.style.width = pct + "%";
            s.track.insertBefore(cl, s.track.firstChild);
        }

        s.cloneCount = n;

        /* position without animation */
        s.track.style.transition = "none";
        void s.track.offsetHeight;
        s.track.style.transform = "translateX(-" + (s.index + n) * pct + "%)";
    }

    function rrBuildDots(id) {
        var s = rrSliders[id];
        if (!s.dotsWrap) return;
        s.dotsWrap.innerHTML = "";
        s.dots = [];
        for (var i = 0; i < s.n; i++) {
            var dot = document.createElement("button");
            dot.className = "rr-slider-dot" + (i === s.index ? " active" : "");
            dot.setAttribute("aria-label", "Slide " + (i + 1));
            (function (idx) {
                dot.addEventListener("click", function () {
                    rrGo(id, idx);
                });
            })(i);
            s.dotsWrap.appendChild(dot);
            s.dots.push(dot);
        }
    }

    function rrUpdateDots(id) {
        var s = rrSliders[id];
        var logical = ((s.index % s.n) + s.n) % s.n;
        s.dots.forEach(function (d, i) {
            d.className = "rr-slider-dot" + (i === logical ? " active" : "");
        });
    }

    function rrMove(id, dir) {
        var s = rrSliders[id];
        if (s.busy) return;
        s.busy = true;
        s.index += dir;
        var pct = 100 / s.pv;
        s.track.style.transition = "transform 0.45s ease";
        s.track.style.transform =
            "translateX(-" + (s.index + s.cloneCount) * pct + "%)";
        rrUpdateDots(id);
    }

    function rrAfterTransition(id) {
        var s = rrSliders[id];
        var n = s.n;
        /* snap to real position if we landed on a clone */
        if (s.index < 0 || s.index >= n) {
            s.index = ((s.index % n) + n) % n;
            var pct = 100 / s.pv;
            s.track.style.transition = "none";
            void s.track.offsetHeight;
            s.track.style.transform =
                "translateX(-" + (s.index + s.cloneCount) * pct + "%)";
        }
        s.busy = false;
    }

    function rrGo(id, idx) {
        var s = rrSliders[id];
        if (s.busy) return;
        s.busy = true;
        s.index = idx;
        var pct = 100 / s.pv;
        s.track.style.transition = "transform 0.45s ease";
        s.track.style.transform =
            "translateX(-" + (s.index + s.cloneCount) * pct + "%)";
        rrUpdateDots(id);
    }
    
    window.rrRebuildSlider = function (id, slides) {
        var wrap = document.getElementById(id);
        if (!wrap) return;
    
        var track = wrap.querySelector(".rr-slider-track");
        if (!track) return;
    
        // Reset slider state
        if (rrSliders[id]) {
            rrSliders[id].busy = false;
            rrSliders[id].index = 0;
        }
    
        // Remove everything (originals + clones)
        track.innerHTML = "";
    
        // Append fresh original slides
        slides.forEach(function (slide) {
            track.appendChild(slide);
        });
    
        // Destroy previous slider object
        delete rrSliders[id];
    
        // Reinitialize slider completely
        rrInitSlider(id);
    }

    rrInitSlider("fleetSlider");
    rrInitSlider("reviewsSlider");

    setInterval(function () {
        var s = rrSliders["reviewsSlider"];
        if (!s || s.busy) return;
        rrMove("reviewsSlider", 1);
    }, 5000);

    var rrResizeTimer;
    window.addEventListener(
        "resize",
        function () {
            clearTimeout(rrResizeTimer);
            rrResizeTimer = setTimeout(function () {
                Object.keys(rrSliders).forEach(function (id) {
                    rrBuildInfinite(id);
                    rrBuildDots(id);
                });
            }, 150);
        },
        { passive: true },
    );

    /* 6. LOGOUT AJAX INTERCEPTION */
    document.addEventListener("submit", function (e) {
        var form = e.target;
        if (!form || !form.action) return;
        if (form.action.indexOf("/logout") === -1) return;
        e.preventDefault();
        var token = form.querySelector('input[name="_token"]');
        $.ajax({
            url: form.action,
            type: "POST",
            data: { _token: token ? token.value : "" },
            headers: { "X-CSRF-TOKEN": token ? token.value : "" },
            success: function () {
                showAlert("success", "You have been logged out.");
                setTimeout(function () {
                    window.location.href = "/";
                }, 1200);
            },
            error: function () {
                window.location.href = "/";
            },
        });
    });

    if (!("IntersectionObserver" in window)) return;

    /* Elements to animate on scroll */
    var targets = [
        ".rr-section-head",
        ".rr-section-head h2",
        ".rr-section-head p",
        ".rr-features-grid > *",
        ".rr-service-card",
        ".rr-testimonial-card",
        ".rr-ambulance-card",
        ".rr-stat-item",
        ".rr-step",
        ".rr-faq-item",
        ".rr-footer__col",
    ].join(",");

    var els = document.querySelectorAll(targets);

    /* Apply reveal class to each matching element */
    els.forEach(function (el) {
        if (!el.classList.contains("rr-reveal")) {
            el.classList.add("rr-reveal");
        }
    });

    /* Stagger children inside grid containers */
    document.querySelectorAll(".rr-features-grid, .rr-services-grid, .rr-stats-row, .rr-steps-list",)
        .forEach(function (grid) {
            Array.from(grid.children).forEach(function (child, i) {
                child.style.transitionDelay = Math.min(i * 90, 450) + "ms";
            });
        });

    /* Testimonials: left/right alternating */
    document.querySelectorAll(".rr-testimonial-card").forEach(function (card, i) {
        card.classList.add(
            i % 2 === 0 ? "rr-reveal--left" : "rr-reveal--right",
        );
        card.style.transitionDelay = Math.min(i * 80, 400) + "ms";
    });

    /* IntersectionObserver to reveal */
    var io = new IntersectionObserver(
        function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add("rr-visible");
                    io.unobserve(e.target);
                }
            });
        },
        {
            threshold: 0.09,
            rootMargin: "0px 0px -35px 0px",
        },
    );

    document.querySelectorAll(".rr-reveal").forEach(function (el) {
        io.observe(el);
    });

    /* Hero section: animate headings immediately */
    document.querySelectorAll(".rr-hero__title, .rr-hero__sub, .rr-hero__actions").forEach(function (el, i) {el.classList.add("rr-hero-anim", "rr-hero-anim--d" + (i + 1));});

    /* Fallback: no IntersectionObserver support */
    if (!("IntersectionObserver" in window)) {
        document.querySelectorAll("[data-animate]").forEach(function (el) {
            el.classList.add("rr-animated");
        });
        return;
    }

    var revealObserver = new IntersectionObserver(
        function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var delay = parseInt(el.getAttribute("data-delay") || "0", 10);
                if (delay > 0) {
                    setTimeout(function () {
                        el.classList.add("rr-animated");
                    }, delay);
                } else {
                    el.classList.add("rr-animated");
                }
                obs.unobserve(el);
            });
        },
        {
            threshold: 0.1,
            rootMargin: "0px 0px -48px 0px",
        },
    );

    document.querySelectorAll("[data-animate]").forEach(function (el) {
        revealObserver.observe(el);
    });
})();

document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    /* ── On page load: scroll to ?s= section param (no hash needed) ── */
    var urlSection = new URLSearchParams(window.location.search).get("s");
    if (urlSection) {
        var targetEl = document.getElementById(urlSection);
        if (targetEl) {
            setTimeout(function () {
                window.scrollTo({
                    top: targetEl.offsetTop - 100,
                    behavior: "smooth",
                });
            }, 200);
        }
    }

    const navLinks = document.querySelectorAll("#rrNavMenu a");

    /* Build list of sections present on this page */
    const sections = [];
    navLinks.forEach(function (link) {
        const id = link.getAttribute("data-scroll");
        const section = id ? document.getElementById(id) : null;
        if (section) sections.push({ id: id, section: section, link: link });
    });

    /* ── Scroll spy (only when sections exist on page) ── */
    if (sections.length) {
        window.addEventListener(
            "scroll",
            function () {
                const scrollY = window.scrollY;
                let current = "";

                sections.forEach(function (s) {
                    const top = s.section.offsetTop - 120;
                    const height = s.section.offsetHeight;
                    if (scrollY >= top && scrollY < top + height)
                        current = s.id;
                });

                if (
                    !current &&
                    sections.length &&
                    scrollY < sections[0].section.offsetTop
                ) {
                    current = sections[0].id;
                }

                navLinks.forEach(function (link) {
                    link.classList.remove("active");
                    if (link.getAttribute("data-scroll") === current)
                        link.classList.add("active");
                });

                /* 5 — update URL section on scroll (no hash) */
                if (current) {
                    history.replaceState(null, "", "?s=" + current);
                }
            },
            { passive: true },
        );
    }

    /* ── Nav click — scroll if on same page, redirect if not ── */
    navLinks.forEach(function (link) {
        link.addEventListener("click", function (e) {
            const targetId = this.getAttribute("data-scroll");
            const target = document.getElementById(targetId);

            if (target) {
                /* Section exists on this page — smooth scroll */
                e.preventDefault();
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: "smooth",
                });
                navLinks.forEach(function (l) {
                    l.classList.remove("active");
                });
                link.classList.add("active");
                history.replaceState(null, "", "?s=" + targetId);
            } else {
                /* Section not on this page — redirect to home with section param */
                e.preventDefault();
                window.location.href = "/?s=" + targetId;
            }
        });
    });

    /* ── [data-scroll] elements outside nav ── */
    document.querySelectorAll("[data-scroll]").forEach(function (el) {
        if (el.closest("#rrNavMenu")) return; /* already handled above */
        el.addEventListener("click", function (e) {
            var id = el.getAttribute("data-scroll");
            var t = document.getElementById(id);
            if (t) {
                e.preventDefault();
                var top =
                    t.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: top, behavior: "smooth" });
                history.replaceState(null, "", "?s=" + id);
            }
        });
    });

    /* ── Contact message character counter ── */
    var msgTextarea = document.getElementById("contact_message");
    var msgCounter = document.getElementById("contact_message_counter");
    if (msgTextarea && msgCounter) {
        function updateCounter() {
            msgCounter.textContent = msgTextarea.value.length + "/500";
        }
        msgTextarea.addEventListener("input", updateCounter);
        updateCounter();
    }
});

// GET LOCATION
$(document).ready(function () {
    $("#getLocation").on("click", function () {
        var btn = $(this);
        var origHtml = btn.html();

        if (!navigator.geolocation) {
            showAlert("error", "Geolocation is not supported by this browser.");
            return;
        }

        btn.prop("disabled", true).html(
            '<i class="fas fa-spinner fa-spin"></i> Detecting location…',
        );

        navigator.geolocation.getCurrentPosition(
            function (position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                $("#latitude").val(lat);
                $("#longitude").val(lng);

                var url =
                    "https://nominatim.openstreetmap.org/reverse?lat=" +
                    lat +
                    "&lon=" +
                    lng +
                    "&format=json";

                $.ajax({
                    url: url,
                    method: "GET",
                    timeout: 8000,
                    success: function (data) {
                        if (data && data.display_name) {
                            $("#pickup_address").val(data.display_name);
                        } else {
                            $("#pickup_address").val(lat + ", " + lng);
                            showAlert(
                                "error",
                                "Could not retrieve address name. Coordinates saved.",
                            );
                        }
                    },
                    error: function () {
                        $("#pickup_address").val(lat + ", " + lng);
                        showAlert(
                            "error",
                            "Address lookup failed. Coordinates saved.",
                        );
                    },
                    complete: function () {
                        btn.prop("disabled", false).html(origHtml);
                    },
                });
            },
            function (err) {
                btn.prop("disabled", false).html(origHtml);
                var msg = "Unable to retrieve your location.";
                if (err.code === err.PERMISSION_DENIED) {
                    msg =
                        "Location permission denied. Please allow location access in your browser settings.";
                } else if (err.code === err.POSITION_UNAVAILABLE) {
                    msg = "Location information is unavailable.";
                } else if (err.code === err.TIMEOUT) {
                    msg = "Location request timed out. Please try again.";
                }
                showAlert("error", msg);
            },
            {
                timeout: 10000,
                maximumAge: 60000,
            },
        );
    });
});

function validatePakPhone(input) {
    let phone = input.value.replace(/\D/g, "");
    if (phone.length > 11) phone = phone.slice(0, 11);
    input.value = phone;
    const regex = /^03[0-9]{9}$/;
    /* find the error element: prefer closest .rr-field .text-danger, fallback to #phone_error */
    const wrapper = input.closest(".rr-field");
    const error =
        (wrapper && wrapper.querySelector(".text-danger")) ||
        document.getElementById("phone_error");
    if (phone === "") {
        if (error) error.innerText = "";
        input.style.borderColor = "";
        return;
    }
    if (!regex.test(phone)) {
        if (error)
            error.innerText = "Enter valid Pakistani number (03XXXXXXXXX)";
        input.style.borderColor = "red";
    } else {
        if (error) error.innerText = "";
        input.style.borderColor = "";
    }
}

function allowOnlyLetters(input) {
    input.value = input.value.replace(/[^a-zA-Z\s]/g, '') .replace(/\s{2,}/g, ' ').replace(/^\s+/g, '');
}

function allowAlphaNumericCommaDot(input) {
    input.value = input.value.replace(/[^a-zA-Z0-9\s.,]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/g, '');
}

function validateForm({ formId, fields, btn, btnTxt, onSuccess }) {
    const form = document.getElementById(formId);
    const Button = document.getElementById(btn);
    let isValid = true;

    fields.forEach(function (field) {
        const input = document.getElementById(field.id);
        if (!input) return;
        input.setCustomValidity("");
        let value = input.type === "file" ? input.value : input.value.trim();

        if (!value || (field.skipIf && value === field.skipIf)) {
            input.setCustomValidity(field.message);
            isValid = false;
        }
        if (
            field.min !== undefined &&
            !isNaN(value) &&
            parseFloat(value) <= field.min
        ) {
            input.setCustomValidity(
                `${field.id.replace(/_/g, " ")} must be greater than ${field.min}`,
            );
            isValid = false;
        }
        if (
            field.max !== undefined &&
            !isNaN(value) &&
            parseFloat(value) > field.max
        ) {
            input.setCustomValidity(
                `${field.id.replace(/_/g, " ")} must be ≤ ${field.max}`,
            );
            isValid = false;
        }
        if (field.minLength !== undefined && value.length < field.minLength) {
            input.setCustomValidity(
                `${field.id.replace(/_/g, " ")} must be at least ${field.minLength} characters`,
            );
            isValid = false;
        }
        if (field.maxLength !== undefined && value.length > field.maxLength) {
            input.setCustomValidity(
                `${field.id.replace(/_/g, " ")} must be at most ${field.maxLength} characters long`,
            );
            isValid = false;
        }
        if (field.validate === "email" && value) {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                input.setCustomValidity("Please enter a valid email address.");
                isValid = false;
            }
        }
        if (field.validate === "phone_no" && value) {
            let phone = value.replace(/\D/g, "");
            phone = phone.substring(0, 11);
            const pakPhoneRegex = /^03[0-9]{9}$/;

            if (!pakPhoneRegex.test(phone)) {
                input.setCustomValidity(
                    "Phone number must be in format: 03123456789",
                );
                isValid = false;
            } else {
                input.setCustomValidity("");
            }
            input.value = phone;
        }
        if (
            field.imgaccept &&
            input.type === "file" &&
            input.files.length > 0
        ) {
            const fileType = input.files[0].type.split("/")[1].toLowerCase();
            const allowedTypes = field.imgaccept.split(",").map(function (t) {
                return t.trim().toLowerCase();
            });
            if (!allowedTypes.includes(fileType)) {
                input.setCustomValidity(
                    `Only ${allowedTypes.join(", ")} files are allowed`,
                );
                isValid = false;
            }
        }
    });

    if (!isValid) {
        form.reportValidity();
        return;
    }

    if (Button) {
        Button.disabled = true;
        if (Button.id === "send-Btn")
            Button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Sending…`;
        else if (Button.id === "med-save-Btn")
            Button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Saving…`;
        else if (Button.id === "dispatchBtn")
            Button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Submitting…`;
    }
    if (typeof onSuccess === "function") onSuccess();
}

function submitFormData({ formId, url, successMessage, onSuccess, onError }) {
    const formElement = document.getElementById(formId);
    const formData = new FormData(formElement);
    const token = formElement.querySelector('input[name="_token"]');

    if (!token || !token.value) {
        showAlert("error", "Something went wrong. Please refresh the page.");
        if (typeof onError === "function") onError();
        return;
    }

    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        headers: { "X-CSRF-TOKEN": token.value },
        processData: false,
        contentType: false,
        success: function (response) {
            showAlert("success", successMessage);
            formElement.reset();
            if (typeof onSuccess === "function") onSuccess(response);
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                const res = xhr.responseJSON;
                if (res && res.errors) {
                    let msgs = "";
                    for (let f in res.errors)
                        msgs += `<div>${res.errors[f][0]}</div>`;
                    showAlert("error", "Validation Error", msgs);
                } else {
                    showAlert(
                        "error",
                        res && res.message ? res.message : "Invalid input.",
                    );
                }
            } else {
                showAlert(
                    "error",
                    "Something went wrong while submitting the form.",
                );
            }
            if (typeof onError === "function") onError();
        },
    });
}

function submitEmergencyRequest() {
    // 1. Validate hospital selection (must pick from suggestions)
    var hospitalOk = RRAutoComplete.requireSelection(
        'hospital_name', 'hospital_lat',
        'Please search and select a hospital from the suggestions.'
    );

    // 2. Validate pickup selection (must pick from suggestions OR use GPS)
    var pickupOk = RRAutoComplete.requireSelection(
        'pickup_address', 'latitude',
        'Please select your pickup location from the suggestions or use "Use my current location".'
    );

    if (!hospitalOk || !pickupOk) return;

    validateForm({
        formId: "bookingForm",
        fields: [
            { id: "mobile_no", message: "Please enter your phone no.", validate: "phone_no" },
            { id: "emergency_email", message: "Please enter your email.", validate: "email" },
            { id: "type", message: "Please select an emergency type.", skipIf: "0" },
        ],
        btn: "dispatchBtn",
        onSuccess: function () {
            submitFormData({
                formId: "bookingForm",
                url: window.routes.emergencyRequest,
                successMessage: "Emergency request submitted successfully! Our team will respond shortly.",
                onSuccess: function (response) {
                    if (response && response.request_id && window.routes && window.routes.trackingBase) {
                        window.location.href = window.routes.trackingBase + response.request_id;
                    } else {
                        var btn = document.getElementById("dispatchBtn");
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-ambulance"></i> Dispatch Ambulance'; }
                        var form = document.getElementById("bookingForm");
                        if (form) form.reset();
                    }
                },
                onError: function () {
                    var btn = document.getElementById("dispatchBtn");
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-ambulance"></i> Dispatch Ambulance'; }
                },
            });
        },
    });
}

function submitContactForm() {
    var contactRecaptchaToken = (typeof grecaptcha !== 'undefined' && typeof contactWidgetId !== 'undefined')
        ? grecaptcha.getResponse(contactWidgetId) : '';
    if (!contactRecaptchaToken) {
        var rcErr = document.getElementById('contact-recaptcha-error');
        if (rcErr) rcErr.style.display = 'block';
        return;
    }
    var rcErr = document.getElementById('contact-recaptcha-error');
    if (rcErr) rcErr.style.display = 'none';
    var captchaInput = document.getElementById('contact_captcha_response');
    if (captchaInput) captchaInput.value = contactRecaptchaToken;

    validateForm({
        formId: "contactForm",
        fields: [
            {
                id: "contact_name",
                message: "Please enter your name.",
                maxLength: 30,
            },
            {
                id: "contact_email",
                message: "Please enter your email.",
                validate: "email",
                maxLength: 30,
            },
            {
                id: "contact_phone",
                message: "Please enter your phone no.",
                validate: "phone_no",
            },
            {
                id: "contact_subject",
                message: "Please enter subject.",
                maxLength: 50,
            },
            {
                id: "contact_message",
                message: "Please enter message.",
                maxLength: 500,
            },
        ],
        btn: "send-Btn",
        btnTxt: "Message",
        onSuccess: function () {
            submitFormData({
                formId: "contactForm",
                url: window.routes.contactSubmit,
                successMessage:
                    "Your message has been sent! We will get back to you shortly.",
                onSuccess: function () {
                    var btn = document.getElementById("send-Btn");
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML =
                            '<i class="fas fa-paper-plane"></i> Send Message';
                    }
                    var counter = document.getElementById(
                        "contact_message_counter",
                    );
                    if (counter) counter.textContent = "0/500";
                    if (window.routes && window.routes.contactHistory) {
                        setTimeout(function () {
                            window.location.href = window.routes.contactHistory;
                        }, 1500);
                    }
                },
            });
        },
    });
}

function showAlert(type, message, detail) {
    const isSuccess = type === "success";
    const alertEl = document.getElementById(
        isSuccess ? "successAlert" : "errorAlert",
    );
    const textEl = document.getElementById(
        isSuccess ? "successAlertText" : "errorAlertText",
    );
    if (!alertEl || !textEl) return;
    textEl.innerHTML = message + (detail ? detail : "");
    alertEl.classList.add("show");
    alertEl.style.display = "block";
    setTimeout(function () {
        if (isSuccess) hideSuccessAlert();
        else hideErrorAlert();
    }, 5000);
}

function hideSuccessAlert() {
    var el = document.getElementById("successAlert");
    if (!el) return;
    el.classList.remove("show");
    setTimeout(function () {
        el.style.display = "none";
    }, 150);
}

function hideErrorAlert() {
    var el = document.getElementById("errorAlert");
    if (!el) return;
    el.classList.remove("show");
    setTimeout(function () {
        el.style.display = "none";
    }, 150);
}