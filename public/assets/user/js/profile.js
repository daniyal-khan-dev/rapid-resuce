/* ── Tab → URL hash + title + breadcrumb sync ── */
const tabLinks = document.querySelectorAll(
    "#v-pills-tab .nav-link[data-rr-hash]",
);

function hashToPath(hash) {
    const id = hash.replace("#", "");
    return id === "profile" ? "/profile" : "/" + id;
}

function applyTabMeta(link) {
    const hash = link.getAttribute("data-rr-hash");
    const title = link.getAttribute("data-rr-title");
    const label = link.getAttribute("data-rr-label");
    history.replaceState(null, "", hashToPath(hash));
    document.title = title;
    const elTitle = document.getElementById("breadcrumbTitle");
    const elText = document.getElementById("breadcrumbText");
    if (elTitle) elTitle.textContent = label;
    if (elText) elText.textContent = label;
}

tabLinks.forEach((link) => {
    // Prevent browser from writing the href hash (#v-pills-...) to the URL bar
    link.addEventListener("click", function (e) {
        e.preventDefault();
        bootstrap.Tab.getOrCreateInstance(link).show();
    });
    link.addEventListener("shown.bs.tab", () => applyTabMeta(link));
});

window.addEventListener("load", function () {
    const hash = window.location.hash;
    const path = window.location.pathname;

    let match = null;

    if (path && path !== "/profile") {
        const pathAsHash = "#" + path.replace(/^\//, "");
        match = [...tabLinks].find(
            (l) => l.getAttribute("data-rr-hash") === pathAsHash,
        );
    }
    if (!match && hash) {
        match = [...tabLinks].find(
            (l) => l.getAttribute("data-rr-hash") === hash,
        );
    }

    if (!match) return;
    const bsTab = new bootstrap.Tab(match);
    bsTab.show();
    applyTabMeta(match);
});

/* ── Photo preview ── */
var photoInput = document.getElementById("photoUploadInput");
if (photoInput) {
    photoInput.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            var prev = document.getElementById("photoPreview");
            var avatar = document.getElementById("profileAvatarDisplay");
            if (prev) prev.src = e.target.result;
            if (avatar) avatar.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

/* INLINE VALIDATION HELPERS */
function allowOnlyAlphabets(input) {
    input.value = input.value.replace(/[^a-zA-Z\s]/g, "");
}

/* Issue 8 — medical fields: only alphabets, spaces and commas */
function allowMedField(input) {
    input.value = input.value.replace(/[^a-zA-Z\s,]/g, "");
}

function validateUsername(input) {
    input.value = input.value.replace(/[^a-zA-Z0-9_.]/g, "");
    clearFieldFeedback("username_feedback", input);
}

function validatePakPhone(input) {
    let phone = input.value.replace(/\D/g, "");
    if (phone.length > 11) phone = phone.slice(0, 11);
    input.value = phone;

    const regex = /^03[0-9]{9}$/;

    const wrapper = input.closest(".rr-field");
    const error = wrapper ? wrapper.querySelector(".text-danger") : null;

    if (phone === "") {
        if (error) error.innerText = "";
        input.style.borderColor = "";
        clearFieldFeedback("phone_feedback", input);
        return;
    }

    if (!regex.test(phone)) {
        if (error) error.innerText = "Enter valid Pakistani number (03XXXXXXXXX)";
        input.style.borderColor = "red";
        clearFieldFeedback("phone_feedback", input);
    } else {
        if (error) error.innerText = "";
        input.style.borderColor = "";
    }
}

function validateNewPwd(input) {
    var val = input.value;
    var err = document.getElementById("new_pwd_error");
    if (!err) return;
    if (val === "") { err.innerText = ""; input.style.borderColor = ""; return; }
    if (val.length < 7) {
        err.innerText = "Password must be at least 7 characters.";
        input.style.borderColor = "red";
    } else {
        err.innerText = "";
        input.style.borderColor = "green";
    }
    validateConfirmNewPwd(document.getElementById("confirm_new_pwd"));
}

function validateConfirmNewPwd(input) {
    if (!input) return;
    var pwd     = document.getElementById("new_pwd");
    var err     = document.getElementById("confirm_new_pwd_error");
    if (!err) return;
    if (input.value === "") { err.innerText = ""; input.style.borderColor = ""; return; }
    if (pwd && input.value !== pwd.value) {
        err.innerText = "Passwords do not match.";
        input.style.borderColor = "red";
    } else {
        err.innerText = "";
        input.style.borderColor = "green";
    }
}

/* ── Password eye toggle ── */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}


/* AVAILABILITY CHECK — real-time */
const _checkTimers  = {};
const _checkResults = { username: null, email: null, phone: null };

function clearFieldFeedback(feedbackId, inputEl) {
    const el = document.getElementById(feedbackId);
    if (!el) return;
    el.innerText = "";
    el.style.display = "none";
    if (inputEl) inputEl.style.borderColor = "";
}

function showFieldFeedback(feedbackId, available, message, inputEl) {
    const el = document.getElementById(feedbackId);
    if (!el) return;

    if (available === null || message === "") {
        el.innerText = "";
        el.style.display = "none";
        if (inputEl) inputEl.style.borderColor = "";
        return;
    }

    el.style.display = "block";

    if (available) {
        el.innerHTML = '<i class="fa fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>' + '<span style="color:#16a34a;">' + message + '</span>';
        if (inputEl) inputEl.style.borderColor = "#16a34a";
    } else {
        el.innerHTML = '<i class="fa fa-circle-xmark" style="color:#b91c1c;margin-right:4px;"></i>' + '<span style="color:#b91c1c;">' + message + '</span>';
        if (inputEl) inputEl.style.borderColor = "#b91c1c";
    }
}

function checkAvailability(field, value, feedbackId, inputEl) {
    clearTimeout(_checkTimers[field]);

    if (!value || value.trim() === "") {
        clearFieldFeedback(feedbackId, inputEl);
        _checkResults[field] = null;
        return;
    }

    el = document.getElementById(feedbackId);
    if (el) {
        el.style.display = "block";
        el.innerHTML = '<i class="fa fa-spinner fa-spin" style="color:#6b7280;margin-right:4px;"></i>' + '<span style="color:#6b7280;">Checking…</span>';
    }

    _checkTimers[field] = setTimeout(function () {
        $.ajax({
            url:  window.routes.checkAvail,
            type: "POST",
            data: { field: field, value: value.trim() },
            headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
            success: function (response) {
                _checkResults[field] = response.available;
                showFieldFeedback(feedbackId, response.available, response.message || "", inputEl);
            },
            error: function () {
                clearFieldFeedback(feedbackId, inputEl);
                _checkResults[field] = null;
            }
        });
    }, 500);
}

/* Attach blur listeners once DOM is ready */
document.addEventListener("DOMContentLoaded", function () {
    var usernameInput = document.getElementById("edit_username");
    var phoneInput    = document.getElementById("edit_phone");

    if (usernameInput) {
        usernameInput.addEventListener("blur", function () {
            var usernameRegex = /^[a-zA-Z][a-zA-Z0-9_]{3,14}$/;
            if (this.value.trim() === "" || !usernameRegex.test(this.value.trim())) return;
            checkAvailability("username", this.value, "username_feedback", this);
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener("blur", function () {
            var regex = /^03[0-9]{9}$/;
            if (this.value.trim() === "" || !regex.test(this.value.trim())) return;
            checkAvailability("phone", this.value, "phone_feedback", this);
        });
    }

    var editModal = document.getElementById("editModal");
    if (editModal) {
        editModal.addEventListener("show.bs.modal", function () {
            clearFieldFeedback("username_feedback", document.getElementById("edit_username"));
            clearFieldFeedback("phone_feedback",    document.getElementById("edit_phone"));
            _checkResults.username = null;
            _checkResults.phone    = null;
        });
    }
});

/* ── EDIT PROFILE ── */
function submitEditProfile() {
    var phoneErr = document.getElementById("phone_error");
    if (phoneErr && phoneErr.innerText !== "") return;

    validateForm({
        formId: "editProfileForm",
        fields: [
            { id: "edit_username",   message: "Please enter user name.", maxLength: 30 },
            { id: "edit_first_name", message: "Please enter first name.", maxLength: 15 },
            { id: "edit_last_name",  message: "Please enter last name.", maxLength: 15 },
            { id: "edit_phone",      message: "Please enter phone no." },
            { id: "edit_dob",        message: "Please select date of birth" },
            { id: "edit_address",    message: "Please enter address." },
        ],
        btn: "update-Btn",
        btnTxt: "Profile",
        onSuccess: function () {
            submitFormData({
                formId: "editProfileForm",
                url: window.routes.updateProfile,
                successMessage: "Profile updated successfully!",
                onSuccess: function (response) {
                    if (response && response.pfp) {
                        var avatarEl   = document.getElementById("profileAvatarDisplay");
                        var previewEl  = document.getElementById("photoPreview");
                        var navAvatars = document.querySelectorAll(".rr-user-menu img, .rr-user-dropdown__avatar");
                        if (avatarEl)  avatarEl.src  = response.pfp;
                        if (previewEl) previewEl.src = response.pfp;
                        navAvatars.forEach(function (img) { img.src = response.pfp; });
                    }
                    if (response && response.userdata) {
                        var u = response.userdata;
                        var d = response.data || {};
                        var fullName = ((d.first_name || '') + ' ' + (d.last_name || '')).trim() || u.username;
                        var navSpan = document.querySelector('.rr-user-menu span');
                        if (navSpan) navSpan.textContent = fullName;
                        var dropName  = document.querySelector('.rr-user-dropdown__info strong');
                        if (dropName)  dropName.textContent  = fullName;
                        var cardH3 = document.querySelector('.rr-profile-card-header h3');
                        if (cardH3) cardH3.textContent = fullName;
                        var cells = document.querySelectorAll('.rr-info-cell');
                        cells.forEach(function (cell) {
                            var lbl = cell.querySelector('.rr-info-cell__label');
                            var val = cell.querySelector('.rr-info-cell__value');
                            if (!lbl || !val) return;
                            var label = lbl.textContent.trim().toLowerCase();
                            if (label === 'phone')    val.textContent = d.phone    || val.textContent;
                            if (label === 'username') val.textContent = u.username || val.textContent;
                            if (label === 'name')     val.textContent = fullName;
                        });
                        function setVal(id, v) { var el = document.getElementById(id); if (el && v) el.value = v; }
                        setVal('edit_username',   u.username);
                        setVal('edit_first_name', d.first_name);
                        setVal('edit_last_name',  d.last_name);
                        setVal('edit_phone',      d.phone);
                        var dobVal = d.date_of_birth ? d.date_of_birth.toString().slice(0, 10) : '';
                        setVal('edit_dob', dobVal);
                        setVal('edit_address',    d.address);
                    }
                    var btn = document.getElementById("update-Btn");
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check"></i> Save Changes'; }
                    var modal = bootstrap.Modal.getInstance(document.getElementById("editModal"));
                    if (modal) modal.hide();
                },
            });
        },
    });
}

/* ═══════════════════════════════════════════════════
   CHANGE EMAIL FLOW
═══════════════════════════════════════════════════ */
var _emailChangeResendTimer = null;

function openChangeEmailModal() {
    var editBsModal = bootstrap.Modal.getInstance(document.getElementById("editModal"));
    if (editBsModal) editBsModal.hide();

    setTimeout(function () {
        var f = document.getElementById("new_email");
        if (f) { f.value = ""; f.style.borderColor = ""; }
        var errEl = document.getElementById("new_email_error");
        if (errEl) { errEl.style.display = "none"; errEl.innerText = ""; }
        var codeEl = document.getElementById("email_change_code");
        if (codeEl) { codeEl.value = ""; codeEl.style.borderColor = ""; }
        var codeErr = document.getElementById("email_change_code_error");
        if (codeErr) { codeErr.style.display = "none"; codeErr.innerText = ""; }
        document.getElementById("changeEmailStep1").classList.remove("d-none");
        document.getElementById("changeEmailStep2").classList.add("d-none");
        var verBtn = document.getElementById("verifyEmailChangeBtn");
        if (verBtn) verBtn.disabled = true;
        var m = new bootstrap.Modal(document.getElementById("changeEmailModal"));
        m.show();
    }, 350);
}

function emailChangeGoBack() {
    document.getElementById("changeEmailStep2").classList.add("d-none");
    document.getElementById("changeEmailStep1").classList.remove("d-none");
    clearInterval(_emailChangeResendTimer);
}

function sendEmailChangeCode() {
    var emailEl = document.getElementById("new_email");
    var errEl   = document.getElementById("new_email_error");
    var btn     = document.getElementById("sendEmailCodeBtn");
    var email   = emailEl ? emailEl.value.trim() : "";

    errEl.style.display = "none"; errEl.innerText = "";

    if (!email) {
        errEl.innerText = "Please enter a new email address."; errEl.style.display = "block"; return;
    }
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errEl.innerText = "Please enter a valid email address."; errEl.style.display = "block"; return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';

    $.ajax({
        url:  window.routes.profileEmailSendCode,
        type: "POST",
        data: { new_email: email },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Send Verification Code';
            document.getElementById("pendingEmailDisplay").textContent = email;
            document.getElementById("changeEmailStep1").classList.add("d-none");
            document.getElementById("changeEmailStep2").classList.remove("d-none");
            var limitMsg = document.getElementById("emailResendLimitMsg");
            if (limitMsg) { limitMsg.style.display = "none"; limitMsg.innerText = ""; }
            startEmailResendCooldown();
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Send Verification Code';
            var res = xhr.responseJSON;
            var msg = (res && res.message) ? res.message : "Failed to send code. Please try again.";
            if (res && res.errors && res.errors.new_email) msg = res.errors.new_email[0];
            errEl.innerText = msg; errEl.style.display = "block";
        }
    });
}

function startEmailResendCooldown() {
    var btn     = document.getElementById("resendEmailChangeBtn");
    var timerEl = document.getElementById("resendEmailChangeTimer");
    var seconds = 60;
    if (btn) btn.disabled = true;
    if (timerEl) timerEl.textContent = seconds;
    clearInterval(_emailChangeResendTimer);
    _emailChangeResendTimer = setInterval(function () {
        seconds--;
        if (timerEl) timerEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(_emailChangeResendTimer);
            if (btn) btn.disabled = false;
            if (timerEl) timerEl.textContent = "0";
        }
    }, 1000);
}

function resendEmailChangeCode() {
    var btn = document.getElementById("resendEmailChangeBtn");
    if (btn) { btn.disabled = true; }

    $.ajax({
        url:  window.routes.profileEmailResend,
        type: "POST",
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            var limitMsg = document.getElementById("emailResendLimitMsg");
            if (limitMsg) { limitMsg.style.display = "none"; }
            startEmailResendCooldown();
            showAlert("success", "Verification code resent to your new email.");
        },
        error: function (xhr) {
            if (btn) btn.disabled = false;
            var res = xhr.responseJSON;
            if (res && res.limit_reached) {
                var limitMsg = document.getElementById("emailResendLimitMsg");
                if (limitMsg) { limitMsg.innerText = res.message; limitMsg.style.display = "block"; }
                if (btn) btn.disabled = true;
            } else {
                showAlert("error", (res && res.message) ? res.message : "Failed to resend code.");
            }
        }
    });
}

