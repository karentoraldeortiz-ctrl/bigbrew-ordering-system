// const form = document.getElementById('loginForm');

// form.addEventListener('submit', (e) => {
//     e.preventDefault();
    
//     const emailInput = document.getElementById('email');
//     const passwordInput = document.getElementById('password');

//     validateField(emailInput);
//     validateField(passwordInput);
// });

// function validateField(input) {
//     const parent = input.parentElement;

//     if (input.value.trim() === "") {
//         input.classList.add('error');
//         parent.classList.add('has-error');
//     } else {
//         input.classList.remove('error');
//         parent.classList.remove('has-error');
//     }
// }

const form = document.getElementById('loginForm');

form.addEventListener('submit', (e) => {
    e.preventDefault();

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    validateField(emailInput);
    validateField(passwordInput);
});
    function validateField(input) {
        const parent = input.parentElement;
        
        if (input.value.trim() === "") {
            input.classList.add('error');
            parent.classList.add('has-error');
        } else {
            input.classList.remove('error');
            parent.classList.remove('has-error');
        }
    }
