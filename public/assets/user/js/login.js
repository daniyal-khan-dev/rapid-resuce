var _resetEmail = '';
var _resendResetInterval = null;

function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function validateUsername(input) {
    input.value = input.value.toLowerCase().replace(/[^a-z0-9_.]/g, '');
}

function showOnly(divId) {
    ['loginFormDiv','ForgotPasswordDiv','ForgotPasswordVerifyDiv','ResetPasswordDiv'].forEach(function(id) {
        var el = document.getElementById(id);
        if (id === divId) {
            el.classList.remove('d-none');
        } else {
            el.classList.add('d-none');
        }
    });
}

function showLoginFormDiv() {
    showOnly('loginFormDiv');
}

function showForgotPasswordDiv() {
    showOnly('ForgotPasswordDiv');
}

function maskEmail(email) {
    var atIndex = email.indexOf('@');
    if (atIndex <= 3) return email;
    var visible = email.substring(0, 3);
    var stars   = '*'.repeat(atIndex - 3);
    var domain  = email.substring(atIndex);
    return visible + stars + domain;
}

function loginValidationCheck() {
    var username = document.getElementById('username');
    var password = document.getElementById('login_password');
    var form     = document.getElementById('loginForm');
    var btn      = document.getElementById('login-btn');

    if (!form.checkValidity()) { form.reportValidity(); return; }

    var usernameRegex = /^[a-zA-Z][a-zA-Z0-9_]{3,14}$/;
    var passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{7,}$/;

    if (!username.value.trim()) {
        username.setCustomValidity('Please enter your Username.');
        form.reportValidity(); return;
    }
    if (!usernameRegex.test(username.value.trim())) {
        username.setCustomValidity('Username must start with a letter and be 4–15 characters. Letters, numbers, underscore only.');
        form.reportValidity(); return;
    }
    username.setCustomValidity('');

    if (!password.value.trim()) {
        password.setCustomValidity('Please enter a password.');
        form.reportValidity(); return;
    }
    if (!passwordRegex.test(password.value.trim())) {
        password.setCustomValidity('Password must be at least 7 characters with a letter, number, and special character.');
        form.reportValidity(); return;
    }
    password.setCustomValidity('');

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in…';
    Login();
}

function Login() {
    var btn   = document.getElementById('login-btn');
    var token = document.querySelector('#loginForm input[name="_token"]');

    if (!token || !token.value) {
        showAlert('error', 'Something went wrong. Please refresh the page.');
        return;
    }

    var recaptchaToken = (typeof grecaptcha !== 'undefined' && typeof loginWidgetId !== 'undefined')
        ? grecaptcha.getResponse(loginWidgetId) : '';
    if (!recaptchaToken) {
        var rcErr = document.getElementById('login-recaptcha-error');
        if (rcErr) rcErr.style.display = 'block';
        btn.disabled  = false;
        btn.innerHTML = 'Sign In';
        return;
    }
    var rcErr = document.getElementById('login-recaptcha-error');
    if (rcErr) rcErr.style.display = 'none';

    var fd = new FormData();
    fd.append('username', document.getElementById('username').value.trim());
    fd.append('password', document.getElementById('login_password').value);
    fd.append('remember', document.getElementById('remember') && document.getElementById('remember').checked ? '1' : '0');
    fd.append('g-recaptcha-response', recaptchaToken);

    $.ajax({
        url: window.routes.loginSubmit,
        type: 'POST',
        data: fd,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function(res) {
            showAlert('success', res.message || 'Login successful!');
            setTimeout(function() { window.location.href = res.redirect || '/'; }, 800);
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var msgs = '';
                for (var f in errors) msgs += '<div>' + errors[f][0] + '</div>';
                showAlert('error', msgs);
            } else {
                showAlert('error', 'Something went wrong. Please try again.');
            }
            btn.disabled  = false;
            btn.innerHTML = 'Sign In';
            if (typeof grecaptcha !== 'undefined' && typeof loginWidgetId !== 'undefined') {
                grecaptcha.reset(loginWidgetId);
            }
        }
    });
}

function sendPasswordResetCode() {
    var email = document.getElementById('forgot_email').value.trim();
    var token = document.querySelector('#forgotPasswordForm input[name="_token"]');
    var btn   = document.getElementById('sendResetCodeBtn');

    if (!email) {
        showAlert('error', 'Please enter your email address.');
        return;
    }

    var forgotRecaptchaToken = (typeof grecaptcha !== 'undefined' && typeof forgotWidgetId !== 'undefined')
        ? grecaptcha.getResponse(forgotWidgetId) : '';
    if (!forgotRecaptchaToken) {
        var rcErr = document.getElementById('forgot-recaptcha-error');
        if (rcErr) rcErr.style.display = 'block';
        return;
    }
    var rcErr = document.getElementById('forgot-recaptcha-error');
    if (rcErr) rcErr.style.display = 'none';

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending…';

    $.ajax({
        url: window.routes.passwordResetSend,
        type: 'POST',
        data: { email: email, 'g-recaptcha-response': forgotRecaptchaToken },
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function(res) {
            if (res.success) {
                _resetEmail = email;
                showAlert('success', res.message);
                showForgotPasswordVerifyDiv(email);
            } else {
                showAlert('error', res.message || 'Failed to send code.');
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
            showAlert('error', msg);
        },
        complete: function() {
            btn.disabled  = false;
            btn.innerHTML = 'Send Reset Code';
        }
    });
}

function showForgotPasswordVerifyDiv(email) {
    document.getElementById('reset_email').value = maskEmail(email);
    document.getElementById('reset_code').value  = '';
    document.getElementById('verifyResetCodeBtn').disabled = true;
    showOnly('ForgotPasswordVerifyDiv');
    startResendResetCooldown();
}

function startResendResetCooldown() {
    var btn      = document.getElementById('resendResetCodeBtn');
    var timerEl  = document.getElementById('resendResetTimer');
    var seconds  = 60;

    btn.disabled  = true;
    timerEl.textContent = seconds;
    btn.innerHTML = 'Resend Code (<span id="resendResetTimer">' + seconds + '</span>s)';

    if (_resendResetInterval) clearInterval(_resendResetInterval);

    _resendResetInterval = setInterval(function() {
        seconds--;
        var t = document.getElementById('resendResetTimer');
        if (t) t.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(_resendResetInterval);
            btn.disabled  = false;
            btn.innerHTML = 'Resend Code';
        }
    }, 1000);
}

