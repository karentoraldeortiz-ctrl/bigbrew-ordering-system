const hamburger = document.getElementById("hamburger");
const navLinks = document.getElementById("navlinks");

  hamburger.addEventListener("click", () => {
    navLinks.classList.toggle("active");
    });

        // function toggleMenu() { 
        //     const navLinks = document.getElementById("navlinks");
        //     navLinks.classList.toggle("active");
        // }       

// check if logged in
function isLoggedIn() {
  return localStorage.getItem("user") !== null;
}

// if (!isLoggedIn()) {
//   alert("Please login first");
//   window.location.href = "login.html";
// }

