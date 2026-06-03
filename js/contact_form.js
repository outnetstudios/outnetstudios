// Función para obtener el valor del parámetro de la URL
function getParameterByName(name) {
    let url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    let regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
    let results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

// Obtener el valor del plan de la URL
const planSeleccionado = getParameterByName('plan');

// Seleccionar el valor en el campo del formulario si el plan está definido
if (planSeleccionado) {
    document.addEventListener('DOMContentLoaded', function() {
        let selectPlan = document.getElementById('plan_interes');
        if (selectPlan) {
            selectPlan.value = planSeleccionado;
        }
    });
}