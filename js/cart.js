// ============================================================
// cart.js — DB version
// ============================================================

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

        modalMsg.textContent = `Remove "${productName}" from your cart?`;

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

        if(remaining === 0) {
            if(emptyMsg)   emptyMsg.style.display  = 'block';
            if(aside)      aside.style.display      = 'none';
            if(cartTitle)  cartTitle.style.display  = 'none';
        }
    }

    // Initial run
    recalcTotals();
});