/* ── Password eye toggle ── */
function toggleAdminPassword() {
    const input = document.getElementById('admin_password');
    const btn   = document.querySelector('.admin-eye-toggle');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/* ── Floating alert helpers (same pattern as user login) ── */
function showAlert(type, message) {
    const alertEl = type === 'success' ? document.getElementById('successAlert') : document.getElementById('errorAlert');
    const textEl = type === 'success' ? document.getElementById('successAlertText') : document.getElementById('errorAlertText');

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

/* ── Login validation + submit ── */
function adminLoginCheck() {
    const email = document.getElementById("admin_email");
    const password = document.getElementById("admin_password");
    const btn = document.getElementById("admin-login-btn");
    const form = document.getElementById("adminLoginForm");

    [email, password].forEach(f => f.setCustomValidity(""));

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&^_-])[A-Za-z\d@$!%*#?&^_-]{7,}$/;
    let isValid = true;

    if (email.value.trim() === "") {
        email.setCustomValidity("Please enter your email address.");
        isValid = false;
    } else if (!emailRegex.test(email.value.trim())) {
        email.setCustomValidity("Please enter a valid email (e.g., example@gmail.com).");
        isValid = false;
    } else if (password.value.trim() === "") {
        password.setCustomValidity("Please enter a password.");
        isValid = false;
    } else if (!passwordRegex.test(password.value.trim())) {
        password.setCustomValidity(
            "Password must be at least 7 characters with a letter, number, and special character."
        );
        isValid = false;
    } 

    if (!isValid || !form.checkValidity()) {
        form.reportValidity();
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing in…';

    AdminLogin();
}

function AdminLogin() {
    const btn   = document.getElementById('admin-login-btn');
    const token = document.querySelector('input[name="_token"]');

    if (!token || token.value === '') {
        showAlert('error', 'Something went wrong. Please refresh the page.');
        return;
    }

    const formData = new FormData();
    formData.append('email',    document.getElementById('admin_email').value.trim());
    formData.append('password', document.getElementById('admin_password').value);
    formData.append('remember', document.getElementById('admin_remember').checked ? '1' : '0');

    $.ajax({
        url: window.adminRoutes.loginApi,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': token.value },
        success: function (response) {
            showAlert('success', response.message || 'Login successful!');
            btn.innerHTML = '<i class="fa fa-circle-check"></i> Redirecting…';
            setTimeout(function () {
                window.location.replace(response.redirect || '/admin/dashboard');
            }, 800);
        },
        error: function (xhr) {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa fa-right-to-bracket"></i> Sign In to Admin Panel';

            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                let messages = '';
                for (let field in errors) {
                    messages += '<div>' + errors[field][0] + '</div>';
                }
                showAlert('error', messages);
            } else {
                showAlert('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
            }
        },
    });
}
