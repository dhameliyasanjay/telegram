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

/* Group Dropdown */
.group-dropdown-wrap { padding: 0 32px 24px; display: flex; align-items: center; gap: 12px; }
.group-select {
  background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
  padding: 10px 40px 10px 16px; color: var(--text); font-family: 'Outfit', sans-serif;
  font-size: 14px; font-weight: 500; cursor: pointer; outline: none;
  transition: border-color 0.2s, box-shadow 0.2s; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%23a1a1aa' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 12px center;
  min-width: 220px;
}
.group-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.group-sync-btn { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; color: var(--text-muted); cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; font-size: 13px; font-family: 'Outfit', sans-serif; }
.group-sync-btn:hover { background: var(--surface-hover); color: var(--text); border-color: var(--border-focus); }
/* Infinite scroll sentinel */
.scroll-sentinel { height: 60px; display: flex; align-items: center; justify-content: center; margin: 0 32px 48px; }
.scroll-loader { display: flex; align-items: center; gap: 12px; color: var(--text-muted); font-size: 14px; }

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

/* Download button on card image */
.card-download-btn {
  position: absolute; top: 10px; right: 10px; z-index: 10;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
  color: #fff; padding: 6px 10px; font-size: 12px; font-weight: 600;
  font-family: 'Outfit', sans-serif; cursor: pointer; text-decoration: none;
  display: inline-flex; align-items: center; gap: 5px;
  opacity: 0; transition: opacity 0.2s, background 0.2s;
  pointer-events: none;
}
.card:hover .card-download-btn { opacity: 1; pointer-events: all; }
.card-download-btn:hover { background: rgba(59,130,246,0.75); border-color: rgba(59,130,246,0.5); }
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

/* Pagination hidden - using infinite scroll */
.pagination { display: none; }

