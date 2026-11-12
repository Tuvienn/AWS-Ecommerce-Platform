/**
 * Điều khiển sidebar Admin trên tablet và điện thoại.
 * Desktop luôn hiển thị sidebar; màn hình hẹp dùng menu mở/đóng dạng off-canvas.
 */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('adminNavToggle');
    const overlay = document.getElementById('adminSidebarOverlay');
    const mobileQuery = window.matchMedia('(max-width: 991.98px)');

    if (!sidebar || !toggle || !overlay) {
        return;
    }

    function setSidebarOpen(open) {
        const shouldOpen = Boolean(open) && mobileQuery.matches;

        sidebar.classList.toggle('is-open', shouldOpen);
        overlay.classList.toggle('is-visible', shouldOpen);
        document.body.classList.toggle('admin-nav-open', shouldOpen);
        toggle.setAttribute('aria-expanded', String(shouldOpen));
        toggle.setAttribute('aria-label', shouldOpen ? 'Đóng menu quản trị' : 'Mở menu quản trị');
        sidebar.setAttribute('aria-hidden', String(mobileQuery.matches && !shouldOpen));
        sidebar.inert = mobileQuery.matches && !shouldOpen;
    }

    toggle.addEventListener('click', function () {
        setSidebarOpen(!sidebar.classList.contains('is-open'));
    });

    overlay.addEventListener('click', function () {
        setSidebarOpen(false);
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            setSidebarOpen(false);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            setSidebarOpen(false);
            toggle.focus();
        }
    });

    mobileQuery.addEventListener('change', function () {
        setSidebarOpen(false);
    });

    setSidebarOpen(false);
});
