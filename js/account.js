document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('editProfileModal');
    const editBtn = document.querySelector('.edit-profile');
    const closeBtn = document.getElementById('closeBtn');
    const closeIcon = document.querySelector('.close-profile-modal');
    const form = document.getElementById('editProfileForm');

    function openModal() {
        if (!modal) return;
        modal.style.display = 'flex';

        document.getElementById('editName').value = '';
        document.getElementById('editEmail').value = '';
        document.getElementById('editPhone').value = '';
        document.getElementById('editBirthday').value = '';
    }

    function closeModal() {
        if (!modal) return;
        modal.style.display = 'none';
    }

    // LOAD SAVED DATA ON PAGE LOAD
    const savedName = localStorage.getItem('userName');
    const savedEmail = localStorage.getItem('userEmail');
    const savedPhone = localStorage.getItem('userPhone');
    const savedBirthday = localStorage.getItem('userBirthday');

    if (savedName) {
        const el = document.getElementById('user-name');
        if (el) el.innerText = savedName;

        const input = document.getElementById('editName');
        if (input) input.value = savedName;
    }

    if (savedEmail) {
        const el = document.getElementById('user-email');
        if (el) el.innerText = savedEmail;

        const input = document.getElementById('editEmail');
        if (input) input.value = savedEmail;
    }

    if (savedPhone) {
        const el = document.getElementById('user-phone');
        if (el) el.innerText = savedPhone;

        const input = document.getElementById('editPhone');
        if (input) input.value = savedPhone;
    }

    if (savedBirthday) {
        const el = document.getElementById('user-birthday');
        if (el) el.innerText = savedBirthday;
    }

    // OPEN MODAL
    if (editBtn) {
        editBtn.addEventListener('click', openModal);
    }

    // CLOSE MODAL BUTTON
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // CLOSE ICON
    if (closeIcon) {
        closeIcon.addEventListener('click', closeModal);
    }

    // CLOSE WHEN CLICK OUTSIDE
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // SAVE FORM
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const newName = document.getElementById('editName').value;
            const newEmail = document.getElementById('editEmail').value;
            const newPhone = document.getElementById('editPhone').value;
            const newBirthday = document.getElementById('editBirthday').value;

            // FIXED PHONE FORMAT
            let formattedPhone = newPhone ? newPhone.replace(/\D/g, '') : '';
            if (formattedPhone.length === 11) {
                formattedPhone = formattedPhone.replace(
                    /(\d{4})(\d{3})(\d{4})/,
                    '$1 $2 $3'
                );
            }

            // BIRTHDAY FORMAT
            let formattedBirthday = "";
            if (newBirthday) {
                const date = new Date(newBirthday);
                formattedBirthday = date.toLocaleDateString('en-GB');
            }

            // SAVE TO LOCALSTORAGE
            localStorage.setItem('userName', newName);
            localStorage.setItem('userEmail', newEmail);
            localStorage.setItem('userPhone', formattedPhone);
            localStorage.setItem('userBirthday', formattedBirthday);

            // UPDATE UI
            const nameDisplay = document.getElementById('user-name');
            const emailDisplay = document.getElementById('user-email');
            const phoneDisplay = document.getElementById('user-phone');
            const bdayDisplay = document.getElementById('user-birthday');

            if (nameDisplay) nameDisplay.innerText = newName;
            if (emailDisplay) emailDisplay.innerText = newEmail;
            if (phoneDisplay) phoneDisplay.innerText = formattedPhone;
            if (bdayDisplay) bdayDisplay.innerText = formattedBirthday;

            closeModal();

            alert("Profile updated successfully!");
        });
    }

});

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



// VIEW RECEIPT

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
                    const itemTotal = Number(price) * Number(quantity);

                    return `
                        <div class="info-row">
                            <span>${name} x${quantity}</span>
                            <strong>₱${itemTotal.toLocaleString()}</strong>
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



// REVIEW

window.openReview = function(orderId) {
    const modal = document.getElementById("receiptModal");
    const content = document.getElementById("receipt-content-area");

    content.innerHTML = `
        <h2>Rate Order #${orderId}</h2>
        <p>Your feedback helps us improve our service</p>
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

// MODAL CLOSE
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