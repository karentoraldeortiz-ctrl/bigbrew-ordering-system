// =============================================
//   BigBrew Admin — admin.js (FIXED)
// =============================================

let allProducts = [];
let activeCategory = 'all';

// ── Image base path (admin folder is inside /admin, assets is at root) ───
// ✅ FIX: DB stores filename only, so prefix this when displaying
const IMG_BASE = '../assets/products/';

// ── Toast ─────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  t.style.background = type === 'success' ? '#4caf50' : '#e53935';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.style.display = 'none', 3000);
}

// ── Load & render products ────────────────────────────────────────────────
async function loadProducts() {
  try {
    const res = await fetch('menu.php?action=list');
    allProducts = await res.json();
    buildCategoryTabs();
    renderTable(allProducts);
  } catch {
    document.getElementById('productTableBody').innerHTML =
      '<tr><td colspan="4" style="color:red;text-align:center;padding:20px;">Failed to load products.</td></tr>';
  }
}

function buildCategoryTabs() {
  const cats = ['all', ...new Set(allProducts.map(p => p.category).filter(Boolean))];
  const tabs  = document.getElementById('categoryTabs');
  tabs.innerHTML = cats.map(c =>
    `<button class="cat-tab ${c === 'all' ? 'active' : ''}" onclick="filterCategory('${c}', this)">
      ${c === 'all' ? 'All' : c}
    </button>`
  ).join('');
}

function filterCategory(cat, btn) {
  activeCategory = cat;
  document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function filterTable() { applyFilters(); }

function applyFilters() {
  const q = (document.getElementById('searchInput').value || '').toLowerCase();
  const filtered = allProducts.filter(p => {
    const matchCat    = activeCategory === 'all' || p.category === activeCategory;
    const matchSearch = !q || p.product_name.toLowerCase().includes(q) || (p.category||'').toLowerCase().includes(q);
    return matchCat && matchSearch;
  });
  renderTable(filtered);
}

function renderTable(products) {
  const tbody = document.getElementById('productTableBody');
  if (!products.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#aaa;padding:24px;">No products found.</td></tr>';
    return;
  }
  tbody.innerHTML = products.map(p => {
    const imgUrl = p.image ? `${IMG_BASE}${p.image}` : '';
    return `
    <tr>
      <td class="td-name">
        <div class="td-name-wrap">
          <div class="td-thumb" style="${imgUrl ? `background-image:url('${imgUrl}')` : ''}">
            ${!imgUrl ? '🧋' : ''}
          </div>
          <div>
            <span class="td-product-name">${p.product_name}</span>
            <div class="td-cat">${p.category || '—'}</div>
          </div>
        </div>
      </td>
      <td class="td-status">
        <span class="status-badge ${p.is_available ? 'avail' : 'unavail'}">
          ${p.is_available ? 'Available' : 'Not Available'}
        </span>
      </td>
      <td class="td-actions">
        <button class="tbl-btn-edit" onclick="openModal(${p.product_id})">
          <i class="fa fa-pencil"></i> Edit
        </button>
        <button class="tbl-btn-avail ${p.is_available ? 'mark-unavail' : 'mark-avail'}"
          onclick="toggleAvailability(${p.product_id}, ${p.is_available})">
          ${p.is_available ? 'Mark Unavailable' : 'Mark Available'}
        </button>
        <button class="tbl-btn-del" onclick="confirmDelete(${p.product_id})">
          <i class="fa fa-trash"></i>
        </button>
      </td>
    </tr>`;
  }).join('');
}

// ── Toggle availability ───────────────────────────────────────────────────
async function toggleAvailability(id, current) {
  const newVal  = parseInt(current) ? 0 : 1;
  const product = allProducts.find(p => p.product_id == id);
  if (!product) return;
  const payload = {
    product_id: id, product_name: product.product_name,
    description: product.description || '', category: product.category || '',
    image: product.image || '', is_available: newVal, sizes: product.sizes || []
  };
  try {
    const res  = await fetch('menu.php?action=update', { method:'PUT', body: JSON.stringify(payload) });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }
    showToast(newVal ? '✅ Marked as Available.' : '🚫 Marked as Unavailable.');
    loadProducts();
  } catch { showToast('Failed to update.', 'error'); }
}

// ── Image handling ────────────────────────────────────────────────────────
function previewImage(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = ev => {
    document.getElementById('previewImg').src                  = ev.target.result;
    document.getElementById('previewImg').style.display        = 'block';
    document.getElementById('uploadPlaceholder').style.display = 'none';
    document.getElementById('replaceOverlay').style.display    = 'flex';
  };
  reader.readAsDataURL(file);
  uploadImage(file);
}

