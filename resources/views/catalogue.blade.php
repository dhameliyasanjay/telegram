<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Product Catalogue</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #09090b; --surface: #18181b; --surface-hover: #27272a;
  --border: rgba(255,255,255,0.08); --border-focus: rgba(255,255,255,0.15);
  --accent: #3b82f6; --accent-glow: rgba(59,130,246,0.4);
  --text: #fafafa; --text-muted: #a1a1aa; --radius: 16px;
  --green: #10b981; --red: #ef4444; --yellow: #f59e0b;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--bg); color:var(--text); font-family:'Outfit',sans-serif; min-height:100vh; }
/* Layout Shell */
.app-layout { display: flex; min-height: 100vh; width: 100%; }
.sidebar {
  width: 270px; background: rgba(18, 18, 22, 0.95); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-right: 1px solid var(--border); display: flex; flex-direction: column;
  position: fixed; top: 0; bottom: 0; left: 0; z-index: 150;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.main-wrapper {
  flex: 1; margin-left: 270px; min-height: 100vh; display: flex; flex-direction: column;
  width: calc(100% - 270px); transition: margin-left 0.3s;
}
@media (max-width: 1024px) {
  .sidebar { transform: translateX(-100%); box-shadow: 20px 0 50px rgba(0,0,0,0.6); }
  .sidebar.open { transform: translateX(0); }
  .main-wrapper { margin-left: 0; width: 100%; }
}
.sidebar-header { padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
.sidebar-close-btn { display: none; background: transparent; border: none; color: var(--text-muted); cursor: pointer; border-radius: 8px; padding: 4px; }
.sidebar-close-btn:hover { background: var(--surface-hover); color: var(--text); }
@media (max-width: 1024px) { .sidebar-close-btn { display: flex; align-items: center; justify-content: center; } }

.sidebar-content { flex: 1; padding: 24px 16px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
.nav-section { display: flex; flex-direction: column; gap: 6px; }
.nav-section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: var(--text-muted); padding: 0 12px 6px; }

.nav-item {
  display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px;
  color: var(--text-muted); font-size: 14px; font-weight: 500; font-family: 'Outfit', sans-serif;
  text-decoration: none; background: transparent; border: 1px solid transparent; cursor: pointer;
  width: 100%; text-align: left; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.nav-item svg { flex-shrink: 0; transition: transform 0.2s, color 0.2s; }
.nav-item:hover { background: var(--surface-hover); color: var(--text); border-color: var(--border); }
.nav-item:hover svg { transform: scale(1.1); color: var(--accent); }
.nav-item.active { background: rgba(59, 130, 246, 0.12); color: #3b82f6; border-color: rgba(59, 130, 246, 0.25); font-weight: 600; }
.nav-item.active svg { color: #3b82f6; }

.nav-item.btn-nav-action.primary-gradient {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: #ffffff; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35); border: none; font-weight: 600; margin-top: 4px;
}
.nav-item.btn-nav-action.primary-gradient:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5); color: #ffffff; }

.sidebar-footer { padding: 20px; border-top: 1px solid var(--border); background: rgba(18, 18, 22, 0.5); }
.sidebar-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); opacity: 0; pointer-events: none; transition: opacity 0.3s; z-index: 140; }
.sidebar-overlay.open { opacity: 1; pointer-events: all; }

/* Header */
.header {
  position: sticky; top: 0; z-index: 100;
  background: rgba(9, 9, 11, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border); padding: 0 32px; height: 76px;
  display: flex; align-items: center; justify-content: space-between;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.sidebar-toggle { display: none; background: var(--surface); border: 1px solid var(--border); color: var(--text); border-radius: 10px; padding: 8px 10px; cursor: pointer; transition: all 0.2s; }
.sidebar-toggle:hover { background: var(--surface-hover); border-color: var(--border-focus); }
@media (max-width: 1024px) { .sidebar-toggle { display: flex; align-items: center; justify-content: center; } }
.page-title h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.4px; color: var(--text); }
.page-subtitle { font-size: 12px; color: var(--text-muted); display: block; font-family: 'Inter', sans-serif; }
.logo { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
.logo svg { width: 32px; height: 32px; }
.header-actions { display: flex; align-items: center; gap: 16px; }
.btn { padding:10px 20px; border-radius:10px; border:none; font-size:14px; font-weight:600; font-family:'Outfit',sans-serif; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; justify-content:center; text-decoration:none; }
.btn-primary { background:var(--text); color:var(--bg); box-shadow:0 4px 12px rgba(255,255,255,0.1); }
.btn-primary:hover { background:#e4e4e7; transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-muted); border:1px solid var(--border); }
.btn-ghost:hover { background:var(--surface-hover); color:var(--text); border-color:var(--border-focus); }
.btn-sm { padding:7px 13px; font-size:12px; }
.btn-danger { background:rgba(239,68,68,0.1); color:var(--red); border:1px solid rgba(239,68,68,0.2); }
.btn-danger:hover { background:rgba(239,68,68,0.2); }
.toolbar { padding:28px 32px 20px; display:flex; gap:14px; align-items:center; flex-wrap:wrap; }
.search-wrap { position:relative; flex:1; min-width:240px; max-width:460px; }
.search-wrap input { width:100%; background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:11px 16px 11px 42px; color:var(--text); font-family:'Inter',sans-serif; font-size:14px; outline:none; transition:all 0.2s; }
.search-wrap input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
.search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); }
.filter-select { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:11px 16px; color:var(--text); font-family:'Outfit',sans-serif; font-size:14px; cursor:pointer; outline:none; transition:border-color 0.2s; }
.filter-select:focus { border-color:var(--accent); }
.table-wrap { padding:0 32px 48px; overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
thead tr { border-bottom:1px solid var(--border); }
th { text-align:left; padding:12px 16px; font-size:11px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; }
tbody tr { border-bottom:1px solid var(--border); transition:background 0.15s; }
tbody tr:hover { background:var(--surface); }
td { padding:14px 16px; font-size:14px; vertical-align:middle; }
.thumb { width:52px; height:52px; border-radius:8px; object-fit:cover; background:var(--surface-hover); display:block; }
.no-img { width:52px; height:52px; border-radius:8px; background:var(--surface-hover); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:10px; }
.status-badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }
.status-pending  { background:rgba(245,158,11,0.15); color:var(--yellow); border:1px solid rgba(245,158,11,0.3); }
.status-on-hold  { background:rgba(161,161,170,0.12); color:var(--text-muted); border:1px solid var(--border); }
.status-active   { background:rgba(16,185,129,0.15); color:var(--green); border:1px solid rgba(16,185,129,0.3); }
.sku-tag { font-family:'Inter',sans-serif; font-size:12px; color:var(--accent); background:rgba(59,130,246,0.1); padding:3px 8px; border-radius:5px; }
.empty-state { padding:80px 32px; text-align:center; color:var(--text-muted); }
.empty-state h3 { font-size:22px; color:var(--text); margin-bottom:8px; }
.spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.2); border-top-color:#fff; border-radius:50%; animation:spin 0.6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
.toast-wrap { position:fixed; bottom:32px; right:32px; display:flex; flex-direction:column; gap:12px; z-index:500; }
.toast { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px 22px; font-size:14px; font-weight:500; box-shadow:0 12px 40px rgba(0,0,0,0.4); animation:slideIn 0.4s cubic-bezier(0.16,1,0.3,1); display:flex; align-items:center; gap:12px; border-left:4px solid var(--accent); }
.toast.success { border-left-color:var(--green); }
.toast.error { border-left-color:var(--red); }
@keyframes slideIn { from { transform:translateX(120%); opacity:0; } to { transform:none; opacity:1; } }
.pagination-bar { display:flex; align-items:center; gap:12px; padding:0 32px 32px; }
/* Inline editable price cell */
.editable-price {
  cursor:pointer; display:inline-flex; align-items:center; gap:6px;
  border-bottom:1px dashed rgba(255,255,255,0.2); padding-bottom:1px;
  transition:border-color 0.2s, color 0.2s;
}
.editable-price:hover { border-bottom-color:var(--accent); color:var(--accent); }
.editable-price .edit-icon { opacity:0; transition:opacity 0.2s; color:var(--text-muted); }
.editable-price:hover .edit-icon { opacity:1; }
.inline-price-input {
  background:var(--bg); border:1px solid var(--accent);
  border-radius:6px; padding:4px 8px; color:var(--text);
  font-size:14px; font-family:'Outfit',sans-serif;
  width:90px; outline:none;
  box-shadow:0 0 0 3px rgba(59,130,246,0.15);
}
/* Profit badge */
.profit-positive { color:#10b981; background:rgba(16,185,129,0.12); padding:3px 9px; border-radius:6px; font-size:13px; font-weight:600; border:1px solid rgba(16,185,129,0.25); display:inline-block; }
.profit-negative { color:#ef4444; background:rgba(239,68,68,0.12); padding:3px 9px; border-radius:6px; font-size:13px; font-weight:600; border:1px solid rgba(239,68,68,0.25); display:inline-block; }
.profit-zero { color:var(--text-muted); background:rgba(255,255,255,0.05); padding:3px 9px; border-radius:6px; font-size:13px; font-weight:600; border:1px solid var(--border); display:inline-block; }
/* Lightbox */
.lightbox-bg {
  position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:900;
  display:flex; align-items:center; justify-content:center;
  backdrop-filter:blur(12px); opacity:0; pointer-events:none;
  transition:opacity 0.25s;
}
.lightbox-bg.open { opacity:1; pointer-events:all; }
.lightbox-img {
  max-width:90vw; max-height:88vh; border-radius:16px;
  box-shadow:0 32px 80px rgba(0,0,0,0.8);
  transform:scale(0.92); transition:transform 0.3s cubic-bezier(0.16,1,0.3,1);
  object-fit:contain; display:block;
}
.lightbox-bg.open .lightbox-img { transform:scale(1); }
.lightbox-close {
  position:fixed; top:24px; right:28px; background:rgba(255,255,255,0.1);
  border:1px solid rgba(255,255,255,0.15); width:40px; height:40px; border-radius:50%;
  color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center;
  transition:background 0.2s; backdrop-filter:blur(6px);
}
.lightbox-close:hover { background:rgba(255,255,255,0.2); }
.lightbox-download {
  position:fixed; top:24px; right:76px; background:rgba(59,130,246,0.7);
  border:1px solid rgba(59,130,246,0.4); border-radius:10px; color:#fff;
  cursor:pointer; display:flex; align-items:center; gap:6px; padding:8px 14px;
  font-size:13px; font-weight:600; font-family:'Outfit',sans-serif;
  text-decoration:none; transition:background 0.2s; backdrop-filter:blur(6px);
}
.lightbox-download:hover { background:rgba(59,130,246,0.9); }
.thumb { cursor:pointer; transition:opacity 0.2s,transform 0.2s; }
.thumb:hover { opacity:0.85; transform:scale(1.06); }
/* Modal */
.cat-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,0.82); z-index:400; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(10px); opacity:0; pointer-events:none; transition:opacity 0.25s; }
.cat-modal-bg.open { opacity:1; pointer-events:all; }
.cat-modal { background:var(--surface); border:1px solid var(--border); border-radius:24px; padding:36px 40px 32px; width:100%; max-width:600px; max-height:92vh; overflow-y:auto; box-shadow:0 32px 80px rgba(0,0,0,0.7); transform:translateY(24px); transition:transform 0.3s cubic-bezier(0.16,1,0.3,1); position:relative; }
.cat-modal-bg.open .cat-modal { transform:translateY(0); }
.cat-modal-title { font-size:22px; font-weight:700; margin-bottom:28px; letter-spacing:-0.4px; display:flex; align-items:center; gap:10px; }
.cat-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.cat-form-grid .full { grid-column:1/-1; }
.cat-label { font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px; display:block; }
.cat-input { width:100%; background:var(--bg); border:1px solid var(--border); border-radius:10px; padding:12px 14px; color:var(--text); font-size:14px; font-family:'Outfit',sans-serif; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
.cat-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
.cat-photo-zone { border:2px dashed var(--border); border-radius:12px; padding:24px; display:flex; flex-direction:column; align-items:center; gap:10px; cursor:pointer; transition:border-color 0.2s; }
.cat-photo-zone:hover { border-color:var(--accent); }
.cat-photo-preview { width:100%; max-height:160px; object-fit:cover; border-radius:8px; display:none; }
.cat-photo-placeholder { color:var(--text-muted); font-size:13px; text-align:center; }
.cat-modal-footer { display:flex; gap:12px; justify-content:flex-end; margin-top:28px; border-top:1px solid var(--border); padding-top:24px; }
.cat-close-btn { position:absolute; top:20px; right:20px; background:var(--bg); border:1px solid var(--border); width:34px; height:34px; border-radius:50%; color:var(--text); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; }
.cat-close-btn:hover { background:var(--surface-hover); }

/* Modal Datatable */
.modal-table-wrap { margin-top:24px; border-top:1px solid var(--border); padding-top:20px; }
.modal-table-title { font-size:13px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; }
.modal-table { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
.modal-table th { padding:8px 10px; color:var(--text-muted); font-weight:600; font-size:11px; text-transform:uppercase; border-bottom:1px solid var(--border); }
.modal-table td { padding:10px; border-bottom:1px solid rgba(255,255,255,0.05); color:var(--text); }
.modal-table tr:last-child td { border-bottom:none; }
.btn-del-sm { background:transparent; border:none; color:var(--red); cursor:pointer; padding:4px 6px; border-radius:6px; transition:background 0.2s; }
.btn-del-sm:hover { background:rgba(239,68,68,0.15); }

/* Multi-select group dropdown */
.multi-select-wrap { position:relative; }
.multi-select-trigger {
  width:100%; background:var(--bg); border:1px solid var(--border); border-radius:10px;
  padding:12px 14px; color:var(--text); font-size:14px; font-family:'Outfit',sans-serif;
  cursor:pointer; display:flex; justify-content:space-between; align-items:center;
  transition:border-color 0.2s,box-shadow 0.2s; user-select:none;
}
.multi-select-trigger.open { border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,0.15); }
.multi-select-trigger svg { transition:transform 0.2s; flex-shrink:0; }
.multi-select-trigger.open svg { transform:rotate(180deg); }
.multi-select-dropdown {
  position:absolute; top:calc(100% + 4px); left:0; right:0; z-index:600;
  background:var(--surface); border:1px solid var(--border-focus); border-radius:12px;
  box-shadow:0 16px 40px rgba(0,0,0,0.5); max-height:220px; overflow-y:auto;
  display:none; padding:6px;
}
.multi-select-dropdown.open { display:block; }
.multi-select-option {
  display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px;
  cursor:pointer; font-size:13px; transition:background 0.15s;
}
.multi-select-option:hover { background:var(--surface-hover); }
.multi-select-option input[type=checkbox] { accent-color:var(--accent); width:15px; height:15px; cursor:pointer; flex-shrink:0; }
.multi-select-tag-count { background:rgba(59,130,246,0.2); color:var(--accent); border-radius:5px; padding:1px 7px; font-size:12px; font-weight:700; }
.multi-select-search-wrap { padding:6px; position:sticky; top:0; background:var(--surface); z-index:2; border-bottom:1px solid var(--border); margin:-6px -6px 6px -6px; border-radius:12px 12px 0 0; }
.multi-select-search { width:100%; background:var(--bg); border:1px solid var(--border); border-radius:8px; padding:8px 12px; color:var(--text); font-size:13px; font-family:'Outfit',sans-serif; outline:none; transition:border-color 0.2s; }
.multi-select-search:focus { border-color:var(--accent); }
</style>
</head>
<body>

<div class="app-layout">
  <!-- Sidebar Overlay for Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

  <!-- Vertical Sidebar -->
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
        <a href="/catalogue" class="nav-item active">
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
        <a href="/orders" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
          <span>Order</span>
        </a>
        <a href="/returns" class="nav-item">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
          <span>Return</span>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Viewport Area -->
  <div class="main-wrapper">
    <!-- Streamlined Header -->
    <header class="header">
      <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="page-title">
          <h2>Product Catalogue</h2>
          <span class="page-subtitle">Manage master inventory, SKUs, prices & status</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" onclick="openCatModal()" style="background: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Add Product
        </button>
      </div>
    </header>

    <div class="toolbar">
      <div class="search-wrap">
        <span class="search-icon">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/></svg>
        </span>
        <input type="text" id="searchInput" placeholder="Search by name, SKU, group…" oninput="debounce()">
      </div>
      <select class="filter-select" id="statusFilter" onchange="loadCatalogue()">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="on-hold">On-Hold</option>
        <option value="active">Active</option>
      </select>
      <span id="totalCount" style="color:var(--text-muted);font-size:13px;margin-left:auto"></span>
    </div>

    <div class="table-wrap">
      <table id="catTable">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Product Name</th>
            <th>SKU ID</th>
            <th>Market Price</th>
            <th>Bank Settlement</th>
            <th>Profit</th>
            <th>Qty</th>
            <th>Group</th>
            <th>Create Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="catBody">
          <tr><td colspan="11" style="text-align:center;padding:60px;color:var(--text-muted)"><span class="spinner"></span></td></tr>
        </tbody>
      </table>
    </div>

    <div class="pagination-bar" id="paginationBar"></div>
  </div> <!-- /.main-wrapper -->
</div> <!-- /.app-layout -->

<div class="toast-wrap" id="toastWrap"></div>

<!-- Lightbox -->
<div class="lightbox-bg" id="lightboxBg" onclick="if(event.target===this)closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()" title="Close">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
  </button>
  <a class="lightbox-download" id="lightboxDownload" href="#" download title="Download photo">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M12 3v13M8 12l4 4 4-4M3 19h18" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Download
  </a>
  <img class="lightbox-img" id="lightboxImg" src="" alt="Product photo">
</div>

<!-- Modal -->
<div class="cat-modal-bg" id="catModalBg" onclick="if(event.target===this)closeCatModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closeCatModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <span id="catModalHeading">Add Product</span>
    </div>
    <form id="catForm" onsubmit="submitCatForm(event)">
      <input type="hidden" id="catProductId">
      <div class="cat-form-grid">
        <div class="full">
          <label class="cat-label">Product Photo</label>
          <div class="cat-photo-zone" onclick="document.getElementById('catPhotoInput').click()">
            <img id="catPhotoPreview" class="cat-photo-preview" alt="">
            <div class="cat-photo-placeholder" id="catPhotoPlaceholder">
              <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;color:var(--text-muted)"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Click to upload a photo
            </div>
            <input type="file" id="catPhotoInput" accept="image/*" style="display:none" onchange="handleCatPhotoPreview(event)">
          </div>
        </div>
        <div class="full">
          <label class="cat-label" for="catProductName">Product Name</label>
          <input class="cat-input" type="text" id="catProductName" placeholder="e.g. Star Master Lamp" oninput="autoGenSku()">
        </div>
        <div>
          <label class="cat-label" for="catSkuId">SKU ID</label>
          <input class="cat-input" type="text" id="catSkuId" placeholder="Auto-generated (JK-…)">
        </div>
        <div>
          <label class="cat-label" for="catStatus">Status</label>
          <select class="cat-input" id="catStatus">
            <option value="pending">Pending</option>
            <option value="on-hold">On-Hold</option>
            <option value="active">Active</option>
          </select>
        </div>
        <div>
          <label class="cat-label" for="catMarketPrice">Market Price (₹)</label>
          <input class="cat-input" type="number" step="0.01" id="catMarketPrice" placeholder="0.00">
        </div>
        <div>
          <label class="cat-label" for="catBankSettlement">Bank Settlement (₹)</label>
          <input class="cat-input" type="number" step="0.01" id="catBankSettlement" placeholder="0.00">
        </div>
        <div>
          <label class="cat-label" for="catQty">Quantity</label>
          <input class="cat-input" type="number" id="catQty" placeholder="0">
        </div>
        <div class="full">
          <label class="cat-label">Group Name</label>
          <div class="multi-select-wrap" id="catGroupWrap">
            <div class="multi-select-trigger" id="catGroupTrigger" onclick="toggleGroupDropdown()">
              <span id="catGroupLabel" style="color:var(--text-muted)">— Select Groups —</span>
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="multi-select-dropdown" id="catGroupDropdown">
              <div class="multi-select-search-wrap" onclick="event.stopPropagation()">
                <input type="text" class="multi-select-search" id="catGroupSearch" placeholder="Search groups..." oninput="filterGroupOptions()">
              </div>
              <div id="catGroupOptions"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="cat-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeCatModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="catSubmitBtn">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          Save Product
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Payment Modal -->
<div class="cat-modal-bg" id="paymentModalBg" onclick="if(event.target===this)closePaymentModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closePaymentModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      Payment Entry
    </div>
    <form id="paymentForm" onsubmit="submitPaymentForm(event)">
      <div class="cat-form-grid">
        <div class="full">
          <label class="cat-label" for="payName">Name</label>
          <input class="cat-input" type="text" id="payName" required placeholder="Enter customer / payer name">
        </div>
        <div>
          <label class="cat-label" for="payValue">Payment Value (₹)</label>
          <input class="cat-input" type="number" step="0.01" id="payValue" required placeholder="0.00">
        </div>
        <div>
          <label class="cat-label" for="payDate">Date</label>
          <input class="cat-input" type="date" id="payDate" required>
        </div>
      </div>
      <div class="cat-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closePaymentModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="paySubmitBtn">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          Save Payment
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Order Modal -->
<div class="cat-modal-bg" id="orderModalBg" onclick="if(event.target===this)closeOrderModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closeOrderModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
      Order Entry
    </div>
    <form id="orderForm" onsubmit="submitOrderForm(event)">
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
        <button type="submit" class="btn btn-primary" id="orderSubmitBtn">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          Save Order
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Return Modal -->
<div class="cat-modal-bg" id="returnModalBg" onclick="if(event.target===this)closeReturnModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closeReturnModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
      Return Order Entry
    </div>
    <form id="returnForm" onsubmit="submitReturnForm(event)">
      <div class="cat-form-grid">
        <div>
          <label class="cat-label" for="returnOrderNumber">Return Order</label>
          <input class="cat-input" type="text" id="returnOrderNumber" required placeholder="e.g. RET-1001 or 2">
        </div>
        <div>
          <label class="cat-label" for="returnDate">Date</label>
          <input class="cat-input" type="date" id="returnDate" required>
        </div>
      </div>
      <div class="cat-modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeReturnModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="returnSubmitBtn">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          Save Return Order
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const API = '/api';
let searchTimer = null;
let currentPage = 1;
let lastPage = 1;
let catSkuTimer = null;

document.addEventListener('DOMContentLoaded', () => loadCatalogue());

function debounce() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage = 1; loadCatalogue(); }, 400);
}

