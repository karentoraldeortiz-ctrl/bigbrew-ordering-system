document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const card = this.closest('.card');
        card.classList.toggle('flip');
    });
});

const steps = document.querySelectorAll('.step');
steps.forEach(step => {
    const img = step.querySelector(".toggle-btn");
    if (!img) return;
    img.addEventListener("click", () => {
        step.classList.toggle("active");
    });
});