function resetImageBox() {
  document.getElementById('previewImg').src                      = '';
  document.getElementById('previewImg').style.display            = 'none';
  document.getElementById('uploadPlaceholder').style.display     = 'flex';
  document.getElementById('replaceOverlay').style.display        = 'none';
  document.getElementById('editImagePath').value                 = '';
}

function setImageFromPath(filename) {
  if (!filename) { resetImageBox(); return; }
  const img = document.getElementById('previewImg');
  // ✅ FIX: DB stores filename only — prefix IMG_BASE for display
  img.src                                                        = IMG_BASE + filename;
  img.style.display                                             = 'block';
  img.onerror                                                   = () => resetImageBox();
  document.getElementById('uploadPlaceholder').style.display   = 'none';
  document.getElementById('replaceOverlay').style.display      = 'flex';
}

async function uploadImage(file) {
  const form = new FormData();
  form.append('image', file);
  try {
    const res  = await fetch('menu.php?action=upload_image', { method:'POST', body: form });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }
    // ✅ data.path is now just the filename (e.g. "product_123.png")
    if (data.path) document.getElementById('editImagePath').value = data.path;
  } catch { showToast('Image upload failed.', 'error'); }
}

// ── Add-ons ───────────────────────────────────────────────────────────────
async function loadAddons(productId = null) {
  const body = document.getElementById('addonsBody');
  body.innerHTML = '<div class="addons-empty">Loading...</div>';

  try {
    const allRes    = await fetch('menu.php?action=addons');
    const allAddons = await allRes.json();

    let assignedIds = [];
    if (productId) {
      const assignedRes = await fetch(`menu.php?action=get_product_addons&id=${productId}`);
      const assigned    = await assignedRes.json();
      assignedIds       = assigned.map(a => parseInt(a.addon_id));
    }

    if (!allAddons.length) {
      body.innerHTML = '<div class="addons-empty">No add-ons available</div>';
      return;
    }

    body.innerHTML = allAddons.map(a => `
      <label class="addon-row ${assignedIds.includes(parseInt(a.addon_id)) ? 'checked' : ''}"
             onclick="this.classList.toggle('checked')">
        <input type="checkbox" class="addon-checkbox"
               value="${a.addon_id}"
               ${assignedIds.includes(parseInt(a.addon_id)) ? 'checked' : ''} />
        <span class="addon-row-name">${a.addon_name}</span>
        <span class="addon-row-price">₱${parseFloat(a.price).toFixed(2)}</span>
      </label>`).join('');

  } catch {
    body.innerHTML = '<div class="addons-empty" style="color:#e53935;">Failed to load</div>';
  }
}

// ── Size rows ─────────────────────────────────────────────────────────────
function renderSizeRows(sizes) {
  document.getElementById('sizeFields').innerHTML = '';
  const rows = (sizes && sizes.length)
    ? sizes
    : [{ size_name: 'Medio', price: '' }, { size_name: 'Grande', price: '' }];
  rows.forEach(s => addSizeRow(s.size_name, s.price));
}

function addSizeRow(sizeName = '', price = '') {
  const div = document.createElement('div');
  div.className = 'size-row';
  div.innerHTML = `
    <input type="text"   class="size-name-input"  placeholder="Size"  value="${sizeName}" />
    <span class="currency">₱</span>
    <input type="number" class="size-price-input" placeholder="0.00" value="${price}" step="0.01" min="0" />
    <button type="button" onclick="this.parentElement.remove()"
      style="margin-left:6px;background:none;border:none;color:#e53935;cursor:pointer;font-size:13px;">
      <i class="fa fa-times"></i>
    </button>`;
  document.getElementById('sizeFields').appendChild(div);
}

function getSizeRows() {
  return [...document.querySelectorAll('#sizeFields .size-row')]
    .map(row => ({
      size_name: row.querySelector('.size-name-input').value.trim(),
      price: parseFloat(row.querySelector('.size-price-input').value) || 0
    }))
    .filter(s => s.size_name);
}