function csrfToken() { return document.querySelector('meta[name="csrf-token"]').content; }

async function loadCatalogue() {
  const search = document.getElementById('searchInput').value.trim();
  const status = document.getElementById('statusFilter').value;
  let url = `${API}/catalogue?per_page=20&page=${currentPage}`;
  if (search) url += `&search=${encodeURIComponent(search)}`;
  if (status) url += `&status=${encodeURIComponent(status)}`;

  try {
    const r = await fetch(url);
    const d = await r.json();
    lastPage = d.last_page ?? 1;
    document.getElementById('totalCount').textContent = `${d.total ?? 0} product${(d.total ?? 0) !== 1 ? 's' : ''}`;
    renderTable(d.data ?? []);
    renderPagination();
  } catch(e) {
    document.getElementById('catBody').innerHTML = `<tr><td colspan="11" style="text-align:center;padding:40px;color:var(--red)">Failed to load data.</td></tr>`;
  }
}

function profitBadge(market, settlement) {
  if (market == null || settlement == null) return '<span style="color:var(--text-muted)">—</span>';
  const diff = Number(settlement) - Number(market);
  const formatted = (diff >= 0 ? '+' : '') + '₹' + Math.abs(diff).toLocaleString('en-IN');
  const cls = diff > 0 ? 'profit-positive' : diff < 0 ? 'profit-negative' : 'profit-zero';
  return `<span class="${cls}">${diff >= 0 ? '+' : '-'}₹${Math.abs(diff).toLocaleString('en-IN')}</span>`;
}

