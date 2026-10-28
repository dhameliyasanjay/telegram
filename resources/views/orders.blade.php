<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Price Monitor - Order Records</title>
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" type="image/svg+xml" href="/icon.svg">
  <meta name="theme-color" content="#3b82f6">
  <meta name="mobile-web-app-capable" content="yes">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0b0c10;
      --card-bg: #12131a;
      --sidebar-bg: #0e0f17;
      --border: rgba(255, 255, 255, 0.08);
      --border-hover: rgba(255, 255, 255, 0.18);
      --text: #f3f4f6;
      --text-muted: #9ca3af;
      --accent: #3b82f6;
      --accent-hover: #2563eb;
      --green: #10b981;
      --red: #ef4444;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
    body { background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }

    /* App Layout */
    .app-layout { display: flex; min-height: 100vh; width: 100vw; overflow-x: hidden; }

    /* Sidebar Styling */
    .sidebar {
      width: 250px; flex-shrink: 0; background: var(--sidebar-bg); border-right: 1px solid var(--border);
      display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100;
      transition: transform 0.3s ease;
    }
    .sidebar-header {
      padding: 24px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border);
    }
    .logo { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 18px; color: #fff; }
    .logo svg { width: 28px; height: 28px; }
    .sidebar-close-btn { display: none; background: none; border: none; color: var(--text-muted); cursor: pointer; }
    .sidebar-content { flex: 1; padding: 20px 12px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
    .nav-section-title { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); padding: 0 12px 8px; }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; color: var(--text-muted);
      text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s ease; border: 1px solid transparent; cursor: pointer; background: none; width: 100%; text-align: left;
    }
    .nav-item:hover { color: var(--text); background: rgba(255, 255, 255, 0.04); }
    .nav-item.active { color: #fff; background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); font-weight: 600; }
    .sidebar-footer { padding: 16px; border-top: 1px solid var(--border); }
    .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 90; }

    /* Main Content Area */
    .main-wrapper { flex: 1; margin-left: 250px; display: flex; flex-direction: column; min-width: 0; min-height: 100vh; }
    .header {
      padding: 20px 32px; background: rgba(11, 12, 16, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 40;
    }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .sidebar-toggle { display: none; background: none; border: 1px solid var(--border); color: var(--text); padding: 8px; border-radius: 8px; cursor: pointer; }
    .page-title h2 { font-size: 22px; font-weight: 700; color: #fff; }
    .page-subtitle { font-size: 13px; color: var(--text-muted); margin-top: 2px; display: block; }
    .header-actions { display: flex; align-items: center; gap: 12px; }

    /* Toolbar & Search */
    .toolbar { padding: 24px 32px 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .search-wrap { position: relative; width: 340px; }
    .search-input {
      width: 100%; padding: 10px 16px 10px 40px; background: var(--card-bg); border: 1px solid var(--border);
      border-radius: 10px; color: var(--text); font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .search-input:focus { border-color: var(--accent); }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
    .record-count { font-size: 14px; color: var(--text-muted); font-weight: 500; }

    /* Datatable Section */
    .table-container { padding: 0 32px 32px; }
    .table-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: rgba(255, 255, 255, 0.02); padding: 14px 20px; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); border-bottom: 1px solid var(--border); }
    td { padding: 16px 20px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: rgba(255, 255, 255, 0.015); }
    .order-badge { background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 4px 10px; border-radius: 6px; font-weight: 600; font-family: monospace; font-size: 13px; }

    /* Buttons */
    .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
    .btn-primary { background: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4); }
    .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
    .btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
    .btn-ghost:hover { color: var(--text); background: rgba(255,255,255,0.05); }
    .btn-danger { background: rgba(239, 68, 68, 0.1); color: var(--red); border: 1px solid rgba(239, 68, 68, 0.2); }
    .btn-danger:hover { background: rgba(239, 68, 68, 0.2); }
    .btn-sm { padding: 6px 12px; font-size: 13px; border-radius: 8px; }
    .action-group { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }

    /* Modal Backdrop & Form */
    .cat-modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 200; align-items: center; justify-content: center; padding: 20px; }
    .cat-modal-bg.open { display: flex; }
    .cat-modal { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 480px; padding: 28px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
    .cat-close-btn { position: absolute; right: 20px; top: 20px; background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; border-radius: 6px; }
    .cat-modal-title { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .cat-form-grid { display: flex; flex-direction: column; gap: 16px; }
    .cat-label { font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; display: block; }
    .cat-input { width: 100%; padding: 11px 14px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 14px; outline: none; }
    .cat-input:focus { border-color: var(--accent); }
    .cat-modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 24px; }

    /* Toast */
    .toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 300; display: flex; flex-direction: column; gap: 10px; }
    .toast { background: var(--card-bg); border: 1px solid var(--border); padding: 12px 18px; border-radius: 10px; font-size: 14px; color: var(--text); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .toast.success { border-color: var(--green); color: var(--green); }
    .toast.error { border-color: var(--red); color: var(--red); }

    @media (max-width: 1024px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .sidebar-overlay.open { display: block; }
      .sidebar-close-btn, .sidebar-toggle { display: block; }
      .main-wrapper { margin-left: 0; }
      .header, .toolbar, .table-container { padding-left: 16px; padding-right: 16px; }
    }
  </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="app-layout">
  <!-- Vertical Sidebar Navigation -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="16" fill="#3b82f6"/><path d="M7 16l3.5 3.5 2.5-2.5 5 5 7-9" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Price Monitor</span>
      </div>
      <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Close sidebar">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="sidebar-content">
      <!-- Section: Navigation -->
      <div class="nav-section">
        <div class="nav-section-title">Navigation</div>
        <a href="/" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l9-9 9 9M5 10v9a1 1 0 001 1h4v-5h4v5h4a1 1 0 001-1v-9"/></svg>
          <span>Dashboard</span>
        </a>
        <a href="/catalogue" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
          <span>Catalogue</span>
        </a>
      </div>

      <!-- Section: Transactions -->
      <div class="nav-section">
        <div class="nav-section-title">Transactions</div>
        <a href="/payments" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          <span>Payment</span>
        </a>
        <a href="/orders" class="nav-item active">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
          <span>Order</span>
        </a>
        <a href="/returns" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
          <span>Return</span>
        </a>
      </div>

      <!-- Section: Account -->
      <div class="nav-section">
        <div class="nav-section-title">Account</div>
        <a href="{{ route('profile') }}" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Auth::user()->name ?? 'Settings' }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
          @csrf
          <button type="submit" class="nav-item" style="color:var(--red); width:100%; border:none; background:none; text-align:left; cursor:pointer;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Sign Out</span>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- Main Viewport Area -->
  <div class="main-wrapper">
    <header class="header">
      <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="page-title">
          <h2>Orders</h2>
          <span class="page-subtitle">Manage customer orders and transaction records</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" onclick="openOrderModal()">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Add Order
        </button>
      </div>
    </header>

    <div class="toolbar">
      <div class="search-wrap">
        <span class="search-icon">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </span>
        <input type="text" class="search-input" id="orderSearchInput" placeholder="Search by order #..." oninput="filterOrders()">
      </div>
      <div class="record-count" id="totalCount">Loading records...</div>
    </div>

    <div class="table-container">
      <div class="table-card">
        <table>
          <thead>
            <tr>
              <th>Order #</th>
              <th>Order Date</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody id="orderTableBody">
            <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--text-muted)">Loading orders...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add / Edit Order Modal -->
