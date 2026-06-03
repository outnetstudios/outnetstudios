<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire Us</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/contact_form.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
</head>
<body>
    <?php include __DIR__ . '/templates/partials/navbar.php'; ?>

    <div class="content">
        <section class="banner-three" style="background-image: url('https://outnetstudios.github.io/outnetstudios/img/imagen-de-fondo.png');">
            <div class="banner-text">
                <h1>Contáctanos</h1>
                <div class="contact-form">
                    <form id="contactForm" method="POST" action="form/submit_contact.php">
                        <label for="nombre_apellido">Nombre y Apellido <span style="color: red;">*</span></label>
                        <input type="text" id="nombre_apellido" name="nombre_apellido" required placeholder="Ej: Juan Pérez">
                        
                        <label for="email">Email <span style="color: red;">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="Ej: correo@ejemplo.com">
                        
                        <label for="pais">Selecciona tu País <span style="color: red;">*</span></label>
                        <select id="pais" name="pais" required>
                            <option value="ES" selected>España</option>
                            <option value="AL">Albania</option>
                            <option value="AT">Austria</option>
                            <option value="BE">Bélgica</option>
                            <option value="BG">Bulgaria</option>
                            <option value="HR">Croacia</option>
                            <option value="CY">Chipre</option>
                            <option value="CZ">República Checa</option>
                            <option value="DK">Dinamarca</option>
                            <option value="EE">Estonia</option>
                            <option value="FI">Finlandia</option>
                            <option value="FR">Francia</option>
                            <option value="DE">Alemania</option>
                            <option value="GR">Grecia</option>
                            <option value="HU">Hungría</option>
                            <option value="IS">Islandia</option>
                            <option value="IE">Irlanda</option>
                            <option value="IT">Italia</option>
                            <option value="LV">Letonia</option>
                            <option value="LT">Lituania</option>
                            <option value="LU">Luxemburgo</option>
                            <option value="MT">Malta</option>
                            <option value="MD">Moldavia</option>
                            <option value="ME">Montenegro</option>
                            <option value="NL">Países Bajos</option>
                            <option value="NO">Noruega</option>
                            <option value="PL">Polonia</option>
                            <option value="PT">Portugal</option>
                            <option value="RO">Rumania</option>
                            <option value="RU">Rusia</option>
                            <option value="SK">Eslovaquia</option>
                            <option value="SI">Eslovenia</option>
                            <option value="SE">Suecia</option>
                            <option value="CH">Suiza</option>
                            <option value="GB">Reino Unido</option>
                            <option value="US">Estados Unidos</option>
                            
                            <option value="AR">Argentina</option>
                            <option value="BR">Brasil</option>
                            <option value="CL">Chile</option>
                            <option value="CO">Colombia</option>
                            <option value="CR">Costa Rica</option>
                            <option value="CU">Cuba</option>
                            <option value="DO">República Dominicana</option>
                            <option value="EC">Ecuador</option>
                            <option value="SV">El Salvador</option>
                            <option value="GT">Guatemala</option>
                            <option value="HN">Honduras</option>
                            <option value="MX">México</option>
                            <option value="NI">Nicaragua</option>
                            <option value="PA">Panamá</option>
                            <option value="PY">Paraguay</option>
                            <option value="PE">Perú</option>
                            <option value="UY">Uruguay</option>
                            <option value="VE">Venezuela</option>
                        </select>
                        
                        <label for="telefono">Número de Teléfono <span style="color: red;">*</span></label>
                        <input type="text" id="telefono" name="telefono" required placeholder="Ej: +34 700700700">

                        <label for="plan_interes">Plan de Interés <span style="color: red;">*</span></label>
                        <select id="plan_interes" name="plan_interes" required>
                            <option value="">Selecciona un plan</option>
                            <option value="Básico">Plan Básico</option>
                            <option value="Profesional">Plan Profesional</option>
                            <option value="Premium">Plan Premium</option>
                        </select>
                        
                        <label for="nombre_empresa">Nombre de la Empresa</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" placeholder="Ej: Mi Empresa S.L.">
                        
                        <label for="sector">Sector / Industria</label>
                        <input type="text" id="sector" name="sector" placeholder="Ej: Tecnología">
                        
                        <label for="descripcion">Descripción de Proyecto <span style="color: red;">*</span></label>
                        <textarea id="descripcion" name="descripcion" required placeholder="Describe tu proyecto"></textarea>
                        
                        <button type="submit">Enviar</button>
                    </form>
                </div>                
            </div>
        </section>
    </div>

    <?php include __DIR__ . '/templates/partials/footer.php'; ?>

    <script src="js/navbar_and_footer.js"></script>
    <script src="js/contact_form.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/libphonenumber-js/1.9.48/libphonenumber-js.min.js"></script>
    <script>
        const countryCodes = {
            "AL": "+355", "AT": "+43", "BE": "+32", "BG": "+359", "HR": "+385",
            "CY": "+357", "CZ": "+420", "DK": "+45", "EE": "+372", "FI": "+358",
            "FR": "+33", "DE": "+49", "GR": "+30", "HU": "+36", "IS": "+354",
            "IE": "+353", "IT": "+39", "LV": "+371", "LT": "+370", "LU": "+352",
            "MT": "+356", "MD": "+373", "ME": "+382", "NL": "+31", "NO": "+47",
            "PL": "+48", "PT": "+351", "RO": "+40", "RU": "+7", "SK": "+421",
            "SI": "+386", "ES": "+34", "SE": "+46", "CH": "+41", "GB": "+44",
            "US": "+1", "AR": "+54", "BR": "+55", "CL": "+56", "CO": "+57",
            "CR": "+506", "CU": "+53", "DO": "+1", "EC": "+593", "SV": "+503",
            "GT": "+502", "HN": "+504", "MX": "+52", "NI": "+505", "PA": "+507",
            "PY": "+595", "PE": "+51", "UY": "+598", "VE": "+58"
        };

        document.getElementById('pais').value = 'ES';
        document.getElementById('telefono').value = countryCodes['ES'] + " ";

        document.getElementById('pais').addEventListener('change', function() {
            const countryCode = countryCodes[this.value];
            const phoneInput = document.getElementById('telefono');

            if (countryCode) {
                phoneInput.value = countryCode + " ";
                phoneInput.placeholder = "Ej: " + countryCode + " 600 000 000";
            } else {
                phoneInput.value = "";
                phoneInput.placeholder = "Ej: 600 000 000";
            }
        });

        document.getElementById('contactForm').addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('telefono');

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput.value)) {
                alert('Por favor, ingresa un correo electrónico válido.');
                event.preventDefault();
                return;
            }

            const phoneNumber = phoneInput.value.replace(/\D/g, '');
            if (phoneNumber.length < 10) {
                alert('Por favor, ingresa un número de teléfono válido.');
                event.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
