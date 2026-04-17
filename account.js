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
                
            `).join(''); 
        });

        // .join('');  ends the .map() loop
        // cino-convert all orders html strings into one big string


// Get elements
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
                formattedPhone = formattedPhone.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
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