document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        e.stopPropagation();
        const card = this.closest('.card');
        card.classList.toggle('flip');
    });
});

const filterButtons = document.querySelectorAll('.tab');
const menuItems = document.querySelectorAll('.card');
const noResultsMessage = document.getElementById('no-results');
document.querySelector('.tab[data-category="all"]').classList.add('active');

document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t =>
            t.classList.remove('active', 'just-activated')
        );
        tab.classList.add('active');
        void tab.offsetWidth;
        tab.classList.add('just-activated');
        setTimeout(() => tab.classList.remove('just-activated'), 650);
    });
});

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        filterButtons.forEach(b => b.classList.remove('active'));
        button.classList.add('active');

        const selectedCategory = button.getAttribute('data-category');
        let hasVisibleItems = false;

        menuItems.forEach(item => {
            if (selectedCategory === 'all' || item.classList.contains(selectedCategory)) {
                item.style.display = 'block';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });

        noResultsMessage.style.display = hasVisibleItems ? 'none' : 'block';
    });
});

// ========== POP UP WINDOW ==========
const modal = document.getElementById('productModal');
const addBtns = document.querySelectorAll('.add-btn');
const closeBtn = document.querySelector('.close-modal');
let selectedProductId = null;

let currentBasePrice = 0;
let selectedSize = "";
let selectedSizeId = null;

addBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        selectedProductId = btn.getAttribute('data-id');

        const category = btn.getAttribute('data-category');
        document.getElementById('modalProductCategory').innerText =
            category ? category.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '';

        const card = e.target.closest('.card-front');
        const img = card.querySelector('img').src;
        const name = card.querySelector('h3').innerText;

        document.getElementById('modalProductImg').src = img;
        document.getElementById('modalProductName').innerText = name;

        // Reset everything
        currentBasePrice = 0;
        selectedSize = "";
        selectedSizeId = null;

        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.addon-check').forEach(c => {
            c.checked = false;
            c.closest('label').style.display = 'none';
        });
        document.getElementById('qtyVal').innerText = "1";
        document.getElementById('btnAddToCart').disabled = true;
        document.getElementById('displayPrice').innerText = "0";
        document.getElementById('selectionText').innerText = "Please select a size";

        // Hide "no addons" message muna habang naglo-load
        const noAddonsMsg = document.getElementById('noAddonsMsg');
        if (noAddonsMsg) noAddonsMsg.style.display = 'none';

        // Build size buttons dynamically
        const sizesJSON = btn.getAttribute('data-sizes');
        const sizeContainer = document.querySelector('.size-container');
        sizeContainer.innerHTML = '';

        if (sizesJSON) {
            const sizes = JSON.parse(sizesJSON);
            sizes.forEach(size => {
                const sizeBtn = document.createElement('button');
                sizeBtn.classList.add('size-opt');
                sizeBtn.setAttribute('data-size-id', size.size_id);
                sizeBtn.setAttribute('data-price', size.price);
                sizeBtn.value = size.size_name;
                sizeBtn.innerHTML = `${size.size_name} <span>&#8369;${size.price}</span>`;

                sizeBtn.addEventListener('click', (e) => {
                    document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    currentBasePrice = parseInt(e.currentTarget.getAttribute('data-price'));
                    selectedSize = e.currentTarget.value;
                    selectedSizeId = e.currentTarget.getAttribute('data-size-id');
                    document.getElementById('btnAddToCart').disabled = false;
                    calculatePrice();
                });

                sizeContainer.appendChild(sizeBtn);
            });
        }

        // Fetch assigned addons para sa product na ito
        fetch(`admin/menu.php?action=get_product_addons&id=${selectedProductId}`)
            .then(res => res.json())
            .then(assignedAddons => {
                const assignedIds = assignedAddons.map(a => parseInt(a.addon_id));
                let visibleCount = 0;

                document.querySelectorAll('.addon-check').forEach(check => {
                    const label = check.closest('label');
                    const addonId = parseInt(check.dataset.addonId);
                    check.checked = false;

                    if (assignedIds.includes(addonId)) {
                        label.style.display = '';
                        visibleCount++;
                    } else {
                        label.style.display = 'none';
                    }
                });

                // Ipakita ang "no add ons" message kung walang assigned
                if (noAddonsMsg) {
                    noAddonsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
                }

                calculatePrice();
            })
            .catch(() => {
                document.querySelectorAll('.addon-check').forEach(check => {
                    check.checked = false;
                    check.closest('label').style.display = 'none';
                });
                if (noAddonsMsg) noAddonsMsg.style.display = 'block';
                calculatePrice();
            });

        modal.style.display = 'flex';
    });
});

