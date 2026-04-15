document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click',function(e) {
       e.stopPropagation();

       const card = this.closest('.card');
       card.classList.toggle('flip');
});
});


const filterButtons = document.querySelectorAll('.tab'); 
const menuItems = document.querySelectorAll('.card'); 
const noResultsMessage = document.getElementById('no-results'); 

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        const selectedCategory = button.getAttribute('data-category');
        let hasVisibleItems = false;

        menuItems.forEach(item => {
          
            if (selectedCategory === 'all' || selectedCategory === 'milk-tea') {
                
              
                if (selectedCategory === 'all' || item.classList.contains('milk-tea')) {
                    item.style.display = 'block';
                    hasVisibleItems = true;
                } else {
                    item.style.display = 'none';
                }

            } else {
               
                item.style.display = 'none';
            }
        });

      
        if (!hasVisibleItems) {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    });
});

// //==========cart notif ========//
// document.addEventListener('DOMContentLoaded', () => {
//     const addButtons = document.querySelectorAll('.add-btn');
//     const notification = document.querySelectorAll('cart-notification');

//     addButtons.forEach(button => {
//         button.addEventListener('click', (e) => {
//             const cardFront = e.target.closest('.card-front');
//             const itemName = cardFront.querySelector('h3').innerText;
//         })
//     })
// })


