document.addEventListener('DOMContentLoaded', () => {
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.getElementById('navbarMenu');
    const navbar = document.querySelector('.navbar');
    const navbarSentinel = document.getElementById('navbarScrollSentinel');

    if (!navbarToggle || !navbar || !navbarMenu) {
        return;
    }

    let isPastThreshold = false;

    const refreshNavbarState = () => {
        const shouldDarken = isPastThreshold || navbarMenu.classList.contains('active');
        navbar.classList.toggle('navbar--solid', shouldDarken);
    };

    const updateNavbarState = () => {
        refreshNavbarState();
    };

    navbarToggle.addEventListener('click', () => {
        navbarMenu.classList.toggle('active');
        navbar.classList.toggle('active');
        updateNavbarState();

        const menuIcon = navbarToggle.querySelector('.menu-icon');
        const closeIcon = navbarToggle.querySelector('.close-icon');

        if (menuIcon && closeIcon) {
            if (menuIcon.style.display === 'none') {
                menuIcon.style.display = 'block';
                closeIcon.style.display = 'none';
            } else {
                menuIcon.style.display = 'none';
                closeIcon.style.display = 'block';
            }
        }
    });

    if (navbarSentinel && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(([entry]) => {
            isPastThreshold = !entry.isIntersecting;
            refreshNavbarState();
        }, {
            threshold: 0,
            rootMargin: '0px',
        });

        observer.observe(navbarSentinel);
    } else {
        const updateFromScroll = () => {
            isPastThreshold = window.scrollY > 0;
            refreshNavbarState();
        };

        window.addEventListener('scroll', updateFromScroll, { passive: true });
        window.addEventListener('resize', updateFromScroll);
        updateFromScroll();
    }

    window.addEventListener('resize', updateNavbarState);
    refreshNavbarState();
});
