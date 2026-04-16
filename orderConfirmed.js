document.addEventListener('DOMContentLoaded', () => {
    const order = JSON.parse(localStorage.getItem('lastOrder'));

    if (!order) {
        window.location.href = "menu.html";
        return;
    }

    // I-populate ang basic info
    document.getElementById('display-id').innerText = `#${order.orderId}`;
    document.getElementById('display-time').innerText = order.time;
    document.getElementById('display-total').innerText = order.total;

    // I-render ang bawat item na binili
    const listContainer = document.getElementById('items-list-container');
    order.items.forEach(item => {
        listContainer.innerHTML += `
            <div class="info-row">
                <span>${item.name} x ${item.qty}</span>
                <span>P ${item.price}</span>
            </div>
        `;
    });
});
// ============== END OF ORDER CONFIRMATION BOX =====
// ======================== RATINGS ================
document.addEventListener('DOMContentLoaded', () => {
    // 1. variable ku
    const stars = document.querySelectorAll('.star');
    const submitBtn = document.querySelector('.btn-submit-feedback');
    const feedbackText = document.querySelector('.feedback-input');
    let selectedRating = 0;

    // 2. STAR LOGIC
    stars.forEach(star => {
        star.addEventListener('mouseover', () => {
            resetStars();
            highlightStars(star.dataset.value);
        });

        star.addEventListener('mouseleave', () => {
            resetStars();
            if (selectedRating > 0) {
                highlightStars(selectedRating);
            }
        });

        star.addEventListener('click', () => {
            selectedRating = star.dataset.value;
            console.log("User rated:", selectedRating);
            highlightStars(selectedRating);
        });
    });

    function highlightStars(count) {
        for (let i = 0; i < count; i++) {
            if (stars[i]) stars[i].classList.add('selected');
        }
    }

    function resetStars() {
        stars.forEach(s => s.classList.remove('selected'));
    }

    // 3. SUBMIT LOGIC 
    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            const message = feedbackText.value.trim();

            if (selectedRating == 0) {
                alert("Please select a star rating first! ⭐");
                return;
            }

            console.log("Submitting feedback:", { selectedRating, message });

            alert(`Thank you for your response!\nRating: ${selectedRating} stars\nComment: ${message || "No comment"}`);

            // Reset form
            feedbackText.value = "";
            resetStars(); 
            selectedRating = 0;
        });
    }
});