<div class="cat-modal-bg" id="orderModalBg" onclick="if(event.target===this)closeOrderModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closeOrderModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title" id="orderModalTitle">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
      <span>Add Order Entry</span>
    </div>
    <form id="orderForm" onsubmit="submitOrderForm(event)">
      <input type="hidden" id="editOrderId" value="">
      <div class="cat-form-grid">
        <div>
          <label class="cat-label" for="orderNumber">Number of Order</label>
          <input class="cat-input" type="text" id="orderNumber" required placeholder="e.g. ORD-1001 or 5">
        </div>
        <div>
          <label class="cat-label" for="orderDate">Date</label>
          <input class="cat-input" type="date" id="orderDate" required>
        </div>
      </div>
      <div class="cat-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeOrderModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="orderSubmitBtn">Save Order</button>
      </div>
    </form>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const API = '/api';
let rawOrders = [];

function csrfToken() { return document.querySelector('meta[name="csrf-token"]').content; }

document.addEventListener('DOMContentLoaded', () => {
  loadOrders();
});

async function loadOrders() {
  try {
    const r = await fetch(`${API}/orders`);
    const d = await r.json();
    rawOrders = d.data || [];
    renderOrders(rawOrders);
  } catch(e) {
    document.getElementById('orderTableBody').innerHTML = `<tr><td colspan="3" style="text-align:center;padding:30px;color:var(--red)">Failed to load orders.</td></tr>`;
  }
}