function onEmailChangeCodeInput(input) {
    input.value = input.value.replace(/\D/g, "").slice(0, 6);
    var btn = document.getElementById("verifyEmailChangeBtn");
    if (btn) btn.disabled = input.value.length !== 6;
}

function verifyEmailChange() {
    var codeEl  = document.getElementById("email_change_code");
    var errEl   = document.getElementById("email_change_code_error");
    var btn     = document.getElementById("verifyEmailChangeBtn");
    var code    = codeEl ? codeEl.value.trim() : "";

    if (errEl) { errEl.style.display = "none"; errEl.innerText = ""; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying…';

    $.ajax({
        url:  window.routes.profileEmailVerify,
        type: "POST",
        data: { code: code },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            btn.disabled = false;
            btn.innerHTML = 'Verify & Update Email';
            clearInterval(_emailChangeResendTimer);
            var bsModal = bootstrap.Modal.getInstance(document.getElementById("changeEmailModal"));
            if (bsModal) bsModal.hide();
            showAlert("success", res.message || "Email updated successfully.");
            var newEmail = res.new_email || "";
            if (newEmail) {
                var displayEl = document.getElementById("profileEmailDisplay");
                if (displayEl) displayEl.textContent = newEmail;
                var editDisplayEl = document.getElementById("editModalEmailDisplay");
                if (editDisplayEl) editDisplayEl.textContent = newEmail;
                var dropEmail = document.querySelector('.rr-user-dropdown__info span');
                if (dropEmail) dropEmail.textContent = newEmail;
                var badgeHtml = '<i class="fa fa-circle-check"></i> Verified';
                var badgeStyle = 'font-size:0.74rem;color:#16a34a;font-weight:700;display:inline-flex;align-items:center;gap:4px;';
                var profileBadge = document.getElementById("profileEmailBadge");
                if (profileBadge) { profileBadge.innerHTML = badgeHtml; profileBadge.style.cssText = badgeStyle; }
                var modalBadge = document.getElementById("editModalEmailBadge");
                if (modalBadge) { modalBadge.innerHTML = '<i class="fa fa-circle-check"></i> Verified'; modalBadge.style.cssText = 'font-size:0.70rem;color:#16a34a;font-weight:700;white-space:nowrap;flex-shrink:0;'; }
            }
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> Verify & Update Email';
            var res = xhr.responseJSON;
            var msg = (res && res.message) ? res.message : "Verification failed. Please try again.";
            if (errEl) { errEl.innerText = msg; errEl.style.display = "block"; }
        }
    });
}

/* ═══════════════════════════════════════════════════
   CHANGE PASSWORD FLOW
═══════════════════════════════════════════════════ */
var _pwdChangeResendTimer = null;

function openChangePasswordModal() {
    var editBsModal = bootstrap.Modal.getInstance(document.getElementById("editModal"));
    if (editBsModal) editBsModal.hide();

    setTimeout(function () {
        ["new_pwd", "confirm_new_pwd"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.value = ""; el.style.borderColor = ""; el.type = "password"; }
        });
        ["new_pwd_error", "confirm_new_pwd_error"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.innerText = "";
        });
        var codeEl = document.getElementById("pwd_change_code");
        if (codeEl) { codeEl.value = ""; codeEl.style.borderColor = ""; }
        var codeErr = document.getElementById("pwd_change_code_error");
        if (codeErr) { codeErr.style.display = "none"; codeErr.innerText = ""; }
        document.getElementById("changePwdStep1").classList.remove("d-none");
        document.getElementById("changePwdStep2").classList.add("d-none");
        var verBtn = document.getElementById("verifyPwdChangeBtn");
        if (verBtn) verBtn.disabled = true;
        var m = new bootstrap.Modal(document.getElementById("changePasswordModal"));
        m.show();
    }, 350);
}

function pwdChangeGoBack() {
    document.getElementById("changePwdStep2").classList.add("d-none");
    document.getElementById("changePwdStep1").classList.remove("d-none");
    clearInterval(_pwdChangeResendTimer);
}

function sendPasswordChangeCode() {
    var pwdEl     = document.getElementById("new_pwd");
    var cPwdEl    = document.getElementById("confirm_new_pwd");
    var pwdErrEl  = document.getElementById("new_pwd_error");
    var cPwdErrEl = document.getElementById("confirm_new_pwd_error");
    var btn       = document.getElementById("sendPwdCodeBtn");

    if (pwdErrEl)  pwdErrEl.innerText  = "";
    if (cPwdErrEl) cPwdErrEl.innerText = "";

    var pwd  = pwdEl  ? pwdEl.value  : "";
    var cPwd = cPwdEl ? cPwdEl.value : "";
    var valid = true;

    if (!pwd || pwd.length < 7) {
        if (pwdErrEl) pwdErrEl.innerText = "Password must be at least 7 characters.";
        if (pwdEl) pwdEl.style.borderColor = "red";
        valid = false;
    }
    if (cPwd !== pwd) {
        if (cPwdErrEl) cPwdErrEl.innerText = "Passwords do not match.";
        if (cPwdEl) cPwdEl.style.borderColor = "red";
        valid = false;
    }
    if (!valid) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';

    $.ajax({
        url:  window.routes.profilePasswordSendCode,
        type: "POST",
        data: { new_password: pwd, confirm_password: cPwd },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Send Verification Code';
            var maskedEl = document.getElementById("pwdChangeMaskedEmail");
            if (maskedEl) maskedEl.textContent = res.masked_email || "";
            document.getElementById("changePwdStep1").classList.add("d-none");
            document.getElementById("changePwdStep2").classList.remove("d-none");
            var limitMsg = document.getElementById("pwdResendLimitMsg");
            if (limitMsg) { limitMsg.style.display = "none"; limitMsg.innerText = ""; }
            startPwdResendCooldown();
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-paper-plane"></i> Send Verification Code';
            var res = xhr.responseJSON;
            if (res && res.errors) {
                if (res.errors.new_password && pwdErrEl)     pwdErrEl.innerText  = res.errors.new_password[0];
                if (res.errors.confirm_password && cPwdErrEl) cPwdErrEl.innerText = res.errors.confirm_password[0];
            } else {
                showAlert("error", (res && res.message) ? res.message : "Failed to send code.");
            }
        }
    });
}

function startPwdResendCooldown() {
    var btn     = document.getElementById("resendPwdChangeBtn");
    var timerEl = document.getElementById("resendPwdTimer");
    var seconds = 60;
    if (btn) btn.disabled = true;
    if (timerEl) timerEl.textContent = seconds;
    clearInterval(_pwdChangeResendTimer);
    _pwdChangeResendTimer = setInterval(function () {
        seconds--;
        if (timerEl) timerEl.textContent = seconds;
        if (seconds <= 0) {
            clearInterval(_pwdChangeResendTimer);
            if (btn) btn.disabled = false;
            if (timerEl) timerEl.textContent = "0";
        }
    }, 1000);
}

function resendPasswordChangeCode() {
    var btn = document.getElementById("resendPwdChangeBtn");
    if (btn) btn.disabled = true;

    $.ajax({
        url:  window.routes.profilePasswordResend,
        type: "POST",
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            var limitMsg = document.getElementById("pwdResendLimitMsg");
            if (limitMsg) { limitMsg.style.display = "none"; }
            startPwdResendCooldown();
            showAlert("success", "Verification code resent to your registered email.");
        },
        error: function (xhr) {
            if (btn) btn.disabled = false;
            var res = xhr.responseJSON;
            if (res && res.limit_reached) {
                var limitMsg = document.getElementById("pwdResendLimitMsg");
                if (limitMsg) { limitMsg.innerText = res.message; limitMsg.style.display = "block"; }
                if (btn) btn.disabled = true;
            } else {
                showAlert("error", (res && res.message) ? res.message : "Failed to resend code.");
            }
        }
    });
}

