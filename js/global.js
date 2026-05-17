const hamburger = document.getElementById("hamburger");

/* ===== CREATE MOBILE NAV ===== */
document.addEventListener("DOMContentLoaded", () => {
  createMobileNav();
  updateCartBadge();
  fetchNotifications();
  setInterval(updateCartBadge, 5000);
  setInterval(fetchNotifications, 2000);
  setActiveNavLink();
  createActiveOrderBar();
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

      const badge       = document.getElementById("cartBadge");
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

/* ===== DESKTOP NAV ACTIVE LINK ===== */
function setActiveNavLink() {
  const currentPage = window.location.pathname.split("/").pop() || "index.php";

  document.querySelectorAll(".nav-links a").forEach(link => {
    const href = link.getAttribute("href");
    if (!href || href === "#") return;

    const linkPage = href.split("/").pop();
    if (linkPage === currentPage) {
      link.classList.add("active");
    }
  });
}

/* ===== ONGOING ORDER BAR ===== */
function createActiveOrderBar() {
  fetch("get_active_order.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      const bar = document.createElement("a");
      bar.href = `receipt.php?order_id=${data.order_id}`;
      bar.className = "active-order-bar";

      bar.innerHTML = `
        <span>Ongoing order: Pick-up (${data.pickup_display})</span>
        <i class="fa-solid fa-chevron-down"></i>
      `;

      document.body.appendChild(bar);
      document.body.classList.add("has-active-order-bar");
    })
    .catch(() => {});
}

/* ===== NOTIFICATIONS ===== */
function fetchNotifications() {
  fetch('get_notifications.php')
    .then(r => r.json())
    .then(data => {
      if (data.count === 0) return;

      data.notifications.forEach(notif => {
        if (notif.is_read == 1) return;

        const alertedKey = `notif_alerted_${notif.notification_id}`;
        if (sessionStorage.getItem(alertedKey)) return;

        sessionStorage.setItem(alertedKey, '1');
        showToast(notif.title, notif.message, `receipt.php?order_id=${notif.order_id}`);
      });

      fetch('mark_notifications_read.php');
    })
    .catch(() => {});
}

/* ===== TOAST ===== */
function showToast(title, message, link) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast-notif';
  toast.innerHTML = `
    <div class="toast-icon">🔔</div>
    <div class="toast-body">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
      <div class="toast-cta">Tap to view order →</div>
    </div>
    <button class="toast-close" aria-label="Dismiss">✕</button>
  `;

  // Click toast body — go to receipt
  toast.addEventListener('click', () => {
    window.location.href = link;
  });

  // Close button — dismiss only, no redirect
  toast.querySelector('.toast-close').addEventListener('click', e => {
    e.stopPropagation();
    dismissToast(toast);
  });

  container.appendChild(toast);

  // Auto-dismiss after 10 seconds
  setTimeout(() => dismissToast(toast), 10000);
}

function dismissToast(toast) {
  if (!toast) return;
  toast.classList.add('toast-hide');
  setTimeout(() => toast.remove(), 400);
}