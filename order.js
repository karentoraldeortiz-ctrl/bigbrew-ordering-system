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
// =========================
// SAFE ELEMENT SELECTORS
// =========================
const modal = document.getElementById("productModal");
const closeModal = document.querySelector(".close-modal");

const addButtons = document.querySelectorAll(".add-btn");

const modalImg = document.getElementById("modalImg");
const modalTitle = document.getElementById("modalTitle");

const sizeOptions = document.querySelectorAll("input[name='size']");
const addonOptions = document.querySelectorAll(".addon-card input");

const totalPriceDisplay = document.getElementById("totalPriceDisplay");
const selectionSummary = document.getElementById("selectionSummary");

const qtyCount = document.getElementById("qtyCount");
const minusQty = document.getElementById("minusQty");
const plusQty = document.getElementById("plusQty");

const addToCartBtn = document.getElementById("addToCartFinal");

const notif = document.getElementById("cart-notification");
const notifMessage = document.getElementById("notif-message");

// =========================
// STATE
// =========================
let selectedBasePrice = 0;
let quantity = 1;

// =========================
// OPEN MODAL
// =========================
addButtons.forEach(btn => {
  btn.addEventListener("click", (e) => {

    const card = e.target.closest(".card");

    if (!card) return;

    const title = card.querySelector("h3")?.innerText || "";
    const img = card.querySelector("img")?.src || "";

    modalTitle.innerText = title;
    modalImg.src = img;

    resetModal();

    if (modal) {
  modal.style.display = "flex";
} else {
  console.error("productModal not found in HTML");
}
  });
});

// =========================
// CLOSE MODAL
// =========================
if (closeModal) {
  closeModal.addEventListener("click", () => {
    modal.style.display = "none";
  });
}

window.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.style.display = "none";
  }
});

// =========================
// RESET MODAL
// =========================
function resetModal() {

  selectedBasePrice = 0;
  quantity = 1;

  qtyCount.innerText = quantity;

  sizeOptions.forEach(opt => opt.checked = false);
  addonOptions.forEach(opt => opt.checked = false);

  totalPriceDisplay.innerText = "P 0";
  selectionSummary.innerText = "Please select a size";

  addToCartBtn.disabled = true;
}

// =========================
// SIZE SELECTION
// =========================
sizeOptions.forEach(option => {
  option.addEventListener("change", () => {
    selectedBasePrice = parseInt(option.value);

    updateTotal();
    updateSummary();
    checkReady();
  });
});

// =========================
// ADDONS
// =========================
addonOptions.forEach(option => {
  option.addEventListener("change", () => {
    updateTotal();
    updateSummary();
  });
});

// =========================
// QUANTITY
// =========================
plusQty.addEventListener("click", () => {
  quantity++;
  qtyCount.innerText = quantity;
  updateTotal();
});

minusQty.addEventListener("click", () => {
  if (quantity > 1) {
    quantity--;
    qtyCount.innerText = quantity;
    updateTotal();
  }
});

// =========================
// TOTAL
// =========================
function updateTotal() {

  let addonTotal = 0;

  addonOptions.forEach(opt => {
    if (opt.checked) {
      addonTotal += parseInt(opt.value);
    }
  });

  let total = (selectedBasePrice + addonTotal) * quantity;

  totalPriceDisplay.innerText = `P ${total}`;
}

// =========================
// SUMMARY
// =========================
function updateSummary() {

  let size = document.querySelector("input[name='size']:checked");
  let sizeName = size ? size.dataset.name : null;

  if (!sizeName) {
    selectionSummary.innerText = "Please select a size";
    return;
  }

  let addons = [];

  addonOptions.forEach(opt => {
    if (opt.checked) {
      addons.push(opt.dataset.name);
    }
  });

  selectionSummary.innerText =
    `Size: ${sizeName}` +
    (addons.length ? ` | Add-ons: ${addons.join(", ")}` : "");
}

// =========================
// BUTTON ENABLE
// =========================
function checkReady() {

  let sizeSelected = document.querySelector("input[name='size']:checked");

  addToCartBtn.disabled = !sizeSelected;
}

// =========================
// ADD TO CART
// =========================
addToCartBtn.addEventListener("click", () => {

  if (notif) {
    notif.style.display = "block";
    notifMessage.innerText = "Item added to cart!";

    setTimeout(() => {
      notif.style.display = "none";
    }, 2000);
  }

  modal.style.display = "none";
});

