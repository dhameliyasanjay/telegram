<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Price Monitor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #09090b;
  --surface: #18181b;
  --surface-hover: #27272a;
  --border: rgba(255,255,255,0.08);
  --border-focus: rgba(255,255,255,0.15);
  --accent: #3b82f6;
  --accent-glow: rgba(59, 130, 246, 0.4);
  --text: #fafafa;
  --text-muted: #a1a1aa;
  --radius: 16px;
  --green: #10b981;
  --red: #ef4444;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
  background: var(--bg); color: var(--text); font-family: 'Outfit', sans-serif;
  min-height: 100vh; overflow-x: hidden;
}
/* Header */
.header {
  position: sticky; top: 0; z-index: 100;
  background: rgba(9, 9, 11, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border); padding: 0 32px; height: 76px;
  display: flex; align-items: center; justify-content: space-between;
}
.logo { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; letter-spacing: -0.5px; }
.logo svg { width: 32px; height: 32px; }
.header-actions { display: flex; align-items: center; gap: 16px; }

/* Buttons */
.btn {
  padding: 10px 20px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; font-family: 'Outfit', sans-serif; 
  cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); display: inline-flex; align-items: center; gap: 8px; justify-content: center;
}
.btn-primary { background: var(--text); color: var(--bg); box-shadow: 0 4px 12px rgba(255,255,255,0.1); }
.btn-primary:hover { background: #e4e4e7; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,255,255,0.15); }
.btn-ghost { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
.btn-ghost:hover { background: var(--surface-hover); color: var(--text); border-color: var(--border-focus); }
.btn-sm { padding: 8px 14px; font-size: 13px; }

/* Stats Bar */
.stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; padding: 32px; }
.stat-card {
  background: linear-gradient(145deg, var(--surface) 0%, rgba(24,24,27,0.4) 100%);
  border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; position: relative; overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}
.stat-card::after {
  content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 2px; 
  background: linear-gradient(90deg, var(--accent), transparent); opacity: 0; transition: opacity 0.3s;
}
.stat-card:hover::after { opacity: 1; }
.stat-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;}
.stat-value { font-size: 36px; font-weight: 700; background: linear-gradient(to right, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

/* Toolbar & Filters */
.toolbar { padding: 0 32px 24px; display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
.search-wrap { position: relative; flex: 1; min-width: 250px; max-width: 480px; }
.search-wrap input {
  width: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; 
  padding: 12px 16px 12px 42px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 14px; transition: all 0.2s;
}
.search-wrap input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
.filter-select { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 12px 16px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 14px; cursor: pointer; outline: none; transition: border-color 0.2s; }
.filter-select:focus { border-color: var(--accent); }

/* Groups Strip */
.groups-strip { padding: 0 32px 24px; display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; }
.groups-strip::-webkit-scrollbar { display: none; }
.group-chip {
  background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 8px 18px; 
  font-size: 13px; font-weight: 500; cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 8px; transition: all 0.2s; color: var(--text-muted);
}
.group-chip:hover { background: var(--surface-hover); color: var(--text); }
.group-chip.active { background: rgba(59,130,246,0.1); border-color: var(--accent); color: var(--accent); box-shadow: 0 0 20px rgba(59,130,246,0.1); }
.group-count { background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 11px; color: var(--text-muted); }
.group-chip.active .group-count { background: var(--accent); color: #fff; }

/* Grid */
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; padding: 0 32px 48px; }

/* Product Card */
.card {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; 
  cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; position: relative;
}
.card:hover { transform: translateY(-4px); border-color: var(--border-focus); box-shadow: 0 16px 40px rgba(0,0,0,0.5); }
.card-img-wrap { width: 100%; height: 240px; overflow: hidden; background: #000; position: relative; }
.card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.2, 0.8, 0.2, 1); opacity: 0.9; }
.card:hover .card-img { transform: scale(1.08); opacity: 1; }
.card-body { padding: 20px; display: flex; flex-direction: column; flex: 1; position: relative; z-index: 2; background: var(--surface); }
.card-group-tag {
  align-self: flex-start; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; 
  color: var(--accent); background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 6px; margin-bottom: 12px; display: flex; align-items: center; gap: 4px;
}
.card-name { font-size: 15px; font-weight: 500; line-height: 1.5; margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; color: var(--text); flex: 1; }
.card-footer { display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid var(--border); padding-top: 16px; margin-top: auto; }
.card-price { font-size: 26px; font-weight: 700; color: #fff; letter-spacing: -0.5px; display: flex; align-items: center; gap: 6px; }
.card-price span.curr { font-size: 14px; color: var(--text-muted); font-weight: 500; }
.card-time { font-size: 12px; color: var(--text-muted); font-family: 'Inter', sans-serif; }
.price-tag { font-size: 11px; padding: 2px 6px; border-radius: 4px; font-weight: 700; background: rgba(255,255,255,0.1); }
.price-updated-recent { color: var(--green); background: rgba(16,185,129,0.15); box-shadow: 0 0 10px rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); }

@keyframes flash-green { 0% { box-shadow: 0 0 30px rgba(16, 185, 129, 0.4); border-color: var(--green); } 100% { box-shadow: none; border-color: var(--border); } }
.card.flash { animation: flash-green 1.5s ease-out; }

/* Empty State */
.empty { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 100px 20px; text-align: center; color: var(--text-muted); }
.empty h3 { font-size: 24px; font-weight: 600; color: var(--text); margin-bottom: 8px; }

/* History Drawer (The "Separate Box") */
.drawer-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); opacity: 0; pointer-events: none; transition: opacity 0.3s; z-index: 200;
}
.drawer-overlay.open { opacity: 1; pointer-events: all; }
.drawer {
  position: fixed; right: 0; top: 0; bottom: 0; width: 100%; max-width: 460px; background: var(--bg); 
  border-left: 1px solid var(--border); transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
  z-index: 210; display: flex; flex-direction: column; box-shadow: -20px 0 60px rgba(0,0,0,0.5);
}
.drawer.open { transform: translateX(0); }
.drawer-header { padding: 32px 32px 24px; border-bottom: 1px solid var(--border); background: var(--surface); }
.drawer-close {
  position: absolute; top: 32px; right: 32px; background: var(--bg); border: 1px solid var(--border); 
  width: 36px; height: 36px; border-radius: 50%; color: var(--text); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.drawer-close:hover { background: var(--surface-hover); transform: scale(1.05); }
.drawer-title { font-size: 22px; font-weight: 600; line-height: 1.4; padding-right: 48px; margin-bottom: 12px; }
.drawer-group { color: var(--accent); font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; background: rgba(59,130,246,0.1); padding: 6px 12px; border-radius: 8px; }
.drawer-body { padding: 32px; overflow-y: auto; flex: 1; background: var(--bg); }

/* Timeline */
.timeline { position: relative; padding-left: 28px; }
.timeline::before { content: ''; position: absolute; left: 6px; top: 12px; bottom: 24px; width: 2px; background: var(--border); }
.timeline-item { position: relative; margin-bottom: 32px; }
.timeline-dot {
  position: absolute; left: -28px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: var(--border); border: 2px solid var(--bg); z-index: 2; transition: all 0.2s;
}
.timeline-item:first-child .timeline-dot { border-color: var(--accent); background: var(--bg); box-shadow: 0 0 16px var(--accent-glow); border-width: 3px; width: 16px; height: 16px; left: -29px;}
.timeline-content { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 20px; transition: transform 0.2s; }
.timeline-content:hover { transform: translateX(4px); border-color: var(--border-focus); }
.timeline-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.timeline-price { font-size: 22px; font-weight: 700; color: #fff; }
.timeline-date { font-size: 12px; color: var(--text-muted); font-family: 'Inter', sans-serif; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px;}
.timeline-text { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; }

/* Modal */
.modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 300; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); opacity: 0; pointer-events: none; transition: opacity 0.2s; }
.modal-bg.open { opacity: 1; pointer-events: all; }
.modal { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 40px; width: 100%; max-width: 480px; box-shadow: 0 24px 64px rgba(0,0,0,0.6); transform: translateY(20px); transition: transform 0.3s; }
.modal-bg.open .modal { transform: translateY(0); }
.modal h2 { font-size: 24px; margin-bottom: 12px; font-weight: 700;}
.modal p { color: var(--text-muted); margin-bottom: 24px; font-size: 14px; line-height: 1.6; }
.modal input {
  width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 12px; padding: 16px; color: var(--text); font-size: 15px; outline: none; transition: border-color 0.2s; margin-bottom: 24px;
}
.modal input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.modal-actions { display: flex; gap: 12px; justify-content: flex-end; }

