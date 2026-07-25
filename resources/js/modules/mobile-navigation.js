const initializedAttribute = 'data-tl-navigation-ready';

export function initializeMobileNavigation(root, Offcanvas) {
    const sidebar = root.querySelector('#tlSidebar') ?? document.querySelector('#tlSidebar');

    if (!sidebar || sidebar.hasAttribute(initializedAttribute)) {
        return;
    }

    sidebar.setAttribute(initializedAttribute, 'true');
    sidebar.querySelectorAll('a.tl-nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                Offcanvas.getOrCreateInstance(sidebar).hide();
            }
        });
    });
}
