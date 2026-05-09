const currentPage = window.location.pathname.split('/').pop();
const onOrdersPage = currentPage === 'orders.php' || currentPage === 'order-details.php';

// If on orders page, clear the alert and update lastCheck
if (onOrdersPage) {
    localStorage.removeItem('hasNewOrder');
    localStorage.setItem('lastOrderCheck', new Date().toISOString().slice(0, 19).replace('T', ' '));
}

// Show toast if there's a pending alert and we're not on orders page
if (localStorage.getItem('hasNewOrder') === '1' && !onOrdersPage) {
    document.getElementById('orderToast').classList.add('show');
}

function checkNewOrders() {
    const lastCheck = localStorage.getItem('lastOrderCheck') ||
        new Date().toISOString().slice(0, 19).replace('T', ' ');

    fetch('check-new-orders.php?since=' + encodeURIComponent(lastCheck))
        .then(res => res.json())
        .then(data => {
            // Only update lastCheck if no new orders — so we don't miss any
            if (data.new_orders > 0) {
                if (!onOrdersPage) {
                    localStorage.setItem('hasNewOrder', '1');
                    document.getElementById('orderToast').classList.add('show');
                }
            } else {
                // No new orders, safe to advance the time window
                localStorage.setItem('lastOrderCheck', new Date().toISOString().slice(0, 19).replace('T', ' '));
            }
        })
        .catch(err => console.error('Order check failed:', err));
}

function goToOrders() {
    localStorage.removeItem('hasNewOrder');
    window.location.href = 'orders.php';
}

// Check every 10 seconds
setInterval(checkNewOrders, 10000);