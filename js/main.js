

document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click',function(e) {
       e.stopPropagation();

       const card = this.closest('.card');
       card.classList.toggle('flip');
});
});


const steps = document.querySelectorAll('.step');

steps.forEach(step => {
    const img = step.querySelector(".toggle-btn");

    img.addEventListener("click", () => {
        step.classList.toggle("active");
    })
})

// for review page
const openBtn = document.getElementById("openBtn");
const modal = document.getElementById("modal");
const closeBtn = document.querySelector(".close");

openBtn.addEventListener("click", () => {
    modal.style.display = "flex";
});

closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
});

window .addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
});

const stars = document.querySelectorAll('.star');
let selectedRating = 0; 

stars.forEach(star => {
    star.addEventListener('click', () => {
        selectedRating = star.getAttribute('data-value'); 
        
        updateStars(selectedRating);

        errorMsg.style.display = "none";
    });
});

function updateStars(rating) {
    stars.forEach(s => {
        // Kung ang value ng star ay less than or equal sa pinindot, magiging active siya
        if (s.getAttribute('data-value') <= rating) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
}


const submitBtn = document.querySelector(".submitBtn");
const errorMsg = document.getElementById('error-msg');
const reviewContainer = document.querySelector(".review-content");
const noReview = document.querySelector(".no-review");

submitBtn.addEventListener("click", () => {
    if (selectedRating === 0) {
        errorMsg.style.display = "block";
        errorMsg.textContent = "Please select a star rating first!";
        return;
    }
    
    const feedbackInput = document.querySelector('input[type="text"]');
    const feedback = feedbackInput.value.trim();
    
    if (feedback === "") return; 

    let name = localStorage.getItem('userName') || "Anonymous";


    const reviewData = {
        name: name,
        rating: selectedRating,
        feedback: feedback,
        date: new Date().toLocaleDateString()
    };

    let reviews = JSON.parse(localStorage.getItem("reviews")) || [];

    reviews.push(reviewData);

    localStorage.setItem("reviews", JSON.stringify(reviews));

    displayReview(reviewData);

    noReview.style.display = "none";

    // reset
    modal.style.display = "none";
    selectedRating = 0;
    updateStars(0);
    feedbackInput.value = "";
});

function displayReview(review) {
    const reviewContainer = document.querySelector(".review-content");

    const reviewCard = document.createElement("div");
    reviewCard.classList.add("review-card");

    reviewCard.innerHTML = `
        <div class="stars">
            ${"★".repeat(review.rating)}${"☆".repeat(5 - review.rating)}
        </div>
        <p>${review.feedback}</p>
        <hr>
        <div class="revcard-text">
        <h6>${review.name}</h6>
        <small>${review.date}</small>
        </div>
    `;

    reviewContainer.appendChild(reviewCard);
}

window.addEventListener("DOMContentLoaded", () => {
    const reviews = JSON.parse(localStorage.getItem("reviews")) || [];
    const noReview = document.querySelector(".no-review");

    if (reviews.length > 0) {
        noReview.style.display = "none";

        reviews.forEach(review => {
            displayReview(review);
        });
    }
});