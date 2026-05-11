document.querySelectorAll('.flip-btn').forEach(button => {
    button.addEventListener('click', function (e) {
        e.stopPropagation();

        const card = this.closest('.card');
        card.classList.toggle('flip');
    });
});

const filterButtons = document.querySelectorAll('.tab');
const menuItems = document.querySelectorAll('.card');
const noResultsMessage = document.getElementById('no-results');
document.querySelector('.tab[data-category="all"]').classList.add('active');

document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t =>
      t.classList.remove('active', 'just-activated')
    );
    tab.classList.add('active');
    void tab.offsetWidth; // force reflow
    tab.classList.add('just-activated');
    setTimeout(() => tab.classList.remove('just-activated'), 650);
  });
});

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        filterButtons.forEach(b => b.classList.remove('active'));
        button.classList.add('active');

        const selectedCategory = button.getAttribute('data-category');
        let hasVisibleItems = false;

        menuItems.forEach(item => {
            if (selectedCategory === 'all' || item.classList.contains(selectedCategory)) {
                item.style.display = 'block';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });

        noResultsMessage.style.display = hasVisibleItems ? 'none' : 'block';
    });
});

// ========== END OF MENU PAGE JS =============

// ================ POP UP WINDOW
const modal = document.getElementById('productModal');
const addBtns = document.querySelectorAll('.add-btn');
const closeBtn = document.querySelector('.close-modal');
let selectedProductId = null;   

// dito b-base natin ung price sa selected size ng user
let currentBasePrice = 0;
let selectedSize = "";
let selectedSizeId = null;

// pag nagclick si user sa certain product, kukunin nya infoo
addBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {

        // let selectedProductId = null;
        selectedProductId = btn.getAttribute('data-id');

        const category = btn.getAttribute('data-category');
        document.getElementById('modalProductCategory').innerText = 
            category ? category.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '';

        const card = e.target.closest('.card-front');
        const img = card.querySelector('img').src;
        const name = card.querySelector('h3').innerText;

        // ditooo, i-update nya yung content
        document.getElementById('modalProductImg').src = img;
        document.getElementById('modalProductName').innerText = name;

        // dito naman, i-reset na nya everything
        currentBasePrice = 0;
        selectedSize = "";
        selectedSizeId = null;

        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.addon-check').forEach(c => c.checked = false);
        document.getElementById('qtyVal').innerText = "1";
        document.getElementById('btnAddToCart').disabled = true;

        // I-build ang size buttons dynamically galing sa DB data
            const sizesJSON   = btn.getAttribute('data-sizes');
            const sizeContainer = document.querySelector('.size-container');
            sizeContainer.innerHTML = ''; // clear muna

            if (sizesJSON) {
                const sizes = JSON.parse(sizesJSON);
                sizes.forEach(size => {
                    const sizeBtn = document.createElement('button');
                    sizeBtn.classList.add('size-opt');
                    sizeBtn.setAttribute('data-size-id', size.size_id);
                    sizeBtn.setAttribute('data-price', size.price);
                    sizeBtn.value = size.size_name;
                    sizeBtn.innerHTML = `${size.size_name} <span>₱${size.price}</span>`;

                    sizeBtn.addEventListener('click', (e) => {
                        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
                        e.currentTarget.classList.add('active');
                        currentBasePrice = parseInt(e.currentTarget.getAttribute('data-price'));
                        selectedSize     = e.currentTarget.value;
                        selectedSizeId   = e.currentTarget.getAttribute('data-size-id');
                        document.getElementById('btnAddToCart').disabled = false;
                        calculatePrice();
                    });

                    sizeContainer.appendChild(sizeBtn);
                });
            }

        modal.style.display = 'flex';
        calculatePrice();

        
    });
});

// size selection
document.querySelectorAll('.size-opt').forEach(btn => {
    btn.addEventListener('click', (e) => {
        document.querySelectorAll('.size-opt').forEach(b => b.classList.remove('active'));
        e.currentTarget.classList.add('active');

        currentBasePrice = parseInt(e.currentTarget.getAttribute('data-price'));
        selectedSize = e.currentTarget.value;
        selectedSizeId = e.currentTarget.getAttribute('data-size-id');

        document.getElementById('btnAddToCart').disabled = false;
        calculatePrice();
    });
});

// calculation
function calculatePrice() {
    let addonsTotal = 0;
    let names = [];

    document.querySelectorAll('.addon-check:checked').forEach(check => {
        addonsTotal += parseInt(check.getAttribute('data-price'));
        names.push(check.value);
    });

    const qty = parseInt(document.getElementById('qtyVal').innerText);
    const total = (currentBasePrice + addonsTotal) * qty;

    document.getElementById('displayPrice').innerText =
        currentBasePrice > 0 ? total : "0";

    if (selectedSize === "") {
        document.getElementById('selectionText').innerText = "Please select a size";
    } else {
        document.getElementById('selectionText').innerText =
            `${selectedSize}${names.length > 0 ? ', ' + names.join(', ') : ''}`;
    }
}

document.querySelectorAll('.addon-check').forEach(c =>
    c.addEventListener('change', calculatePrice)
);

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

// add to cart
document.getElementById('btnAddToCart').addEventListener('click', () => {

    let addonsTotal = 0;

    document.querySelectorAll('.addon-check:checked').forEach(check => {
    addonsTotal += parseInt(check.getAttribute('data-price'));
    
});

if (!selectedProductId || !selectedSizeId) {
    alert("Please select product and size");
    return;
}


        fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            product_id: selectedProductId,
            size_id: selectedSizeId,
            quantity: document.getElementById('qtyVal').innerText,
            unit_price: currentBasePrice + addonsTotal,
            addons: JSON.stringify(
                Array.from(document.querySelectorAll('.addon-check:checked')).map(c => c.value)
            )
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            modal.style.display = 'none';
            showCartToast('Item added to cart!');
            updateCartBadge();
        } else {
            alert(data.message);
        }
    });

});

// close modal
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => {
    if (e.target == modal) modal.style.display = 'none';
};

// ========== CART TOAST + BADGE ==========

function showCartToast(msg) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.innerHTML = `<span>🛒</span><span>${msg}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('hide');
        toast.addEventListener('animationend', () => toast.remove());
    }, 2500);
}

// ── Realtime availability polling ──────────────────────────
function applyAvailability(availabilityMap) {
    document.querySelectorAll('.card').forEach(card => {
        const btn = card.querySelector('.add-btn');
        const pid = btn ? btn.dataset.id : null;
        if (!pid) return;

        const isAvailable = availabilityMap[pid];
        const front = card.querySelector('.card-front');

        // Check current state para hindi mag-flicker kung walang pagbabago
        const alreadyUnavailable = card.classList.contains('not-available');

        if (!isAvailable && !alreadyUnavailable) {
            // Naging not available
            card.classList.add('not-available');
            btn.style.display = 'none';

            if (!front.querySelector('.unavailable-badge')) {
                const badge = document.createElement('div');
                badge.className = 'unavailable-badge';
                badge.textContent = 'Not Available';
                front.appendChild(badge);
            }

        } else if (isAvailable && alreadyUnavailable) {
            // Naging available ulit
            card.classList.remove('not-available');
            btn.style.display = '';

            const badge = front.querySelector('.unavailable-badge');
            if (badge) badge.remove();
        }
    });
}

function pollAvailability() {
    fetch('get-availability.php')
        .then(res => res.json())
        .then(data => applyAvailability(data))
        .catch(err => console.warn('Availability poll failed:', err));
}

// Poll agad on load, tapos every 10 seconds
pollAvailability();
setInterval(pollAvailability, 10000);