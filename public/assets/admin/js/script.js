/*Admin Shared Utilities */
(function () {
    if (window.bootstrap && window.bootstrap.Modal) {
        bootstrap.Modal.Default.focus = false;
    }
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".modal").forEach(function (modal) {
            document.body.appendChild(modal);
        });
    });

    /* Stagger table rows */
    document.querySelectorAll(".table tbody tr").forEach(function (tr, i) {
        tr.style.animationDelay = Math.min(i * 22, 400) + "ms";
    });

    /* Stagger stat cards */
    document.querySelectorAll(".stat-card").forEach(function (card, i) {
        card.style.animationDelay = i * 70 + "ms";
    });

    /* Scroll-reveal for below-fold elements */
    if ("IntersectionObserver" in window) {
        var io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add("adm-visible");
                        io.unobserve(e.target);
                    }
                });
            },
            {
                threshold: 0.08,
                rootMargin: "0px 0px -30px 0px",
            },
        );
        document.querySelectorAll(".adm-reveal").forEach(function (el) {
            io.observe(el);
        });
    }
})();

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute("content");
}

function toggleSidebar() {
    document.getElementById("admSidebar").classList.toggle("adm-sidebar--open");
    document.getElementById("admSidebarOverlay").classList.toggle("adm-sidebar-overlay--visible");
}

window.PGD = (function () {
    "use strict";
    var _s = {};

    function _r(p) {
        return Array.from(document.querySelectorAll(p.sel));
    }

    function _render(p) {
        var all = _r(p), 
        total = Math.max(1, Math.ceil(all.length / p.per));
        if (p.cur > total) p.cur = total;
        all.forEach(function (row, i) {
            row.style.display = Math.floor(i / p.per) + 1 === p.cur ? "" : "none";
        });
        var s = (p.cur - 1) * p.per + 1, 
        e = Math.min(p.cur * p.per, all.length);
        if (p.info) p.info.textContent = all.length > 0 ? "Showing " + s + "–" + e + " of " + all.length : "";
        if (p.pages) p.pages.textContent = "Page " + p.cur + " of " + total;
        if (p.prev) p.prev.disabled = p.cur <= 1;
        if (p.next) p.next.disabled = p.cur >= total;
    }

    function init(o) {
        var p = {
            id: o.id,
            sel: o.sel,
            prev: document.getElementById(o.prevId),
            next: document.getElementById(o.nextId),
            info: document.getElementById(o.infoId),
            pages: document.getElementById(o.pagesId),
            per: o.perPage || 20,
            cur: 1,
            on: true,
        };
        _s[o.id] = p;
        if (p.prev)
            p.prev.addEventListener("click", function () {
                if (p.on && p.cur > 1) {
                    p.cur--;
                    _render(p);
                }
            });
        if (p.next)
            p.next.addEventListener("click", function () {
                if (p.on) {
                    var t = Math.max(1, Math.ceil(_r(p).length / p.per));
                    if (p.cur < t) {
                        p.cur++;
                        _render(p);
                    }
                }
            });
        _render(p);
    }

    function applyFilter(id, matched) {
        var p = _s[id];
        if (!p) return;
        var all = _r(p);
        if (matched === null) {
            p.on = true;
            p.cur = 1;
            _render(p);
            return;
        }
        p.on = false;
        all.forEach(function (row) {
            row.style.display = matched.indexOf(row) >= 0 ? "" : "none";
        });
        var n = matched.length;
        if (p.info) p.info.textContent = n + " result" + (n !== 1 ? "s" : "") + " found";
        if (p.pages) p.pages.textContent = "Filtered";
        if (p.prev) p.prev.disabled = true;
        if (p.next) p.next.disabled = true;
    }
    return {
        init: init,
        applyFilter: applyFilter,
    };
})();

function updateCharCount(input, counterId) {
    var el = document.getElementById(counterId);
    if (el) el.textContent = input.value.length;
}

function allowOnlyLetters(input) {
    input.value = input.value.replace(/[^a-zA-Z\s]/g, '') .replace(/\s{2,}/g, ' ').replace(/^\s+/g, '');
}

function allowAlphaNumericCommaDot(input) {
    input.value = input.value.replace(/[^a-zA-Z0-9\s.,]/g, '').replace(/\s{2,}/g, ' ').replace(/^\s+/g, '');
}

function validatePakPhone(input) {
    let phone = input.value;

    // allow only + and digits
    phone = phone.replace(/[^0-9+]/g, '');

    // ensure + is only at start
    if (phone.indexOf('+') > 0) {
        phone = phone.replace(/\+/g, '');
    }

    // enforce +92 at start
    if (!phone.startsWith('+92')) {
        phone = '+92' + phone.replace(/^\+?92?/, '');
    }

    // limit total length (+92 + 10 digits = 13 chars)
    if (phone.length > 13) {
        phone = phone.slice(0, 13);
    }

    input.value = phone;

    // regex for +92XXXXXXXXXX
    const regex = /^\+92[0-9]{10}$/;

    const wrapper = input.closest(".rr-field");
    const error = (wrapper && wrapper.querySelector(".text-danger")) || document.getElementById("phone_error");

    if (phone === "+92") {
        if (error) error.innerText = "";
        input.style.borderColor = "";
        return;
    }

    if (!regex.test(phone)) {
        if (error) error.innerText = "Enter valid Pakistani number (+92XXXXXXXXXX)";
        input.style.borderColor = "red";
    } else {
        if (error) error.innerText = "";
        input.style.borderColor = "";
    }
}

