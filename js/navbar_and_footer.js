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

                    // Alternar entre los íconos
                    const menuIcon = navbarToggle.querySelector('.menu-icon');
                    const closeIcon = navbarToggle.querySelector('.close-icon');

                    menuIcon.style.display = menuIcon.style.display === 'none';
                    closeIcon.style.display = closeIcon.style.display === 'none';
                });

                window.addEventListener('scroll', () => {
                    const navbar = document.querySelector('.navbar');
                    if (window.scrollY > 0) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });
                
            })
    }

    if (footerContainer) {
        fetch('footer.html')
            .then(response => response.text())
            .then(data => {
                footerContainer.innerHTML = data;
            })
    }
});
