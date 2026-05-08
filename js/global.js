const hamburger = document.getElementById("hamburger");
const navLinks = document.getElementById("navlinks");

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

            // cart icon badge — may number pa rin
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.add('visible');
                    badge.style.transform = 'scale(1.4)';
                    setTimeout(() => badge.style.transform = 'scale(1)', 200);
                } else {
                    badge.classList.remove('visible');
                }
            }

            // hamburger badge — dot lang, walang number
            if (badgeMobile) {
                if (count > 0) {
                    badgeMobile.classList.add('visible');
                } else {
                    badgeMobile.classList.remove('visible');
                }
            }
        });
}

updateCartBadge();
setInterval(updateCartBadge, 5000);