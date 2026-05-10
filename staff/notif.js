const currentPage = window.location.pathname.split('/').pop();

function showToast() {
    const toast = document.getElementById('orderToast');
    if (toast) toast.classList.add('show');
}

function hideToast() {
    const toast = document.getElementById('orderToast');
    if (toast) toast.classList.remove('show');
}

// Initialize lastSeenOrderId on first load
if (!localStorage.getItem('lastSeenOrderId')) {
    localStorage.setItem('lastSeenOrderId', '0');
}

// Show toast on load if not dismissed
if (localStorage.getItem('toastDismissed') !== '1' && localStorage.getItem('hasNewOrder') === '1') {
    showToast();
}

function checkNewOrders() {
    const lastSeenId = localStorage.getItem('lastSeenOrderId') || '0';

    fetch('check-new-orders.php?last_id=' + lastSeenId)
        .then(res => res.json())
        .then(data => {
            if (data.new_order_id && data.new_order_id > parseInt(lastSeenId)) {
                // Brand new order came in
                localStorage.setItem('lastSeenOrderId', data.new_order_id);
                localStorage.setItem('hasNewOrder', '1');
                localStorage.removeItem('toastDismissed');
                showToast();
            } else if (localStorage.getItem('toastDismissed') !== '1' && localStorage.getItem('hasNewOrder') === '1') {
                showToast();
            }
        })
        .catch(err => console.error('Order check failed:', err));
}

function goToOrders() {
    localStorage.setItem('toastDismissed', '1');
    hideToast();
    window.location.href = 'orders.php';
}

function dismissToast() {
    localStorage.setItem('toastDismissed', '1');
    hideToast();
}

checkNewOrders();
setInterval(checkNewOrders, 5000);


// order details

        function toggleItems(orderId) {
            const dropdown = document.getElementById('items-' + orderId);
            const btn = document.getElementById('btn-' + orderId);
            dropdown.classList.toggle('open');
            btn.classList.toggle('open');
        }

        // Click card → go to order details
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('click', function () {
                window.location.href = 'order-details.php?order_id=' + this.dataset.orderId;
            });
        });