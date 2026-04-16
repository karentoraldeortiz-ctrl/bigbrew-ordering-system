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


const modal = document.getElementById('productModal');
const addBtns = document.querySelectorAll('.add-btn');
const closeBtn = document.querySelector('.close-modal');

// dito b-base natin ung price sa selected size ng user
let currentBasePrice = 0;
let selectedSize = "";

// pag nagclick si user sa certain product, kukunin nya infoo
addBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
        const card = e.target.closest('.card-front');
        const img = card.querySelector('img').src;
        const name = card.querySelector('h3').innerText;

     // ditooo, i-update nya yung content
        document.getElementById('modalProductImg').src = img;
        document.getElementById('modalProductName').innerText = name;

       //dito naman, i-reset na nya everything
        currentBasePrice = 0;
        selectedSize = "";
        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.addon-check').forEach(c => c.checked = false);
        document.getElementById('qtyVal').innerText = "1";
        document.getElementById('btnAddToCart').disabled = true;
        
        modal.style.display = 'flex';
        calculatePrice();
    });
});

// eto, once na mag click si user ng size, kukunin yung "data-price" 
document.querySelectorAll('.size-opt').forEach(btn => {
    btn.addEventListener('click', (e) => {
        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');
        
        currentBasePrice = parseInt(e.currentTarget.getAttribute('data-price'));
        selectedSize = e.currentTarget.value;
        // eto, di sha pwede mag add to cart without selecting sizw
        document.getElementById('btnAddToCart').disabled = false;
        calculatePrice();
    });
});

// dito calculation na lang, nagpaturo ako ke gpt dito kasi ang hirap HHAHAHAHA
function calculatePrice() {
    let addonsTotal = 0;
    let names = [];

    document.querySelectorAll('.addon-check:checked').forEach(check => {
        addonsTotal += parseInt(check.getAttribute('data-price'));
        names.push(check.value);
    });

    const qty = parseInt(document.getElementById('qtyVal').innerText);
    const total = (currentBasePrice + addonsTotal) * qty;

    document.getElementById('displayPrice').innerText = currentBasePrice > 0 ? total : "0";
    
   
    if (selectedSize === "") {
        document.getElementById('selectionText').innerText = "Please select a size";
    } else {
        document.getElementById('selectionText').innerText = `${selectedSize}${names.length > 0 ? ', ' + names.join(', ') : ''}`;
    }
}


document.querySelectorAll('.addon-check').forEach(c => c.addEventListener('change', calculatePrice));
document.getElementById('btnPlus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('qtyVal').innerText);
    document.getElementById('qtyVal').innerText = ++q;
    calculatePrice();
});
document.getElementById('btnMinus').addEventListener('click', () => {
    let q = parseInt(document.getElementById('qtyVal').innerText);
    if (q > 1) {
        document.getElementById('qtyVal').innerText = --q;
        calculatePrice();
    }
});

                   
document.getElementById('btnAddToCart').addEventListener('click', () => {
    const item = {
        name: document.getElementById('modalProductName').innerText,
        size: selectedSize,
        addons: Array.from(document.querySelectorAll('.addon-check:checked')).map(c => c.value),
        price: parseInt(document.getElementById('displayPrice').innerText),
        qty: parseInt(document.getElementById('qtyVal').innerText),
        img: document.getElementById('modalProductImg').src
    };
    // ETO NASA LOCAL STORAGE LANG SHA FOR NOW, DI PA SHA MA-SHOW SA CART PAGE
    let cart = JSON.parse(localStorage.getItem('bigBrewCart')) || [];
    cart.push(item);
    localStorage.setItem('bigBrewCart', JSON.stringify(cart));

    modal.style.display = 'none';
    alert("Item added to cart!");
});

// Close modal
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };