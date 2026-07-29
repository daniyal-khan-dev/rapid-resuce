/* ── Driver Login — AJAX ─────────────────────────────────────────────────── */

function toggleDrvPassword() {
    const input = document.getElementById('drv_password_input');
    const btn   = document.querySelector('.drv-eye-toggle');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/* ── Floating alert helpers (same pattern as admin login) ── */
function showAlert(type, message) {
    const alertEl = type === 'success' ? document.getElementById('successAlert') : document.getElementById('errorAlert');
    const textEl  = type === 'success' ? document.getElementById('successAlertText') : document.getElementById('errorAlertText');

    textEl.innerHTML = message;
    alertEl.classList.add('show');
    alertEl.style.display = 'block';

    setTimeout(function () {
        if (type === 'success') hideSuccessAlert();
        else hideErrorAlert();
    }, 5000);
}

function hideSuccessAlert() {
    const el = document.getElementById('successAlert');
    el.classList.remove('show');
    setTimeout(function () { el.style.display = 'none'; }, 150);
}

function hideErrorAlert() {
    const el = document.getElementById('errorAlert');
    el.classList.remove('show');
    setTimeout(function () { el.style.display = 'none'; }, 150);
}

function clearDrvLoginErrors() {
    ['email', 'password'].forEach(f => {
        const el = document.getElementById('drv_err_' + f);
        if (el) el.textContent = '';
    });
}

const loginForm = document.getElementById('drvLoginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearDrvLoginErrors();

        const email    = document.getElementById('drv_email').value.trim();
        const password = document.getElementById('drv_password_input').value;
        const remember = document.getElementById('drv_remember') ? document.getElementById('drv_remember').checked : false;

        if (!email)    { document.getElementById('drv_err_email').textContent = 'Email is required.'; return; }
        if (!password) { document.getElementById('drv_err_password').textContent = 'Password is required.'; return; }

        const btn = document.getElementById('drvLoginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in…';

        const formData = new FormData();
        formData.append('email',    email);
        formData.append('password', password);
        formData.append('remember', remember ? '1' : '0');
        formData.append('_token',   window.driverRoutes.csrfToken);

        $.ajax({
            url: window.driverRoutes.loginApi,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': window.driverRoutes.csrfToken },
            success: function (data) {
                showAlert('success', data.message || 'Login successful!');
                btn.innerHTML = '<i class="fa fa-circle-check"></i> Redirecting…';
                setTimeout(function () {
                    window.location.replace(data.redirect || '/driver/dashboard');
                }, 800);
            },
            error: function (xhr) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-truck-medical"></i> Sign In to Portal';

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let messages = '';
                    for (let field in errors) {
                        const el = document.getElementById('drv_err_' + field);
                        if (el) {
                            el.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                        } else {
                            messages += '<div>' + (Array.isArray(errors[field]) ? errors[field][0] : errors[field]) + '</div>';
                        }
                    }
                    if (messages) showAlert('error', messages);
                } else {
                    showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Login failed. Please try again.');
                }
            },
        });
    });
}