/* ── Catalogue Modal ───────────────────────────────────────────────── */
.cat-modal-bg {
  position: fixed; inset: 0; background: rgba(0,0,0,0.82); z-index: 400;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(10px); opacity: 0; pointer-events: none; transition: opacity 0.25s;
}
.cat-modal-bg.open { opacity: 1; pointer-events: all; }
.cat-modal {
  background: var(--surface); border: 1px solid var(--border); border-radius: 24px;
  padding: 36px 40px 32px; width: 100%; max-width: 600px; max-height: 92vh;
  overflow-y: auto; box-shadow: 0 32px 80px rgba(0,0,0,0.7);
  transform: translateY(24px); transition: transform 0.3s cubic-bezier(0.16,1,0.3,1);
  position: relative;
}
.cat-modal-bg.open .cat-modal { transform: translateY(0); }
.cat-modal-title {
  font-size: 22px; font-weight: 700; margin-bottom: 28px; letter-spacing: -0.4px;
  display: flex; align-items: center; gap: 10px;
}
.cat-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.cat-form-grid .full { grid-column: 1 / -1; }
.cat-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px; display: block; }
.cat-input {
  width: 100%; background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
  padding: 12px 14px; color: var(--text); font-size: 14px; font-family: 'Outfit', sans-serif;
  outline: none; transition: border-color 0.2s, box-shadow 0.2s;
}
.cat-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.cat-photo-zone {
  border: 2px dashed var(--border); border-radius: 12px; padding: 24px;
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  cursor: pointer; transition: border-color 0.2s; position: relative;
}
.cat-photo-zone:hover { border-color: var(--accent); }
.cat-photo-preview { width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; display: none; }
.cat-photo-placeholder { color: var(--text-muted); font-size: 13px; text-align: center; }
.cat-modal-footer { display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; border-top: 1px solid var(--border); padding-top: 24px; }
.cat-close-btn {
  position: absolute; top: 20px; right: 20px; background: var(--bg); border: 1px solid var(--border);
  width: 34px; height: 34px; border-radius: 50%; color: var(--text); cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.cat-close-btn:hover { background: var(--surface-hover); }

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
    <a href="/catalogue" class="btn btn-ghost" style="text-decoration:none">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h10"/></svg>
        Catalogue
    </a>
    <button class="btn btn-ghost" onclick="openCatModal()" id="addCatBtn">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Add Product
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

<!-- Group Dropdown -->
<div class="group-dropdown-wrap">
  <select class="group-select" id="groupSelect" onchange="onGroupChange(this.value)">
    <option value="">All Groups</option>
  </select>
  <button class="group-sync-btn" id="groupSyncBtn" onclick="syncSelectedGroup()" title="Sync selected group">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
    Sync Group
  </button>
</div>

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

<!-- Infinite Scroll Sentinel -->
<div class="scroll-sentinel" id="scrollSentinel">
  <div class="scroll-loader" id="scrollLoader" style="display:none">
    <span class="spinner"></span>
    <span>Loading more products...</span>
  </div>
</div>

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

<!-- ── Catalogue Add/Edit Modal ───────────────────────────────────────── -->
<div class="cat-modal-bg" id="catModalBg" onclick="if(event.target===this)closeCatModal()">
  <div class="cat-modal" onclick="event.stopPropagation()">
    <button class="cat-close-btn" onclick="closeCatModal()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </button>
    <div class="cat-modal-title">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <span id="catModalHeading">Add Product</span>
    </div>

    <form id="catForm" onsubmit="submitCatForm(event)" enctype="multipart/form-data">
      <input type="hidden" id="catProductId" value="">

      <div class="cat-form-grid">
        <!-- Photo Upload -->
        <div class="full">
          <label class="cat-label">Product Photo</label>
          <div class="cat-photo-zone" onclick="document.getElementById('catPhotoInput').click()">
            <img id="catPhotoPreview" class="cat-photo-preview" alt="Preview">
            <div class="cat-photo-placeholder" id="catPhotoPlaceholder">
              <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;color:var(--text-muted)"><path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Click to upload a photo
            </div>
            <input type="file" id="catPhotoInput" accept="image/*" style="display:none" onchange="handleCatPhotoPreview(event)">
          </div>
        </div>

        <!-- Product Name -->
        <div class="full">
          <label class="cat-label" for="catProductName">Product Name</label>
          <input class="cat-input" type="text" id="catProductName" placeholder="e.g. Star Master Lamp" oninput="autoGenSku()">
        </div>

        <!-- SKU ID -->
        <div>
          <label class="cat-label" for="catSkuId">SKU ID</label>
          <input class="cat-input" type="text" id="catSkuId" placeholder="Auto-generated (JK-…)">
        </div>

        <!-- Status -->
        <div>
          <label class="cat-label" for="catStatus">Status</label>
          <select class="cat-input" id="catStatus">
            <option value="pending">Pending</option>
            <option value="on-hold">On-Hold</option>
            <option value="active">Active</option>
          </select>
        </div>

        <!-- Market Price -->
        <div>
          <label class="cat-label" for="catMarketPrice">Market Price (₹)</label>
          <input class="cat-input" type="number" step="0.01" id="catMarketPrice" placeholder="0.00">
        </div>

        <!-- Bank Settlement -->
        <div>
          <label class="cat-label" for="catBankSettlement">Bank Settlement (₹)</label>
          <input class="cat-input" type="number" step="0.01" id="catBankSettlement" placeholder="0.00">
        </div>

        <!-- Qty -->
        <div>
          <label class="cat-label" for="catQty">Quantity</label>
          <input class="cat-input" type="number" id="catQty" placeholder="0">
        </div>

        <!-- Group Name -->
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

<script>
const API = '/api';
let currentGroup = null;
let currentPhotoHash = null;
let searchTimer = null;
let allGroups = [];
// Infinite scroll state
let infinitePage = 1;
let infiniteLastPage = 1;
let infiniteLoading = false;
let infiniteObserver = null;

// ── Boot ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadGroups();
  resetBoard();
  setupInfiniteScroll();
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
  const card = document.getElementById(`prod-${product.id}`);
  if(card) {
      card.classList.remove('flash');
      void card.offsetWidth;
      card.classList.add('flash');
  } else {
      resetBoard();
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

// ── Groups Dropdown ───────────────────────────────────────────────────────
async function loadGroups() {
  try {
    const r = await fetch(`${API}/groups`);
    const d = await r.json();
    allGroups = d.groups ?? [];
    renderGroupDropdown();
  } catch(e) {}
}

function renderGroupDropdown() {
  const sel = document.getElementById('groupSelect');
  // Keep first 'All Groups' option
  while (sel.options.length > 1) sel.remove(1);
  allGroups.forEach(g => {
    const opt = document.createElement('option');
    opt.value = g.id;
    opt.textContent = `${g.title} (${g.products_count || 0})`;
    sel.appendChild(opt);
  });
  // Restore selection
  sel.value = currentGroup ?? '';
}

function onGroupChange(val) {
  currentGroup = val ? parseInt(val) : null;
  currentPhotoHash = null;
  document.getElementById('activePhotoFilter').style.display = 'none';
  resetBoard();
}

async function syncSelectedGroup() {
  const id = currentGroup;
  const btn = document.getElementById('groupSyncBtn');
  const orig = btn.innerHTML;
  const url = id ? `${API}/groups/${id}/sync` : `${API}/groups/sync-all`;
  if (!id && !confirm('Sync ALL groups history?')) return;
  btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border-width:2px"></span> Syncing...';
  btn.disabled = true;
  try {
    const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' } });
    const d = await r.json();
    if (!r.ok) throw new Error(d.error || 'Sync failed');
    toast('Sync dispatched! Watch for live updates.', 'success');
  } catch(e) {
    toast(e.message, 'error');
  } finally {
    btn.innerHTML = orig;
    btn.disabled = false;
  }
}

async function syncAllGroups(e) {
  const btn = document.getElementById('syncAllBtn');
  if (!confirm(`Initialize full historical sync for ALL groups?\n\nThis will crawl the entire history of all connected channels. This process runs in the background.`)) return;
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Syncing...';
  btn.disabled = true;
  try {
    const r = await fetch(`${API}/groups/sync-all`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' } });
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

// ── Board & Infinite Scroll ───────────────────────────────────────────────
function resetBoard() {
  infinitePage = 1;
  infiniteLastPage = 1;
  infiniteLoading = false;
  document.getElementById('grid').innerHTML = '';
  if (infiniteObserver) { infiniteObserver.disconnect(); infiniteObserver = null; }
  loadMoreProducts();
  setupInfiniteScroll();
}

async function loadMoreProducts() {
  if (infiniteLoading || infinitePage > infiniteLastPage) return;
  infiniteLoading = true;
  document.getElementById('scrollLoader').style.display = 'flex';

  const search = document.getElementById('searchInput').value.trim();
  let url = `${API}/board?per_page=24&page=${infinitePage}`;
  if (currentPhotoHash) {
    url += `&photo_hash=${currentPhotoHash}`;
  } else {
    if (currentGroup) url += `&group_id=${currentGroup}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
  }

  const sortVal = document.getElementById('sortSelect')?.value;
  if (sortVal) url += `&sort=${sortVal}`;

  try {
    const r = await fetch(url);
    const d = await r.json();
    const products = d.products ?? [];
    infiniteLastPage = d.last_page ?? 1;

    const grid = document.getElementById('grid');
    if (infinitePage === 1 && !products.length) {
      grid.innerHTML = `<div class="empty"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><h3>No products found</h3><p>Try syncing your groups or adjusting the search.</p></div>`;
    } else {
      products.forEach(p => {
        grid.insertAdjacentHTML('beforeend', cardHtml(p));
      });
    }
    infinitePage++;
  } catch(e) {
    if (infinitePage === 1) {
      document.getElementById('grid').innerHTML = `<div class="empty"><h3>Connection Error</h3><p>${e.message}</p></div>`;
    }
  } finally {
    infiniteLoading = false;
    document.getElementById('scrollLoader').style.display = 'none';
  }
}

function setupInfiniteScroll() {
  const sentinel = document.getElementById('scrollSentinel');
  infiniteObserver = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && infinitePage <= infiniteLastPage && !infiniteLoading) {
      loadMoreProducts();
    }
  }, { rootMargin: '200px' });
  infiniteObserver.observe(sentinel);
}

// Legacy alias used elsewhere
function loadBoard(page) { resetBoard(); }

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

  const dlBtn = p.latest_photo_url
    ? `<a class="card-download-btn" href="${escHtml(p.latest_photo_url)}" download onclick="event.stopPropagation()" title="Download image"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M12 3v13M8 12l4 4 4-4M3 19h18" stroke-linecap="round" stroke-linejoin="round"/></svg>Download</a>`
    : '';

  return `
  <div class="card" id="prod-${p.id}" onclick="showHistory(${p.id},'${escHtml(p.product_name.replace(/'/g,"\\\'"))}','${escHtml(p.group?.title||'')}')">
    <div class="card-img-wrap">${img}${dlBtn}</div>
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
        resetBoard();
    } catch (err) {
        toast(err.message || 'Failed to compute image hash.', 'error');
    }
    e.target.value = '';
}

function clearPhotoFilter() {
    currentPhotoHash = null;
    document.getElementById('activePhotoFilter').style.display = 'none';
    resetBoard();
}

function toast(msg, type='') {
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

// ── Catalogue Modal ──────────────────────────────────────────────────────────
let catSkuTimer = null;

async function openCatModal(product = null) {
  const bg = document.getElementById('catModalBg');
  document.getElementById('catModalHeading').textContent = product ? 'Edit Product' : 'Add Product';
  document.getElementById('catProductId').value = product ? product.id : '';
  document.getElementById('catProductName').value = product ? product.product_name : '';
  document.getElementById('catSkuId').value = product ? product.sku_id : '';
  document.getElementById('catMarketPrice').value = product ? (product.market_price ?? '') : '';
  document.getElementById('catBankSettlement').value = product ? (product.bank_settlement ?? '') : '';
  document.getElementById('catQty').value = product ? (product.qty ?? '') : '';
  await loadGroupsDropdown();
  // Restore selected groups
  const selected = product && Array.isArray(product.group_name) ? product.group_name : [];
  restoreGroupSelections(selected);
  updateGroupLabel();
  document.getElementById('catStatus').value = product ? product.status : 'pending';
  // Photo preview
  const preview = document.getElementById('catPhotoPreview');
  const placeholder = document.getElementById('catPhotoPlaceholder');
  if (product && product.photo_url) {
    preview.src = product.photo_url;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
  } else {
    preview.style.display = 'none';
    placeholder.style.display = 'block';
    preview.src = '';
  }
  document.getElementById('catPhotoInput').value = '';
  bg.classList.add('open');
}

function closeCatModal() {
  document.getElementById('catModalBg').classList.remove('open');
}

function handleCatPhotoPreview(e) {
  const file = e.target.files[0];
  if (!file) return;
  const preview = document.getElementById('catPhotoPreview');
  const placeholder = document.getElementById('catPhotoPlaceholder');
  preview.src = URL.createObjectURL(file);
  preview.style.display = 'block';
  placeholder.style.display = 'none';
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
        <input type="checkbox" value="${escHtml(title)}" onchange="updateGroupLabel()">
        <span>${escHtml(title)}</span>
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
  if (isOpen) {
    dropdown.classList.remove('open');
    trigger.classList.remove('open');
  } else {
    dropdown.classList.add('open');
    trigger.classList.add('open');
  }
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
    label.innerHTML = `${escHtml(selected[0])} <span class="multi-select-tag-count">+${selected.length - 1} more</span>`;
    label.style.color = 'var(--text)';
  }
}

// Close dropdown when clicking outside
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
  // Only auto-gen if SKU is empty or was previously auto-generated (starts with JK-)
  const currentSku = skuInput.value;
  if (currentSku && !currentSku.startsWith('JK-')) return;
  clearTimeout(catSkuTimer);
  catSkuTimer = setTimeout(async () => {
    if (!name) { skuInput.value = ''; return; }
    try {
      const r = await fetch(`/api/catalogue/sku-preview?name=${encodeURIComponent(name)}`);
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
  const origHtml = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Saving...';
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

  const url    = isEdit ? `/api/catalogue/${productId}` : '/api/catalogue';
  const method = 'POST';

  try {
    const r = await fetch(url, {
      method,
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
      body: fd,
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Failed to save product.');
    toast(isEdit ? 'Product updated!' : 'Product added to catalogue!', 'success');
    closeCatModal();
  } catch(err) {
    toast(err.message, 'error');
  } finally {
    btn.innerHTML = origHtml;
    btn.disabled = false;
  }
}
</script>
</body>
</html>
