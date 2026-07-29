/* Real-time sync (Services / FAQs / Testimonials) via existing Reverb/Pusher connection.
   Subscribes only once to the shared window.channel and updates only the affected DOM. */
(function () {
    "use strict";

    if (window.__rrRealtimeBound) return;
    window.__rrRealtimeBound = true;

    function esc(str) {
        return String(str == null ? "" : str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    /* ---------------------------------------------------------------- */
    /* SERVICES                                                          */
    /* ---------------------------------------------------------------- */
    var servicesGrid = document.getElementById("servicesGrid");

    function serviceCardHtml(s) {
        return (
            '<div class="col-md-6 col-lg-4 rr-animated" data-service-id="' + s.id + '">' +
                '<div class="rr-card">' +
                    '<div class="rr-card__icon"><i class="' + esc(s.icon) + '"></i></div>' +
                    "<h4>" + esc(s.title) + "</h4>" +
                    "<p>" + esc(s.description) + "</p>" +
                "</div>" +
            "</div>"
        );
    }

    function removeServicesEmptyState() {
        if (!servicesGrid) return;
        var empty = servicesGrid.querySelector(".col-12.text-center");
        if (empty) empty.remove();
    }

    function checkServicesEmptyState() {
        if (!servicesGrid) return;
        if (servicesGrid.children.length === 0) {
            servicesGrid.innerHTML =
                '<div class="col-12 text-center py-4" style="color:var(--rr-text-light)">No services listed yet.</div>';
        }
    }

    function insertServiceSorted(node, id) {
        if (!servicesGrid) return;
        var inserted = false;
        var children = Array.from(servicesGrid.children).filter(function (c) {
            return c.hasAttribute("data-service-id");
        });
        for (var i = 0; i < children.length; i++) {
            var existingId = parseInt(children[i].getAttribute("data-service-id"), 10);
            if (id < existingId) {
                servicesGrid.insertBefore(node, children[i]);
                inserted = true;
                break;
            }
        }
        if (!inserted) servicesGrid.appendChild(node);
    }

    function upsertService(s) {
        if (!servicesGrid) return;
        var existing = servicesGrid.querySelector('[data-service-id="' + s.id + '"]');

        if (parseInt(s.status, 10) !== 1) {
            if (existing) existing.remove();
            checkServicesEmptyState();
            return;
        }

        if (existing) {
            existing.querySelector(".rr-card__icon i").className = s.icon;
            existing.querySelector("h4").textContent = s.title;
            existing.querySelector("p").textContent = s.description;
            return;
        }

        removeServicesEmptyState();
        var wrapper = document.createElement("div");
        wrapper.innerHTML = serviceCardHtml(s);
        insertServiceSorted(wrapper.firstElementChild, s.id);
    }

    function removeService(id) {
        if (!servicesGrid) return;
        var existing = servicesGrid.querySelector('[data-service-id="' + id + '"]');
        if (existing) existing.remove();
        checkServicesEmptyState();
    }

    /* ---------------------------------------------------------------- */
    /* FAQS                                                              */
    /* ---------------------------------------------------------------- */
    var faqAccordion = document.getElementById("faqAccordion");

    function faqItemHtml(f, expanded) {
        var headId = "faqHead-rt-" + f.id;
        var bodyId = "faqBody-rt-" + f.id;
        return (
            '<div class="accordion-item rr-accordion__item" data-faq-id="' + f.id + '">' +
                '<h2 class="accordion-header" id="' + headId + '">' +
                    '<button class="accordion-button rr-accordion__btn' + (expanded ? "" : " collapsed") + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + bodyId + '" aria-expanded="' + (expanded ? "true" : "false") + '" aria-controls="' + bodyId + '">' +
                        '<span class="rr-accordion__num"></span>' +
                        esc(f.question) +
                    "</button>" +
                "</h2>" +
                '<div id="' + bodyId + '" class="accordion-collapse collapse' + (expanded ? " show" : "") + '" aria-labelledby="' + headId + '" data-bs-parent="#faqAccordion">' +
                    '<div class="accordion-body rr-accordion__body">' + esc(f.answer) + "</div>" +
                "</div>" +
            "</div>"
        );
    }

    function removeFaqEmptyState() {
        if (!faqAccordion) return;
        Array.from(faqAccordion.children).forEach(function (c) {
            if (!c.hasAttribute("data-faq-id")) c.remove();
        });
    }

    function checkFaqEmptyState() {
        if (!faqAccordion) return;
        if (faqAccordion.children.length === 0) {
            faqAccordion.innerHTML =
                '<div class="accordion-item rr-accordion__item"><p style="padding:16px;color:var(--rr-text-light)">No FAQs available yet.</p></div>';
        }
    }

    function renumberFaqs() {
        if (!faqAccordion) return;
        var items = Array.from(faqAccordion.querySelectorAll("[data-faq-id]"));
        items.forEach(function (item, i) {
            var num = item.querySelector(".rr-accordion__num");
            if (num) num.textContent = String(i + 1).padStart(2, "0");
        });
    }

    function insertFaqSorted(node, id) {
        if (!faqAccordion) return;
        var inserted = false;
        var children = Array.from(faqAccordion.children).filter(function (c) {
            return c.hasAttribute("data-faq-id");
        });
        for (var i = 0; i < children.length; i++) {
            var existingId = parseInt(children[i].getAttribute("data-faq-id"), 10);
            if (id < existingId) {
                faqAccordion.insertBefore(node, children[i]);
                inserted = true;
                break;
            }
        }
        if (!inserted) faqAccordion.appendChild(node);
    }

    function upsertFaq(f) {
        if (!faqAccordion) return;
        var existing = faqAccordion.querySelector('[data-faq-id="' + f.id + '"]');

        if (parseInt(f.status, 10) !== 1) {
            if (existing) {
                existing.remove();
                renumberFaqs();
                checkFaqEmptyState();
            }
            return;
        }

        if (existing) {
            var btn = existing.querySelector(".accordion-button");
            var body = existing.querySelector(".accordion-body");
            if (btn) {
                var num = btn.querySelector(".rr-accordion__num");
                var numHtml = num ? num.outerHTML : '<span class="rr-accordion__num"></span>';
                btn.innerHTML = numHtml + esc(f.question);
            }
            if (body) body.textContent = f.answer;
            return;
        }

        removeFaqEmptyState();
        var hasExpandedItem = !!faqAccordion.querySelector(".accordion-collapse.show");
        var wrapper = document.createElement("div");
        wrapper.innerHTML = faqItemHtml(f, !hasExpandedItem);
        insertFaqSorted(wrapper.firstElementChild, f.id);
        renumberFaqs();
    }

    function removeFaq(id) {
        if (!faqAccordion) return;
        var existing = faqAccordion.querySelector('[data-faq-id="' + id + '"]');
        if (existing) existing.remove();
        renumberFaqs();
        checkFaqEmptyState();
    }

    /* ---------------------------------------------------------------- */
    /* TESTIMONIALS                                                      */
    /* ---------------------------------------------------------------- */
    var reviewsWrap = document.getElementById("reviewsSlider");
    function refreshReviewsWrap() {
        reviewsWrap = document.getElementById("reviewsSlider");
    }
    var testimonialsState = [];

    function seedTestimonialsState() {
        if (!reviewsWrap) return;
        var track = reviewsWrap.querySelector(".rr-slider-track");
        if (!track) return;
        Array.from(track.querySelectorAll(".rr-slide:not(.rr-clone)")).forEach(function (slide) {
            var id = parseInt(slide.getAttribute("data-testimonial-id"), 10);
            var stars = slide.querySelectorAll(".rr-review-stars .fas.fa-star").length;
            var content = slide.querySelector(".rr-review-text");
            var name = slide.querySelector(".rr-reviewer-name");
            var role = slide.querySelector(".rr-reviewer-role");
            testimonialsState.push({
                id: id,
                rating: stars,
                content: content ? content.textContent.replace(/^"|"$/g, "") : "",
                name: name ? name.textContent : "",
                role: role ? role.textContent : "",
            });
        });
    }

    function starsHtml(rating) {
        var html = "";
        for (var i = 1; i <= rating; i++) html += '<i class="fas fa-star"></i>';
        for (var i = rating + 1; i <= 5; i++) html += '<i class="far fa-star"></i>';
        return html;
    }

    function testimonialSlideEl(t) {
        var div = document.createElement("div");
        div.className = "rr-slide";
        div.setAttribute("data-testimonial-id", t.id);
        div.innerHTML =
            '<div class="rr-review-card">' +
                '<div class="rr-review-stars">' + starsHtml(t.rating) + "</div>" +
                '<p class="rr-review-text">"' + esc(t.content) + '"</p>' +
                '<div class="rr-reviewer">' +
                    '<div class="rr-reviewer-avatar">' + esc((t.name || "").charAt(0).toUpperCase()) + "</div>" +
                    "<div>" +
                        '<div class="rr-reviewer-name">' + esc(t.name) + "</div>" +
                        '<div class="rr-reviewer-role">' + esc(t.role || "") + "</div>" +
                    "</div>" +
                "</div>" +
            "</div>";
        return div;
    }

    function rebuildTestimonialsSlider() {
        refreshReviewsWrap();
        toggleReviewsState();
    
        if (testimonialsState.length === 0) return;
        testimonialsState.sort(function(a,b){
            return a.id-b.id;
        });
    
        var els = testimonialsState.map(testimonialSlideEl);
        window.rrRebuildSlider("reviewsSlider", els);
    }
        
    function updateTestimonialInPlace(t) {
        document.querySelectorAll('[data-testimonial-id="' + t.id + '"]').forEach(function (slide) {
            var stars = slide.querySelector(".rr-review-stars");
            var content = slide.querySelector(".rr-review-text");
            var name = slide.querySelector(".rr-reviewer-name");
            var role = slide.querySelector(".rr-reviewer-role");
            var avatar = slide.querySelector(".rr-reviewer-avatar");
            if (stars) stars.innerHTML = starsHtml(t.rating);
            if (content) content.textContent = '"' + t.content + '"';
            if (name) name.textContent = t.name;
            if (role) role.textContent = t.role || "";
            if (avatar) avatar.textContent = (t.name || "").charAt(0).toUpperCase();
        });
    }

    function upsertTestimonial(t) {
        if (!reviewsWrap) return;
        var idx = testimonialsState.findIndex(function (x) { return x.id === t.id; });
        var isActive = parseInt(t.status, 10) === 1;

        if (!isActive) {
            if (idx !== -1) {
                testimonialsState.splice(idx, 1);
                rebuildTestimonialsSlider();
            }
            return;
        }

        if (idx !== -1) {
            testimonialsState[idx] = t;
            var existsInDom = document.querySelector('[data-testimonial-id="' + t.id + '"]');
            if (existsInDom) {
                updateTestimonialInPlace(t);
            } else {
                rebuildTestimonialsSlider();
            }
            return;
        }

        testimonialsState.push(t);
        rebuildTestimonialsSlider();
    }

    function removeTestimonial(id) {
        var idx = testimonialsState.findIndex(function (x) { return x.id === id; });
        if (idx === -1) return;
        testimonialsState.splice(idx, 1);
        rebuildTestimonialsSlider();
    }

    function toggleReviewsState() {
        refreshReviewsWrap();
        var empty = document.getElementById("reviewsEmpty");
        var controls = reviewsWrap ? reviewsWrap.nextElementSibling : document.querySelector(".rr-slider-controls");
    
        if (testimonialsState.length === 0) {
            if (reviewsWrap) reviewsWrap.style.display = "none";
            if (controls) controls.style.display = "none";
            if (empty) empty.style.display = "block";
        } else {
            if (reviewsWrap) reviewsWrap.style.display = "";
            if (controls) controls.style.display = "";
            if (empty) empty.style.display = "none";
        }
    }

    if (reviewsWrap) seedTestimonialsState();
    
    /* ---------------------------------------------------------------- */
    /* BRANCHES                                                          */
    /* ---------------------------------------------------------------- */
    var branchesWrap = document.getElementById("branchesWrap");
    var branchesState = window.initialBranches || [];

    function toggleBranchesPlaceholder() {
        var placeholder = branchesWrap.querySelector(".rr-branch:not([data-branch-id])");
        if (!placeholder) return;
        placeholder.style.display = branchesState.length ? "none" : "";
    }
        
    function branchElement(branch) {
        var div = document.createElement("div");
        div.className = "rr-branch";
        div.dataset.branchId = branch.id;
    
        var html =
            '<h5><i class="fas fa-building"></i> ' + esc(branch.name) + '</h5>' +
    
            '<div class="rr-branch__row">' +
                '<i class="fas fa-map-marker-alt"></i>' +
                '<span>Address:</span> ' +
                esc(branch.address) +
            '</div>' +
    
            '<div class="rr-branch__row">' +
                '<i class="fas fa-phone-alt"></i>' +
                '<span>Telephone:</span> ' +
                esc(branch.phone) +
            '</div>'
        ;
    
        if (branch.email) {
            html +=
                '<div class="rr-branch__row">' +
                    '<i class="fas fa-envelope"></i>' +
                    '<span>Email:</span> ' +
                    esc(branch.email) +
                '</div>'
            ;
        }
    
        div.innerHTML = html;
        return div;
    }
        
    function rebuildBranches() {
        refreshContactInfo();
        if (!branchesWrap) {
            return;
        }
        branchesWrap.querySelectorAll(".rr-branch[data-branch-id]").forEach(function (e) { e.remove();});
        branchesState.sort(function (a, b) { return a.id - b.id; });
        branchesState.forEach(function (branch) {
            branchesWrap.insertBefore(
                branchElement(branch),
                branchesWrap.querySelector(".rr-branch:not([data-branch-id])")
            );
        });
        toggleBranchesPlaceholder();
    }

    function refreshContactInfo() {
        var contact = null;
        branchesState.sort(function (a, b) { return a.id - b.id; });
    
        for (var i = 0; i < branchesState.length; i++) {
            if (parseInt(branchesState[i].status, 10) === 1) {
                contact = branchesState[i];
                break;
            }
        }
    
        if (!contact) {
            contact = {
                phone: "+92 xxx xxxxxxx",
                email: "info@rapidrescue.com",
                address: "XYZ Corporate Office, DHA Phase 6, Karachi, Pakistan",
            };
        }
    
        /* PHONE */
        document.querySelectorAll('[data-rr-ci="phone"]').forEach(function (el) {
            el.textContent = contact.phone || "";
        });
    
        /* EMAIL */
        document.querySelectorAll('[data-rr-ci="email"]').forEach(function (el) {
            el.textContent = contact.email || "";
        });
    
        /* ADDRESS */
        document.querySelectorAll('[data-rr-ci="address"]').forEach(function (el) {
            el.textContent = contact.address || "";
        });
    
        /* tel: links */
        document.querySelectorAll('a[data-rr-ci="phone"]').forEach(function (el) {
            el.href = "tel:" + (contact.phone || "");
            el.innerHTML = '<i class="fas fa-phone-alt"></i> ' + esc(contact.phone || "");
        });
    
        /* mailto: links */
        document.querySelectorAll('a[data-rr-ci="email"]').forEach(function (el) {
            el.href = "mailto:" + (contact.email || "");
            el.innerHTML = '<i class="fas fa-envelope"></i> ' + esc(contact.email || "");
        });
    
        /* email link inside privacy page */
        document.querySelectorAll('a[data-rr-ci="email-link"]').forEach(function (el) {
            el.href = "mailto:" + (contact.email || "");
            el.textContent = contact.email || "";
        });
    }
        
    function upsertBranch(branch) {
    
        var idx = branchesState.findIndex(function (b) { return b.id == branch.id; });
        var active = parseInt(branch.status, 10) === 1;
        if (!active) {
            if (idx !== -1) {
                branchesState.splice(idx, 1);
                rebuildBranches();
            }
            return;
        }
    
        if (idx !== -1) {
            branchesState[idx] = branch;
        } else {
            branchesState.push(branch);
        }
        rebuildBranches();
    }

    function removeBranch(id) {
        var idx = branchesState.findIndex(function (b) { return b.id == id; });
        if (idx === -1) return;
        branchesState.splice(idx, 1);
        rebuildBranches();
    }

    refreshContactInfo();
    if (branchesWrap) {
        rebuildBranches();
    }

    
    /* ---------------------------------------------------------------- */
    /* AMBULANCES (fleet slider)                                        */
    /* ---------------------------------------------------------------- */
    var fleetSlider = document.getElementById("fleetSlider");
    var fleetAmbs   = (window.initialFleetAmbs || []).slice();

    var _typeFullLabels = {
        1: "Basic Life Support",
        2: "Advanced Life Support",
        3: "Critical Care Transport",
        4: "Neonatal Transport",
        5: "Air Ambulance",
    };

    function fleetStarsHtml(rating) {
        var r = parseFloat(rating) || 0;
        var html = "";
        for (var i = 1; i <= 5; i++) {
            if (i <= Math.floor(r))      html += '<i class="fas fa-star"></i>';
            else if (i - r < 1)          html += '<i class="fas fa-star-half-alt"></i>';
            else                         html += '<i class="far fa-star"></i>';
        }
        return html;
    }

    function fleetFeaturesHtml(raw) {
        if (!raw) return "";
        var features;
        try { features = JSON.parse(raw); } catch (e) { features = null; }
        if (!Array.isArray(features)) {
            features = String(raw).split(",").map(function (s) { return s.trim(); });
        }
        features = features.filter(Boolean).slice(0, 4);
        if (!features.length) return "";
        var html = '<div class="rr-fleet-card__badge">';
        features.forEach(function (f) {
            html += '<span><i class="fas fa-check-circle me-1" style="color:var(--rr-primary)"></i>' + esc(f) + "</span>";
        });
        return html + "</div>";
    }

    function ambulanceSlideEl(a) {
        var div = document.createElement("div");
        div.className = "rr-slide";
        div.setAttribute("data-ambulance-id", a.id);

        var imgSrc = a.card_image
            ? "/assets/admin/img/fleet/" + esc(a.card_image)
            : "/assets/admin/img/other/ambulance.png";
        var imgAlt = esc(a.card_title || a.vehicle_number || "");

        var ratingHtml = "";
        if (a.card_rating) {
            ratingHtml =
                '<div class="rr-fleet-card__rating">' +
                fleetStarsHtml(a.card_rating) +
                '<span class="text-muted">' + esc(String(a.card_rating)) +
                (a.card_trips ? " · " + esc(String(a.card_trips)) + " trips" : "") +
                "</span></div>";
        }

        div.innerHTML =
            '<div class="rr-fleet-card">' +
                '<div class="rr-fleet-card__img"><img src="' + imgSrc + '" alt="' + imgAlt + '"></div>' +
                '<div class="rr-fleet-card__body">' +
                    '<span class="rr-fleet-card__type">' + esc(_typeFullLabels[a.type] || "") + "</span>" +
                    "<h4>" + esc(a.card_title || a.vehicle_number || "") + "</h4>" +
                    "<p>" + esc(a.card_description || "Fully equipped unit ready for any emergency.") + "</p>" +
                    fleetFeaturesHtml(a.card_features) +
                    ratingHtml +
                "</div>" +
            "</div>";
        return div;
    }

    function toggleFleetState() {
        fleetSlider = document.getElementById("fleetSlider");
        var fleetEmpty    = document.getElementById("fleetEmpty");
        var fleetControls = fleetSlider ? fleetSlider.nextElementSibling : null;

        if (fleetAmbs.length === 0) {
            if (fleetSlider)    fleetSlider.style.display    = "none";
            if (fleetControls)  fleetControls.style.display  = "none";
            if (fleetEmpty)     fleetEmpty.style.display     = "";
        } else {
            if (fleetSlider)    fleetSlider.style.display    = "";
            if (fleetControls)  fleetControls.style.display  = "";
            if (fleetEmpty)     fleetEmpty.style.display     = "none";
        }
    }

    function rebuildFleetSlider() {
        toggleFleetState();
        if (fleetAmbs.length === 0) return;
        fleetAmbs.sort(function (a, b) { return a.type - b.type; });
        var els = fleetAmbs.map(ambulanceSlideEl);
        window.rrRebuildSlider("fleetSlider", els);
    }
    

    function upsertAmbulance(a) {
        var show = !!a.card_title && parseInt(a.status, 10) !== 4;
        var idx  = fleetAmbs.findIndex(function (x) { return x.id === a.id; });

        if (!show) {
            if (idx !== -1) {
                fleetAmbs.splice(idx, 1);
                rebuildFleetSlider();
            }
            return;
        }

        if (idx !== -1) {
            fleetAmbs[idx] = a;
        } else {
            fleetAmbs.push(a);
        }
        rebuildFleetSlider();
    }

    function removeAmbulance(id) {
       var idx = fleetAmbs.findIndex(function (x) { return Number(x.id) === Number(id); });
       if (idx === -1) { return;}
       fleetAmbs.splice(idx, 1);
       rebuildFleetSlider();
    }
    
    /* Initialise fleet state visibility on page load */
    toggleFleetState();


    /* ---------------------------------------------------------------- */
    /* CHANNEL BINDING (reuses the single shared Reverb/Pusher channel)  */
    /* ---------------------------------------------------------------- */
    function bind() {
        if (!window.channel || window.__rrRealtimeChannelBound) return;
        window.__rrRealtimeChannelBound = true;
    
        window.channel.bind("admin-content-updated", function (event) {
            switch (event.entity) {
    
                case "ambulance":
                    if (event.action === "deleted") {
                        removeAmbulance(event.data.id);
                    } else {
                        upsertAmbulance(event.data);
                    }
                    break;

                case "service":
                    if (event.action === "deleted") {
                        removeService(event.data.id);
                    } else {
                        upsertService(event.data);
                    }
                    break;
    
                case "faq":
                    if (event.action === "deleted") {
                        removeFaq(event.data.id);
                    } else {
                        upsertFaq(event.data);
                    }
                    break;
    
                case "testimonial":
                    if (event.action === "deleted") {
                        removeTestimonial(event.data.id);
                    } else {
                        upsertTestimonial(event.data);
                    }
                    break;
                case "branches":
                    if (event.action === "deleted") {
                        removeBranch(event.data.id);
                    } else {
                        upsertBranch(event.data);
                    }
                    break;
            }
        });
    }
    
    if (window.channel) {
        bind();
    } else {
        var tries = 0;
        var waitId = setInterval(function () {
            tries++;
            if (window.channel || tries > 50) {
                clearInterval(waitId);
                bind();
            }
        }, 100);
    }
})();
