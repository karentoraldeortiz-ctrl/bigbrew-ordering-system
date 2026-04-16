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

// =========================
// ELEMENTS
// =========================
const modal = document.getElementById("productModal");
const closeModal = document.querySelector(".close-btn");

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
let currentProduct = {};

// =========================
// OPEN MODAL
// =========================
addButtons.forEach(btn => {
  btn.addEventListener("click", () => {

    const card = btn.closest(".card");
    const title = card.querySelector("h3")?.innerText;
    const img = card.querySelector("img")?.src;

    currentProduct = { title, img };

    modalTitle.innerText = title;
    modalImg.src = img;

    resetModal();
    modal.style.display = "flex";
  });
});

// =========================
// CLOSE MODAL
// =========================
closeModal.addEventListener("click", () => {
  modal.style.display = "none";
});

window.addEventListener("click", (e) => {
  if (e.target === modal) {
    modal.style.display = "none";
  }
});

// =========================
// RESET
// =========================
function resetModal() {
  selectedBasePrice = 0;
  quantity = 1;

  qtyCount.innerText = quantity;

  sizeOptions.forEach(opt => opt.checked = false);
  addonOptions.forEach(opt => opt.checked = false);

  totalPriceDisplay.innerText = "₱0";
  selectionSummary.innerText = "Please select a size";

  addToCartBtn.disabled = true;
}

// =========================
// SIZE SELECT
// =========================
sizeOptions.forEach(option => {
  option.addEventListener("change", () => {
    selectedBasePrice = Number(option.value);

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
// QTY
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
// TOTAL CALCULATION
// =========================
function updateTotal() {

  let addonTotal = 0;

  addonOptions.forEach(opt => {
    if (opt.checked) {
      addonTotal += Number(opt.value);
    }
  });

  let total = (selectedBasePrice + addonTotal) * quantity;

  totalPriceDisplay.innerText = `₱${total}`;
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
// ENABLE BUTTON
// =========================
function checkReady() {
  let sizeSelected = document.querySelector("input[name='size']:checked");
  addToCartBtn.disabled = !sizeSelected;
}

// =========================
// ADD TO CART
// =========================
addToCartBtn.addEventListener("click", () => {

  const item = {
    name: currentProduct.title,
    image: currentProduct.img,
    price: totalPriceDisplay.innerText
  };

  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  cart.push(item);
  localStorage.setItem("cart", JSON.stringify(cart));

  notif.style.display = "block";
  notifMessage.innerText = "Item added to cart!";

  setTimeout(() => {
    notif.style.display = "none";
  }, 2000);

  modal.style.display = "none";
});