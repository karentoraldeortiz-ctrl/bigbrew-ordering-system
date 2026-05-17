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

            if(result.success) {
                const card = document.getElementById(`cart-card-${cart_item_id}`);
                if(card) card.remove();
                recalcTotals();
                checkIfEmpty();
                updateCartBadge();
                checkUnavailableItems();
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

        // ── Update GCash badge + checkout button dynamically ──
        const requiresGcash = grandTotal >= 100;
        const downpayment   = requiresGcash ? (grandTotal * 0.5).toFixed(2) : 0;
        const checkoutBtn   = document.querySelector('.checkout-btn');
        const gcashBadge    = document.querySelector('.gcash-required-badge');
        const gcashModal    = document.getElementById('gcashModal');

        // Update badge
        if (gcashBadge) {
            gcashBadge.style.display = requiresGcash ? 'block' : 'none';
            gcashBadge.innerHTML = `💙 <strong>GCash Downpayment Required</strong>
                Min. downpayment: <strong>₱${parseFloat(downpayment).toLocaleString('en-PH', {minimumFractionDigits:2})}</strong>
                (50% of ₱${grandTotal.toFixed(2)} total)`;
        }

        // Update amount inside modal
        const amountValue = document.querySelector('.gcash-amount-value');
        const amountSub   = document.querySelector('.gcash-amount-sub');
        if (amountValue) amountValue.textContent = `₱${parseFloat(downpayment).toFixed(2)}`;
        if (amountSub)   amountSub.textContent   = `out of ₱${grandTotal.toFixed(2)} total`;

        // Update checkout button
        if (checkoutBtn && !checkoutBtn.disabled) {
            if (requiresGcash) {
                checkoutBtn.textContent = 'Checkout & Pay GCash';
                checkoutBtn.classList.add('gcash');
                checkoutBtn.type = 'button';

                // ✅ FIX: Check kung naka-render ang gcashModal sa DOM
                // Kung wala (dahil PHP ay hindi nag-render — subtotal < 100 sa page load),
                // i-reload ang page para ma-render ng PHP ang modal kasama ang tamang amounts.
                if (gcashModal) {
                    checkoutBtn.onclick = () => {
                        if (typeof openGCashModal === 'function') {
                            openGCashModal();
                        }
                    };
                    // Show modal container if it was hidden
                    gcashModal.style.display = '';
                } else {
                    // Modal hindi naka-render — reload para ma-generate ng PHP
                    checkoutBtn.onclick = () => {
                        window.location.reload();
                    };
                }

            } else {
                checkoutBtn.textContent = 'Checkout';
                checkoutBtn.classList.remove('gcash');
                checkoutBtn.type = 'submit';
                checkoutBtn.onclick = null;

                // Hide modal if total dropped below 100
                if (gcashModal) gcashModal.style.display = 'none';
            }
        }
    }

    // ============================================================
    // CHECK IF EMPTY
    // ============================================================
    function checkIfEmpty() {
        const remaining = document.querySelectorAll('.cart-item').length;
        const aside     = document.querySelector('aside');
        const container = document.getElementById('cart-items-container');

        if(remaining === 0) {
            if(aside) aside.style.display = 'none';
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

            // Add unavailable tag kung wala pa
            const h4 = card.querySelector('h4');
            if (h4 && !card.querySelector('.unavailable-tag')) {
                const tag = document.createElement('span');
                tag.className   = 'unavailable-tag';
                tag.textContent = '⚠️ No longer available';
                h4.insertAdjacentElement('afterend', tag);
            }

            // Add Remove button kung wala pa
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


// payment method
window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
window.__initialAvailability = <?php
    $avail_q    = mysqli_query($conn, "SELECT product_id, is_available FROM products");
    $avail_data = [];
    while ($r = mysqli_fetch_assoc($avail_q)) $avail_data[$r['product_id']] = (int)$r['is_available'];
    echo json_encode($avail_data);
?>;
window.IS_BUY_AGAIN = <?php
    echo isset($_SESSION['buy_again_order']) ? 'true' : 'false';
    unset($_SESSION['buy_again_order']);
?>;

let selectedReceiptFile = null;
let currentPaymentMethod = 'pickup';
let currentSubtotal = <?php echo $subtotal; ?>;

// ── Payment method change ─────────────────────────────────────────────────────
function onPaymentChange(method) {
    currentPaymentMethod = method;

    // Update selected styles
    document.getElementById('opt-pickup').classList.toggle('selected', method === 'pickup');
    document.getElementById('opt-gcash').classList.toggle('selected', method === 'gcash_full');

    // Update badges
    const downpayBadge = document.getElementById('gcashDownpayBadge');
    const fullBadge    = document.getElementById('gcashFullBadge');
    const btn          = document.getElementById('checkoutBtn');

    if (method === 'gcash_full') {
        downpayBadge.style.display = 'none';
        fullBadge.style.display    = 'block';
        if (btn) { btn.textContent = 'Checkout & Pay GCash'; btn.classList.add('gcash'); }
    } else {
        fullBadge.style.display = 'none';
        if (currentSubtotal >= 100) {
            downpayBadge.style.display = 'block';
            if (btn) { btn.textContent = 'Checkout & Pay GCash'; btn.classList.add('gcash'); }
        } else {
            downpayBadge.style.display = 'none';
            if (btn) { btn.textContent = 'Checkout'; btn.classList.remove('gcash'); }
        }
    }

    updateGcashModalContent();
}

function updateGcashModalContent() {
    const label  = document.getElementById('gcash-modal-label');
    const amount = document.getElementById('gcash-modal-amount');
    const sub    = document.getElementById('gcash-modal-sub');
    const title  = document.getElementById('gcash-modal-title');
    const desc   = document.getElementById('gcash-modal-desc');

    if (currentPaymentMethod === 'gcash_full') {
        if (title)  title.textContent  = 'GCash Full Payment';
        if (desc)   desc.textContent   = 'Pay the full amount via GCash.';
        if (label)  label.textContent  = 'Full Payment Amount';
        if (amount) amount.textContent = '₱' + currentSubtotal.toFixed(2);
        if (sub)    sub.textContent    = 'Full payment — nothing to pay upon pickup';
    } else {
        const dp = (currentSubtotal * 0.5).toFixed(2);
        if (title)  title.textContent  = 'GCash Downpayment';
        if (desc)   desc.textContent   = 'Orders ₱100+ require at least 50% downpayment via GCash.';
        if (label)  label.textContent  = 'Minimum Downpayment';
        if (amount) amount.textContent = '₱' + dp;
        if (sub)    sub.textContent    = 'out of ₱' + currentSubtotal.toFixed(2) + ' total';
    }
}

// ── Checkout handler ──────────────────────────────────────────────────────────
function handleCheckout() {
    const needsGcash = currentPaymentMethod === 'gcash_full' || (currentPaymentMethod === 'pickup' && currentSubtotal >= 100);
    if (needsGcash) {
        openGCashModal();
    } else {
        // Normal pickup checkout
        document.getElementById('hidden_pickup_time').value    = document.getElementById('pick-up-time').value;
        document.getElementById('hidden_notes').value          = document.getElementById('barista-note').value;
        document.getElementById('hidden_payment_method').value = 'pickup';
        document.getElementById('orderForm').submit();
    }
}

// ── Warning modal ─────────────────────────────────────────────────────────────
function openGCashModal() {
    updateGcashModalContent();
    document.getElementById('gcashPaymentWarnModal').classList.add('active');
}
function closeGcashPaymentWarn() {
    document.getElementById('gcashPaymentWarnModal').classList.remove('active');
}
function proceedToGcashModal() {
    closeGcashPaymentWarn();
    document.getElementById('gcashModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
document.getElementById('gcashPaymentWarnModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeGcashPaymentWarn();
});

// ── GCash QR Modal ────────────────────────────────────────────────────────────
function closeGCashModal() {
    document.getElementById('gcashModal').classList.remove('active');
    document.body.style.overflow = '';
}
function goToStep2() {
    document.getElementById('gcash-step-1').classList.remove('active');
    document.getElementById('gcash-step-2').classList.add('active');
    document.getElementById('step-indicator-1').classList.replace('active', 'done');
    document.getElementById('step-num-1').innerHTML = '✓';
    document.getElementById('step-indicator-2').classList.add('active');
    document.getElementById('step-line').classList.add('done');
}
function goToStep1() {
    document.getElementById('gcash-step-2').classList.remove('active');
    document.getElementById('gcash-step-1').classList.add('active');
    document.getElementById('step-indicator-1').classList.remove('done');
    document.getElementById('step-indicator-1').classList.add('active');
    document.getElementById('step-num-1').innerHTML = '1';
    document.getElementById('step-indicator-2').classList.remove('active');
    document.getElementById('step-line').classList.remove('done');
}
function handleReceiptSelect(input) {
    if (!input.files || !input.files[0]) return;
    selectedReceiptFile = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('receiptPreview').style.display = 'block';
        document.getElementById('uploadZone').style.display = 'none';
        document.getElementById('confirmOrderBtn').disabled = false;
    };
    reader.readAsDataURL(selectedReceiptFile);
}
function removeReceipt() {
    selectedReceiptFile = null;
    document.getElementById('receiptPreview').style.display = 'none';
    document.getElementById('uploadZone').style.display = 'block';
    document.getElementById('confirmOrderBtn').disabled = true;
    document.getElementById('receiptFileInput').value = '';
}

const uploadZone = document.getElementById('uploadZone');
if (uploadZone) {
    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            selectedReceiptFile = file;
            const reader = new FileReader();
            reader.onload = evt => {
                document.getElementById('previewImg').src = evt.target.result;
                document.getElementById('receiptPreview').style.display = 'block';
                document.getElementById('uploadZone').style.display = 'none';
                document.getElementById('confirmOrderBtn').disabled = false;
            };
            reader.readAsDataURL(file);
        }
    });
}

function submitOrderWithReceipt() {
    if (!selectedReceiptFile) return;
    const btn = document.getElementById('confirmOrderBtn');
    btn.disabled = true;
    btn.textContent = 'Placing Order...';

    document.getElementById('hidden_pickup_time').value    = document.getElementById('pick-up-time').value;
    document.getElementById('hidden_notes').value          = document.getElementById('barista-note').value;
    document.getElementById('hidden_payment_method').value = currentPaymentMethod;

    const dt = new DataTransfer();
    dt.items.add(selectedReceiptFile);
    document.getElementById('hiddenReceiptInput').files = dt.files;
    document.getElementById('orderForm').submit();
}

document.getElementById('gcashModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeGCashModal();
});

// Initialize badge on load
onPaymentChange('pickup');

// Clear order_success params from URL
if (window.location.search.includes('order_success=1')) {
    window.history.replaceState({}, document.title, window.location.pathname);
}