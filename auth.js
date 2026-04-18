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

function login() {
    const email = document.getElementById("email").value;
    
    localStorage.setItem("user", email);

    alert("Login successful");
    window.location.href = "index.html";

}

function logout() {
  localStorage.removeItem("user");
  window.location.href = "index.html";
}

function signup() {
    const email = document.getElementById("email").value;
    localStorage.setItem("user", email);

    alert("Account created successfully.");
    window.location.href = "login.html";
}

const loginForm = document.getElementById('loginForm');

if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        validateField(emailInput);
        validateField(passwordInput);

        const errorText = passwordInput.parentElement.querySelector('.error-text');

        if (passwordInput.value !== "" && passwordInput.value.length < 6) {
            passwordInput.classList.add('error');
            passwordInput.parentElement.classList.add('has-error');

            if (errorText) {
                errorText.textContent = "Password must be at least 6 characters";
            }
        }
        else if (passwordInput.value.length >= 6) {
            login();
        }
    });
}


const signForm = document.getElementById('signForm');

if (signForm) {
    signForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        const nameInput = document.getElementById('name');

        validateField(emailInput);
        validateField(passwordInput);
        validateField(confirmInput);
        validateField(nameInput);

        if (passwordInput.value === "" ||confirmInput.value === "") {
            validateField(passwordInput);
            validateField(confirmInput);
        }
        else if (passwordInput.value !== confirmInput.value) {
            confirmInput.classList.add('error');
            confirmInput.parentElement.classList.add('has-error');

            const errorText = confirmInput.parentElement.querySelector('.error-text');
            if (errorText) {
                errorText.textContent = "Password do not match";
            }
        } else {
            signup();
        }
        
    });
}

function validateField(input) {
    if (!input) return; // safety
    const parent = input.parentElement;

    if (input.value.trim() === "") {
        input.classList.add('error');
        parent.classList.add('has-error');
    } else {
        input.classList.remove('error');
        parent.classList.remove('has-error');
    }
}

// let confirmPass = document.getElementById('confirmPassword');
// let pass = document.getElementById('password');
//     if (pass !== confirmPass) {
//         validateField(confirmInput)
//     }
