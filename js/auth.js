// ===== LOGIN FORM VALIDATION =====
// ===== LOGIN FORM VALIDATION =====
const loginForm = document.getElementById('loginForm');

if (loginForm) {
    const emailInput    = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Real-time: mawawala error kapag nag-type na
    emailInput.addEventListener('input', function () {
        if (this.value.trim() !== '') clearError(this);
    });

    passwordInput.addEventListener('input', function () {
        if (this.value.trim() !== '') clearError(this);
        // Kung may PHP server error (invalid credentials), i-clear din yung server error span
        const serverErr = document.getElementById('serverError');
        if (serverErr) serverErr.style.display = 'none';
        // I-clear din yung red border ng email pag nag-type sa password
        // (kasi parehong nag-eered kapag wrong credentials)
    });

    emailInput.addEventListener('input', function () {
        const serverErr = document.getElementById('serverError');
        if (serverErr) serverErr.style.display = 'none';
        // I-clear din yung red border ng password
        clearError(passwordInput);
    });

    loginForm.addEventListener('submit', function (e) {
        let valid = true;

        if (emailInput.value.trim() === '') {
            markError(emailInput, 'Email is required.');
            valid = false;
        } else {
            clearError(emailInput);
        }

        if (passwordInput.value.trim() === '') {
            markError(passwordInput, 'Password is required.');
            valid = false;
        } else if (passwordInput.value.length < 6) {
            markError(passwordInput, 'Password must be at least 6 characters.');
            valid = false;
        } else {
            clearError(passwordInput);
        }

        if (!valid) e.preventDefault();
    });
}// ===== SIGNUP FORM VALIDATION =====
const signForm = document.getElementById('signForm');

if (signForm) {
    signForm.addEventListener('submit', function (e) {
        const nameInput     = document.getElementById('name');
        const emailInput    = document.getElementById('email');
        const phoneInput    = document.getElementById('phone');
        const passwordInput = document.getElementById('password');
        const confirmInput  = document.getElementById('confirmPassword');

        let valid = true;

        if (nameInput.value.trim() === '') {
            markError(nameInput);
            valid = false;
        } else {
            clearError(nameInput);
        }

        if (emailInput.value.trim() === '') {
            markError(emailInput);
            valid = false;
        } else {
            clearError(emailInput);
        }

        // Phone — required + PH format
        const rawPhone = phoneInput.value.replace(/\D/g, '');
        if (rawPhone === '') {
            markError(phoneInput, 'This field is required.');
            valid = false;
        } else if (!/^09\d{9}$/.test(rawPhone)) {
            markError(phoneInput, 'Invalid PH format (e.g. 09XX-XXX-XXXX)');
            valid = false;
        } else {
            clearError(phoneInput);
        }

        if (passwordInput.value.trim() === '') {
            markError(passwordInput);
            valid = false;
        } else if (passwordInput.value.length < 6) {
            markError(passwordInput, 'Password must be at least 6 characters');
            valid = false;
        } else {
            clearError(passwordInput);
        }

        if (confirmInput.value.trim() === '') {
            markError(confirmInput);
            valid = false;
        } else if (confirmInput.value !== passwordInput.value) {
            markError(confirmInput, 'Passwords do not match');
            valid = false;
        } else {
            clearError(confirmInput);
        }

        if (!valid) e.preventDefault();
    });
}

// ===== HELPER FUNCTIONS =====
function markError(input, msg) {
    input.classList.add('error');

    // Hanapin yung pinakamalapit na container
    const container = input.closest('.info-grp, .input-text, .prac');
    if (container) container.classList.add('has-error');

    // Hanapin ang .error-text sa container, hindi sa direct parent
    const errSpan = container?.querySelector('.error-text');
    if (errSpan) {
        if (msg) errSpan.textContent = msg;  // override message
        errSpan.style.display = 'block';
    }
}

function clearError(input) {
    input.classList.remove('error');

    const container = input.closest('.info-grp, .input-text, .prac');
    if (container) container.classList.remove('has-error');

    const errSpan = container?.querySelector('.error-text');
    if (errSpan) errSpan.style.display = 'none';
}
// ===== EYE TOGGLE =====
document.querySelectorAll('.eye-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
        const input = document.getElementById(this.getAttribute('data-target'));
        const icon  = this.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        }
    });
});

// ===== PHONE AUTO-FORMAT =====
const phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function () {
        let val = this.value.replace(/\D/g, '');
        if (val.length > 11) val = val.slice(0, 11);

        if      (val.length > 7) val = val.slice(0,4) + '-' + val.slice(4,7) + '-' + val.slice(7);
        else if (val.length > 4) val = val.slice(0,4) + '-' + val.slice(4);

        this.value = val;
    });
}

// ===== AUTH ERROR MODAL =====
function closeAuthModal() {
    document.getElementById('errorModal').style.display = 'none';
}

// Close kapag nag-click sa overlay
document.getElementById('errorModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeAuthModal();
});