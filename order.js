document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click',function(e) {
       e.stopPropagation();

       const card = this.closest('.card');
       card.classList.toggle('flip');
});
});