function editIcon() {
  return `<svg class="edit-icon" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderTable(items) {
  const tbody = document.getElementById('catBody');
  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="11"><div class="empty-state"><h3>No products found</h3><p>Add your first product using the button above.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = items.map(p => {
    const photo = p.photo_url
      ? `<img class="thumb" src="${esc(p.photo_url)}" alt="" onclick="openLightbox('${esc(p.photo_url)}')" onerror="this.outerHTML='<div class=\\'no-img\\'>No Img</div>'">`
      : `<div class="no-img">No Img</div>`;
    const priceVal  = p.market_price    != null ? Number(p.market_price) : null;
    const settleVal = p.bank_settlement != null ? Number(p.bank_settlement) : null;
    const priceDisp  = priceVal  != null ? `₹${priceVal.toLocaleString('en-IN')}` : '—';
    const settleDisp = settleVal != null ? `₹${settleVal.toLocaleString('en-IN')}` : '—';
    const qty    = p.qty != null ? p.qty : '—';
    const statusClass = { pending: 'status-pending', 'on-hold': 'status-on-hold', active: 'status-active' }[p.status] ?? '';
    const createdDate = formatDate(p.created_at);
    return `<tr id="row-${p.id}" data-market="${p.market_price ?? ''}" data-settlement="${p.bank_settlement ?? ''}">
      <td>${photo}</td>
      <td style="max-width:220px;font-weight:500">${esc(p.product_name)}</td>
      <td><span class="sku-tag">${esc(p.sku_id)}</span></td>
      <td id="mp-cell-${p.id}">
        <span class="editable-price" onclick="startInlineEdit(${p.id},'market_price',this)" title="Click to edit">
          ${priceDisp}${editIcon()}
        </span>
      </td>
      <td id="bs-cell-${p.id}">
        <span class="editable-price" onclick="startInlineEdit(${p.id},'bank_settlement',this)" title="Click to edit">
          ${settleDisp}${editIcon()}
        </span>
      </td>
      <td id="profit-cell-${p.id}">${profitBadge(p.market_price, p.bank_settlement)}</td>
      <td>${qty}</td>
      <td style="color:var(--text-muted)">${esc(p.group_name ?? '—')}</td>
      <td style="color:var(--text-muted);white-space:nowrap">${createdDate}</td>
      <td><span class="status-badge ${statusClass}">${esc(p.status)}</span></td>
      <td>
        <div style="display:flex;gap:8px">
          <button class="btn btn-ghost btn-sm" onclick="editProduct(${p.id})" title="Edit" style="padding:7px 9px">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id},'${esc(p.product_name)}')" title="Delete" style="padding:7px 9px">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
          </button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

