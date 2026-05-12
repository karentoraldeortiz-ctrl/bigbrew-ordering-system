// =============================================
//   BigBrew Admin — dashboard.js
// =============================================

const IMG_BASE = '../assets/products/';

// ── Load stat cards ───────────────────────────────────────────────────────
async function loadStats() {
  try {
    const res  = await fetch('dashboard.php?action=stats');
    const data = await res.json();

    document.getElementById('statRevenue').textContent =
      '₱' + Number(data.revenue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('statOrders').textContent  = Number(data.orders).toLocaleString();
    document.getElementById('statClients').textContent = Number(data.clients).toLocaleString();
    document.getElementById('statProducts').textContent = Number(data.products).toLocaleString();
  } catch {
    ['statRevenue', 'statOrders', 'statClients', 'statProducts'].forEach(id => {
      document.getElementById(id).textContent = '—';
    });
  }
}

// ── Load sales chart ──────────────────────────────────────────────────────
async function loadChart() {
  try {
    const res  = await fetch('dashboard.php?action=chart');
    const data = await res.json();

    const hasData = data.some(d => d.total > 0);
    if (!hasData) {
      document.getElementById('salesChart').style.display = 'none';
      document.getElementById('chartEmpty').style.display = 'flex';
      return;
    }

    const labels = data.map(d => {
      const date = new Date(d.date);
      return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    });
    const values = data.map(d => d.total);

    const ctx = document.getElementById('salesChart').getContext('2d');

    // Gradient fill
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(180, 100, 30, 0.35)');
    gradient.addColorStop(1, 'rgba(180, 100, 30, 0.0)');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Revenue (₱)',
          data: values,
          borderColor: '#b46418',
          backgroundColor: gradient,
          borderWidth: 2.5,
          pointBackgroundColor: '#b46418',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          fill: true,
          tension: 0.4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ' ₱' + Number(ctx.parsed.y).toLocaleString('en-PH', {
                minimumFractionDigits: 2
              })
            },
            backgroundColor: '#2c1a0e',
            titleColor: '#f5e6d0',
            bodyColor: '#e8c98a',
            padding: 10,
            cornerRadius: 8,
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#7a5c3a', font: { family: 'Poppins', size: 11 } }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(180,100,30,0.08)' },
            ticks: {
              color: '#7a5c3a',
              font: { family: 'Poppins', size: 11 },
              callback: val => '₱' + Number(val).toLocaleString()
            }
          }
        }
      }
    });
  } catch (e) {
    console.warn('Chart load failed:', e);
  }
}

// ── Load top selling products ─────────────────────────────────────────────
async function loadTopProducts() {
  const container = document.getElementById('topProductsBody');
  try {
    const res      = await fetch('dashboard.php?action=top_products');
    const products = await res.json();

    if (!products.length) {
      container.innerHTML = `
        <div class="panel-empty">
          <i class="fa fa-box-open"></i>
          <p>No sales data yet.</p>
        </div>`;
      return;
    }

    // Find max sold for progress bar scaling
    const maxSold = Math.max(...products.map(p => parseInt(p.total_sold)));

    container.innerHTML = products.map((p, i) => {
      const imgUrl  = p.image ? `${IMG_BASE}${p.image}` : '';
      const pct     = maxSold > 0 ? Math.round((parseInt(p.total_sold) / maxSold) * 100) : 0;
      const revenue = parseFloat(p.total_revenue).toLocaleString('en-PH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
      });
      const medals  = ['🥇', '🥈', '🥉'];
      const rank    = medals[i] || `#${i + 1}`;

      return `
        <div class="top-product-row">
          <div class="top-product-rank">${rank}</div>
          <div class="top-product-img" style="${imgUrl
            ? `background-image:url('${imgUrl}')`
            : 'background:#f5e6d0;'}">
            ${!imgUrl ? '🧋' : ''}
          </div>
          <div class="top-product-info">
            <div class="top-product-name">${p.product_name}</div>
            <div class="top-product-cat">${p.category || '—'}</div>
            <div class="top-product-bar-wrap">
              <div class="top-product-bar" style="width:${pct}%"></div>
            </div>
          </div>
          <div class="top-product-stats">
            <span class="top-sold">${parseInt(p.total_sold).toLocaleString()} sold</span>
            <span class="top-revenue">₱${revenue}</span>
          </div>
        </div>`;
    }).join('');

  } catch {
    container.innerHTML = `<div class="panel-empty"><p>Failed to load.</p></div>`;
  }
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadChart();
  loadTopProducts();
});