// Calculation
function calculatePrice() {
    let addonsTotal = 0;
    let names = [];

    document.querySelectorAll('.addon-check:checked').forEach(check => {
        addonsTotal += parseInt(check.getAttribute('data-price'));
        names.push(check.value);
    });

    const qty = parseInt(document.getElementById('qtyVal').innerText);
    const total = (currentBasePrice + addonsTotal) * qty;

    document.getElementById('displayPrice').innerText =
        currentBasePrice > 0 ? total : "0";

    if (selectedSize === "") {
        document.getElementById('selectionText').innerText = "Please select a size";
    } else {
        document.getElementById('selectionText').innerText =
            `${selectedSize}${names.length > 0 ? ', ' + names.join(', ') : ''}`;
    }
}

document.querySelectorAll('.addon-check').forEach(c =>
    c.addEventListener('change', calculatePrice)
);

document.getElementById('btnPlus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('qtyVal').innerText);
    document.getElementById('qtyVal').innerText = ++q;
    calculatePrice();
});

document.getElementById('btnMinus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('qtyVal').innerText);
    if (q > 1) {
        document.getElementById('qtyVal').innerText = --q;
        calculatePrice();
    }
});

// Add to cart
document.getElementById('btnAddToCart').addEventListener('click', () => {
    let addonsTotal = 0;

    document.querySelectorAll('.addon-check:checked').forEach(check => {
        addonsTotal += parseInt(check.getAttribute('data-price'));
    });

    if (!selectedProductId || !selectedSizeId) {
        alert("Please select product and size");
        return;
    }

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            product_id: selectedProductId,
            size_id: selectedSizeId,
            quantity: document.getElementById('qtyVal').innerText,
            unit_price: currentBasePrice + addonsTotal,
            addons: JSON.stringify(
                Array.from(document.querySelectorAll('.addon-check:checked')).map(c => c.value)
            )
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            modal.style.display = 'none';
            showCartToast('Item added to cart!');
            updateCartBadge();
        } else {
            alert(data.message);
        }
    });
});

// Close modal
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => {
    if (e.target == modal) modal.style.display = 'none';
};

// ========== CART TOAST + BADGE ==========
function showCartToast(msg) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.innerHTML = `<span>🛒</span><span>${msg}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        toast.addEventListener('animationend', () => toast.remove());
    }, 2500);
}

// ── Realtime availability polling ──────────────────────────
function applyAvailability(availabilityMap) {
    document.querySelectorAll('.card').forEach(card => {
        const btn = card.querySelector('.add-btn');

        if (btn && btn.dataset.id) {
            card.dataset.productId = btn.dataset.id;
        }

        const resolvedPid = card.dataset.productId;
        if (!resolvedPid) return;

        const isAvailable = availabilityMap[resolvedPid];
        const front = card.querySelector('.card-front');
        const alreadyUnavailable = card.classList.contains('not-available');

        if (!isAvailable && !alreadyUnavailable) {
            card.classList.add('not-available');
            if (btn) btn.style.display = 'none';

            if (!front.querySelector('.unavailable-badge')) {
                const newBadge = document.createElement('div');
                newBadge.className = 'unavailable-badge';
                newBadge.textContent = 'Not Available';
                front.appendChild(newBadge);
            }
        } else if (isAvailable && alreadyUnavailable) {
            card.classList.remove('not-available');
            if (btn) btn.style.display = '';

            const existingBadge = front.querySelector('.unavailable-badge');
            if (existingBadge) existingBadge.remove();
        }
    });
}

// index filter into category
document.addEventListener("DOMContentLoaded", () => {
  const hash = window.location.hash.replace("#", "");

  if (hash) {
    const targetTab = document.querySelector(`.tab[data-category="${hash}"]`);

    if (targetTab) {
      targetTab.click();
    }
  }
});