// ── Open modal ────────────────────────────────────────────────────────────
async function openModal(productId) {
  const now = new Date();
  document.getElementById('auditDate').textContent =
    now.toLocaleDateString('en-PH', { month:'2-digit', day:'2-digit', year:'numeric' }) +
    ', ' + now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });

  loadAddons(productId);

  if (productId) {
    document.getElementById('modalTitle').textContent      = 'EDIT MENU ITEM';
    document.getElementById('btnDeleteItem').style.display = 'inline-flex';
    try {
      const res = await fetch(`menu.php?action=get&id=${productId}`);
      const p   = await res.json();
      document.getElementById('editProductId').value        = p.product_id;
      document.getElementById('editName').value             = p.product_name;
      document.getElementById('editCategory').value         = p.category || '';
      document.getElementById('editDesc').value             = p.description || '';
      document.getElementById('availabilityToggle').checked = !!p.is_available;
      // ✅ p.image is filename only — setImageFromPath handles the prefix
      document.getElementById('editImagePath').value        = p.image || '';
      setImageFromPath(p.image || '');
      renderSizeRows(p.sizes);
    } catch { showToast('Failed to load product.', 'error'); return; }
  } else {
    document.getElementById('modalTitle').textContent        = 'ADD MENU ITEM';
    document.getElementById('btnDeleteItem').style.display   = 'none';
    document.getElementById('editProductId').value           = '';
    document.getElementById('editName').value                = '';
    document.getElementById('editCategory').value            = '';
    document.getElementById('editDesc').value                = '';
    document.getElementById('availabilityToggle').checked    = true;
    resetImageBox();
    renderSizeRows([]);
  }

  updateAvailabilityLabel();
  document.getElementById('modalOverlay').classList.add('open');
}

// ── Close modal ───────────────────────────────────────────────────────────
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function closeOnBackdrop(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

function updateAvailabilityLabel() {
  document.getElementById('availabilityStatus').textContent =
    document.getElementById('availabilityToggle').checked
      ? 'Product is currently available'
      : 'Product is currently unavailable';
}

// ── Save product ──────────────────────────────────────────────────────────
async function saveProduct() {
  const id    = document.getElementById('editProductId').value;
  const name  = document.getElementById('editName').value.trim();
  const cat   = document.getElementById('editCategory').value.trim();
  const desc  = document.getElementById('editDesc').value.trim();
  const avail = document.getElementById('availabilityToggle').checked ? 1 : 0;
  const image = document.getElementById('editImagePath').value;
  const sizes = getSizeRows();

  // ── Validation ──────────────────────────────────────────────────────────
  if (!name) {
    showToast('⚠️ Product name is required.', 'error');
    document.getElementById('editName').focus();
    return;
  }

  if (!cat) {
    showToast('⚠️ Category is required.', 'error');
    document.getElementById('editCategory').focus();
    return;
  }

  if (sizes.length === 0) {
    showToast('⚠️ Add at least one size variant.', 'error');
    return;
  }

  // Check kung may size na walang price o zero price
  const invalidSize = sizes.find(s => !s.price || s.price <= 0);
  if (invalidSize) {
    showToast(`⚠️ "${invalidSize.size_name}" must have a valid price.`, 'error');
    return;
  }

  // ── Proceed sa save ─────────────────────────────────────────────────────
  const payload = { product_name: name, description: desc, category: cat, image, is_available: avail, sizes };
  try {
    let res;
    if (id) {
      payload.product_id = parseInt(id);
      res = await fetch('menu.php?action=update', { method:'PUT', body: JSON.stringify(payload) });
    } else {
      res = await fetch('menu.php?action=add', { method:'POST', body: JSON.stringify(payload) });
    }
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }

    const productId   = data.product_id || parseInt(id);
    const selectedIds = [...document.querySelectorAll('.addon-checkbox:checked')]
                          .map(cb => parseInt(cb.value));

    await fetch('menu.php?action=save_product_addons', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ product_id: productId, addon_ids: selectedIds })
    });

    showToast(id ? '✅ Product updated!' : '✅ Product added!');
    closeModal();
    loadProducts();
  } catch { showToast('Save failed. Try again.', 'error'); }
}
// ── Delete ────────────────────────────────────────────────────────────────
async function confirmDelete(productId) {
  if (!confirm('Delete this product?')) return;
  try {
    const res  = await fetch('menu.php?action=delete', {
      method: 'DELETE', body: JSON.stringify({ product_id: productId })
    });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }
    showToast('Product deleted.');
    loadProducts();
  } catch { showToast('Delete failed.', 'error'); }
}

async function deleteProductFromModal() {
  const id = document.getElementById('editProductId').value;
  if (!id || !confirm('Delete this product?')) return;
  try {
    const res  = await fetch('menu.php?action=delete', {
      method: 'DELETE', body: JSON.stringify({ product_id: parseInt(id) })
    });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }
    showToast('Product deleted.');
    closeModal();
    loadProducts();
  } catch { showToast('Delete failed.', 'error'); }
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadProducts);