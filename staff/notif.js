let lastCheck = localStorage.getItem('lastOrderCheck') || 
    new Date().toISOString().slice(0, 19).replace('T', ' ');

const currentPage = window.location.pathname.split('/').pop();
const onOrdersPage = currentPage === 'orders.php' || currentPage === 'order-details.php';

// Kung nasa orders page na, i-clear ang toast at i-update ang lastCheck
if (onOrdersPage) {
    localStorage.setItem('lastOrderCheck', new Date().toISOString().slice(0, 19).replace('T', ' '));
    localStorage.removeItem('hasNewOrder');
}

// Kung may nakaimbak na new order alert at hindi pa napupuntahan ang orders
if (localStorage.getItem('hasNewOrder') && !onOrdersPage) {
    document.getElementById('orderToast').classList.add('show');
}

function checkNewOrders() {
    fetch('check-new-orders.php?since=' + encodeURIComponent(lastCheck))
        .then(res => res.json())
        .then(data => {
            if (data.new_orders > 0 && !onOrdersPage) {
                localStorage.setItem('hasNewOrder', '1');
                document.getElementById('orderToast').classList.add('show');
            }
            lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
            localStorage.setItem('lastOrderCheck', lastCheck);
        })
        .catch(err => console.error('Order check failed:', err));
}

function goToOrders() {
    localStorage.removeItem('hasNewOrder');
    window.location.href = 'orders.php';
}

setInterval(checkNewOrders, 10000);