/* Toasts */
.toast-wrap { position: fixed; bottom: 32px; right: 32px; display: flex; flex-direction: column; gap: 12px; z-index: 500; }
.toast { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px 24px; font-size: 14px; font-weight: 500; box-shadow: 0 12px 40px rgba(0,0,0,0.4); animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); display: flex; align-items: center; gap: 12px; border-left: 4px solid var(--accent); }
.toast.success { border-left-color: var(--green); }
.toast.error { border-left-color: var(--red); }
@keyframes slideIn { from { transform: translateX(120%) opacity: 0; } to { transform: none; opacity: 1; } }

/* Live Indicator */
.live-indicator { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: var(--text); background: var(--surface); padding: 8px 16px; border-radius: 24px; border: 1px solid var(--border); }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green); box-shadow: 0 0 12px var(--green); animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

/* Loader */
.spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Pagination */
.pagination { padding: 0 32px 48px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="logo">
    <svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="16" fill="#3b82f6"/><path d="M7 16l3.5 3.5 2.5-2.5 5 5 7-9" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    Price Monitor
  </div>
  <div class="header-actions">
    <div class="live-indicator" id="liveIndicator">
        <span class="live-dot" id="liveDot" style="background:var(--red); box-shadow:0 0 12px var(--red)"></span>
        <span id="liveText">Connecting...</span>
    </div>
    <button class="btn btn-ghost" onclick="syncAllGroups(event)" id="syncAllBtn">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        Sync All History
    </button>
    <button class="btn btn-primary" onclick="openAddModal()">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Add Group
    </button>
  </div>
</header>

<!-- Stats -->
<div class="stats-bar" id="statsBar">
  <div class="stat-card">
      <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Monitored Groups</div>
      <div class="stat-value" id="statGroups">—</div>
  </div>
  <div class="stat-card">
      <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> Total Products</div>
      <div class="stat-value" id="statProducts">—</div>
  </div>
  <div class="stat-card">
      <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Updated Today</div>
      <div class="stat-value" id="statToday">—</div>
  </div>
  <div class="stat-card">
      <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Parsed Prices</div>
      <div class="stat-value" id="statPriced">—</div>
  </div>
</div>

<!-- Groups -->
<div class="groups-strip" id="groupsStrip"></div>

<!-- Toolbar -->
<div class="toolbar">
  <div class="search-wrap">
    <span class="search-icon">
      <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35" stroke-linecap="round"/></svg>
    </span>
    <input type="text" id="searchInput" placeholder="Search products across all groups…" oninput="debounceSearch()">
  </div>
  <input type="file" id="photoFilterInput" style="display:none" accept="image/*" onchange="handlePhotoFilter(event)">
  <button class="btn btn-ghost btn-sm" onclick="document.getElementById('photoFilterInput').click()" title="Filter by Photo">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
  </button>
  <div id="activePhotoFilter" style="display:none; align-items:center; gap:8px; background:rgba(59,130,246,0.1); color:var(--accent); padding:6px 12px; border-radius:8px; font-size:13px; font-weight:500; border:1px solid rgba(59,130,246,0.3);">
    <span>📷 Image Filter</span>
    <button style="background:transparent;border:none;color:inherit;cursor:pointer;display:flex;align-items:center;" onclick="clearPhotoFilter()"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </div>
  <select class="filter-select" id="sortSelect" onchange="loadBoard()" style="margin-left:auto">
    <option value="latest">Latest Updated First</option>
    <option value="price_asc">Price: Low to High</option>
    <option value="price_desc">Price: High to Low</option>
  </select>
  <button class="btn btn-ghost btn-sm" onclick="loadBoard()">Refresh</button>
</div>

<!-- Grid -->
<div class="grid" id="grid">
  <div class="empty">
      <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <h3>Loading workspace...</h3>
  </div>
</div>

<!-- Pagination -->
<div class="pagination" id="pagination"></div>

<!-- Add Group Modal -->
<div class="modal-bg" id="addModal" onclick="e=>e.target===this&&closeAddModal()">
  <div class="modal" onclick="event.stopPropagation()">
    <h2>Add Telegram Group</h2>
    <p>Paste an invite link or public username. The system will join the group and begin ingesting historical product data immediately.</p>
    <input type="text" id="groupLinkInput" placeholder="https://t.me/+XXXX or @username">
    <div id="addError" style="color:var(--red);font-size:14px;display:none;margin-bottom:16px;background:rgba(239,68,68,0.1);padding:12px;border-radius:8px;border:1px solid rgba(239,68,68,0.2)"></div>
    <div class="modal-actions">
      <button class="btn btn-ghost" onclick="closeAddModal()">Cancel</button>
      <button class="btn btn-primary" id="addBtn" onclick="addGroup()">Connect Group</button>
    </div>
  </div>
</div>

<!-- History Panel (Drawer) -->
<div class="drawer-overlay" id="historyOverlay" onclick="closeHistory()"></div>
<div class="drawer" id="historyDrawer">
  <div class="drawer-header">
    <button class="drawer-close" onclick="closeHistory()"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <div class="drawer-group" id="historyGroup">Group Name</div>
    <h3 class="drawer-title" id="historyTitle">Product Name</h3>
  </div>
  <div class="drawer-body">
      <div class="timeline" id="historyItems">
          <!-- Timeline items injected here -->
      </div>
  </div>
</div>

<!-- Toasts -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
const API = '/api';
let currentPage = 1;
let currentGroup = null;
let currentPhotoHash = null;
let searchTimer = null;
let allGroups = [];

// ── Boot ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadGroups();
  loadBoard();
  connectSse();
  document.getElementById('addModal').addEventListener('click', function(e){
    if(e.target === this) closeAddModal();
  });
});

