const currentPage = window.location.pathname.split('/').pop();
const onOrdersPage = currentPage === 'orders.php' || currentPage === 'order-details.php';

// If on orders page, clear the alert
if (onOrdersPage) {
    localStorage.removeItem('hasNewOrder');
}

// Show toast immediately if there's a stored alert
if (localStorage.getItem('hasNewOrder') === '1' && !onOrdersPage) {
    document.getElementById('orderToast').classList.add('show');
}

function checkNewOrders() {
    fetch('check-new-orders.php')
        .then(res => res.json())
        .then(data => {
            if (data.new_orders > 0 && !onOrdersPage) {
                localStorage.setItem('hasNewOrder', '1');
                document.getElementById('orderToast').classList.add('show');
            } else if (onOrdersPage) {
                localStorage.removeItem('hasNewOrder');
                document.getElementById('orderToast').classList.remove('show');
            }
        })
        .catch(err => console.error('Order check failed:', err));
}

function goToOrders() {
    localStorage.removeItem('hasNewOrder');
    window.location.href = 'orders.php';
}

// Check immediately on page load, then every 5 seconds
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