// ── Inline price editing ─────────────────────────────────────────────────
function startInlineEdit(productId, field, spanEl) {
  // Don't open a second input if already editing
  if (spanEl.parentElement.querySelector('.inline-price-input')) return;
  const row = document.getElementById(`row-${productId}`);
  const currentRaw = field === 'market_price'
    ? row.dataset.market
    : row.dataset.settlement;
  const input = document.createElement('input');
  input.type = 'number';
  input.step = '0.01';
  input.className = 'inline-price-input';
  input.value = currentRaw !== '' ? currentRaw : '';
  input.placeholder = '0.00';
  // Hide the span, show input
  spanEl.style.display = 'none';
  spanEl.parentElement.appendChild(input);
  input.focus();
  input.select();

  const commit = async () => {
    const newVal = input.value.trim();
    input.remove();
    spanEl.style.display = '';
    const parsed = newVal !== '' ? parseFloat(newVal) : null;
    // Check if value actually changed
    const oldVal = currentRaw !== '' ? parseFloat(currentRaw) : null;
    if (parsed === oldVal) return; // no change
    await saveInlinePrice(productId, field, parsed, spanEl);
  };

  input.addEventListener('blur', commit);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
    if (e.key === 'Escape') { input.remove(); spanEl.style.display = ''; }
  });
}

