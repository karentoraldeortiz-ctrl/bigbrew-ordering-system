// ── Known order IDs on first load ────────────────────────────────────────
const knownOrderIds = new Set(
    [...document.querySelectorAll('.order-card')].map(c => c.dataset.orderId)
);

const filterPending = new URLSearchParams(window.location.search).get('filter') === 'pending';

// ── Silent poll ───────────────────────────────────────────────────────────
async function pollOrders() {
    try {
        const url = 'orders-api.php' + (filterPending ? '?filter=pending' : '');
        const res  = await fetch(url);
        const orders = await res.json();

        // Check for new orders
        let hasNew = false;
        for (const order of orders) {
            if (!knownOrderIds.has(String(order.order_id))) {
                hasNew = true;
                knownOrderIds.add(String(order.order_id));
                prependOrderCard(order);
            }
        }

        if (hasNew) showToast();

        // Update statuses silently for existing cards
        for (const order of orders) {
            updateCardStatus(order.order_id, order.order_status);
        }

    } catch (e) {
        console.warn('Poll failed:', e);
    }
}

// ── Build and prepend a new order card ───────────────────────────────────
function prependOrderCard(order) {
    const oid     = order.order_id;
    const padded  = String(oid).padStart(3, '0');
    const statusBg = getStatusBg(order.order_status);

    const itemsHtml = order.items.length === 0
        ? `<p style="font-size:13px;color:#aaa;">No items found.</p>`
        : order.items.map(item => `
            <div class="order-item-row">
                <div>
                    <span class="order-item-name">
                        ${item.product_name ?? 'Unknown'} (${item.size_name ?? 'N/A'})
                    </span>
                    ${item.addons ? `<span style="color:#aaa;font-size:12px;"> · ${item.addons}</span>` : ''}
                    <span style="color:#aaa;"> x${item.quantity}</span>
                </div>
                <span class="order-item-price">P ${(item.unit_price * item.quantity).toFixed(2)}</span>
            </div>`).join('');

    const notesHtml = order.notes
        ? `<p class="order-notes">📝 Note: ${order.notes}</p>` : '';

    const card = document.createElement('div');
    card.className = 'order-card new-order-highlight'; // flash animation
    card.dataset.orderId = oid;
    card.innerHTML = `
        <div class="order-card-top">
            <div class="card-left">
                <h4>ORD-${padded}</h4>
                <div class="meta-row">
                    <i class="fa fa-clock"></i> ${order.created_display}
                </div>
                <span class="pickup-badge">⏱ ${order.pickup_display}</span>
            </div>
            <div class="card-right">
                <div class="card-status">
                    <form method="POST" onclick="event.stopPropagation()">
                        <input type="hidden" name="order_id" value="${oid}">
                        <select name="status" onchange="dismissToast(); this.form.submit();"
                                style="background:${statusBg}">
                            <option value="pending"          ${order.order_status === 'pending'          ? 'selected' : ''}>Pending</option>
                            <option value="preparing"        ${order.order_status === 'preparing'        ? 'selected' : ''}>Preparing</option>
                            <option value="ready_for_pickup" ${order.order_status === 'ready_for_pickup' ? 'selected' : ''}>Ready for Pickup</option>
                            <option value="completed"        ${order.order_status === 'completed'        ? 'selected' : ''}>Completed</option>
                            <option value="cancelled"        ${order.order_status === 'cancelled'        ? 'selected' : ''}>Cancelled</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </div>
            </div>
        </div>
        <div class="order-items-dropdown" id="items-${oid}">
            ${itemsHtml}${notesHtml}
        </div>`;

    // Click → go to order details
    card.addEventListener('click', function () {
        dismissToast();
        window.location.href = 'order-details.php?order_id=' + oid;
    });

    const section = document.querySelector('.orders-content');
    section.prepend(card);

    // Remove highlight after animation
    setTimeout(() => card.classList.remove('new-order-highlight'), 3000);
}

// ── Update status color of existing card's select ────────────────────────
function updateCardStatus(orderId, status) {
    const card   = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
    if (!card) return;
    const select = card.querySelector('select[name="status"]');
    if (!select || document.activeElement === select) return; // skip if staff is using it
    select.style.background = getStatusBg(status);
    select.value = status;
}

// ── Status background colors ──────────────────────────────────────────────
function getStatusBg(status) {
    const map = {
        completed:        'rgba(180,180,180,0.35)',
        ready_for_pickup: 'rgba(136,214,108,0.5)',
        cancelled:        'rgba(255,100,100,0.3)',
        preparing:        'rgba(100,150,255,0.3)',
        pending:          'rgba(255,220,100,0.5)',
    };
    return map[status] ?? 'rgba(255,220,100,0.5)';
}

// ── Toast ─────────────────────────────────────────────────────────────────
function showToast() {
    const toast = document.getElementById('orderToast');
    if (toast) toast.classList.add('show');
}

// ── Click card → order details ────────────────────────────────────────────
document.querySelectorAll('.order-card').forEach(card => {
    card.addEventListener('click', function () {
        dismissToast();
        window.location.href = 'order-details.php?order_id=' + this.dataset.orderId;
    });
});

// ── Start polling every 2 seconds ────────────────────────────────────────
setInterval(pollOrders, 1000);