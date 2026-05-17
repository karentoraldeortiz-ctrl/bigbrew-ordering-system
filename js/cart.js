// ============================================================
// cart.js — DB version (walang PHP, walang duplicate functions)
// ============================================================

const loginBtn = document.querySelector(".login-required-btn");
if (loginBtn) {
    loginBtn.addEventListener("click", () => {
        alert("Please log in first before placing your order.");
        window.location.href = "login.php";
    });
}

// ============================================================
// SHOW REMOVE MODAL — GLOBAL (labas ng DOMContentLoaded)
// ============================================================
function showRemoveModal(cart_item_id) {
    const card        = document.getElementById(`cart-card-${cart_item_id}`);
    const productName = card.querySelector('h4').textContent;

    const modal      = document.getElementById('remove-modal');
    const modalMsg   = document.getElementById('remove-modal-msg');
    const confirmBtn = document.getElementById('remove-confirm-btn');
    const cancelBtn  = document.getElementById('remove-cancel-btn');

    modalMsg.textContent = `Are you sure you want to remove "${productName}" from your cart?`;
    modal.classList.add('active');

    confirmBtn.onclick = async () => {
        modal.classList.remove('active');
        await window.removeItem(cart_item_id);
    };

    cancelBtn.onclick = () => {
        modal.classList.remove('active');
    };
}

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // UPDATE QUANTITY — + o - button
    // ============================================================
    window.updateQty = async (cart_item_id, change) => {
        const qtySpan    = document.getElementById(`qty-${cart_item_id}`);
        const currentQty = parseInt(qtySpan.textContent.trim());

        if (currentQty + change <= 0) {
            showRemoveModal(cart_item_id);
            return;
        }

        try {
            const formData = new FormData();
            formData.append('cart_item_id', cart_item_id);
            formData.append('action', 'update');
            formData.append('change', change);

            const response = await fetch('update_qty.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                qtySpan.textContent = result.new_qty;

                // I-sync ang currentSubtotal bago recalcTotals
                recalcTotals();
                checkIfEmpty();
            } else {
                alert('Something went wrong: ' + result.message);
            }

        } catch (err) {
            console.error('Update qty error:', err);
            alert('Connection error. Please try again.');
        }
    };

    // ============================================================
    // REMOVE ITEM
    // ============================================================
    window.removeItem = async (cart_item_id) => {
        try {
            const formData = new FormData();
            formData.append('cart_item_id', cart_item_id);
            formData.append('action', 'remove');

            const response = await fetch('update_qty.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const card = document.getElementById(`cart-card-${cart_item_id}`);
                if (card) card.remove();
                recalcTotals();
                checkIfEmpty();
                updateCartBadge();
                checkUnavailableItems();
            }
        } catch (err) {
            console.error('Remove item error:', err);
        }
    };

    // ============================================================
    // RECALCULATE TOTALS
    // ============================================================
    function recalcTotals() {
        let grandTotal = 0;

        document.querySelectorAll('.cart-item').forEach(card => {
            const unitPrice    = parseFloat(card.dataset.unitPrice) || 0;
            const cart_item_id = card.dataset.cartItemId;
            const qtySpan      = document.getElementById(`qty-${cart_item_id}`);
            const qty          = parseInt(qtySpan?.textContent.trim()) || 0;
            const itemTotal    = unitPrice * qty;

            grandTotal += itemTotal;

            const itemPriceEl = document.getElementById(`item-price-${cart_item_id}`);
            if (itemPriceEl) itemPriceEl.textContent = `P ${itemTotal.toFixed(2)}`;
        });

        // I-update ang global currentSubtotal para ma-sync ang GCash logic
        if (typeof currentSubtotal !== 'undefined') {
            currentSubtotal = grandTotal;
        }

        const subtotalDisplay = document.getElementById('subtotal-amount');
        const totalDisplay    = document.getElementById('total-amount');
        if (subtotalDisplay) subtotalDisplay.textContent = `P ${grandTotal.toFixed(2)}`;
        if (totalDisplay)    totalDisplay.textContent    = `P ${grandTotal.toFixed(2)}`;

        // I-refresh ang payment badge at checkout button
        if (typeof onPaymentChange === 'function') {
            onPaymentChange(typeof currentPaymentMethod !== 'undefined' ? currentPaymentMethod : 'pickup');
        }
    }

    // ============================================================
    // CHECK IF EMPTY
    // ============================================================
    function checkIfEmpty() {
        const remaining = document.querySelectorAll('.cart-item').length;
        const aside     = document.querySelector('aside');
        const container = document.getElementById('cart-items-container');

        if (remaining === 0) {
            if (aside) aside.style.display = 'none';
            if (container) container.innerHTML = `
                <div class="empty-cart">
                    <h3>Your Cart</h3>
                    <p>Your cart is empty.</p>
                    <a href="menu.php">Browse Menu</a>
                </div>
            `;
            updateCartBadge();
        }
    }

    // Initial run
    recalcTotals();
});

// ============================================================
// CHECK UNAVAILABLE ITEMS — disable checkout kung meron
// ============================================================
function checkUnavailableItems() {
    const unavailableItems = document.querySelectorAll('.cart-item.item-unavailable');
    const checkoutBtn      = document.querySelector('.checkout-btn');
    if (!checkoutBtn) return;

    if (unavailableItems.length > 0) {
        checkoutBtn.disabled      = true;
        checkoutBtn.style.opacity = '0.5';
        checkoutBtn.style.cursor  = 'not-allowed';
        checkoutBtn.title         = 'Remove unavailable items first';
    } else {
        checkoutBtn.disabled      = false;
        checkoutBtn.style.opacity = '';
        checkoutBtn.style.cursor  = '';
        checkoutBtn.title         = '';
    }
}

// ============================================================
// REALTIME AVAILABILITY POLLING
// ============================================================
function applyCartAvailability(data) {
    document.querySelectorAll('.cart-item').forEach(card => {
        const productId = card.dataset.productId;
        if (!productId) return;

        const isAvailable = data[productId];

        if (!isAvailable) {
            card.classList.add('item-unavailable');

            const h4 = card.querySelector('h4');
            if (h4 && !card.querySelector('.unavailable-tag')) {
                const tag = document.createElement('span');
                tag.className   = 'unavailable-tag';
                tag.textContent = '⚠️ No longer available';
                h4.insertAdjacentElement('afterend', tag);
            }

            const cartItemId = card.dataset.cartItemId;
            if (cartItemId && !card.querySelector('.remove-unavailable-btn')) {
                const removeBtn = document.createElement('button');
                removeBtn.className   = 'remove-unavailable-btn';
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = () => showRemoveModal(cartItemId);
                const details = card.querySelector('.cart-item-details');
                if (details) details.appendChild(removeBtn);
            }

        } else {
            card.classList.remove('item-unavailable');
            const tag = card.querySelector('.unavailable-tag');
            if (tag) tag.remove();
            const removeBtn = card.querySelector('.remove-unavailable-btn');
            if (removeBtn) removeBtn.remove();
        }
    });
    checkUnavailableItems();
}

function pollCartAvailability() {
    if (window.__initialAvailability) {
        applyCartAvailability(window.__initialAvailability);
        window.__initialAvailability = null;
    }

    fetch('get-availability.php')
        .then(res => res.json())
        .then(data => applyCartAvailability(data))
        .catch(err => console.warn('Cart availability poll failed:', err));
}

pollCartAvailability();
setInterval(pollCartAvailability, 1000);

// Clear order_success params from URL
if (window.location.search.includes('order_success=1')) {
    window.history.replaceState({}, document.title, window.location.pathname);
}