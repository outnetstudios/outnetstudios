document.addEventListener('DOMContentLoaded', () => {
    const navbarContainer = document.querySelector('#navbar-container');
    const footerContainer = document.querySelector('#footer-container');

    if (navbarContainer) {
        fetch('navbar.html')
            .then(response => response.text())
            .then(data => {
                navbarContainer.innerHTML = data;

                const navbarToggle = document.getElementById('navbarToggle');
                const navbarMenu = document.getElementById('navbarMenu');
                const navbar = document.querySelector('.navbar');

                navbarToggle.addEventListener('click', () => {
                    // Alternar la clase active en el menú y el navbar
                    navbarMenu.classList.toggle('active');
                    navbar.classList.toggle('active');

                    // Forzar la aplicación del color de fondo en iOS
                    if (navbar.classList.contains('active')) {
                        navbar.style.backgroundColor = 'var(--navbar-bg-color)';
                    } else {
                        navbar.style.backgroundColor = ''; // Reset al color original
                    }

                    // Alternar entre los íconos
                    const menuIcon = navbarToggle.querySelector('.menu-icon');
                    const closeIcon = navbarToggle.querySelector('.close-icon');

                    menuIcon.style.display = menuIcon.style.display === 'none';
                    closeIcon.style.display = closeIcon.style.display === 'none';
                });

                window.addEventListener('scroll', () => {
                    if (window.scrollY > 0) {
                        navbar.classList.add('scrolled');
                        navbar.style.backgroundColor = 'var(--navbar-bg-color)'; // Asegurar el color en scroll
                    } else {
                        navbar.classList.remove('scrolled');
                        if (!navbar.classList.contains('active')) {
                            navbar.style.backgroundColor = ''; // Solo eliminar color si el menú no está activo
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading navbar:', error));
    }

    if (footerContainer) {
        fetch('footer.html')
            .then(response => response.text())
            .then(data => {
                footerContainer.innerHTML = data;
            })
            .catch(error => console.error('Error loading footer:', error));
    }
});
