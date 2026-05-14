// =============================================
//   BigBrew Admin — dashboard.js
// =============================================

const IMG_BASE = '../assets/products/';
let chartInstance = null; // track chart instance para ma-destroy before redraw

// ── Load stat cards ───────────────────────────────────────────────────────
async function loadStats() {
  try {
    const res  = await fetch('dashboard.php?action=stats');
    const data = await res.json();

    document.getElementById('statRevenue').textContent =
      '₱' + Number(data.revenue).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      // sub sa revenue, ipakita rin yung revenue this month or no sales if zero
      const revMonthBadge = document.getElementById('statRevenueMonth');
      if (revMonthBadge) {
        revMonthBadge.textContent = data.revenue_month > 0
          ? `This month: ₱${Number(data.revenue_month).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`
          : 'No sales this month';
      }
    document.getElementById('statOrders').textContent  = Number(data.orders).toLocaleString();
    document.getElementById('statClients').textContent = Number(data.clients).toLocaleString();

// ← dagdag — new this week
const newBadge = document.getElementById('statClientsNew');
if (newBadge) {
  newBadge.textContent = data.clients_new > 0
    ? `+${data.clients_new} new this week`
    : 'No new this week';
}
    document.getElementById('statProducts').textContent = Number(data.products).toLocaleString();
  } catch {
    ['statRevenue', 'statOrders', 'statClients', 'statProducts'].forEach(id => {
      document.getElementById(id).textContent = '—';
    });
  }
}

// ── Load sales chart ──────────────────────────────────────────────────────
async function loadChart(range = 7) {
  try {
    const res  = await fetch(`dashboard.php?action=chart&range=${range}`);
    const data = await res.json();

    const chartCanvas = document.getElementById('salesChart');
    const chartEmpty  = document.getElementById('chartEmpty');

    // Destroy existing chart before redrawing
    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }

    const hasData = data.some(d => d.total > 0);
    if (!hasData) {
      chartCanvas.style.display = 'none';
      chartEmpty.style.display  = 'flex';
      return;
    }

    chartCanvas.style.display = 'block';
    chartEmpty.style.display  = 'none';

    const labels = data.map(d => {
      const date = new Date(d.date);
      return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
    });
    const values = data.map(d => d.total);

    const bgColors     = values.map(v => v > 0 ? '#b46418' : 'rgba(180,100,24,0.12)');
    const borderColors = values.map(() => '#b46418');
    const borderWidths = values.map(v => v > 0 ? 0 : 1.5);

    const ctx = document.getElementById('salesChart').getContext('2d');

    chartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Revenue (₱)',
          data: values,
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: borderWidths,
          borderRadius: 6,
          borderSkipped: false,
          minBarLength: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            filter: () => true,
            callbacks: {
              label: ctx => {
                const val = ctx.parsed.y;
                return val === 0
                  ? ' No sales'
                  : ' ₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
              }
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
// ── Load peak hours chart ─────────────────────────────────────────────────
async function loadPeakHours() {
  try {
    const res  = await fetch('dashboard.php?action=peak_hours');
    const data = await res.json();

    const hasData = data.some(d => d.total > 0);
    if (!hasData) {
      document.getElementById('peakChart').style.display = 'none';
      document.getElementById('peakEmpty').style.display = 'flex';
      return;
    }

    const labels = data.map(d => d.hour);
    const values = data.map(d => d.total);
    const maxVal = Math.max(...values);

    const bgColors = values.map(v => {
      if (v === 0) return 'rgba(180,100,24,0.08)';
      if (v === maxVal) return '#b46418';        // peak hour — solid
      return 'rgba(180,100,24,0.45)';            // normal hours — semi
    });

    const ctx = document.getElementById('peakChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Orders',
          data: values,
          backgroundColor: bgColors,
          borderRadius: 4,
          borderSkipped: false,
          minBarLength: 3,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            filter: () => true,
            callbacks: {
              label: ctx => {
                const val = ctx.parsed.y;
                return val === 0 ? ' No orders' : ` ${val} order${val > 1 ? 's' : ''}`;
              }
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
            ticks: {
              color: '#7a5c3a',
              font: { family: 'Poppins', size: 10 },
              maxRotation: 45,
            }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(180,100,30,0.08)' },
            ticks: {
              color: '#7a5c3a',
              font: { family: 'Poppins', size: 11 },
              stepSize: 1,
              callback: val => Number.isInteger(val) ? val : ''
            }
          }
        }
      }
    });
  } catch (e) {
    console.warn('Peak hours load failed:', e);
  }
}
// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadChart(7);
  loadTopProducts();
  loadPeakHours();

  // Date filter buttons
  document.querySelectorAll('.chart-filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.chart-filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      loadChart(parseInt(this.dataset.range));
    });
  });
});