async function saveInlinePrice(productId, field, value, spanEl) {
  try {
    const fd = new FormData();
    fd.append(field, value !== null ? value : '');
    // We need to send _method override because the route is POST with id
    const r = await fetch(`${API}/catalogue/${productId}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Save failed.');
    // Update row data attributes
    const row = document.getElementById(`row-${productId}`);
    if (field === 'market_price') {
      row.dataset.market = value !== null ? value : '';
    } else {
      row.dataset.settlement = value !== null ? value : '';
    }
    // Re-render the span
    const disp = value != null ? `₹${Number(value).toLocaleString('en-IN')}` : '—';
    spanEl.innerHTML = `${disp}<svg class="edit-icon" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
    // Refresh profit cell
    const market     = field === 'market_price'     ? value : (row.dataset.market !== '' ? parseFloat(row.dataset.market) : null);
    const settlement = field === 'bank_settlement'  ? value : (row.dataset.settlement !== '' ? parseFloat(row.dataset.settlement) : null);
    document.getElementById(`profit-cell-${productId}`).innerHTML = profitBadge(market, settlement);
    toast('Price updated!', 'success');
  } catch(err) {
    toast(err.message, 'error');
  }
}

function renderPagination() {
  const bar = document.getElementById('paginationBar');
  if (lastPage <= 1) { bar.innerHTML = ''; return; }
  let html = `<button class="btn btn-ghost btn-sm" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>← Prev</button>`;
  html += `<span style="color:var(--text-muted);font-size:13px">Page ${currentPage} of ${lastPage}</span>`;
  html += `<button class="btn btn-ghost btn-sm" onclick="goPage(${currentPage+1})" ${currentPage===lastPage?'disabled':''}>Next →</button>`;
  bar.innerHTML = html;
}

function goPage(p) {
  if (p < 1 || p > lastPage) return;
  currentPage = p;
  loadCatalogue();
}

async function editProduct(id) {
  try {
    const r = await fetch(`${API}/catalogue/${id}`);
    const p = await r.json();
    openCatModal(p);
  } catch(e) { toast('Failed to load product.', 'error'); }
}

async function deleteProduct(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  try {
    const r = await fetch(`${API}/catalogue/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    if (!r.ok) throw new Error('Delete failed.');
    toast('Product deleted.', 'success');
    loadCatalogue();
  } catch(e) { toast(e.message, 'error'); }
}

async function openCatModal(product = null) {
  document.getElementById('catModalHeading').textContent = product ? 'Edit Product' : 'Add Product';
  document.getElementById('catProductId').value       = product ? product.id : '';
  document.getElementById('catProductName').value     = product ? product.product_name : '';
  document.getElementById('catSkuId').value           = product ? product.sku_id : '';
  document.getElementById('catMarketPrice').value     = product ? (product.market_price ?? '') : '';
  document.getElementById('catBankSettlement').value  = product ? (product.bank_settlement ?? '') : '';
  document.getElementById('catQty').value             = product ? (product.qty ?? '') : '';
  await loadGroupsDropdown();
  // Use group_name_array (safe accessor) to restore checked state
  const selected = product && Array.isArray(product.group_name_array) ? product.group_name_array : [];
  restoreGroupSelections(selected);
  updateGroupLabel();
  document.getElementById('catStatus').value          = product ? product.status : 'pending';
  const preview = document.getElementById('catPhotoPreview');
  const holder  = document.getElementById('catPhotoPlaceholder');
  if (product && product.photo_url) {
    preview.src = product.photo_url; preview.style.display = 'block'; holder.style.display = 'none';
  } else {
    preview.style.display = 'none'; holder.style.display = 'block'; preview.src = '';
  }
  document.getElementById('catPhotoInput').value = '';
  document.getElementById('catModalBg').classList.add('open');
}

function closeCatModal() { document.getElementById('catModalBg').classList.remove('open'); }

function handleCatPhotoPreview(e) {
  const file = e.target.files[0]; if (!file) return;
  const preview = document.getElementById('catPhotoPreview');
  preview.src = URL.createObjectURL(file);
  preview.style.display = 'block';
  document.getElementById('catPhotoPlaceholder').style.display = 'none';
}

let _allGroupTitles = [];

async function loadGroupsDropdown() {
  try {
    const r = await fetch('/api/groups');
    const d = await r.json();
    _allGroupTitles = (d.groups ?? []).map(g => g.title);
    const container = document.getElementById('catGroupOptions');
    container.innerHTML = _allGroupTitles.map(title => `
      <label class="multi-select-option">
        <input type="checkbox" value="${esc(title)}" onchange="updateGroupLabel()">
        <span>${esc(title)}</span>
      </label>
    `).join('');
    document.getElementById('catGroupSearch').value = '';
    filterGroupOptions();
  } catch(e) {}
}

function filterGroupOptions() {
  const query = document.getElementById('catGroupSearch').value.toLowerCase();
  const options = document.querySelectorAll('#catGroupOptions .multi-select-option');
  options.forEach(opt => {
    const text = opt.querySelector('span').textContent.toLowerCase();
    opt.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

function toggleGroupDropdown() {
  const trigger = document.getElementById('catGroupTrigger');
  const dropdown = document.getElementById('catGroupDropdown');
  const isOpen = dropdown.classList.contains('open');
  dropdown.classList.toggle('open', !isOpen);
  trigger.classList.toggle('open', !isOpen);
}

function getSelectedGroups() {
  return Array.from(document.querySelectorAll('#catGroupDropdown input[type=checkbox]:checked'))
    .map(cb => cb.value);
}

function restoreGroupSelections(selected) {
  document.querySelectorAll('#catGroupDropdown input[type=checkbox]').forEach(cb => {
    cb.checked = selected.includes(cb.value);
  });
}

function updateGroupLabel() {
  const selected = getSelectedGroups();
  const label = document.getElementById('catGroupLabel');
  if (!selected.length) {
    label.textContent = '— Select Groups —';
    label.style.color = 'var(--text-muted)';
  } else if (selected.length === 1) {
    label.textContent = selected[0];
    label.style.color = 'var(--text)';
  } else {
    label.innerHTML = `${esc(selected[0])} <span class="multi-select-tag-count">+${selected.length - 1} more</span>`;
    label.style.color = 'var(--text)';
  }
}

document.addEventListener('click', function(e) {
  const wrap = document.getElementById('catGroupWrap');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('catGroupDropdown')?.classList.remove('open');
    document.getElementById('catGroupTrigger')?.classList.remove('open');
  }
});

async function autoGenSku() {
  const name = document.getElementById('catProductName').value.trim();
  const skuInput = document.getElementById('catSkuId');
  if (skuInput.value && !skuInput.value.startsWith('JK-')) return;
  clearTimeout(catSkuTimer);
  catSkuTimer = setTimeout(async () => {
    if (!name) { skuInput.value = ''; return; }
    try {
      const r = await fetch(`${API}/catalogue/sku-preview?name=${encodeURIComponent(name)}`);
      const d = await r.json();
      skuInput.value = d.sku || '';
    } catch(e) {}
  }, 400);
}

async function submitCatForm(e) {
  e.preventDefault();
  const btn = document.getElementById('catSubmitBtn');
  const productId = document.getElementById('catProductId').value;
  const isEdit = !!productId;
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving…';
  btn.disabled = true;
  const fd = new FormData();
  fd.append('product_name',    document.getElementById('catProductName').value);
  fd.append('sku_id',          document.getElementById('catSkuId').value);
  fd.append('market_price',    document.getElementById('catMarketPrice').value);
  fd.append('bank_settlement', document.getElementById('catBankSettlement').value);
  fd.append('qty',             document.getElementById('catQty').value);
  fd.append('group_name', JSON.stringify(getSelectedGroups()));
  fd.append('status',          document.getElementById('catStatus').value);
  const photoFile = document.getElementById('catPhotoInput').files[0];
  if (photoFile) fd.append('photo', photoFile);
  try {
    const r = await fetch(isEdit ? `${API}/catalogue/${productId}` : `${API}/catalogue`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save.');
    toast(isEdit ? 'Product updated!' : 'Product added!', 'success');
    closeCatModal();
    currentPage = 1;
    loadCatalogue();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function openLightbox(url) {
  const bg  = document.getElementById('lightboxBg');
  const img = document.getElementById('lightboxImg');
  const dl  = document.getElementById('lightboxDownload');
  img.src = url;
  dl.href = url;
  bg.classList.add('open');
}

function closeLightbox() {
  document.getElementById('lightboxBg').classList.remove('open');
  document.getElementById('lightboxImg').src = '';
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeLightbox();
    closeCatModal();
    closePaymentModal();
    closeOrderModal();
    closeReturnModal();
  }
});

// ── Payment, Order & Return Modal JS Handlers ─────────────────────────────
function openPaymentModal() {
  document.getElementById('payName').value = '';
  document.getElementById('payValue').value = '';
  document.getElementById('payDate').valueAsDate = new Date();
  document.getElementById('paymentModalBg').classList.add('open');
  loadPayments();
}

function closePaymentModal() {
  document.getElementById('paymentModalBg').classList.remove('open');
}

async function loadPayments() {
  const tbody = document.getElementById('payListBody');
  const countTag = document.getElementById('payCountTag');
  try {
    const r = await fetch(`${API}/payments`);
    const d = await r.json();
    const items = d.data ?? [];
    if (countTag) countTag.textContent = `${items.length} records`;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:16px">No payment records found.</td></tr>`;
      return;
    }
    tbody.innerHTML = items.map(p => `
      <tr>
        <td style="font-weight:500">${esc(p.name)}</td>
        <td style="color:var(--green)">₹${Number(p.payment_value).toLocaleString('en-IN')}</td>
        <td style="color:var(--text-muted)">${formatDate(p.date)}</td>
        <td style="text-align:right">
          <button class="btn-del-sm" onclick="deletePayment(${p.id})" title="Delete">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
          </button>
        </td>
      </tr>
    `).join('');
  } catch(e) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--red);padding:12px">Failed to load payments.</td></tr>`;
  }
}

