// ============================================================
// cart.js — DB version
// ============================================================
const loginBtn = document.querySelector(".login-required-btn");

if(loginBtn){
    loginBtn.addEventListener("click", () => {
        alert("Please log in first before placing your order.");
        window.location.href = "login.php";
    });
}

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // UPDATE QUANTITY — + o - button
    // Kapag naging 0 ang result → ipakita ang remove modal
    // ============================================================
    window.updateQty = async (cart_item_id, change) => {
        const qtySpan    = document.getElementById(`qty-${cart_item_id}`);
        const currentQty = parseInt(qtySpan.textContent.trim());

        // Kung - ay pipindutin at 1 na ang qty → modal agad, wag pa pumunta sa server
        if(currentQty + change <= 0) {
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

            if(result.success) {
                qtySpan.textContent = result.new_qty;
                recalcTotals();
                checkIfEmpty();
            } else {
                alert('Something went wrong: ' + result.message);
            }

        } catch(err) {
            console.error('Update qty error:', err);
            alert('Connection error. Please try again.');
        }
    };

    // ============================================================
    // MODAL — lalabas kapag naging 0 na ang qty
    // ============================================================
    function showRemoveModal(cart_item_id) {
        const card        = document.getElementById(`cart-card-${cart_item_id}`);
        const productName = card.querySelector('h4').textContent;

        const modal      = document.getElementById('remove-modal');
        const modalMsg   = document.getElementById('remove-modal-msg');
        const confirmBtn = document.getElementById('remove-confirm-btn');
        const cancelBtn  = document.getElementById('remove-cancel-btn');

        modalMsg.textContent = `Are you sure you want to remove "${productName}" from your cart?`;

        // Buksan ang modal
        modal.classList.add('active');

        // Confirm — tanggalin sa DB at DOM
        confirmBtn.onclick = async () => {
            modal.classList.remove('active');
            await removeItem(cart_item_id);
        };

        // Cancel — isara lang, walang gagawin
        cancelBtn.onclick = () => {
            modal.classList.remove('active');
        };
    }

    // ============================================================
    // REMOVE ITEM — tinatawag ng modal confirm button
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

            if(result.success) {
                const card = document.getElementById(`cart-card-${cart_item_id}`);
                if(card) card.remove();
                recalcTotals();
                checkIfEmpty();
                updateCartBadge();
            }
        } catch(err) {
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
            if(itemPriceEl) itemPriceEl.textContent = `P ${itemTotal.toFixed(2)}`;
        });

        const subtotalDisplay = document.getElementById('subtotal-amount');
        const totalDisplay    = document.getElementById('total-amount');

        if(subtotalDisplay) subtotalDisplay.textContent = `P ${grandTotal.toFixed(2)}`;
        if(totalDisplay)    totalDisplay.textContent    = `P ${grandTotal.toFixed(2)}`;
    }

    // ============================================================
    // CHECK IF EMPTY
    // ============================================================
    function checkIfEmpty() {
        const remaining  = document.querySelectorAll('.cart-item').length;
        const emptyMsg   = document.querySelector('.empty-cart');
        const aside      = document.querySelector('aside');
        const cartHeader = document.getElementById('cart-items-container');
        const cartTitle  = document.getElementById('cart-title');
        const container = document.getElementById('cart-items-container');

        if(remaining === 0) {
            if(emptyMsg)   emptyMsg.style.display  = 'block';
            if(aside)      aside.style.display      = 'none';
            if(cartTitle)  cartTitle.style.display  = 'none';
            if(container) container.innerHTML = `
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

    // ============================================================
// CHECK UNAVAILABLE ITEMS — i-disable checkout kung meron
// ============================================================
// I-call din sa removeItem para mag-update pagkatanggal ng unavailable item
const originalRemoveItem = window.removeItem;
window.removeItem = async (cart_item_id) => {
    await originalRemoveItem(cart_item_id);
    // checkUnavailableItems();
};

// checkUnavailableItems(); 

});
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
// REALTIME AVAILABILITY POLLING — cart page
// ============================================================
function applyCartAvailability(data) {
    document.querySelectorAll('.cart-item').forEach(card => {
        const productId = card.dataset.productId;
        if (!productId) return;

        const isAvailable    = data[productId];
        const alreadyFlagged = card.classList.contains('item-unavailable');

        if (!isAvailable && !alreadyFlagged) {
            card.classList.add('item-unavailable');
            const h4 = card.querySelector('h4');
            if (h4 && !card.querySelector('.unavailable-tag')) {
                const tag = document.createElement('span');
                tag.className   = 'unavailable-tag';
                tag.textContent = '⚠️ No longer available';
                h4.insertAdjacentElement('afterend', tag);
            }
        } else if (isAvailable && alreadyFlagged) {
            card.classList.remove('item-unavailable');
            const tag = card.querySelector('.unavailable-tag');
            if (tag) tag.remove();
        }
    });
    checkUnavailableItems();
}

function pollCartAvailability() {
    // Gamitin muna ang preloaded data — zero delay
    if (window.__initialAvailability) {
        applyCartAvailability(window.__initialAvailability);
        window.__initialAvailability = null;
    }

    // Fetch pa rin for subsequent updates
    fetch('get-availability.php')
        .then(res => res.json())
        .then(data => applyCartAvailability(data))
        .catch(err => console.warn('Cart availability poll failed:', err));
}

pollCartAvailability();
setInterval(pollCartAvailability, 1000);
