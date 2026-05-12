<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Menu Management | BigBrew Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="admin.css" />
  </head>
  <body>

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
      <div class="logo">
        <img src="../assets/logo/bbmaysan.png" alt="BigBrew"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
        <div class="logo-fallback">
          <i class="fa fa-coffee"></i><span>BIGBREW</span>
        </div>
      </div>
      <hr />
      <div class="main-menu">
        <h6>MAIN MENU</h6>
        <div class="dash-tab">
          <a href="dashboard.php"><h3><i class="fa fa-dashboard"></i> Dashboard</h3></a>
        </div>
        <div class="menu-tab active">
          <a href="menu.php"><h3><i class="fa fa-utensils"></i> Menu Management</h3></a>
        </div>
        <div class="reviews-tab">
          <a href="reviews.php"><h3><i class="fa fa-star"></i> Reviews</h3></a>
        </div>
      </div>
      <hr />
      <div class="acc">
        <h6>ACCOUNT</h6>
        <div class="settings-tab">
          <a href="settings.php"><h3><i class="fa fa-cog"></i> Settings</h3></a>
        </div>
        <div class="logout-tab">
          <a href="logout.php"><h3><i class="fa fa-sign-out"></i> Logout</h3></a>
        </div>
      </div>
      <div class="staff-acc">
        <i class="fa fa-user"></i>
        <div>
          <h5>Admin User</h5>
          <p>Admin@bigbrew.com</p>
        </div>
      </div>
    </aside>

    <!-- ── MAIN ── -->
    <main class="menu-main">
      <div class="menu-page-header">
        <div>
          <h2>Menu Management</h2>
          <p>Add, edit, or remove products from your menu</p>
        </div>
        <div class="menu-header-actions">
          <div class="menu-search-wrap">
            <i class="fa fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search products..." oninput="filterTable()" />
          </div>
          <button class="btn-add-product" onclick="openModal(null)">
            <i class="fa fa-plus"></i> Add New Product
          </button>
        </div>
      </div>

      <!-- Category tabs -->
      <div class="category-tabs" id="categoryTabs">
        <button class="cat-tab active" onclick="filterCategory('all', this)">All</button>
      </div>

      <!-- Toast -->
      <div id="toast" class="toast" style="display:none;"></div>

      <!-- Table -->
      <div class="menu-table-wrap">
        <table class="menu-prod-table">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Category</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="productTableBody">
            <tr><td colspan="4" class="table-loading"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- ── MODAL ── -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeOnBackdrop(event)">
      <div class="modal-box">
        <div class="modal-header">
          <h3 id="modalTitle">ADD MENU ITEM</h3>
          <button class="modal-close-btn" onclick="closeModal()"><i class="fa fa-times"></i></button>
        </div>

        <div class="modal-body">
          <!-- LEFT -->
          <div class="modal-left">
            <div class="modal-section-title">Product Image</div>
            <div class="image-upload-box" id="imageUploadBox"
              onclick="document.getElementById('imageFileInput').click()">
              <span class="upload-emoji">🧋</span>
              <span class="replace-image-btn">+ Replace Image</span>
              <input type="file" id="imageFileInput" accept="image/*"
                style="display:none" onchange="previewImage(event)" />
            </div>
            <input type="hidden" id="editImagePath" value="" />

            <div class="modal-section-title" style="margin-top:16px;">Customization Option</div>
            <p class="addon-label">Add-ons</p>
            <table class="custom-table">
              <thead><tr><th>Name</th><th>Price</th></tr></thead>
              <tbody id="addonsBody">
                <tr><td colspan="2" style="text-align:center;color:#aaa;">Loading...</td></tr>
              </tbody>
            </table>
          </div>

          <!-- MIDDLE -->
          <div class="modal-middle">
            <input type="hidden" id="editProductId" value="" />
            <div class="field">
              <label>Name:</label>
              <input type="text" id="editName" placeholder="Product name" />
            </div>
            <div class="field">
              <label>Category:</label>
              <input type="text" id="editCategory" placeholder="e.g. Beverages" />
            </div>
            <div class="field">
              <label>Description:</label>
              <textarea id="editDesc" placeholder="Short description..."></textarea>
            </div>
            <div class="availability-row">
              <div>
                <div class="av-label">Availability</div>
                <div class="av-sub" id="availabilityStatus">Product is currently available</div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="availabilityToggle" checked onchange="updateAvailabilityLabel()" />
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="audit-box">
              <div class="audit-title">Audit/System Info</div>
              Last Updated by: <strong>Admin</strong><br />
              Date: <strong id="auditDate"></strong>
            </div>
          </div>

          <!-- RIGHT -->
          <div class="modal-right">
            <div class="modal-section-title">Pricing</div>
            <p class="addon-label">Size Variants:</p>
            <div class="size-fields" id="sizeFields"></div>
            <button class="add-topping-btn" type="button" onclick="addSizeRow()">
              <i class="fa fa-plus"></i> Add size
            </button>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-delete-item" id="btnDeleteItem" onclick="deleteProductFromModal()" style="display:none;">
            <i class="fa fa-trash"></i> Delete Item
          </button>
          <button class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
          <button class="btn-save-changes" onclick="saveProduct()">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </div>
      </div>
    </div>

    <script>
      let allProducts = [];
      let activeCategory = 'all';

      // Toast
      function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.style.display = 'block';
        t.style.background = type === 'success' ? '#4caf50' : '#e53935';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.style.display = 'none', 3000);
      }

      // Load products
      async function loadProducts() {
        try {
          const res = await fetch('product_api.php?action=list');
          allProducts = await res.json();
          buildCategoryTabs();
          renderTable(allProducts);
        } catch {
          document.getElementById('productTableBody').innerHTML =
            '<tr><td colspan="4" style="color:red;text-align:center;padding:20px;">Failed to load products.</td></tr>';
        }
      }

      // Build category filter tabs
      function buildCategoryTabs() {
        const cats = ['all', ...new Set(allProducts.map(p => p.category).filter(Boolean))];
        const tabs  = document.getElementById('categoryTabs');
        tabs.innerHTML = cats.map(c =>
          `<button class="cat-tab ${c === 'all' ? 'active' : ''}"
            onclick="filterCategory('${c}', this)">
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
          const matchCat  = activeCategory === 'all' || p.category === activeCategory;
          const matchSearch = !q || p.product_name.toLowerCase().includes(q) || (p.category||'').toLowerCase().includes(q);
          return matchCat && matchSearch;
        });
        renderTable(filtered);
      }

      function renderTable(products) {
        const tbody = document.getElementById('productTableBody');
        if (!products.length) {
          tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#aaa;padding:24px;">No products found.</td></tr>';
          return;
        }
        tbody.innerHTML = products.map(p => `
          <tr>
            <td class="td-name">
              <div class="td-name-wrap">
                <div class="td-thumb" style="${p.image ? `background-image:url('${p.image}')` : ''}">
                  ${!p.image ? '🧋' : ''}
                </div>
                <span>${p.product_name}</span>
              </div>
            </td>
            <td class="td-cat">${p.category || '—'}</td>
            <td>
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
          </tr>`).join('');
      }

      // Toggle availability inline
      async function toggleAvailability(id, current) {
        const newVal = current ? 0 : 1;
        const product = allProducts.find(p => p.product_id == id);
        if (!product) return;
        const payload = {
          product_id:   id,
          product_name: product.product_name,
          description:  product.description || '',
          category:     product.category || '',
          image:        product.image || '',
          is_available: newVal,
          sizes:        product.sizes || []
        };
        try {
          const res  = await fetch('product_api.php?action=update', { method:'PUT', body: JSON.stringify(payload) });
          const data = await res.json();
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast(newVal ? 'Marked as Available.' : 'Marked as Unavailable.');
          loadProducts();
        } catch {
          showToast('Failed to update.', 'error');
        }
      }

      // Addons
      async function loadAddons() {
        try {
          const res    = await fetch('product_api.php?action=addons');
          const addons = await res.json();
          document.getElementById('addonsBody').innerHTML = addons.length
            ? addons.map(a => `<tr><td>${a.addon_name}</td><td>₱${parseFloat(a.price).toFixed(2)}</td></tr>`).join('')
            : '<tr><td colspan="2" style="color:#aaa;text-align:center;">No add-ons</td></tr>';
        } catch {
          document.getElementById('addonsBody').innerHTML =
            '<tr><td colspan="2" style="color:red;">Failed to load</td></tr>';
        }
      }

      // Size rows
      function renderSizeRows(sizes) {
        document.getElementById('sizeFields').innerHTML = '';
        const rows = (sizes && sizes.length)
          ? sizes
          : [{ size_name: 'Media', price: '' }, { size_name: 'Grande', price: '' }];
        rows.forEach(s => addSizeRow(s.size_name, s.price));
      }

      function addSizeRow(sizeName = '', price = '') {
        const div = document.createElement('div');
        div.className = 'size-row';
        div.innerHTML = `
          <input type="text" class="size-name-input" placeholder="Size" value="${sizeName}" />
          <span class="currency">₱</span>
          <input type="number" class="size-price-input" placeholder="0.00" value="${price}" step="0.01" min="0" />
          <button type="button" onclick="this.parentElement.remove()"
            style="margin-left:6px;background:none;border:none;color:#e53935;cursor:pointer;">
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

      // Open modal
      async function openModal(productId) {
        const now = new Date();
        document.getElementById('auditDate').textContent =
          now.toLocaleDateString('en-PH', { month:'2-digit', day:'2-digit', year:'numeric' }) +
          ', ' + now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });

        loadAddons();

        if (productId) {
          document.getElementById('modalTitle').textContent = 'EDIT MENU ITEM';
          document.getElementById('btnDeleteItem').style.display = 'inline-flex';
          try {
            const res = await fetch(`product_api.php?action=get&id=${productId}`);
            const p   = await res.json();
            document.getElementById('editProductId').value        = p.product_id;
            document.getElementById('editName').value             = p.product_name;
            document.getElementById('editCategory').value         = p.category || '';
            document.getElementById('editDesc').value             = p.description || '';
            document.getElementById('availabilityToggle').checked = !!p.is_available;
            document.getElementById('editImagePath').value        = p.image || '';

            const box = document.getElementById('imageUploadBox');
            if (p.image) {
              box.innerHTML = `
                <img src="${p.image}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;"
                  onerror="this.style.display='none'" />
                <span class="replace-image-btn"
                  onclick="document.getElementById('imageFileInput').click()">+ Replace Image</span>
                <input type="file" id="imageFileInput" accept="image/*"
                  style="display:none" onchange="previewImage(event)" />`;
            } else {
              resetImageBox();
            }
            renderSizeRows(p.sizes);
          } catch {
            showToast('Failed to load product.', 'error');
            return;
          }
        } else {
          document.getElementById('modalTitle').textContent       = 'ADD MENU ITEM';
          document.getElementById('btnDeleteItem').style.display  = 'none';
          document.getElementById('editProductId').value          = '';
          document.getElementById('editName').value               = '';
          document.getElementById('editCategory').value           = '';
          document.getElementById('editDesc').value               = '';
          document.getElementById('availabilityToggle').checked   = true;
          document.getElementById('editImagePath').value          = '';
          resetImageBox();
          renderSizeRows([]);
        }

        updateAvailabilityLabel();
        document.getElementById('modalOverlay').classList.add('open');
      }

      function resetImageBox() {
        document.getElementById('imageUploadBox').innerHTML = `
          <span class="upload-emoji">🧋</span>
          <span class="replace-image-btn">+ Replace Image</span>
          <input type="file" id="imageFileInput" accept="image/*"
            style="display:none" onchange="previewImage(event)" />`;
      }

      function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
      function closeOnBackdrop(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

      function updateAvailabilityLabel() {
        document.getElementById('availabilityStatus').textContent =
          document.getElementById('availabilityToggle').checked
            ? 'Product is currently available'
            : 'Product is currently unavailable';
      }

      function previewImage(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
          document.getElementById('imageUploadBox').innerHTML = `
            <img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:10px;" />
            <span class="replace-image-btn"
              onclick="document.getElementById('imageFileInput').click()">+ Replace Image</span>
            <input type="file" id="imageFileInput" accept="image/*"
              style="display:none" onchange="previewImage(event)" />`;
        };
        reader.readAsDataURL(file);
        uploadImage(file);
      }

      async function uploadImage(file) {
        const form = new FormData();
        form.append('image', file);
        try {
          const res  = await fetch('product_api.php?action=upload_image', { method:'POST', body: form });
          const data = await res.json();
          if (data.path) document.getElementById('editImagePath').value = data.path;
        } catch {
          showToast('Image upload failed.', 'error');
        }
      }

      async function saveProduct() {
        const id    = document.getElementById('editProductId').value;
        const name  = document.getElementById('editName').value.trim();
        const cat   = document.getElementById('editCategory').value.trim();
        const desc  = document.getElementById('editDesc').value.trim();
        const avail = document.getElementById('availabilityToggle').checked ? 1 : 0;
        const image = document.getElementById('editImagePath').value;
        const sizes = getSizeRows();

        if (!name) { showToast('Product name is required.', 'error'); return; }

        const payload = { product_name: name, description: desc, category: cat, image, is_available: avail, sizes };

        try {
          let res;
          if (id) {
            payload.product_id = parseInt(id);
            res = await fetch('product_api.php?action=update', { method:'PUT', body: JSON.stringify(payload) });
          } else {
            res = await fetch('product_api.php?action=add', { method:'POST', body: JSON.stringify(payload) });
          }
          const data = await res.json();
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast(id ? 'Product updated!' : 'Product added!');
          closeModal();
          loadProducts();
        } catch {
          showToast('Save failed. Try again.', 'error');
        }
      }

      async function confirmDelete(productId) {
        if (!confirm('Delete this product?')) return;
        try {
          const res  = await fetch('product_api.php?action=delete', {
            method: 'DELETE', body: JSON.stringify({ product_id: productId })
          });
          const data = await res.json();
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast('Product deleted.');
          loadProducts();
        } catch {
          showToast('Delete failed.', 'error');
        }
      }

      async function deleteProductFromModal() {
        const id = document.getElementById('editProductId').value;
        if (!id || !confirm('Delete this product?')) return;
        try {
          const res  = await fetch('product_api.php?action=delete', {
            method: 'DELETE', body: JSON.stringify({ product_id: parseInt(id) })
          });
          const data = await res.json();
          if (data.error) { showToast(data.error, 'error'); return; }
          showToast('Product deleted.');
          closeModal();
          loadProducts();
        } catch {
          showToast('Delete failed.', 'error');
        }
      }

      document.addEventListener('DOMContentLoaded', loadProducts);
    </script>

    <style>
      /* ── Search in header ── */
      .menu-search-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 7px 13px;
        min-width: 220px;
      }
      .menu-search-wrap i { color: #bbb; font-size: 13px; }
      .menu-search-wrap input {
        border: none; outline: none; background: transparent;
        font-family: 'Poppins', sans-serif; font-size: 13px;
        color: var(--dark-color); width: 100%;
      }

      /* ── Category tabs ── */
      .category-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .cat-tab {
        padding: 6px 18px;
        border-radius: 50px;
        border: 1px solid var(--border);
        background: white;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: var(--dark-color);
        cursor: pointer;
        transition: all 0.15s;
      }
      .cat-tab:hover { border-color: var(--pop-color); color: var(--pop-color); }
      .cat-tab.active {
        background: var(--pop-color);
        border-color: var(--pop-color);
        color: white;
      }

      /* ── Table wrap ── */
      .menu-table-wrap {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border);
        overflow: hidden;
        flex: 1;
        overflow-y: auto;
        scrollbar-width: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      }
      .menu-table-wrap::-webkit-scrollbar { display: none; }

      .menu-prod-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
      }
      .menu-prod-table thead {
        background: #f9f6f2;
        position: sticky;
        top: 0;
        z-index: 1;
      }
      .menu-prod-table th {
        text-align: left;
        padding: 13px 20px;
        font-size: 12px;
        font-weight: 600;
        color: #888;
        border-bottom: 1px solid var(--border);
      }
      .menu-prod-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f5f0ea;
        color: var(--dark-color);
        vertical-align: middle;
      }
      .menu-prod-table tr:last-child td { border-bottom: none; }
      .menu-prod-table tbody tr:hover { background: #fdf8f2; }

      /* Thumbnail + name */
      .td-name-wrap {
        display: flex;
        align-items: center;
        gap: 11px;
      }
      .td-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f5ece0 center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
        border: 1px solid var(--border);
      }
      .td-cat { color: #999; font-size: 12.5px; }

      /* Status badge */
      .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 11.5px;
        font-weight: 600;
      }
      .status-badge.avail   { background: #dcfce7; color: #15803d; }
      .status-badge.unavail { background: #fee2e2; color: #991b1b; }

      /* Action buttons */
      .td-actions { display: flex; gap: 7px; align-items: center; }

      .tbl-btn-edit {
        display: flex; align-items: center; gap: 5px;
        padding: 5px 13px; border-radius: 7px;
        border: 1px solid var(--border);
        background: var(--light-color);
        font-family: 'Poppins', sans-serif;
        font-size: 12px; font-weight: 600;
        color: var(--dark-color); cursor: pointer;
        transition: all 0.15s;
      }
      .tbl-btn-edit:hover { background: #f3e8d5; border-color: var(--pop-color); color: var(--pop-color); }

      .tbl-btn-avail {
        padding: 5px 13px; border-radius: 7px;
        font-family: 'Poppins', sans-serif;
        font-size: 12px; font-weight: 600;
        cursor: pointer; transition: all 0.15s; border: 1px solid;
      }
      .tbl-btn-avail.mark-unavail { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
      .tbl-btn-avail.mark-unavail:hover { background: #fca5a5; }
      .tbl-btn-avail.mark-avail   { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
      .tbl-btn-avail.mark-avail:hover   { background: #bbf7d0; }

      .tbl-btn-del {
        width: 30px; height: 30px; border-radius: 7px;
        border: 1px solid #fecaca; background: #fee2e2;
        color: #991b1b; font-size: 12px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
      }
      .tbl-btn-del:hover { background: #fca5a5; }

      .table-loading { text-align: center; color: #aaa; padding: 30px; }

      /* Toast */
      .toast {
        position: fixed; bottom: 24px; right: 24px;
        color: #fff; padding: 12px 20px; border-radius: 8px;
        font-size: 14px; z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,.2);
        animation: fadeInToast .2s ease;
      }
      @keyframes fadeInToast {
        from { opacity:0; transform:translateY(10px); }
        to   { opacity:1; transform:translateY(0); }
      }

      /* Size input */
      .size-name-input {
        width: 80px; margin-right: 6px;
        border: 1px solid #ddd; border-radius: 6px;
        padding: 6px 8px; font-family: 'Poppins', sans-serif; font-size: 13px;
      }

      /* Mobile */
      @media (max-width: 900px) {
        .menu-search-wrap { min-width: 140px; }
        .menu-prod-table th,
        .menu-prod-table td { padding: 10px 12px; font-size: 12px; }
        .td-actions { flex-wrap: wrap; gap: 5px; }
        .tbl-btn-avail { font-size: 11px; padding: 4px 10px; }
      }
    </style>
  </body>
</html>