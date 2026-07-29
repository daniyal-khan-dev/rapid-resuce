/* UTILITY: password eye toggle */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector("i");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

/* INLINE VALIDATION HELPERS */
function allowOnlyAlphabets(input) {
    input.value = input.value.replace(/[^a-zA-Z]/g, "");
}

function validateUsername(input) {
    input.value = input.value.toLowerCase().replace(/[^a-z0-9_.]/g, "");
    clearFieldFeedback("username_feedback", input);
}

function validatePakPhone(input) {
    let phone = input.value.replace(/\D/g, "");
    if (phone.length > 11) phone = phone.slice(0, 11);
    input.value = phone;

    const error = document.getElementById("phone_error");
    const regex = /^03[0-9]{9}$/;

    if (phone === "") {
        error.innerText = "";
        input.style.borderColor = "";
        clearFieldFeedback("phone_feedback", input);
        return;
    }

    if (!regex.test(phone)) {
        error.innerText = "Enter valid Pakistani number (03XXXXXXXXX)";
        input.style.borderColor = "red";
        clearFieldFeedback("phone_feedback", input);
    } else {
        error.innerText = "";
        input.style.borderColor = "";
    }
}

function validatePassword(input) {
    const password = input.value;
    const error = document.getElementById("password_error");
    const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{8,}$/;

    if (password === "") {
        error.innerText = "";
        input.style.borderColor = "";
        return;
    }

    if (!regex.test(password)) {
        error.innerText = "Password must contain alphabet, number, special character & 8+ chars";
        input.style.borderColor = "red";
    } else {
        error.innerText = "";
        input.style.borderColor = "green";
    }
    validateConfirmPassword();
}

