function login() {
    alert("Login successful");
    window.location.href = "/bigbrew-ordering-system/index.php";
}

function logout() {
  window.location.href = "/bigbrew-ordering-system/index.php";
}

// function signup() {
//     const email = document.getElementById("email").value;
//     localStorage.setItem("user", email);

//     alert("Account created successfully.");
//     window.location.href = "login.html";
// }

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
            loginForm.submit();
        }
    });
}

// const signForm = document.getElementById('signForm');

// if (signForm) {
//     signForm.addEventListener('submit', (e) => {

//         const passwordInput = document.getElementById('password');
//         const confirmInput = document.getElementById('confirmPassword');

//         if (passwordInput.value !== confirmInput.value) {
//             e.preventDefault();
//             alert("Password do not match");
//         }

//     });
// }
// const signForm = document.getElementById('signForm');

// if (signForm) {
//     signForm.addEventListener('submit', (e) => {
//         // e.preventDefault();

//         const emailInput = document.getElementById('email');
//         const passwordInput = document.getElementById('password');
//         const confirmInput = document.getElementById('confirmPassword');
//         const nameInput = document.getElementById('name');

//         validateField(emailInput);
//         validateField(passwordInput);
//         validateField(confirmInput);
//         validateField(nameInput);

//         if (passwordInput.value === "" ||confirmInput.value === "") {
//             e.preventDefault();
//             validateField(passwordInput);
//             validateField(confirmInput);
//         }
//         else if (passwordInput.value !== confirmInput.value) {
//             e.preventDefault();
//             confirmInput.classList.add('error');
//             confirmInput.parentElement.classList.add('has-error');

//             const errorText = confirmInput.parentElement.querySelector('.error-text');
//             if (errorText) {
//                 errorText.textContent = "Password do not match";
//             }
//         }
//         // signForm.submit(); // only if valid
//     });
// }

function validateField(input) {
    if (!input) return; 
    const parent = input.parentElement;

    if (input.value.trim() === "") {
        input.classList.add('error');
        parent.classList.add('has-error');
    } else {
        input.classList.remove('error');
        parent.classList.remove('has-error');
    }
}

