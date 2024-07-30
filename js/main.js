// main.js

// Función para cargar contenido HTML en un elemento específico
function loadHTML(file, elementId) {
    fetch(file)
        .then(response => response.text())
        .then(data => {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = data;
                // Configura el menú desplegable después de cargar el HTML
                if (elementId === 'navbar') {
                    setupMenuToggle();
                }
            } else {
                console.error(`Elemento con ID ${elementId} no encontrado`);
            }
        })
        .catch(error => console.error('Error al cargar el archivo HTML:', error));
}

// Función para manejar el menú desplegable
function setupMenuToggle() {
    const menuToggle = document.querySelector('.menu-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (menuToggle && dropdownMenu) {
        menuToggle.addEventListener('click', () => {
            const isActive = dropdownMenu.classList.toggle('active');
            menuToggle.classList.toggle('active', isActive);
            menuToggle.setAttribute('aria-label', isActive ? 'Cerrar menú' : 'Abrir menú');
        });
    } else {
        console.error('Botón de menú o menú desplegable no encontrado');
    }
}

// Función para manejar el formulario de contacto en la página hireus.html
function setupContactForm() {
    if (document.body.id === 'hireus') { // Solo ejecutar en hireus.html
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(event) {
                event.preventDefault();
                alert('Formulario enviado');
            });
        } else {
            console.error('Formulario no encontrado en hireus.html');
        }
    }
}

// Ejecutar funciones al cargar el DOM
window.addEventListener('DOMContentLoaded', () => {
    loadHTML('navbar.html', 'navbar');
    loadHTML('footer.html', 'footer');
});
