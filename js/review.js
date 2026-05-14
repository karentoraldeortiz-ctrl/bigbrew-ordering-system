// ─── SUBMIT REVIEW (original, hindi binago) ───────────────────────────────────

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


// ─── EDIT REVIEW (bagong dagdag) ──────────────────────────────────────────────

let editSelectedRating = 0;

const editModal    = document.getElementById('editModal');
const closeEditBtn = document.getElementById('closeEditModal');
const saveEditBtn  = document.getElementById('saveEditBtn');
const editErrorMsg = document.getElementById('edit-error-msg');

// Dalawang open buttons: isa sa review card, isa sa write-box
const openEditBtn  = document.getElementById('openEditBtn');
const openEditBtn2 = document.getElementById('openEditBtn2');

// Basahin ang pre-selected rating (galing sa PHP) at i-highlight agad
document.querySelectorAll('#editStarContainer .star').forEach(star => {
    if (star.classList.contains('selected')) {
        editSelectedRating = parseInt(star.dataset.value);
    }

    star.addEventListener('click', () => {
        editSelectedRating = parseInt(star.dataset.value);
        document.querySelectorAll('#editStarContainer .star').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.value) <= editSelectedRating);
        });
    });
});

// I-highlight ang pre-selected stars on load
document.querySelectorAll('#editStarContainer .star').forEach(s => {
    s.classList.toggle('active', parseInt(s.dataset.value) <= editSelectedRating);
});

function openEditModal() {
    if (editModal) editModal.style.display = 'flex';
}

openEditBtn?.addEventListener('click', openEditModal);
openEditBtn2?.addEventListener('click', openEditModal);

closeEditBtn?.addEventListener('click', () => {
    editModal.style.display = 'none';
    if (editErrorMsg) editErrorMsg.style.display = 'none';
});

editModal?.addEventListener('click', (e) => {
    if (e.target === editModal) editModal.style.display = 'none';
});

saveEditBtn?.addEventListener('click', () => {
    const comment = document.getElementById('editReviewInput').value.trim();
    if (editErrorMsg) editErrorMsg.style.display = 'none';

    if (editSelectedRating === 0) {
        editErrorMsg.textContent = 'Please select a rating!';
        editErrorMsg.style.display = 'block';
        return;
    }
    if (!comment) {
        editErrorMsg.textContent = 'Please write a comment!';
        editErrorMsg.style.display = 'block';
        return;
    }

    saveEditBtn.disabled = true;
    saveEditBtn.textContent = 'Saving...';

    fetch('edit_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rating: editSelectedRating, comment })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Review updated! ✅');
            editModal.style.display = 'none';
            location.reload();
        } else {
            editErrorMsg.textContent = data.message || 'Failed to update.';
            editErrorMsg.style.display = 'block';
            saveEditBtn.disabled = false;
            saveEditBtn.textContent = '💾 I-save';
        }
    })
    .catch(() => {
        editErrorMsg.textContent = 'Network error. Please try again.';
        editErrorMsg.style.display = 'block';
        saveEditBtn.disabled = false;
        saveEditBtn.textContent = '💾 I-save';
    });
});