function validateConfirmPassword() {
    const password = document.getElementById("reg_password").value;
    const confirmPassword = document.getElementById("regCPassword").value;
    const error = document.getElementById("confirm_password_error");
    const input = document.getElementById("regCPassword");

    if (confirmPassword === "") {
        error.innerText = "";
        input.style.borderColor = "";
        return;
    }

    if (password !== confirmPassword) {
        error.innerText = "Passwords do not match";
        input.style.borderColor = "red";
    } else {
        error.innerText = "";
        input.style.borderColor = "green";
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
        el.innerHTML = '<i class="fa fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>' +
                       '<span style="color:#16a34a;">' + message + '</span>';
        if (inputEl) inputEl.style.borderColor = "#16a34a";
    } else {
        el.innerHTML = '<i class="fa fa-circle-xmark" style="color:#b91c1c;margin-right:4px;"></i>' +
                       '<span style="color:#b91c1c;">' + message + '</span>';
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

    const el = document.getElementById(feedbackId);
    if (el) {
        el.style.display = "block";
        el.innerHTML = '<i class="fa fa-spinner fa-spin" style="color:#6b7280;margin-right:4px;"></i>' +
                       '<span style="color:#6b7280;">Checking…</span>';
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

/* tach blur listeners once DOM is read */
document.addEventListener("DOMContentLoaded", function () {
    const usernameInput = document.getElementById("username");
    const emailInput    = document.getElementById("email");
    const phoneInput    = document.getElementById("phone");

    if (usernameInput) {
        usernameInput.addEventListener("blur", function () {
            const usernameRegex = /^[a-z][a-z0-9_.]{2,14}$/;
            if (this.value.trim() === "" || !usernameRegex.test(this.value.trim())) return;
            checkAvailability("username", this.value, "username_feedback", this);
        });
    }

    if (emailInput) {
        emailInput.addEventListener("blur", function () {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (this.value.trim() === "" || !emailRegex.test(this.value.trim())) return;
            checkAvailability("email", this.value, "email_feedback", this);
        });
    }

    if (phoneInput) {
        phoneInput.addEventListener("blur", function () {
            const regex = /^03[0-9]{9}$/;
            if (this.value.trim() === "" || !regex.test(this.value.trim())) return;
            checkAvailability("phone", this.value, "phone_feedback", this);
        });
    }
});

/* FINAL SIGNUP VALIDATION + SUBMIT */
function signUpValidationCheck() {
    const firstName       = document.getElementById("first_name");
    const lastName        = document.getElementById("last_name");
    const username        = document.getElementById("username");
    const email           = document.getElementById("email");
    const phone           = document.getElementById("phone");
    const password        = document.getElementById("reg_password");
    const confirmPassword = document.getElementById("regCPassword");
    const terms           = document.getElementById("terms");
    const signUpBtn       = document.getElementById("signUp-btn");
    const form            = document.getElementById("signUpForm");

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (document.getElementById("phone_error").innerText !== "" ||
        document.getElementById("password_error").innerText !== "" ||
        document.getElementById("confirm_password_error").innerText !== "") {
        return;
    }

    if (_checkResults.username === false) {
        showAlert("error", "Username is already taken. Please choose another.");
        return;
    }
    if (_checkResults.email === false) {
        showAlert("error", "Email is already registered. Please use another.");
        return;
    }
    if (_checkResults.phone === false) {
        showAlert("error", "Phone number is already registered. Please use another.");
        return;
    }

    const emailRegex    = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const usernameRegex = /^[a-z][a-z0-9_.]{2,14}$/;
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{7,}$/;
    let isValid = true;

    [firstName, lastName, username, email, phone, password, confirmPassword].forEach(
        f => f.setCustomValidity("")
    );

    if (firstName.value.trim() === "") {
        firstName.setCustomValidity("Please enter your first name.");
        isValid = false;
    } else if (lastName.value.trim() === "") {
        lastName.setCustomValidity("Please enter your last name.");
        isValid = false;
    } else if (username.value.trim() === "") {
        username.setCustomValidity("Please enter your Username.");
        isValid = false;
    } else if (!usernameRegex.test(username.value.trim())) {
        username.setCustomValidity(
            "Username must start with a lowercase letter and be 3-15 characters. Lowercase letters, numbers, dot and underscore only."
        );
        isValid = false;
    } else if (email.value.trim() === "") {
        email.setCustomValidity("Please enter your email address.");
        isValid = false;
    } else if (!emailRegex.test(email.value.trim())) {
        email.setCustomValidity("Please enter a valid email (e.g., example@gmail.com).");
        isValid = false;
    } else if (phone.value.trim() === "") {
        phone.setCustomValidity("Please enter a phone number.");
        isValid = false;
    } else if (password.value.trim() === "") {
        password.setCustomValidity("Please enter a password.");
        isValid = false;
    } else if (!passwordRegex.test(password.value.trim())) {
        password.setCustomValidity(
            "Password must be at least 7 characters with a letter, number, and special character."
        );
        isValid = false;
    } else if (confirmPassword.value.trim() === "") {
        confirmPassword.setCustomValidity("Please confirm your password.");
        isValid = false;
    } else if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords do not match.");
        isValid = false;
    } else if (!terms.checked) {
        showAlert("error", "You must agree to the Terms & Conditions.");
        isValid = false;
    }

    if (!isValid) {
        form.reportValidity();
        return;
    }

    signUpBtn.disabled = true;
    signUpBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing up…';

    SignUp();
}

function SignUp() {
    const signUpBtn  = document.getElementById("signUp-btn");
    const token      = document.querySelector('input[name="_token"]');
    const formData   = new FormData();
    const imageInput = document.getElementById("pfp");

    formData.append("first_name", document.getElementById("first_name").value.trim());
    formData.append("last_name",  document.getElementById("last_name").value.trim());
    formData.append("username",   document.getElementById("username").value.trim());
    formData.append("email",      document.getElementById("email").value.trim());
    formData.append("phone",      document.getElementById("phone").value.trim());
    formData.append("password",   document.getElementById("reg_password").value);

    if (imageInput.files.length > 0) {
        formData.append("pfp", imageInput.files[0]);
    }

    if (!token || token.value === "") {
        showAlert("error", "Error (75685): Something went wrong. Please refresh the page.");
        return;
    }

    var recaptchaToken = (typeof grecaptcha !== 'undefined' && typeof signupWidgetId !== 'undefined')
        ? grecaptcha.getResponse(signupWidgetId) : '';
    if (!recaptchaToken) {
        var rcErr = document.getElementById('signup-recaptcha-error');
        if (rcErr) rcErr.style.display = 'block';
        signUpBtn.disabled  = false;
        signUpBtn.innerHTML = "Sign Up";
        return;
    }
    var rcErr = document.getElementById('signup-recaptcha-error');
    if (rcErr) rcErr.style.display = 'none';
    formData.append('g-recaptcha-response', recaptchaToken);

    $.ajax({
        url:         window.routes.signSubmit,
        type:        "POST",
        data:        formData,
        contentType: false,
        processData: false,
        headers:     { "X-CSRF-TOKEN": token.value },
        success: function (response) {
            const email = document.getElementById("email").value.trim();

            document.getElementById("signUpForm").reset();
            ["username_feedback", "email_feedback", "phone_feedback"].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.innerText = ""; el.style.display = "none"; }
            });
            _checkResults.username = null;
            _checkResults.email    = null;
            _checkResults.phone    = null;
            signUpBtn.disabled  = false;
            signUpBtn.innerHTML = "Sign Up";

            // Transition to email verification panel
            showEmailVerificationPanel(email);

            // Send verification code
            sendInitialVerificationCode(email);
        },
        error: function (xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                let messages = "";
                for (let field in errors) {
                    messages += "<div>" + errors[field][0] + "</div>";
                }
                showAlert("error", messages);
            } else {
                showAlert("error", "Something went wrong. Please try again.");
            }
            signUpBtn.disabled  = false;
            signUpBtn.innerHTML = "Sign Up";
            if (typeof grecaptcha !== 'undefined' && typeof signupWidgetId !== 'undefined') {
                grecaptcha.reset(signupWidgetId);
            }
        }
    });
}

/* EMAIL VERIFICATION PANEL */
var _verifyEmail      = "";
var _resendCountToday = 0;
var _resendTimerInt   = null;