function renderOrders(items) {
  const tbody = document.getElementById('orderTableBody');
  document.getElementById('totalCount').textContent = `${items.length} order record${items.length !== 1 ? 's' : ''}`;
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;padding:40px;color:var(--text-muted)">No order records found.</td></tr>`;
    return;
  }
  tbody.innerHTML = items.map(o => `
    <tr>
      <td><span class="order-badge">${esc(o.order_number)}</span></td>
      <td>${formatDate(o.date)}</td>
      <td style="text-align:right">
        <div class="action-group">
          <button class="btn btn-ghost btn-sm" onclick="editOrder(${o.id})">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteOrder(${o.id})">Delete</button>
        </div>
      </td>
    </tr>
  `).join('');
}

function filterOrders() {
  const q = document.getElementById('orderSearchInput').value.toLowerCase().trim();
  const filtered = rawOrders.filter(o => (o.order_number || '').toLowerCase().includes(q));
  renderOrders(filtered);
}

function openOrderModal() {
  document.getElementById('editOrderId').value = '';
  document.getElementById('orderNumber').value = '';
  document.getElementById('orderDate').value = new Date().toISOString().split('T')[0];
  document.querySelector('#orderModalTitle span').textContent = 'Add Order Entry';
  document.getElementById('orderSubmitBtn').textContent = 'Save Order';
  document.getElementById('orderModalBg').classList.add('open');
}

function editOrder(id) {
  const o = rawOrders.find(item => item.id == id);
  if (!o) return;
  document.getElementById('editOrderId').value = o.id;
  document.getElementById('orderNumber').value = o.order_number || '';
  if (o.date) {
    document.getElementById('orderDate').value = o.date.split('T')[0];
  }
  document.querySelector('#orderModalTitle span').textContent = 'Edit Order Entry';
  document.getElementById('orderSubmitBtn').textContent = 'Update Order';
  document.getElementById('orderModalBg').classList.add('open');
}

function closeOrderModal() {
  document.getElementById('orderModalBg').classList.remove('open');
}

async function submitOrderForm(e) {
  e.preventDefault();
  const editId = document.getElementById('editOrderId').value;
  const btn = document.getElementById('orderSubmitBtn');
  btn.disabled = true;
  btn.textContent = editId ? 'Updating...' : 'Saving...';
  try {
    const fd = new FormData();
    fd.append('order_number', document.getElementById('orderNumber').value);
    fd.append('date', document.getElementById('orderDate').value);
    
    const url = editId ? `${API}/orders/${editId}` : `${API}/orders`;
    const r = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save order.');
    toast(d.message || (editId ? 'Order updated!' : 'Order added!'), 'success');
    closeOrderModal();
    loadOrders();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = editId ? 'Update Order' : 'Save Order';
  }
}

async function deleteOrder(id) {
  if (!confirm('Delete this order record?')) return;
  try {
    const r = await fetch(`${API}/orders/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to delete.');
    toast('Order deleted!', 'success');
    loadOrders();
  } catch(err) {
    toast(err.message, 'error');
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}

function toast(msg, type = '') {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}
</script>
</body>
</html>
