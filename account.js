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
                        <p>No orders yet. Start brewing! ☕</p>
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
                
            `).join(''); 
        });

        // .join('');  ends the .map() loop
        // cino-convert all orders html strings into one big string


// Get elements
const modal = document.getElementById('editProfileModal');
const editBtn = document.querySelector('.edit-profile');
const closeBtn = document.getElementById('closeBtn');
const closeIcon = document.querySelector('.close-profile-modal');
const form = document.getElementById('editProfileForm');

// OPEN MODAL
if (editBtn) {
    editBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
    });
}

// CLOSE MODAL (Cancel button)
if (closeBtn) {
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });
}

// CLOSE MODAL (X icon)
if (closeIcon) {
    closeIcon.addEventListener('click', () => {
        modal.style.display = 'none';
    });
}

// CLOSE when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

// HANDLE FORM SUBMIT
form.addEventListener('submit', (e) => {
    e.preventDefault();

    const newName = document.getElementById('editName').value;

    // Update profile display
    const userNameDisplay = document.getElementById('user-name');
    if (userNameDisplay && newName) {
        userNameDisplay.innerText = newName;
    }

    modal.style.display = 'none';
    alert("Profile updated successfully!");
});