function onPwdChangeCodeInput(input) {
    input.value = input.value.replace(/\D/g, "").slice(0, 6);
    var btn = document.getElementById("verifyPwdChangeBtn");
    if (btn) btn.disabled = input.value.length !== 6;
}

function verifyAndChangePassword() {
    var codeEl = document.getElementById("pwd_change_code");
    var errEl  = document.getElementById("pwd_change_code_error");
    var btn    = document.getElementById("verifyPwdChangeBtn");
    var code   = codeEl ? codeEl.value.trim() : "";

    if (errEl) { errEl.style.display = "none"; errEl.innerText = ""; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying…';

    $.ajax({
        url:  window.routes.profilePasswordChange,
        type: "POST",
        data: { code: code },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (res) {
            btn.disabled = false;
            btn.innerHTML = ' Verify & Change Password';
            clearInterval(_pwdChangeResendTimer);
            var bsModal = bootstrap.Modal.getInstance(document.getElementById("changePasswordModal"));
            if (bsModal) bsModal.hide();
            showAlert("success", res.message || "Password changed successfully.");
        },
        error: function (xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> Verify & Change Password';
            var res = xhr.responseJSON;
            var msg = (res && res.message) ? res.message : "Verification failed. Please try again.";
            if (errEl) { errEl.innerText = msg; errEl.style.display = "block"; }
        }
    });
}

/* ── MEDICAL CARD ── */
function submitMedicalCard() {
    validateForm({
        formId: "addMedicalForm",
        fields: [
            { id: "blood_type",      message: "Please select a blood type.", skipIf: "0" },
            { id: "medical_history", message: "Please enter medical history." },
            { id: "allergies",       message: "Please enter allergies." },
            { id: "medications",     message: "Please enter medications." },
            { id: "contact_name",    message: "Please enter emergency contact person full name." },
            { id: "relation",        message: "Please enter emergency contact person relation." },
            { id: "contact_phone",   message: "Please enter emergency contact person phone no." },
        ],
        btn: "med-save-Btn",
        btnTxt: "Medical Card",
        onSuccess: function () {
            /* Issue 3 — validate contact phone format on submit */
            var phoneVal = document.getElementById("contact_phone").value.trim();
            if (phoneVal && !/^03[0-9]{9}$/.test(phoneVal)) {
                showAlert("error", "Please enter a valid Pakistani phone number (03XXXXXXXXX).");
                var btn = document.getElementById("med-save-Btn");
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-heart-pulse"></i> Save Medical Card'; }
                return;
            }
            submitFormData({
                formId: "addMedicalForm",
                url: window.routes.storeMedicalCard,
                successMessage: "Medical card saved successfully!",
                onSuccess: function (response) {
                    var btn = document.getElementById("med-save-Btn");
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-heart-pulse"></i> Save Medical Card'; }
                    var modal = bootstrap.Modal.getInstance(document.getElementById("addModal"));
                    if (modal) modal.hide();
                    if (response && response.card) {
                        updateMedicalCardDisplay(response.card);
                    }
                },
            });
        },
    });
}

function deleteMedicalCard() {
    var modal = new bootstrap.Modal(document.getElementById('deleteMedCardModal'));
    modal.show();
}

function confirmDeleteMedicalCard() {
    var bsModal = bootstrap.Modal.getInstance(document.getElementById('deleteMedCardModal'));
    if (bsModal) bsModal.hide();
    var token = document.querySelector('input[name="_token"]');
    setTimeout(function () {
        $.ajax({
            url: window.routes.deleteMedicalCard,
            type: "POST",
            data: { _token: token ? token.value : "" },
            headers: { "X-CSRF-TOKEN": token ? token.value : "" },
            success: function () {
                showAlert("success", "Medical card deleted successfully.");
                var filled = document.getElementById("medCardFilled");
                var empty  = document.getElementById("medCardEmpty");
                if (filled) filled.classList.add("d-none");
                if (empty)  empty.classList.remove("d-none");
                var addBtn = document.getElementById("medCardAddBtn");
                if (addBtn) { addBtn.innerHTML = '<i class="fa fa-plus"></i> Add Medical Card'; addBtn.setAttribute("data-bs-target", "#addModal"); }
                var medModal = document.getElementById("addModal");
                if (medModal) medModal.querySelector(".modal-title").textContent = "Add Medical Card";
                document.getElementById("addMedicalForm").reset();
            },
            error: function () {
                showAlert("error", "Something went wrong. Please try again.");
            },
        });
    }, 400);
}

function updateMedicalCardDisplay(card) {
    var empty  = document.getElementById("medCardEmpty");
    var filled = document.getElementById("medCardFilled");
    if (empty)  empty.classList.add("d-none");
    if (filled) filled.classList.remove("d-none");

    var el = function (id) { return document.getElementById(id); };
    if (el("mc_blood_type")) el("mc_blood_type").textContent = card.blood_type || "—";
    if (el("mc_name")) el("mc_name").textContent = (window.profileName || "");
    if (el("mc_history")) el("mc_history").textContent = card.medical_history || "—";
    if (el("mc_allergies")) el("mc_allergies").textContent = card.allergies || "—";
    if (el("mc_medications")) el("mc_medications").textContent = card.medications || "—";
    if (el("mc_contact_name")) el("mc_contact_name").textContent = card.contact_name || "—";
    /* Issue 4 — update both contact phone elements immediately */
    if (el("mc_contact_phone")) el("mc_contact_phone").textContent = card.contact_phone || "—";
    if (el("mc_contact_phone2")) el("mc_contact_phone2").textContent = card.contact_phone || "—";
    if (el("mc_relation")) el("mc_relation").textContent = card.contact_name || "—";
    if (el("mc_relation_label")) el("mc_relation_label").textContent= card.relation || "";

    var addBtn = document.getElementById("medCardAddBtn");
    if (addBtn) addBtn.innerHTML = '<i class="fa fa-pen"></i> Update Medical Card';
    var modalTitle = document.querySelector('#addModal .modal-title');
    if (modalTitle) modalTitle.textContent = "Update Medical Card";

    prefillMedicalForm(card);
}

function prefillMedicalForm(card) {
    var fields = {
        blood_type:      card.blood_type      || "",
        med_dob:         card.dob             || "",
        medical_history: card.medical_history || "",
        allergies:       card.allergies       || "",
        medications:     card.medications     || "",
        contact_name:    card.contact_name    || "",
        relation:        card.relation        || "",
        contact_phone:   card.contact_phone   || "",
    };
    Object.keys(fields).forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.value = fields[id];
    });
}

