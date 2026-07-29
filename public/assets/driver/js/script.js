function toggleSidebar() {
    document.getElementById("driSidebar").classList.toggle("dri-sidebar--open");
    document.getElementById("driSidebarOverlay").classList.toggle("dri-sidebar-overlay--visible");
}

/* ── TOAST SYSTEM ────────────────────────────────────────────────── */
var _driToastSuccessTimer = null;
var _driToastErrorTimer   = null;

function driShowSuccess(msg) {
    var el = document.getElementById('driToastSuccess');
    if (!el) return;
    var t = el.querySelector('.dri-toast-text');
    if (t) t.textContent = msg;
    clearTimeout(_driToastSuccessTimer);
    el.classList.add('show');
    _driToastSuccessTimer = setTimeout(function () { el.classList.remove('show'); }, 3800);
}

function driShowError(msg) {
    var el = document.getElementById('driToastError');
    if (!el) return;
    var t = el.querySelector('.dri-toast-text');
    if (t) t.textContent = msg;
    clearTimeout(_driToastErrorTimer);
    el.classList.add('show');
    _driToastErrorTimer = setTimeout(function () { el.classList.remove('show'); }, 4200);
}

window.driToastSuccess = driShowSuccess;
window.driToastError   = driShowError;
