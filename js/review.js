let selectedRating = 0;

const openBtn = document.getElementById('openBtn');
const modal = document.getElementById('modal');
const closeBtn = document.querySelector('.close');

if (openBtn) {
    openBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
    });
}

if (closeBtn) {
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        selectedRating = 0;
        document.querySelectorAll('.star-rating-large .star').forEach(s => s.classList.remove('active'));
        document.getElementById('reviewInput').value = '';
        document.getElementById('error-msg').style.display = 'none';
    });
}

modal?.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
});

document.querySelectorAll('.star-rating-large .star').forEach(star => {
    star.addEventListener('click', () => {
        selectedRating = parseInt(star.dataset.value);
        document.querySelectorAll('.star-rating-large .star').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.value) <= selectedRating);
        });
    });
});

document.getElementById('submitBtn')?.addEventListener('click', () => {
    const comment = document.getElementById('reviewInput').value.trim();
    const errorMsg = document.getElementById('error-msg');
    errorMsg.style.display = 'none';

    if (selectedRating === 0) {
        errorMsg.textContent = 'Please select a rating!';
        errorMsg.style.display = 'block';
        return;
    }
    if (!comment) {
        errorMsg.textContent = 'Please write a comment!';
        errorMsg.style.display = 'block';
        return;
    }

    fetch('submit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rating: selectedRating, comment, order_id: null })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Review submitted! Salamat 🎉');
            modal.style.display = 'none';
            location.reload();
        } else {
            errorMsg.textContent = data.message || 'Failed to submit.';
            errorMsg.style.display = 'block';
        }
    });
});