document.addEventListener('DOMContentLoaded', () => {
    const order = JSON.parse(localStorage.getItem('lastOrder'));

    if (order) {
        // --- 1. DISPLAY LOGIC ---
        document.getElementById('display-id').innerText = `#${order.orderId}`;
        document.getElementById('display-time').innerText = order.time;

        const cleanTotal = order.total.toString().replace('P', '').trim();
        document.getElementById('display-total').innerText = `P ${cleanTotal}`;

        const container = document.getElementById('items-list-container');
        let itemsHTML = `<div class="items-header">Items</div>`;

        order.items.forEach(item => {
            itemsHTML += `
                <div class="info-row">
                    <span>${item.name} x ${item.qty}</span>
                    <span>P ${item.price.toString().replace('P', '').trim()}</span>
                </div>
            `;
        });
        container.innerHTML = itemsHTML;

        // --- 2. SAVE TO HISTORY 
        
        // dito kunin current history
        let history = JSON.parse(localStorage.getItem('orderHistory')) || [];


        const isAlreadySaved = history.some(h => h.orderId === order.orderId);

        if (!isAlreadySaved) {
            const historyEntry = {
                orderId: order.orderId,
                status: "Completed",
                time: order.time,
                date: new Date().toLocaleDateString(), 
                total: cleanTotal,
                items: order.items 
            };

            history.unshift(historyEntry); 
            localStorage.setItem('orderHistory', JSON.stringify(history));
            console.log("Order saved to history!");
        }
    }
});