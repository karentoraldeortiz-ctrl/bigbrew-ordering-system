// helper para safe lahat ng numbers
function cleanNumber(value) {
    if (typeof value === "string") {
        value = value.replace(/,/g, "");
    }
    return Number(value) || 0;
}

// browser event ng JS
document.addEventListener('DOMContentLoaded', () => {
    const historyList = document.getElementById('order-history-list');
    const history = JSON.parse(localStorage.getItem('orderHistory')) || [];

    if (history.length === 0) {
        historyList.innerHTML = `
            <div class="empty-history">
                <p>No orders yet. Start brewing!</p>
                <a href="menu.html" class="btn-order-now">Order Now</a>
            </div>`;
        return;
    }

    historyList.innerHTML = history.map(order => {
        let total = cleanNumber(order.total);

        return `
            <div class="orderbox">
                <div class="order-box-header">
                    <div class="OB-header1">
                        <h3>#${order.orderId}</h3>
                        <div class="status-tag">${order.status || 'Completed'}</div>
                    </div>
                    <div class="OB-header2">
                        <i class="fa-solid fa-clock"></i>
                        <div>${order.time || 'ASAP'}</div>
                    </div>
                </div>

                <div class="order-box-body">
                    <ul>
                        ${order.items.map(item => {
                            const qty = cleanNumber(item.qty || item.quantity);
                            return `<li>${item.name} x${qty}</li>`;
                        }).join('')}
                    </ul>
                </div>

                <div class="hr"><hr></div>   

                <div class="order-total">
                    <div><p>${order.date}</p></div>
                    <div><h4>Total: ₱${total.toLocaleString()}</h4></div>   
                </div>
            </div>

            <div class="order-actions">

                <button onclick="viewReceipt(${order.orderId})" class="view-btn">
                    View Receipt
                </button>

                ${
                  order.review
                    ? `<button class="reviewed-btn" disabled>Reviewed</button>`
                    : `<button onclick="openReview(${order.orderId})" class="review-btn">
                          Add Review
                       </button>`
                }

            </div>
        `;
    }).join('');
});


// ==========================
// VIEW RECEIPT
// ==========================
function viewReceipt(orderId) {
    const history = JSON.parse(localStorage.getItem("orderHistory")) || [];
    const order = history.find(o => o.orderId == orderId);

    if (!order) {
        console.error("Order not found!");
        return;
    }

    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receipt-content-area");

    let cleanTotal = cleanNumber(order.total);

    content.innerHTML = `
        <div class="check-icon">
            <i class="fa-regular fa-circle-check"></i>
        </div>
        <h1>Order Confirmed!</h1>
        <p>Thank you for your order, Brew! Your beverages are being prepared.</p>

        <div class="order-info-box">
            <div class="info-row line-bottom">
                <span>Order ID</span>
                <strong style="color: black">#${order.orderId}</strong>
            </div>

            <div class="info-row line-bottom">
                <span>Pickup Time</span>
                <strong class="display-time">asap</strong>
            </div>

            <div id="items-list-container">
                ${order.items ? order.items.map(item => { 
                    const quantity = cleanNumber(item.quantity || item.qty);
                    const price = cleanNumber(item.price);
                    const name = item.name || "Item";

                    return `
                        <div class="info-row">
                            <span>${name} x${quantity}</span>
                            <strong>₱${(price * quantity).toLocaleString()}</strong>
                        </div>
                    `;
                }).join('') : '<p>No items found</p>'}
            </div>

            <div class="info-row total-section">
                <span class="total-label" style="color: black">Total</span>
                <span class="total-amount">₱${cleanTotal.toLocaleString()}</span>
            </div>
        </div>

        <div class="review-display-section">
            ${order.review 
                ? `<p><strong>Rating:</strong> ${order.review.rating}/5 ⭐</p>
                   <p>"${order.review.comment}"</p>`
                : `<p style="color: #888; font-size: 0.8rem;">No review yet.</p>`
            }
        </div>
    `;

    modal.style.display = "flex";
}


// ==========================
// REVIEW
// ==========================
window.openReview = function(orderId) {
    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receipt-content-area");

    content.innerHTML = `
        <h2>Rate Order #${orderId}</h2>
        <input type="number" id="rating-${orderId}" min="1" max="5" class="feedback-input" placeholder="Rating (1-5)" />
        <textarea id="comment-${orderId}" class="feedback-input" placeholder="How was your drink?"></textarea>
        <button class="btn-submit-feedback" onclick="submitReview('${orderId}')">Submit Review</button>
    `;

    modal.style.display = "flex";
};


window.submitReview = function(orderId) {
    let history = JSON.parse(localStorage.getItem("orderHistory")) || [];
    const index = history.findIndex(o => o.orderId == orderId);

    const rating = document.getElementById(`rating-${orderId}`).value;
    const comment = document.getElementById(`comment-${orderId}`).value;

    if (!rating || rating < 1 || rating > 5) {
        alert("Please rate 1 to 5");
        return;
    }

    if (index !== -1) {
        history[index].review = { rating, comment };
        localStorage.setItem("orderHistory", JSON.stringify(history));
        alert("Thank you for your review!");

        document.getElementById("receiptModal").style.display = "none";
        location.reload(); // para mag refresh UI
    }
};


// ==========================
// MODAL CLOSE
// ==========================
function setupModalClose() {
    const modal = document.getElementById("receiptModal");
    const closeBtn = document.getElementById("closeReceipt");

    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = "none";
    }

    window.onclick = (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    };
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    setupModalClose();
});