// ── SSE ───────────────────────────────────────────────────────────────────
function connectSse() {
  const dot = document.getElementById('liveDot');
  const txt = document.getElementById('liveText');
  const es = new EventSource('/sse');
  es.addEventListener('ping', () => { 
      dot.style.background = 'var(--green)'; 
      dot.style.boxShadow = '0 0 12px var(--green)';
      txt.textContent = 'Live Syncing';
  });
  es.addEventListener('product_update', e => {
    const data = JSON.parse(e.data);
    handleLiveUpdate(data);
  });
  es.addEventListener('reconnect', () => { es.close(); setTimeout(connectSse, 1000); });
  es.onopen = () => { 
      dot.style.background = 'var(--green)'; 
      dot.style.boxShadow = '0 0 12px var(--green)';
      txt.textContent = 'Live Syncing';
  };
  es.onerror = () => { 
      dot.style.background = 'var(--red)'; 
      dot.style.boxShadow = '0 0 12px var(--red)';
      txt.textContent = 'Disconnected';
      setTimeout(connectSse, 5000); 
      es.close(); 
  };
}

function handleLiveUpdate(data) {
  const { action, product } = data;
  toast(action === 'new' ? `✦ Discovered: ${product.product_name.substring(0,35)}...` : `↑ Price updated: ${product.product_name.substring(0,35)}...`, action === 'new' ? 'success' : '');
  
  // Flash effect if product is on current page
  const card = document.getElementById(`prod-${product.id}`);
  if(card) {
      card.classList.remove('flash');
      void card.offsetWidth; // trigger reflow
      card.classList.add('flash');
  } else {
      loadBoard(currentPage);
  }
  loadStats();
}

