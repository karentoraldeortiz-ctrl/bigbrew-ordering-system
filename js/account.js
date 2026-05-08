let selectedRating = 0;

document.addEventListener('DOMContentLoaded', () => {

  const historyContainer = document.getElementById('order-history-list');

  function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  function openReviewModal() {
    selectedRating = 0;
    document.getElementById('review-comment').value = '';
    document.querySelectorAll('.rev-star').forEach(s => {
      s.style.color = '#ccc';
      s.onclick = () => {
        selectedRating = parseInt(s.dataset.value);
        document.querySelectorAll('.rev-star').forEach(star => {
          star.style.color = parseInt(star.dataset.value) <= selectedRating ? '#b36b21' : '#ccc';
        });
      };
    });
    const m = document.getElementById('reviewModal');
    m.style.position       = 'fixed';
    m.style.inset          = '0';
    m.style.background     = 'rgba(0,0,0,0.6)';
    m.style.display        = 'flex';
    m.style.alignItems     = 'center';
    m.style.justifyContent = 'center';
    m.style.zIndex         = '9999';
  }

  function renderReviewBox() {
    fetch('check_reviewed.php')
      .then(res => res.json())
      .then(reviewData => {
        const reviewCard = document.createElement('div');
        reviewCard.className = 'write-box account-review-box';

        const btnHtml = reviewData.already_reviewed
          ? `<button disabled style="opacity:0.5;cursor:not-allowed;">✅ Already Reviewed</button>`
          : `<button id="openBtn">Write a Review</button>`;

        reviewCard.innerHTML = `
          <div class="write-review">
            <h3>Love BigBrew? Share Your Experience!</h3>
            <p>Your feedback helps us serve you better and helps others find great drinks.</p>
            ${btnHtml}
          </div>
        `;

      const accountSection = document.querySelector('.account-hero');
accountSection.appendChild(reviewCard);

        if (!reviewData.already_reviewed) {
          document.getElementById('openBtn').addEventListener('click', openReviewModal);
        }
      })
      .catch(() => {
        // Kung failed ang check, show na lang yung button
        const reviewCard = document.createElement('div');
        reviewCard.className = 'write-box account-review-box';
        reviewCard.innerHTML = `
          <div class="write-review">
            <h3>Love BigBrew? Share Your Experience!</h3>
            <p>Your feedback helps us serve you better and helps others find great drinks.</p>
            <button id="openBtn">Write a Review</button>
          </div>
        `;
        const accountContent = document.querySelector('.account-content');
        accountContent.after(reviewCard);
        document.getElementById('openBtn').addEventListener('click', openReviewModal);
      });
  }

  // --- ORDERS FETCH ---
  fetch('get_orders.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        historyContainer.innerHTML = `<p class="empty-history">Could not load orders: ${data.message || ''}</p>`;
        renderReviewBox();
        return;
      }

      const orders = data.orders;
      if (orders.length === 0) {
        historyContainer.innerHTML = `<p class="empty-history">No orders yet. <a href="menu.php">Browse our menu!</a></p>`;
        renderReviewBox();
        return;
      }

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
              </div>
            </div>
          </div>
        `;
      });

      historyContainer.innerHTML = html;
      renderReviewBox();
    })
    .catch(() => {
      historyContainer.innerHTML = `<p class="empty-history">Failed to load orders.</p>`;
      renderReviewBox();
    });

  // --- EDIT PROFILE MODAL ---
  const editBtn  = document.querySelector('.edit-profile');
  const modal    = document.getElementById('editProfileModal');
  const closeBtn = document.getElementById('closeBtn');
  const closeBtnX = document.querySelector('.close-profile-modal');

  if (editBtn && modal) {
    editBtn.addEventListener('click', () => {
      document.getElementById('editName').value     = document.getElementById('user-name').innerText.trim();
      document.getElementById('editEmail').value    = document.getElementById('user-email').innerText.trim();
      document.getElementById('editPhone').value    = document.getElementById('user-phone').innerText.trim();
      document.getElementById('editBirthday').value = document.getElementById('user-birthday').innerText.trim();
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

  document.getElementById('editProfileForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const data = {
      name:     document.getElementById('editName').value,
      email:    document.getElementById('editEmail').value,
      phone:    document.getElementById('editPhone').value,
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
        document.getElementById('user-name').innerText = data.name;
        alert('Profile updated!');
        closeModal();
      } else {
        alert(result.message || 'Failed to update.');
      }
    });
  });

  // --- REVIEW MODAL CONTROLS ---
  window.closeReviewModal = function () {
    document.getElementById('reviewModal').style.display = 'none';
  }

  window.submitReview = function () {
    const comment = document.getElementById('review-comment').value.trim();
    if (selectedRating === 0) return alert('Please select a rating!');
    fetch('submit_review.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: null, rating: selectedRating, comment })
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