function openBookingDetail(btn) {
    var d = btn.dataset;
    document.getElementById('bd_type').textContent      = d.type      || '—';
    document.getElementById('bd_address').textContent   = d.address   || '—';
    document.getElementById('bd_hospital').textContent  = d.hospital  || '—';
    document.getElementById('bd_mobile').textContent    = d.mobile    || '—';
    document.getElementById('bd_ambulance').textContent = d.ambulance || '—';
    document.getElementById('bd_driver').textContent    = d.driver    || '—';
    document.getElementById('bd_date').textContent      = d.date      || '—';

    var statusEl = document.getElementById('bd_status');
    var status   = d.status || '—';
    statusEl.textContent = status;
    statusEl.style.color = status.toLowerCase() === 'completed' ? '#166534' : '#374151';

    var completedWrap = document.getElementById('bd_completed_wrap');
    var completedEl   = document.getElementById('bd_completed');
    if (d.completed && d.completed !== '—') {
        completedEl.textContent = d.completed;
        completedWrap.style.display = 'block';
    } else {
        completedWrap.style.display = 'none';
    }

    var notesWrap = document.getElementById('bd_notes_wrap');
    var notesEl   = document.getElementById('bd_notes');
    if (d.notes && d.notes.trim() !== '') {
        notesEl.textContent = d.notes;
        notesWrap.style.display = 'block';
    } else {
        notesWrap.style.display = 'none';
    }

    document.getElementById('bdModalSubtitle').textContent = 'Request #' + d.id + ' · ' + d.date;

    var modal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    modal.show();
}

