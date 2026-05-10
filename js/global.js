const hamburger = document.getElementById("hamburger");

/* ===== CREATE MOBILE NAV ===== */
document.addEventListener("DOMContentLoaded", () => {
  createMobileNav();
  updateCartBadge();
  setInterval(updateCartBadge, 5000);
});

function createMobileNav() {
  const isLoggedIn = window.IS_LOGGED_IN === true;

  const overlay = document.createElement("div");
  overlay.className = "mobile-menu-overlay";

  const sideMenu = document.createElement("div");
  sideMenu.className = "mobile-side-menu";

  sideMenu.innerHTML = `
    <a href="about.php">About</a>
    <a href="reviews.php">Reviews</a>
    <a href="terms.php">Terms & Conditions</a>
    <a href="privacy.php">Privacy Policy</a>
    <a href="about.php#contact">Contact</a>
    ${isLoggedIn
      ? `<a href="logout.php" class="logout-link">Logout</a>`
      : `<a href="login.php">Login</a>`
    }
  `;

  const bottomNav = document.createElement("div");
  bottomNav.className = "mobile-bottom-nav";

  bottomNav.innerHTML = `
    <a href="index.php" data-page="index.php">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>

    <a href="menu.php" data-page="menu.php">
      <i class="fa-solid fa-mug-saucer"></i>
      <span>Menu</span>
    </a>

    <a href="cart.php" data-page="cart.php" class="bottom-cart-link">
      <i class="fa-solid fa-cart-shopping"></i>
      <span>Cart</span>
      <em id="bottomCartBadge" class="bottom-cart-badge"></em>
    </a>

    <a href="account.php" data-page="account.php">
      <i class="fa-regular fa-circle-user"></i>
      <span>Account</span>
    </a>
  `;

  document.body.appendChild(overlay);
  document.body.appendChild(sideMenu);
  document.body.appendChild(bottomNav);

  if (hamburger) {
    hamburger.addEventListener("click", () => {
      sideMenu.classList.toggle("active");
      overlay.classList.toggle("active");
      hamburger.classList.toggle("active");
    });
  }

  overlay.addEventListener("click", () => {
    sideMenu.classList.remove("active");
    overlay.classList.remove("active");
    if (hamburger) hamburger.classList.remove("active");
  });

  const currentPage = window.location.pathname.split("/").pop() || "index.php";

  document.querySelectorAll(".mobile-bottom-nav a").forEach(link => {
    if (link.dataset.page === currentPage) {
      link.classList.add("active");
    }
  });
}

/* ===== CART BADGE ===== */
function updateCartBadge() {
  fetch("get_cart_count.php")
    .then(res => res.json())
    .then(data => {
      const count = data.count || 0;

      const badge = document.getElementById("cartBadge");
      const badgeMobile = document.getElementById("cartBadgeMobile");
      const bottomBadge = document.getElementById("bottomCartBadge");

      if (badge) {
        if (count > 0) {
          badge.textContent = count > 99 ? "99+" : count;
          badge.classList.add("visible");
          badge.style.transform = "scale(1.4)";
          setTimeout(() => badge.style.transform = "scale(1)", 200);
        } else {
          badge.classList.remove("visible");
        }
      }

      if (badgeMobile) {
        if (count > 0) {
          badgeMobile.classList.add("visible");
        } else {
          badgeMobile.classList.remove("visible");
        }
      }

      if (bottomBadge) {
        if (count > 0) {
          bottomBadge.textContent = count > 99 ? "99+" : count;
          bottomBadge.classList.add("visible");
        } else {
          bottomBadge.classList.remove("visible");
        }
      }
    })
    .catch(() => {});
}




/* MOVEMENT FOR SCROLL - STAGGER EFFECT HEHE */
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
    }
  });
}, { threshold: 0.15 });

document.querySelectorAll('.reveal').forEach((el, i) => {
  el.style.transitionDelay = `${i * 0.1}s`;
  observer.observe(el);
});