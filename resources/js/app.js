// Admin panel mobile nav — off-canvas sidebar toggle (see admin/layouts/app.blade.php).
const sidebar = document.getElementById('admin-sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
const openBtn = document.getElementById('sidebar-toggle');
const closeBtn = document.getElementById('sidebar-close');

if (sidebar && backdrop && openBtn) {
    const openSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        openBtn.setAttribute('aria-expanded', 'true');
    };

    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
        openBtn.setAttribute('aria-expanded', 'false');
    };

    openBtn.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    backdrop.addEventListener('click', closeSidebar);

    // Tapping a nav link on mobile should close the drawer instead of leaving it open behind the new page.
    sidebar.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', closeSidebar));
}
