const planAliasMap = {
    basic: 'basic',
    basico: 'basic',
    'básico': 'basic',
    'plan basico': 'basic',
    'plan básico': 'basic',
    professional: 'professional',
    profesional: 'professional',
    'plan profesional': 'professional',
    premium: 'premium',
    'plan premium': 'premium',
};

const countryCodes = {
    AL: '+355', AT: '+43', BE: '+32', BG: '+359', HR: '+385',
    CY: '+357', CZ: '+420', DK: '+45', EE: '+372', FI: '+358',
    FR: '+33', DE: '+49', GR: '+30', HU: '+36', IS: '+354',
    IE: '+353', IT: '+39', LV: '+371', LT: '+370', LU: '+352',
    MT: '+356', MD: '+373', ME: '+382', NL: '+31', NO: '+47',
    PL: '+48', PT: '+351', RO: '+40', RU: '+7', SK: '+421',
    SI: '+386', ES: '+34', SE: '+46', CH: '+41', GB: '+44',
    US: '+1', AR: '+54', BR: '+55', CL: '+56', CO: '+57',
    CR: '+506', CU: '+53', DO: '+1', EC: '+593', SV: '+503',
    GT: '+502', HN: '+504', MX: '+52', NI: '+505', PA: '+507',
    PY: '+595', PE: '+51', UY: '+598', VE: '+58',
};

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    const selectPlan = document.getElementById('plan_interes');
    const selectCountry = document.getElementById('pais');
    const phoneInput = document.getElementById('telefono');
    const modal = document.getElementById('contactModal');
    const modalMessage = document.getElementById('contactModalMessage');
    const modalClose = document.getElementById('contactModalClose');
    const modalOverlay = document.getElementById('contactModalOverlay');
    const submitButton = form ? form.querySelector('button[type="submit"]') : null;

    if (!form || !selectPlan || !selectCountry || !phoneInput || !modal || !modalMessage || !modalClose || !modalOverlay) {
        return;
    }

    const openModal = (message, isSuccess = true) => {
        modalMessage.textContent = message;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        modal.dataset.state = isSuccess ? 'success' : 'error';
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    const setCountryPhone = (countryCode) => {
        const prefix = countryCodes[countryCode];
        if (prefix) {
            phoneInput.value = `${prefix} `;
            phoneInput.placeholder = `Ej: ${prefix} 600 000 000`;
        } else {
            phoneInput.value = '';
            phoneInput.placeholder = 'Ej: 600 000 000';
        }
    };

    const params = new URLSearchParams(window.location.search);
    const rawPlan = (params.get('plan') || '').trim().toLowerCase();
    const normalizedPlan = planAliasMap[rawPlan] || '';

    if (normalizedPlan) {
        selectPlan.value = normalizedPlan;
    }

    selectCountry.value = 'ES';
    setCountryPhone('ES');

    selectCountry.addEventListener('change', function () {
        setCountryPhone(this.value);
    });

    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const emailInput = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const phoneNumber = phoneInput.value.replace(/\D/g, '');

        if (!emailPattern.test(emailInput.value)) {
            openModal('Por favor, ingresa un correo electrónico válido.', false);
            return;
        }

        if (phoneNumber.length < 10) {
            openModal('Por favor, ingresa un número de teléfono válido.', false);
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch('form/submit_contact.php', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const payload = await response.json().catch(() => ({
                success: false,
                message: 'No se pudo procesar tu solicitud en este momento.',
            }));

            if (!response.ok || !payload.success) {
                openModal(payload.message || 'No se pudo procesar tu solicitud en este momento.', false);
                return;
            }

            form.reset();
            selectCountry.value = 'ES';
            selectPlan.value = normalizedPlan || '';
            setCountryPhone('ES');
            openModal(payload.message || 'Tus datos fueron enviados correctamente.', true);
        } catch (error) {
            openModal('No se pudo procesar tu solicitud en este momento.', false);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
});