function onResetCodeInput() {
    var val = document.getElementById('reset_code').value.replace(/\D/g, '');
    document.getElementById('reset_code').value = val;
    document.getElementById('verifyResetCodeBtn').disabled = val.length < 6;
}

function resendResetCode() {
    var token = document.querySelector('#forgotPasswordVerifyForm input[name="_token"]');
    var btn   = document.getElementById('resendResetCodeBtn');

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';

    $.ajax({
        url: window.routes.passwordResetResend,
        type: 'POST',
        data: { email: _resetEmail },
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function(res) {
            if (res.success) {
                showAlert('success', res.message);
                document.getElementById('reset_code').value = '';
                document.getElementById('verifyResetCodeBtn').disabled = true;
                startResendResetCooldown();
            } else {
                showAlert('error', res.message || 'Failed to resend code.');
                if (res.limit_reached) {
                    btn.disabled  = true;
                    btn.innerHTML = 'Limit reached';
                } else {
                    btn.disabled  = false;
                    btn.innerHTML = 'Resend Code';
                }
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
            showAlert('error', msg);
            if (xhr.status === 429) {
                btn.disabled  = true;
                btn.innerHTML = 'Daily limit reached';
            } else {
                btn.disabled  = false;
                btn.innerHTML = 'Resend Code';
            }
        }
    });
}

function verifyResetCode() {
    var code  = document.getElementById('reset_code').value.trim();
    var token = document.querySelector('#forgotPasswordVerifyForm input[name="_token"]');
    var btn   = document.getElementById('verifyResetCodeBtn');

    if (code.length < 6) return;

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying…';

    $.ajax({
        url: window.routes.passwordResetVerify,
        type: 'POST',
        data: { email: _resetEmail, code: code },
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function(res) {
            if (res.success) {
                if (_resendResetInterval) clearInterval(_resendResetInterval);
                showAlert('success', res.message);
                showOnly('ResetPasswordDiv');
            } else {
                showAlert('error', res.message || 'Invalid code.');
                btn.disabled  = false;
                btn.innerHTML = 'Verify Code';
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
            showAlert('error', msg);
            btn.disabled  = false;
            btn.innerHTML = 'Verify Code';
        }
    });
}

function validateNewPassword(input) {
    var regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{7,}$/;
    var errEl = document.getElementById('new_password_error');
    if (input.value && !regex.test(input.value)) {
        errEl.textContent = 'Min 7 characters with a letter, number, and special character.';
    } else {
        errEl.textContent = '';
    }
    validateConfirmNewPassword();
}

function validateConfirmNewPassword() {
    var pw  = document.getElementById('new_password').value;
    var cpw = document.getElementById('confirm_new_password').value;
    var errEl = document.getElementById('confirm_new_password_error');
    if (cpw && pw !== cpw) {
        errEl.textContent = 'Passwords do not match.';
    } else {
        errEl.textContent = '';
    }
}

function submitResetPassword() {
    var pw  = document.getElementById('new_password').value;
    var cpw = document.getElementById('confirm_new_password').value;
    var token = document.querySelector('#resetPasswordForm input[name="_token"]');
    var btn   = document.getElementById('resetPasswordBtn');

    var regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{7,}$/;

    if (!pw) { showAlert('error', 'Please enter a new password.'); return; }
    if (!regex.test(pw)) { showAlert('error', 'Password must be at least 7 characters with a letter, number, and special character.'); return; }
    if (pw !== cpw) { showAlert('error', 'Passwords do not match.'); return; }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Resetting…';

    $.ajax({
        url: window.routes.passwordResetReset,
        type: 'POST',
        data: { new_password: pw, confirm_password: cpw },
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function(res) {
            if (res.success) {
                showAlert('success', res.message);
                setTimeout(function() { window.location.href = res.redirect || '/login'; }, 1200);
            } else {
                showAlert('error', res.message || 'Failed to reset password.');
                btn.disabled  = false;
                btn.innerHTML = 'Reset Password';
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
            showAlert('error', msg);
            btn.disabled  = false;
            btn.innerHTML = 'Reset Password';
        }
    });
}

function showAlert(type, message) {
    var alertEl = type === 'success' ? document.getElementById('successAlert') : document.getElementById('errorAlert');
    var textEl  = type === 'success' ? document.getElementById('successAlertText') : document.getElementById('errorAlertText');
    textEl.innerHTML = message;
    alertEl.classList.add('show');
    alertEl.style.display = 'block';
    setTimeout(function() {
        if (type === 'success') hideSuccessAlert();
        else hideErrorAlert();
    }, 5000);
}

function hideSuccessAlert() {
    var el = document.getElementById('successAlert');
    el.classList.remove('show');
    setTimeout(function() { el.style.display = 'none'; }, 150);
}

function hideErrorAlert() {
    var el = document.getElementById('errorAlert');
    el.classList.remove('show');
    setTimeout(function() { el.style.display = 'none'; }, 150);
}
