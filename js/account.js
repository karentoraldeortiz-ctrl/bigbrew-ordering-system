// ============================================================
// account.js — BigBrew Maysan
// Handles: Order History loading from DB, Edit Profile modal
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

  // ============================================================
  // LOAD ORDER HISTORY FROM DATABASE
  // ============================================================
  const historyContainer = document.getElementById('order-history-list');

  fetch('get_orders.php')
    .then(res => res.json())
    .then(data => {
      console.log('get_orders response:', data); // DEBUG

      if (!data.success) {
        historyContainer.innerHTML = `<p class="empty-history">Could not load orders: ${data.message || ''}</p>`;
        return;
      }

      const orders = data.orders;

      if (orders.length === 0) {
        historyContainer.innerHTML = `<p class="empty-history">No orders yet. <a href="menu.php">Browse our menu!</a></p>`;
        return;
      }

      const pickupLabels = {
        'asap': 'ASAP',
        'in-15-min': 'In 15 minutes',
        'in-30-min': 'In 30 minutes',
        'in-45-min': 'In 45 minutes',
        'in-1-hour': 'In 1 hour',
        'in-1-5-hour': 'In 1 hour 30 minutes',
      };

      let html = '';
      orders.forEach(order => {
        const statusClass = `status-${order.order_status.toLowerCase()}`;
        const pickupDisplay = pickupLabels[order.pickup_time] || order.pickup_time;
        const date = new Date(order.created_at).toLocaleDateString('en-PH', {
          month: 'short', day: 'numeric', year: 'numeric'
        });
        const total = parseFloat(order.total_amount).toFixed(2);

        // Build short item summary (first 2 items + "and X more")
        let itemSummary = 'No items';
        if (order.items && order.items.length > 0) {
          const shown = order.items.slice(0, 2).map(i =>
            `${i.product_name} (${i.size_name})`
          );
          itemSummary = shown.join(', ');
          if (order.items.length > 2) {
            itemSummary += ` & ${order.items.length - 2} more`;
          }
        }

        html += `
          <div class="order-history-item">
            <div class="order-history-top">
              <div>
                <span class="order-history-id">#${order.order_id}</span>
                <span class="order-history-date">${date}</span>
              </div>
              <span class="order-status-badge ${statusClass}">
                ${capitalizeFirst(order.order_status)}
              </span>
            </div>
            <p class="order-history-items">${itemSummary}</p>
            <div class="order-history-bottom">
              <span class="order-history-total">P ${total}</span>
              <a href="receipt.php?order_id=${order.order_id}" class="btn-view-receipt">
                View Receipt
              </a>
            </div>
          </div>
        `;
      });

      historyContainer.innerHTML = html;
    })
    .catch(err => {
      console.error('Fetch error:', err); // DEBUG
      historyContainer.innerHTML = `<p class="empty-history">Failed to load orders. Check console for details.</p>`;
    });

  // ============================================================
  // EDIT PROFILE MODAL
  // ============================================================
  const editBtn = document.querySelector('.edit-profile');
  const modal = document.getElementById('editProfileModal');
  const closeBtn = document.getElementById('closeBtn');
  const closeBtnX = document.querySelector('.close-profile-modal');

  if (editBtn && modal) {
    editBtn.addEventListener('click', () => {
      const nameEl = document.getElementById('user-name');
      const emailEl = document.getElementById('user-email');
      const phoneEl = document.getElementById('user-phone');
      const bdayEl = document.getElementById('user-birthday');

      if (nameEl) document.getElementById('editName').value = nameEl.innerText.trim();
      if (emailEl) document.getElementById('editEmail').value = emailEl.innerText.trim();
      if (phoneEl) document.getElementById('editPhone').value = phoneEl.innerText.trim();
      if (bdayEl) document.getElementById('editBirthday').value = bdayEl.innerText.trim();

      modal.style.display = 'flex';
    });
  }

  function closeModal() {
    if (modal) modal.style.display = 'none';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (closeBtnX) closeBtnX.addEventListener('click', closeModal);

  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  }

  const editForm = document.getElementById('editProfileForm');
  if (editForm) {
    editForm.addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Profile updated! (Wire up to update_profile.php)');
      closeModal();
    });
  }

  // ============================================================
  // HELPERS
  // ============================================================
  function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

});