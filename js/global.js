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


hamburger.addEventListener("click", () => {
    navLinks.classList.toggle("active");
});

// ========== CART BADGE ==========
function updateCartBadge() {
    fetch('get_cart_count.php')
        .then(res => res.json())
        .then(data => {
            const count = data.count || 0;
            const badge = document.getElementById('cartBadge');
            const badgeMobile = document.getElementById('cartBadgeMobile');

            [badge, badgeMobile].forEach(el => {
                if (!el) return;
                if (count > 0) {
                    el.textContent = count > 99 ? '99+' : count;
                    el.classList.add('visible');
                    el.style.transform = 'scale(1.4)';
                    setTimeout(() => el.style.transform = 'scale(1)', 200);
                } else {
                    el.classList.remove('visible');
                }
            });
        });
}

// I-load agad pagbukas ng kahit anong page
updateCartBadge();