/* ── Real-time booking status updates via Reverb ─────────────────────────
   Subscribes once per visible booking row to the per-request public channel
   (emergency.{id}) and updates only the affected badge — no page reload,
   no polling, no duplicate subscriptions.                                 */
(function () {
    'use strict';

    var STATUS_LABELS = {
        '1': 'Pending',
        '2': 'Dispatched',
        '3': 'On Way',
        '4': 'Arrived',
        '5': 'Transporting',
        '6': 'Completed',
        '7': 'Cancelled',
        '8': 'Awaiting Acceptance'
    };

    var STATUS_STYLES = {
        '1': 'background:rgba(245,158,11,0.12);color:#b45309;',
        '2': 'background:rgba(59,130,246,0.12);color:#1d4ed8;',
        '3': 'background:rgba(139,92,246,0.12);color:#6d28d9;',
        '4': 'background:rgba(20,184,166,0.12);color:#0f766e;',
        '5': 'background:rgba(249,115,22,0.12);color:#c2410c;',
        '6': 'background:rgba(34,197,94,0.12);color:#166534;',
        '7': 'background:rgba(107,114,128,0.12);color:#374151;',
        '8': 'background:rgba(245,158,11,0.12);color:#b45309;'
    };

    var BADGE_BASE = 'padding:3px 10px;border-radius:20px;font-size:0.73rem;font-weight:700;';

    function bindBookingRows() {
        if (!window.pusher) { setTimeout(bindBookingRows, 200); return; }

        document.querySelectorAll('[id^="rrBkRow_"]').forEach(function (row) {
            var id = row.id.replace('rrBkRow_', '');
            if (!id) return;

            var ch = window.pusher.subscribe('emergency.' + id);
            ch.bind('emergency-request-status-changed', function (data) {
                var status = String(data.status);

                // Keep data-status in sync (used by openBookingDetail)
                row.dataset.status = status;

                // Update the visible status badge
                var badge = document.getElementById('rrBkBadge_' + id);
                if (badge) {
                    badge.textContent  = STATUS_LABELS[status] || status;
                    badge.style.cssText = BADGE_BASE + (STATUS_STYLES[status] || 'background:#f3f4f6;color:#374151;');
                }
            });
        });
    }

    // Only run when the bookings tab is present on the page
    if (document.querySelector('[id^="rrBkRow_"]') ||
        document.getElementById('v-pills-bookings')) {
        bindBookingRows();
    }
})();