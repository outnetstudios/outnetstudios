document.addEventListener('DOMContentLoaded', () => {
    const navbarToggle = document.getElementById('navbarToggle');
    const navbarMenu = document.getElementById('navbarMenu');
    const navbar = document.querySelector('.navbar');

    if (!navbarToggle || !navbar || !navbarMenu) {
        return;
    }

    const updateNavbarState = () => {
        const shouldDarken = window.scrollY > 0 || navbarMenu.classList.contains('active');
        navbar.classList.toggle('scrolled', shouldDarken);
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

    window.addEventListener('scroll', updateNavbarState);
    window.addEventListener('resize', updateNavbarState);
    updateNavbarState();
});
