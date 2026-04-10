
// steps.forEach(step => {
//     const btn = step.querySelector(".toggle-btn");

//     btn.addEventListener("click", () => {
//         steps.forEach(s => s.classList.remove("active"));
//         step.classList.toggle("active");
//     });
// });

document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click',function(e) {
       e.stopPropagation();

       const card = this.closest('.card');
       card.classList.toggle('flip');
});
});

// const steps = document.querySelectorAll('.step');

// steps.forEach(step => {
//     const img = step.querySelector(".toggle-btn");  

//     img.addEventListener("click", () => {
//         step.classList.toggle("active");
//     });
// });

const steps = document.querySelectorAll('.step');

steps.forEach(step => {
    const img = step.querySelector(".toggle-btn");

    img.addEventListener("click", () => {
        step.classList.toggle("active");
    })
})