// ── Stats ─────────────────────────────────────────────────────────────────
async function loadStats() {
  try {
    const r = await fetch(`${API}/board/stats`);
    const d = await r.json();
    document.getElementById('statGroups').textContent = d.total_groups ?? 0;
    document.getElementById('statProducts').textContent = d.total_products ?? 0;
    document.getElementById('statToday').textContent = d.updated_today ?? 0;
    document.getElementById('statPriced').textContent = d.with_price ?? 0;
  } catch(e) {}
}

// ── Groups ────────────────────────────────────────────────────────────────
async function loadGroups() {
  try {
    const r = await fetch(`${API}/groups`);
    const d = await r.json();
    allGroups = d.groups ?? [];
    renderGroupChips();
  } catch(e) {}
}

function renderGroupChips() {
  const strip = document.getElementById('groupsStrip');
  strip.innerHTML = '';
  if (!allGroups.length) return;

  const all = document.createElement('span');
  all.className = 'group-chip' + (!currentGroup ? ' active' : '');
  all.textContent = 'All Groups';
  all.onclick = () => { currentGroup = null; document.querySelectorAll('.group-chip').forEach(c=>c.classList.remove('active')); all.classList.add('active'); loadBoard(1); };
  strip.appendChild(all);

  allGroups.forEach(g => {
    const chip = document.createElement('span');
    chip.className = 'group-chip' + (currentGroup === g.id ? ' active' : '');
    chip.innerHTML = `${escHtml(g.title)} <span class="group-count">${g.products_count||0}</span>`;

    const syncBtn = document.createElement('button');
    syncBtn.className = 'btn btn-ghost btn-sm';
    syncBtn.style.cssText = 'padding:2px 6px; border:none; margin-left:4px; color:inherit;';
    syncBtn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>';
    syncBtn.title = 'Sync this group';
    syncBtn.onclick = e => { e.stopPropagation(); syncGroup(g.id, e, syncBtn); };
    chip.appendChild(syncBtn);

    chip.addEventListener('click', () => {
      currentGroup = g.id;
      document.querySelectorAll('.group-chip').forEach(c=>c.classList.remove('active'));
      chip.classList.add('active');
      loadBoard(1);
    });
    strip.appendChild(chip);
  });
}

