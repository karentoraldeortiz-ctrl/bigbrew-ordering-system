// browser event ng JS, it tells the page na structure is ready and now can safely
// access HTML elements
document.addEventListener('DOMContentLoaded', () => {
            const historyList = document.getElementById('order-history-list');
            const history = JSON.parse(localStorage.getItem('orderHistory')) || [];
            // converts string into JS array/object then store or saved the data as string
            
            // check lang if may laman yung variable then if wala, execute nya yan
            if (history.length === 0) {
                historyList.innerHTML = `
                    <div class="empty-history">
                        <p>No orders yet. Start brewing!</p>
                        <a href="menu.html" class="btn-order-now">Order Now</a>
                    </div>`;
                return;
            }

            // I-render ang bawat order mula sa localStorage
            // ni-replace ung content inside sa order history kasi yung data na nakukuha is
            // galing local storage so the data is not written in HTML, nag ch-change sha overtime
            // and diff for every user and we dont do this ma-duplicate mga order
            historyList.innerHTML = history.map(order => `
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
                            ${order.items.map(item => `
                                <li>${item.name} x${item.qty}</li>
                            `).join('')}
                        </ul>
                    </div>
                    <div class="hr"><hr></div>   
                    <div class="order-total">
                        <div><p>${order.date}</p></div>
                        <div><h4>Total: ₱${order.total}</h4></div>   
                    </div>
                </div>

                 <div class="order-actions">

            <button onclick="viewReceipt(${order.orderId})" class="view-btn">
                View Receipt
            </button>

            ${
              order.review
                ? `<button class="reviewed-btn" disabled>Reviewed </button>`
                : `<button onclick="openReview(${order.orderId})" class="review-btn">
                      Add Review
                   </button>`
            }

        </div>

    </div>
                
            `).join(''); 
        });

// view order receipt
    function viewReceipt(orderId) {
    const history = JSON.parse(localStorage.getItem("orderHistory")) || [];
    const order = history.find(o => o.orderId == orderId);

    if (!order) {
        console.error("Order not found!");
        return;
    }

    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receipt-content-area");

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
                    const quantity = item.quantity || item.qty || 0;
                    const price = item.price || 0;
                    const name = item.name || "Item";

                    return `
                        <div class="info-row">
                            <span>${name} x${quantity}</span>
                            <strong>P${(price * quantity).toLocaleString()}</strong>
                        </div>
                    `;
                }).join('') : '<p>No items found</p>'}
            </div>

            <div class="info-row total-section">
                <span class="total-label" style="color: black">Total</span>
                <span class="total-amount">P ${order.total || 0}</span>
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

// OPEN REVIEW FORM

function openReview(orderId) {
    const history = JSON.parse(localStorage.getItem("orderHistory")) || [];
    const order = history.find(o => o.orderId === orderId);

    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receipt-content-area");

    content.innerHTML = `
        <h2 style="margin-bottom: 20px;">Rate Order #${order.orderId}</h2>
        
        <div class="star-rating">
            <input type="number" id="rating-${orderId}" min="1" max="5" class="feedback-input" placeholder="Rating (1-5)" style="height: auto;"/>
        </div>

        <textarea id="comment-${orderId}" class="feedback-input" placeholder="Write your review..."></textarea>

        <button class="btn-submit-feedback" onclick="submitReview('${orderId}')">
            Submit Review
        </button>
    `;

    modal.style.display = "flex";
}

//  SUBMIT REVIEW

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
        renderOrders(); // Refresh para maging disabled ang button
    }


    const hasReviewed = order.review !== undefined && order.review !== null;


let actionButton = "";

if (hasReviewed) {
    
    actionButton = `<button class="reviewed-btn" disabled>Reviewed</button>`;
} else {
   
    actionButton = `<button class="review-btn" onclick="openReview('${order.orderId}')">Add Review</button>`;
}

// 3. display
return `
    <div class="order-card">
        <!-- ... ibang details ... -->
        <div class="order-actions">
            <button class="view-btn" onclick="viewReceipt('${order.orderId}')">View Receipt</button>
            ${actionButton}
        </div>
    </div>
`;s
};
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

// Initialize on Load
document.addEventListener('DOMContentLoaded', () => {
    setupModalClose();
    // ... (rest of your profile code)
});