async function submitPaymentForm(e) {
  e.preventDefault();
  const btn = document.getElementById('paySubmitBtn');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving…';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('name', document.getElementById('payName').value);
    fd.append('payment_value', document.getElementById('payValue').value);
    fd.append('date', document.getElementById('payDate').value);
    const r = await fetch(`${API}/payments`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save payment.');
    toast(d.message || 'Payment added successfully!', 'success');
    document.getElementById('payName').value = '';
    document.getElementById('payValue').value = '';
    loadPayments();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

async function deletePayment(id) {
  if (!confirm('Are you sure you want to delete this payment record?')) return;
  try {
    const r = await fetch(`${API}/payments/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to delete.');
    toast('Payment deleted!', 'success');
    loadPayments();
  } catch(err) {
    toast(err.message, 'error');
  }
}

function openOrderModal() {
  document.getElementById('orderNumber').value = '';
  document.getElementById('orderDate').valueAsDate = new Date();
  document.getElementById('orderModalBg').classList.add('open');
  loadOrders();
}

function closeOrderModal() {
  document.getElementById('orderModalBg').classList.remove('open');
}

async function loadOrders() {
  const tbody = document.getElementById('orderListBody');
  const countTag = document.getElementById('orderCountTag');
  try {
    const r = await fetch(`${API}/orders`);
    const d = await r.json();
    const items = d.data ?? [];
    if (countTag) countTag.textContent = `${items.length} records`;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:16px">No order records found.</td></tr>`;
      return;
    }
    tbody.innerHTML = items.map(o => `
      <tr>
        <td style="font-weight:500">${esc(o.order_number)}</td>
        <td style="color:var(--text-muted)">${formatDate(o.date)}</td>
        <td style="text-align:right">
          <button class="btn-del-sm" onclick="deleteOrder(${o.id})" title="Delete">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
          </button>
        </td>
      </tr>
    `).join('');
  } catch(e) {
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--red);padding:12px">Failed to load orders.</td></tr>`;
  }
}

async function submitOrderForm(e) {
  e.preventDefault();
  const btn = document.getElementById('orderSubmitBtn');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving…';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('order_number', document.getElementById('orderNumber').value);
    fd.append('date', document.getElementById('orderDate').value);
    const r = await fetch(`${API}/orders`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save order.');
    toast(d.message || 'Order added successfully!', 'success');
    document.getElementById('orderNumber').value = '';
    loadOrders();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

async function deleteOrder(id) {
  if (!confirm('Are you sure you want to delete this order record?')) return;
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

function openReturnModal() {
  document.getElementById('returnOrderNumber').value = '';
  document.getElementById('returnDate').valueAsDate = new Date();
  document.getElementById('returnModalBg').classList.add('open');
  loadReturnOrders();
}

function closeReturnModal() {
  document.getElementById('returnModalBg').classList.remove('open');
}

async function loadReturnOrders() {
  const tbody = document.getElementById('returnListBody');
  const countTag = document.getElementById('returnCountTag');
  try {
    const r = await fetch(`${API}/return-orders`);
    const d = await r.json();
    const items = d.data ?? [];
    if (countTag) countTag.textContent = `${items.length} records`;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:16px">No return order records found.</td></tr>`;
      return;
    }
    tbody.innerHTML = items.map(ro => `
      <tr>
        <td style="font-weight:500">${esc(ro.return_order_number)}</td>
        <td style="color:var(--text-muted)">${formatDate(ro.date)}</td>
        <td style="text-align:right">
          <button class="btn-del-sm" onclick="deleteReturnOrder(${ro.id})" title="Delete">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>
          </button>
        </td>
      </tr>
    `).join('');
  } catch(e) {
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--red);padding:12px">Failed to load return orders.</td></tr>`;
  }
}

async function submitReturnForm(e) {
  e.preventDefault();
  const btn = document.getElementById('returnSubmitBtn');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving…';
  btn.disabled = true;
  try {
    const fd = new FormData();
    fd.append('return_order_number', document.getElementById('returnOrderNumber').value);
    fd.append('date', document.getElementById('returnDate').value);
    const r = await fetch(`${API}/return-orders`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save return order.');
    toast(d.message || 'Return order added successfully!', 'success');
    document.getElementById('returnOrderNumber').value = '';
    loadReturnOrders();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

async function deleteReturnOrder(id) {
  if (!confirm('Are you sure you want to delete this return order record?')) return;
  try {
    const r = await fetch(`${API}/return-orders/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to delete.');
    toast('Return order deleted!', 'success');
    loadReturnOrders();
  } catch(err) {
    toast(err.message, 'error');
  }
}

function toast(msg, type = '') {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebarOverlay');
  if (sb) sb.classList.toggle('open');
  if (ov) ov.classList.toggle('open');
}
</script>
</body>
</html>