/*Form Validation */
function validateForm({ formId, fields, btn, onSuccess }) {
    const form = document.getElementById(formId);
    const Button = document.getElementById(btn);
    let isValid = true;

    fields.forEach(function (field) {
        const input = document.getElementById(field.id);
        if (!input) return;
        input.setCustomValidity("");
        const value = input.type === "file" ? input.value : input.value.trim();

        if (!value || (field.skipIf !== undefined && value === field.skipIf)) {
            input.setCustomValidity(field.message);
            isValid = false;
            return;
        }
        if (field.minLength !== undefined && value.length < field.minLength) {
            input.setCustomValidity(
                "Must be at least " + field.minLength + " characters.",
            );
            isValid = false;
            return;
        }
        if (field.maxLength !== undefined && value.length > field.maxLength) {
            input.setCustomValidity(
                "Must be at most " + field.maxLength + " characters.",
            );
            isValid = false;
            return;
        }
        if (field.validate === "email" && value) {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                input.setCustomValidity("Please enter a valid email address.");
                isValid = false;
                return;
            }
        }
        if (field.imgaccept && input.type === "file" && input.files.length > 0) {
            const fileType = input.files[0].type.split("/")[1].toLowerCase();
            const allowedTypes = field.imgaccept.split(",").map(function (t) {
                return t.trim().toLowerCase();
            });
            if (!allowedTypes.includes(fileType)) {
                input.setCustomValidity(
                    "Only " + allowedTypes.join(", ") + " files are allowed.",
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
        Button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving\u2026';
    }
    if (typeof onSuccess === "function") onSuccess();
}

/*Form Submission (fetch) */
function submitFormData({ formId, url, successMessage, onSuccess, onError }) {
    const formElement = document.getElementById(formId);
    const formData = new FormData(formElement);
    const token = formElement.querySelector('input[name="_token"]');

    if (!token || !token.value) {
        showAlert("error", "CSRF token missing. Please refresh the page.");
        if (typeof onError === "function") onError();
        return;
    }

    fetch(url, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": token.value, Accept: "application/json" },
        body: formData,
    })
        .then(function (r) {
            return r.json().then(function (data) {
                return { status: r.status, data: data };
            });
        })
        .then(function (res) {
            if (res.status === 200 && res.data.success !== false) {
                showAlert("success", successMessage);
                formElement.reset();
                if (typeof onSuccess === "function") onSuccess(res.data);
            } else if (res.status === 422 && res.data.errors) {
                var msgs = "";
                for (var f in res.data.errors) {
                    var err = res.data.errors[f];
                    msgs += "<div>" + (Array.isArray(err) ? err[0] : err) + "</div>";
                }
                showAlert("error", "Validation Error", msgs);
                if (typeof onError === "function") onError();
            } else {
                showAlert("error", res.data.message || "An error occurred.");
                if (typeof onError === "function") onError();
            }
        })
        .catch(function () {
            showAlert("error", "Something went wrong. Please try again.");
            if (typeof onError === "function") onError();
        });
}

/*Custom Confirm Dialog */
function confirmAction(message, onConfirm) {
    var msgEl = document.getElementById("globalConfirmMsg");
    if (msgEl && message) msgEl.textContent = message;

    var modal = bootstrap.Modal.getOrCreateInstance(
        document.getElementById("globalConfirmModal"),
    );
    var okBtn = document.getElementById("globalConfirmOkBtn");

    var newBtn = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newBtn, okBtn);
    newBtn.addEventListener("click", function () {
        modal.hide();
        if (typeof onConfirm === "function") onConfirm();
    });

    modal.show();
}

/*Password Eye Toggle */
function togglePwEye(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isText = input.type === "text";
    input.type = isText ? "password" : "text";
    var icon = btn.querySelector("i");
    if (icon) icon.className = isText ? "fa fa-eye" : "fa fa-eye-slash";
}

/*Toast Alerts */
function showAlert(type, message, detail) {
    const isSuccess = type === "success";
    const alertEl = document.getElementById(
        isSuccess ? "admSuccessAlert" : "admErrorAlert",
    );
    const textEl = document.getElementById(
        isSuccess ? "admSuccessAlertText" : "admErrorAlertText",
    );
    if (!alertEl || !textEl) return;
    textEl.innerHTML = message + (detail ? detail : "");
    alertEl.classList.add("show");
    clearTimeout(alertEl._timer);
    alertEl._timer = setTimeout(function () {
        alertEl.classList.remove("show");
    }, 5000);
}