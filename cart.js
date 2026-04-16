
=======
document.addEventListener('DOMContentLoaded', () => {
    // 1. DOM Elements
    const itemsContainer = document.getElementById('cart-items-container');
    const emptyMsg = document.querySelector('.empty-cart');
    const asideSummary = document.querySelector('aside');
    const subtotalDisplay = document.getElementById('subtotal-amount');
    const totalDisplay = document.getElementById('total-amount');
    const checkoutBtn = document.getElementById('checkout-btn');

    // 2. Main Display Function
    function displayCart() {
        // Kuhanin ang data mula sa LocalStorage
        let cart = JSON.parse(localStorage.getItem('bigBrewCart')) || [];

        // Check kung empty ang cart
        if (cart.length === 0) {
            emptyMsg.style.display = 'block';
            asideSummary.style.display = 'none'; // Tago ang sidebar kung walang order
            if (itemsContainer) itemsContainer.innerHTML = "";
            return;
        }

        // Kung may laman, ipakita ang sidebar at itago ang empty message
        emptyMsg.style.display = 'none';
        asideSummary.style.display = 'block';
        
        if (itemsContainer) {
            itemsContainer.innerHTML = "";
            let grandTotal = 0;

            cart.forEach((item, index) => {
                grandTotal += item.price;
                
                // I-render ang bawat item card
             itemsContainer.innerHTML += `
    <div class="cart-card">
        <img src="${item.img}" alt="${item.name}">
        
        <div class="item-info">
            <h4>${item.name}</h4>
            <p>${item.size}, ${item.addons.join(', ')}</p>
            
            <div class="qty-stepper">
                <button onclick="updateQty(${index}, -1)">-</button>
                <span>${item.qty}</span>
                <button onclick="updateQty(${index}, 1)">+</button>
            </div>
        </div>
        
        <div class="item-price">P ${item.price}</div>
    </div>
                `;
            });

            // Update subtotal at grand total sa sidebar
            subtotalDisplay.innerText = `P ${grandTotal}`;
            totalDisplay.innerText = `P ${grandTotal}`;
        }
    }

    // 3. Update Quantity Function
    window.updateQty = (index, change) => {
        let cart = JSON.parse(localStorage.getItem('bigBrewCart'));
        
        // Hanapin ang base price per unit
        let unitPrice = cart[index].price / cart[index].qty;
        
        cart[index].qty += change;

        if (cart[index].qty <= 0) {
            // Tanggalin ang item kung ang quantity ay naging zero
            if (confirm("Remove this item from cart?")) {
                cart.splice(index, 1);
            } else {
                cart[index].qty = 1; // Ibalik sa 1 kung nag-cancel
            }
        } else {
            // Update ang total price ng specific item na ito
            cart[index].price = unitPrice * cart[index].qty;
        }

        localStorage.setItem('bigBrewCart', JSON.stringify(cart));
        displayCart();
    };

    // 4. Checkout Logic
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            const cart = JSON.parse(localStorage.getItem('bigBrewCart')) || [];
            const pickupTime = document.getElementById('pick-up-time').value;
            const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
            const note = document.querySelector('.note-barista').value;

            // Dito ini-imbak ang final order details
            const finalOrder = {
                items: cart,
                time: pickupTime,
                payment: paymentMethod,
                note: note,
                total: totalDisplay.innerText
            };

            console.log("Order submitted:", finalOrder);
            alert(`Order Placed Successfully!\nTotal: ${finalOrder.total}\nPayment: ${paymentMethod}`);
            
            // Opsyonal: Linisin ang cart pagkatapos mag-checkout
            // localStorage.removeItem('bigBrewCart');
            // location.reload();
        });
    }

    // Initial Run
    displayCart();
});
>>>>>>> 75519981cc38781bcbb1c42a35bd02290b172b3b