async function syncGroup(id, e, btn) {
  if (e) e.stopPropagation();
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px"></span>';
  btn.disabled = true;
  try {
    const options = { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' } };
    const r = await fetch(`${API}/groups/${id}/sync`, options);
    const d = await r.json();
    if (!r.ok) throw new Error(d.error || 'Sync failed');
    toast(`Sync dispatched for group! Watch for live updates.`, 'success');
  } catch(e) {
    toast(e.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

async function syncAllGroups(e) {
  const btn = document.getElementById('syncAllBtn');
  if (!confirm(`Initialize full historical sync for ALL groups?\n\nThis will crawl the entire history of all connected channels to ensure absolutely no products are missed. This process runs in the background.`)) return;
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Syncing...';
  btn.disabled = true;
  try {
    const options = { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' } };
    const r = await fetch(`${API}/groups/sync-all`, options);
    const d = await r.json();
    if (!r.ok) throw new Error(d.error || 'Sync-all failed');
    toast(d.message || 'Full Sync-all dispatched!', 'success');
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

// ── Board ─────────────────────────────────────────────────────────────────
async function loadBoard(page = 1) {
  currentPage = page;
  const search = document.getElementById('searchInput').value.trim();
  let url = `${API}/board?per_page=24&page=${page}`;
  if (currentPhotoHash) {
      url += `&photo_hash=${currentPhotoHash}`;
  } else {
      if (currentGroup) url += `&group_id=${currentGroup}`;
      if (search) url += `&search=${encodeURIComponent(search)}`;
  }

  try {
    const r = await fetch(url);
    const d = await r.json();
    renderGrid(d.products ?? []);
    renderPagination(d.current_page, d.last_page);
  } catch(e) {
    document.getElementById('grid').innerHTML = `<div class="empty"><h3>Connection Error</h3><p>${e.message}</p></div>`;
  }
}

function renderGrid(products) {
  const grid = document.getElementById('grid');
  if (!products.length) {
    grid.innerHTML = `<div class="empty">
      <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <h3>No products found</h3><p>Try syncing your groups or adjusting the search.</p></div>`;
    return;
  }
  grid.innerHTML = products.map(p => cardHtml(p)).join('');
}

function cardHtml(p) {
  const hasPrice = p.latest_price != null;
  const priceDisplay = hasPrice ? `₹${Number(p.latest_price).toLocaleString('en-IN')}` : `Unlisted`;
  const img = p.latest_photo_url
    ? `<img src="${escHtml(p.latest_photo_url)}" alt="" class="card-img" loading="lazy" onerror="this.parentElement.innerHTML='<span style=\\'color:var(--text-muted)\\'>Image Unavailable</span>'">`
    : `<span style="color:var(--text-muted)">No image</span>`;
  const time = p.last_updated_at ? timeAgo(p.last_updated_at) : '';
  
  // Highlight if recently updated (within 24 hours) vs first seen
  const isRecentUpdate = p.last_updated_at && p.first_seen_at && p.last_updated_at !== p.first_seen_at && (new Date() - new Date(p.last_updated_at) < 86400000);
  const priceClass = isRecentUpdate ? 'price-tag price-updated-recent' : 'price-tag';
  const priceBadge = isRecentUpdate ? `<span class="${priceClass}">UPDATED</span>` : '';

  return `
  <div class="card" id="prod-${p.id}" onclick="showHistory(${p.id},'${escHtml(p.product_name.replace(/'/g,"\\\'"))}','${escHtml(p.group?.title||'')}')">
    <div class="card-img-wrap">${img}</div>
    <div class="card-body">
      <div class="card-group-tag">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
          ${escHtml(p.group?.title||'Unknown')}
      </div>
      <div class="card-name">${escHtml(p.product_name)}</div>
      <div class="card-footer">
          <div>
            <div class="card-price">${hasPrice ? '<span class="curr">₹</span>' : ''}${hasPrice ? Number(p.latest_price).toLocaleString('en-IN') : '<span style="font-size:16px;color:var(--text-muted);font-weight:500">Unlisted</span>'}</div>
            ${priceBadge}
          </div>
          <div class="card-time">${time}</div>
      </div>
    </div>
  </div>`;
}

function renderPagination(cur, last) {
  const wrap = document.getElementById('pagination');
  if (last <= 1) { wrap.innerHTML=''; return; }
  
  let html = '';
  const addBtn = (page, text, active, disabled) => {
    if (disabled) {
      html += `<button class="btn btn-ghost btn-sm" disabled style="opacity:0.5;cursor:default">${text}</button>`;
    } else {
      html += `<button class="btn ${active?'btn-primary':'btn-ghost'} btn-sm" onclick="loadBoard(${page})">${text}</button>`;
    }
  };

  addBtn(cur - 1, 'Prev', false, cur === 1);
  const delta = window.innerWidth < 600 ? 1 : 2;
  const range = [];
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= cur - delta && i <= cur + delta)) {
      range.push(i);
    }
  }
  let l;
  for (let i of range) {
    if (l) {
      if (i - l === 2) addBtn(l + 1, l + 1, false, false);
      else if (i - l !== 1) addBtn(0, '...', false, true);
    }
    addBtn(i, i, i === cur, false);
    l = i;
  }
  addBtn(cur + 1, 'Next', false, cur === last);
  wrap.innerHTML = html;
}

// ── History Panel ─────────────────────────────────────────────────────────
async function showHistory(id, name, group) {
  const drawer = document.getElementById('historyDrawer');
  const overlay = document.getElementById('historyOverlay');
  document.getElementById('historyTitle').textContent = name;
  document.getElementById('historyGroup').innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> ${group}`;
  document.getElementById('historyItems').innerHTML = '<div style="color:var(--text-muted);font-size:14px">Loading history timeline...</div>';
  
  overlay.classList.add('open');
  drawer.classList.add('open');
  
  try {
    const r = await fetch(`${API}/products/${id}/history`);
    const d = await r.json();
    const items = d.history ?? [];
    
    if(!items.length) {
        document.getElementById('historyItems').innerHTML = '<div style="color:var(--text-muted);font-size:14px">No price variations recorded yet.</div>';
        return;
    }
    
    document.getElementById('historyItems').innerHTML = items.map((h, index) => {
        const isLatest = index === 0;
        const priceDisp = h.price != null ? `₹${Number(h.price).toLocaleString('en-IN')}` : 'Unlisted';
        return `
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <div class="timeline-header">
                    <div class="timeline-price">${priceDisp}</div>
                    <div class="timeline-date">${h.posted_at ? timeAgo(h.posted_at) : ''}</div>
                </div>
                <div class="timeline-text">${escHtml((h.message_text||'').substring(0,100))}...</div>
            </div>
        </div>`;
    }).join('');
  } catch(e) {
      document.getElementById('historyItems').innerHTML = '<div style="color:var(--red);font-size:14px">Failed to load history.</div>';
  }
}
function closeHistory() { 
    document.getElementById('historyDrawer').classList.remove('open'); 
    document.getElementById('historyOverlay').classList.remove('open'); 
}

// ── Add Group Modal ───────────────────────────────────────────────────────
function openAddModal() { document.getElementById('addModal').classList.add('open'); document.getElementById('groupLinkInput').focus(); }
function closeAddModal() { document.getElementById('addModal').classList.remove('open'); document.getElementById('addError').style.display='none'; }

async function addGroup() {
  const link = document.getElementById('groupLinkInput').value.trim();
  if (!link) return;
  const btn = document.getElementById('addBtn');
  const err = document.getElementById('addError');
  err.style.display = 'none';
  btn.innerHTML = '<span class="spinner"></span> Connecting...';
  btn.disabled = true;
  try {
    const r = await fetch(`${API}/groups`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: JSON.stringify({ link })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || d.error || 'Failed to connect group.');
    closeAddModal();
    document.getElementById('groupLinkInput').value = '';
    toast(d.message || 'Group connected successfully!', 'success');
    loadGroups();
    loadBoard(1);
    loadStats();
  } catch(e) {
    err.textContent = e.message;
    err.style.display = 'block';
  } finally {
    btn.textContent = 'Connect Group';
    btn.disabled = false;
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────
function debounceSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(() => loadBoard(1), 400); }
function csrfToken() { return document.querySelector('meta[name="csrf-token"]').content; }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function timeAgo(iso) {
  const d = new Date(iso), n = new Date(), diff = Math.floor((n-d)/1000);
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
  return `${Math.floor(diff/86400)}d ago`;
}

async function handlePhotoFilter(e) {
    const file = e.target.files[0];
    if (!file) return;

    try {
        const formData = new FormData();
        formData.append('photo', file);
        
        const r = await fetch(`${API}/board/hash-photo`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const d = await r.json();
        if (!r.ok) throw new Error(d.message || 'Failed to hash image.');
        
        currentPhotoHash = d.photo_hash;
        
        document.getElementById('activePhotoFilter').style.display = 'flex';
        loadBoard(1);
    } catch (err) {
        toast(err.message || 'Failed to compute image hash.', 'error');
    }
    e.target.value = '';
}

function clearPhotoFilter() {
    currentPhotoHash = null;
    document.getElementById('activePhotoFilter').style.display = 'none';
    loadBoard(1);
}

function toast(msg, type='') {
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
