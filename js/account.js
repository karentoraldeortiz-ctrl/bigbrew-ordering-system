let selectedRating = 0;

document.addEventListener('DOMContentLoaded', () => {

  const historyContainer = document.getElementById('order-history-list');

  // ── CANCEL MODAL (injected once into the DOM) ──────────────────────────────
  const cancelModalHTML = `
    <div id="cancelModal" class="auth-modal-overlay" style="display:none;">
      <div class="auth-modal-card">
        <div class="auth-modal-icon">🗑️</div>
        <h3>Cancel Order?</h3>
        <p>Are you sure you want to cancel this order? This cannot be undone.</p>
        <div class="auth-modal-actions">
          <button class="auth-btn-secondary" id="cancelModalClose">Go Back</button>
          <form method="POST" action="cancel_order.php" id="cancelModalForm" style="width:100%;">
            <input type="hidden" name="order_id" id="cancelModalOrderId" value="">
            <button type="submit" class="auth-btn-danger">Yes, Cancel Order</button>
          </form>
        </div>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', cancelModalHTML);

  const cancelModal    = document.getElementById('cancelModal');
  const cancelModalClose = document.getElementById('cancelModalClose');

  function openCancelModal(orderId) {
    document.getElementById('cancelModalOrderId').value = orderId;
    cancelModal.style.display = 'flex';
  }

  function closeCancelModal() {
    cancelModal.style.display = 'none';
  }

  cancelModalClose.addEventListener('click', closeCancelModal);
  cancelModal.addEventListener('click', (e) => {
    if (e.target === cancelModal) closeCancelModal();
  });

  // ── HELPERS ────────────────────────────────────────────────────────────────
  function formatStatus(status) {
    const statusMap = {
      pending:          'Pending',
      preparing:        'Preparing',
      ready_for_pickup: 'Ready for Pickup',
      completed:        'Completed',
      cancelled:        'Cancelled'
    };
    return statusMap[status] || capitalizeFirst(status.replace(/_/g, ' '));
  }

  function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }

  // ── BUILD ACTION BUTTONS PER STATUS ───────────────────────────────────────
  // View label: "View Order" while in-progress, "View Receipt" once done/cancelled
  function buildButtons(order) {
    const id     = order.order_id;
    const status = order.order_status.toLowerCase();

    const isFinished = status === 'completed' || status === 'cancelled';
    const viewLabel  = isFinished ? 'View Receipt' : 'View Order';
    const viewBtn    = `<a href="receipt.php?order_id=${id}" class="btn-view-receipt">${viewLabel}</a>`;

    if (status === 'pending') {
      // Active Cancel + View Order
      return `
        ${viewBtn}
        <button class="btn-cancel-order-history btn-cancel-active"
                data-order-id="${id}">
          Cancel
        </button>
      `;
    }

    if (status === 'preparing') {
      // Disabled Cancel (shows tooltip why) + View Order
      return `
        ${viewBtn}
        <button class="btn-cancel-order-history btn-cancel-disabled"
                disabled
                title="Cannot cancel — order is already being prepared.">
          Cancel
        </button>
      `;
    }

    if (status === 'ready_for_pickup') {
      // No cancel button at all — too late, no point showing it
      return `${viewBtn}`;
    }

    if (status === 'completed') {
      // View Receipt + Buy Again
      return `
        ${viewBtn}
        <form method="POST" action="buy_again.php" style="margin:0;">
          <input type="hidden" name="order_id" value="${id}">
          <button type="submit" class="btn-buy-again-history">Buy Again</button>
        </form>
      `;
    }

    if (status === 'cancelled') {
      // View Receipt + Reorder (order was cancelled, not enjoyed)
      return `
        ${viewBtn}
        <form method="POST" action="buy_again.php" style="margin:0;">
          <input type="hidden" name="order_id" value="${id}">
          <button type="submit" class="btn-reorder-history">Reorder</button>
        </form>
      `;
    }

    // Fallback
    return `${viewBtn}`;
  }

  // ── LOAD ORDERS ────────────────────────────────────────────────────────────
  function loadOrders() {
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

        let html = '';

        orders.forEach(order => {
          const status      = order.order_status.toLowerCase();
          const statusClass = `status-${status.replace(/_/g, '-')}`;

          const date = new Date(order.created_at).toLocaleDateString('en-PH', {
            month: 'short',
            day:   'numeric',
            year:  'numeric'
          });

          const total = parseFloat(order.total_amount).toFixed(2);

          let itemSummary = 'No items';
          if (order.items && order.items.length > 0) {
            const shown = order.items.slice(0, 2).map(i => {
              const category = i.category
                ? i.category.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
                : 'No Category';
              return `${i.product_name} - ${category} (${i.size_name})`;
            });
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
                  ${formatStatus(order.order_status)}
                </span>
              </div>

              <p class="order-history-items">${itemSummary}</p>

              <div class="order-history-bottom">
                <span class="order-history-total">P ${total}</span>
                <div class="order-history-actions">
                  ${buildButtons(order)}
                </div>
              </div>
            </div>
          `;
        });

        historyContainer.innerHTML = html;

        // Attach cancel button listeners (re-attached every render)
        historyContainer.querySelectorAll('.btn-cancel-active').forEach(btn => {
          btn.addEventListener('click', () => {
            openCancelModal(btn.dataset.orderId);
          });
        });
      })
      .catch(() => {
        historyContainer.innerHTML = `<p class="empty-history">Failed to load orders.</p>`;
      });
  }

  loadOrders();
  setInterval(loadOrders, 1000);

  // ── REVIEW BOX ─────────────────────────────────────────────────────────────
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

        const btnHtml = !reviewData.has_orders
          ? `<button disabled style="opacity:0.5;cursor:not-allowed;">Order first to leave a review</button>`
          : reviewData.already_reviewed
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

        if (reviewData.has_orders && !reviewData.already_reviewed) {
          document.getElementById('openBtn').addEventListener('click', openReviewModal);
        }
      })
      .catch(() => {
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

  renderReviewBox();

  // ── EDIT PROFILE MODAL ─────────────────────────────────────────────────────
  const editBtn   = document.querySelector('.edit-profile');
  const modal     = document.getElementById('editProfileModal');
  const closeBtn  = document.getElementById('closeBtn');
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

  // ── REVIEW MODAL CONTROLS ──────────────────────────────────────────────────
  window.closeReviewModal = function () {
    document.getElementById('reviewModal').style.display = 'none';
  };

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
  };

});