// navbar_and_footer.js
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

                navbarToggle.addEventListener('click', () => {
                    navbarMenu.classList.toggle('active');
                    // Alternar entre los íconos
                    const menuIcon = navbarToggle.querySelector('.menu-icon');
                    const closeIcon = navbarToggle.querySelector('.close-icon');
                    menuIcon.style.display = menuIcon.style.display === 'none';
                    closeIcon.style.display = closeIcon.style.display === 'none';
                });


                // Agregar el código para cambiar el color de fondo al hacer scroll
                window.addEventListener('scroll', () => {
                    const navbar = document.querySelector('.navbar');
                    if (window.scrollY > 0) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
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