function showEmailVerificationPanel(email) {
    _verifyEmail = email;

    // Pre-fill and lock email field
    const emailField = document.getElementById("verify_email");
    emailField.value    = email;
    emailField.disabled = true;

    // Reset code input and buttons
    document.getElementById("verification_code").value = "";
    document.getElementById("verifyEmailBtn").disabled  = true;
    document.getElementById("resendCodeBtn").disabled   = true;
    document.getElementById("resendBtnText").innerText  = "Resend Code";
    document.getElementById("resendTimer").style.display = "none";
    document.getElementById("resendLimitMsg").style.display = "none";

    // Show verification panel, hide signup form
    document.getElementById("SignupFormDiv").classList.add("d-none");
    document.getElementById("EmailVerificationDiv").classList.remove("d-none");
}

function sendInitialVerificationCode(email) {
    $.ajax({
        url:  window.routes.sendVerify,
        type: "POST",
        data: { email: email },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function () {
            // Start 60-second cooldown before enabling resend
            startResendCooldown();
        },
        error: function () {
            showAlert("error", "Failed to send verification code. Please try the Resend button.");
            document.getElementById("resendCodeBtn").disabled = false;
        }
    });
}

function onVerificationCodeInput(input) {
    // Only allow digits
    input.value = input.value.replace(/\D/g, "");
    const verifyBtn = document.getElementById("verifyEmailBtn");
    verifyBtn.disabled = (input.value.length < 6);
}

function startResendCooldown() {
    const btn       = document.getElementById("resendCodeBtn");
    const timerSpan = document.getElementById("resendTimer");
    const btnText   = document.getElementById("resendBtnText");

    btn.disabled            = true;
    timerSpan.style.display = "inline";
    btnText.innerText       = "Resend Code";

    let seconds = 60;
    timerSpan.innerText = "(" + seconds + "s)";

    clearInterval(_resendTimerInt);
    _resendTimerInt = setInterval(function () {
        seconds--;
        timerSpan.innerText = "(" + seconds + "s)";
        if (seconds <= 0) {
            clearInterval(_resendTimerInt);
            timerSpan.style.display = "none";
            // Only enable if under daily limit
            if (_resendCountToday < 4) {
                btn.disabled = false;
            } else {
                showResendLimitMessage();
            }
        }
    }, 1000);
}

function showResendLimitMessage() {
    const msg = document.getElementById("resendLimitMsg");
    msg.innerText      = "You have reached the maximum of 4 resend requests for today. Please try again tomorrow.";
    msg.style.display  = "block";
    document.getElementById("resendCodeBtn").disabled = true;
}

function resendVerificationCode() {
    const btn = document.getElementById("resendCodeBtn");
    btn.disabled = true;

    $.ajax({
        url:  window.routes.resendVerify,
        type: "POST",
        data: { email: _verifyEmail },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (response) {
            _resendCountToday = response.resend_count || (_resendCountToday + 1);
            showAlert("success", "A new verification code has been sent to your email.");
            startResendCooldown();
        },
        error: function (xhr) {
            if (xhr.status === 429 && xhr.responseJSON && xhr.responseJSON.limit_reached) {
                _resendCountToday = 4;
                showResendLimitMessage();
            } else {
                showAlert("error", "Failed to resend code. Please try again.");
                btn.disabled = false;
            }
        }
    });
}

function submitVerifyEmail() {
    const verifyBtn = document.getElementById("verifyEmailBtn");
    const code      = document.getElementById("verification_code").value.trim();

    if (code.length < 6) return;

    verifyBtn.disabled   = true;
    verifyBtn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying…';

    $.ajax({
        url:  window.routes.verifyCode,
        type: "POST",
        data: { email: _verifyEmail, code: code },
        headers: { "X-CSRF-TOKEN": window.routes.csrfToken },
        success: function (response) {
            showAlert("success", response.message || "Email verified successfully!");
            clearInterval(_resendTimerInt);
            setTimeout(function () {
                window.location.href = response.redirect;
            }, 1500);
        },
        error: function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : "Verification failed. Please try again.";
            showAlert("error", msg);
            verifyBtn.disabled  = false;
            verifyBtn.innerHTML = "Verify Email";
        }
    });
}

/* ALERT HELPERS */
function showAlert(type, message) {
    const alert = type === "success"
        ? document.getElementById("successAlert")
        : document.getElementById("errorAlert");
    const text = type === "success"
        ? document.getElementById("successAlertText")
        : document.getElementById("errorAlertText");

    text.innerHTML = message;
    alert.classList.add("show");
    alert.style.display = "block";

    setTimeout(function () {
        type === "success" ? hideSuccessAlert() : hideErrorAlert();
    }, 5000);
}

function hideSuccessAlert() {
    const alert = document.getElementById("successAlert");
    alert.classList.remove("show");
    setTimeout(function () { alert.style.display = "none"; }, 150);
}

function hideErrorAlert() {
    const alert = document.getElementById("errorAlert");
    alert.classList.remove("show");
    setTimeout(function () { alert.style.display = "none"; }, 150);
}