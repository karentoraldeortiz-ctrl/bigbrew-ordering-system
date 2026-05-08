let selectedRating = 0; // ← GLOBAL, nasa labas

document.addEventListener('DOMContentLoaded', () => {

  const historyContainer = document.getElementById('order-history-list');

  fetch('get_orders.php')
    .then(res => res.json())
    .then(data => {
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
        const date = new Date(order.created_at).toLocaleDateString('en-PH', {
          month: 'short', day: 'numeric', year: 'numeric'
        });
        const total = parseFloat(order.total_amount).toFixed(2);
        let itemSummary = 'No items';
        if (order.items && order.items.length > 0) {
          const shown = order.items.slice(0, 2).map(i => `${i.product_name} (${i.size_name})`);
          itemSummary = shown.join(', ');
          if (order.items.length > 2) itemSummary += ` & ${order.items.length - 2} more`;
        }
        const reviewBtn = order.reviewed
          ? `<span class="btn-reviewed">✅ Reviewed</span>`
          : `<button class="btn-leave-review" onclick="openReviewModal(${order.order_id})">⭐ Review</button>`;

        html += `
          <div class="order-history-item">
            <div class="order-history-top">
              <div>
                <span class="order-history-id">#${order.order_id}</span>
                <span class="order-history-date">${date}</span>
              </div>
              <span class="order-status-badge ${statusClass}">${capitalizeFirst(order.order_status)}</span>
            </div>
            <p class="order-history-items">${itemSummary}</p>
            <div class="order-history-bottom">
              <span class="order-history-total">P ${total}</span>
              <div style="display:flex;gap:8px;align-items:center;">
                <a href="receipt.php?order_id=${order.order_id}" class="btn-view-receipt">View Receipt</a>
                ${reviewBtn}
              </div>
            </div>
          </div>
        `;
      });
      historyContainer.innerHTML = html;
    })
    .catch(err => {
      historyContainer.innerHTML = `<p class="empty-history">Failed to load orders.</p>`;
    });

  // EDIT PROFILE MODAL
  const editBtn = document.querySelector('.edit-profile');
  const modal = document.getElementById('editProfileModal');
  const closeBtn = document.getElementById('closeBtn');
  const closeBtnX = document.querySelector('.close-profile-modal');

  if (editBtn && modal) {
    editBtn.addEventListener('click', () => {
      const nameEl  = document.getElementById('user-name');
      const emailEl = document.getElementById('user-email');
      const phoneEl = document.getElementById('user-phone');
      const bdayEl  = document.getElementById('user-birthday');
      if (nameEl)  document.getElementById('editName').value    = nameEl.innerText.trim();
      if (emailEl) document.getElementById('editEmail').value   = emailEl.innerText.trim();
      if (phoneEl) document.getElementById('editPhone').value   = phoneEl.innerText.trim();
      if (bdayEl)  document.getElementById('editBirthday').value = bdayEl.innerText.trim();
      modal.style.display = 'flex';
    });
  }

  function closeModal() {
    if (modal) modal.style.display = 'none';
  }
  if (closeBtn)  closeBtn.addEventListener('click', closeModal);
  if (closeBtnX) closeBtnX.addEventListener('click', closeModal);
  if (modal) {
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  }

  const editForm = document.getElementById('editProfileForm');
  editForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const data = {
        name: document.getElementById('editName').value,
        email: document.getElementById('editEmail').value,
        phone: document.getElementById('editPhone').value,
        birthday: document.getElementById('editBirthday').value,
    };

    fetch('update_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            // i-update yung displayed name
            document.getElementById('user-name').innerText = data.name;
            alert('Profile updated!');
            closeModal();
        } else {
            alert(result.message || 'Failed to update.');
        }
    });
});

  function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // REVIEW MODAL
  window.openReviewModal = function(orderId) {
    selectedRating = 0;
    document.getElementById('review-order-id').value = orderId;
    document.getElementById('review-comment').value = '';
    document.querySelectorAll('.rev-star').forEach(s => s.style.color = '#ccc');
    const m = document.getElementById('reviewModal');
    m.style.position      = 'fixed';
    m.style.inset         = '0';
    m.style.background    = 'rgba(0,0,0,0.6)';
    m.style.display       = 'flex';
    m.style.alignItems    = 'center';
    m.style.justifyContent = 'center';
    m.style.zIndex        = '9999';
  }

  window.closeReviewModal = function() {
    document.getElementById('reviewModal').style.display = 'none';
  }

  document.querySelectorAll('.rev-star').forEach(star => {
    star.addEventListener('click', () => {
      selectedRating = parseInt(star.dataset.value);
      document.querySelectorAll('.rev-star').forEach(s => {
        s.style.color = parseInt(s.dataset.value) <= selectedRating ? '#b36b21' : '#ccc';
      });
    });
  });

  window.submitReview = function() {
    const order_id = document.getElementById('review-order-id').value;
    const comment  = document.getElementById('review-comment').value.trim();
    if (selectedRating === 0) return alert('Please select a rating!');
    fetch('submit_review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id, rating: selectedRating, comment })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Review submitted! Salamat 🎉');
        closeReviewModal();
        location.reload();
      } else {
        alert(data.message || 'Failed to submit.');
      }
    });
  }

});