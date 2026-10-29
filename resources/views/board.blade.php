<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Price Monitor</title>
<link rel="manifest" href="/manifest.json">
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<meta name="theme-color" content="#3b82f6">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
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

/* ── Smart Comparison & Multi-Group UI ─────────────────────────────── */
.price-range-filter { display: flex; align-items: center; gap: 6px; }
.filter-num-input {
  width: 86px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px;
  padding: 11px 12px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 13.5px;
  outline: none; transition: all 0.2s;
}
.filter-num-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.filter-sep { color: var(--text-muted); font-size: 14px; font-weight: 500; }

.badge-multi-group {
  position: absolute; top: 10px; left: 10px; z-index: 10;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.95) 0%, rgba(139, 92, 246, 0.95) 100%);
  backdrop-filter: blur(8px); color: #fff; font-size: 11px; font-weight: 700;
  padding: 5px 10px; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;
  box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4); text-transform: uppercase; letter-spacing: 0.5px;
}
.badge-single-group {
  position: absolute; top: 10px; left: 10px; z-index: 10;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); color: #d4d4d8;
  font-size: 11px; font-weight: 500; padding: 4px 8px; border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.12); max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.chip-pill { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-right: 6px; margin-bottom: 6px; }
.chip-brand { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25); }
.chip-model { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.25); }
.card-meta-chips { display: flex; flex-wrap: wrap; margin-bottom: 8px; }

.card-best-group { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
.card-best-group strong { color: #60a5fa; font-weight: 600; }
.best-dot { width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 6px rgba(16,185,129,0.6); }

.badge-best-price {
  font-size: 10px; font-weight: 700; color: #10b981; background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 5px; padding: 2px 7px;
  letter-spacing: 0.5px; margin-left: 6px;
}
.card-price-spread { font-size: 12px; color: var(--text-muted); margin-top: 4px; font-family: 'Inter', sans-serif; }
.spread-savings { color: #10b981; font-weight: 600; }

.btn-compare-action {
  background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.25);
  color: #60a5fa; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 8px;
  cursor: pointer; transition: all 0.2s; font-family: 'Outfit', sans-serif; display: inline-flex; align-items: center; gap: 4px;
}
.btn-compare-action:hover {
  background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 4px 14px rgba(59,130,246,0.35);
}

/* Compare Drawer */
.drawer.drawer-wide { max-width: 620px; }
.compare-hero-grid {
  display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 18px;
}
.compare-hero-card {
  background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 12px;
  padding: 12px 14px; display: flex; flex-direction: column; gap: 4px;
}
.compare-hero-card.hero-cheapest {
  background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25);
}
.compare-hero-card.hero-cheapest .hero-val { color: #10b981; }
.compare-hero-card.hero-savings {
  background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.25);
}
.compare-hero-card.hero-savings .hero-val { color: #60a5fa; }
.hero-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); font-weight: 600; }
.hero-val { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: -0.5px; }
.hero-sub { font-size: 11px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.compare-body-intro { margin-bottom: 20px; }
.compare-body-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.compare-rule-note { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

/* Compare Offers List */
.compare-offers-list { display: flex; flex-direction: column; gap: 14px; }
.compare-offer-row {
  background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
  padding: 16px 18px; transition: border-color 0.2s, transform 0.2s;
}
.compare-offer-row:hover { border-color: var(--border-focus); transform: translateY(-1px); }
.compare-offer-row.is-cheapest-row {
  border-color: rgba(16, 185, 129, 0.4); background: linear-gradient(145deg, rgba(16,185,129,0.05) 0%, var(--surface) 100%);
}
.offer-row-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; margin-bottom: 12px; }
.offer-group-meta { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 0; }
.offer-group-title { font-size: 15px; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.offer-group-members { font-size: 11.5px; color: var(--text-muted); }
.offer-price-block { text-align: right; flex-shrink: 0; }
.offer-main-price { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; }
.offer-main-price.price-cheapest { color: #10b981; }
.pill-cheapest-tag {
  display: inline-block; font-size: 10px; font-weight: 700; background: rgba(16,185,129,0.18);
  color: #10b981; border: 1px solid rgba(16,185,129,0.35); padding: 2px 7px; border-radius: 6px;
  letter-spacing: 0.5px; margin-top: 3px;
}
.pill-diff-tag {
  display: inline-block; font-size: 11px; font-weight: 600; background: rgba(239, 68, 68, 0.12);
  color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); padding: 2px 7px; border-radius: 6px;
  margin-top: 3px;
}
.offer-row-bottom {
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
  padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 12px; color: var(--text-muted);
}
.offer-actions { display: flex; align-items: center; gap: 8px; }
.btn-tg-link {
  background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);
  padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none;
  display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
}
.btn-tg-link:hover { background: #3b82f6; color: #fff; }
.btn-history-toggle {
  background: transparent; color: var(--text-muted); border: 1px solid var(--border);
  padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer;
  display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;
}
.btn-history-toggle:hover { background: var(--surface-hover); color: var(--text); border-color: var(--border-focus); }
.offer-history-box {
  margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border);
}

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
        <a href="/" class="nav-item active">
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
        <a href="/orders" class="nav-item">
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
        @if(Auth::check() && Auth::user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}" class="nav-item" style="color: #60a5fa; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 6px;">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            <span style="font-weight: 600;">Admin Panel</span>
          </a>
        @endif
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

    <div class="sidebar-footer">
      <div class="live-indicator" id="liveIndicator">
        <span class="live-dot" id="liveDot" style="background:var(--red); box-shadow:0 0 12px var(--red)"></span>
        <span id="liveText">Connecting...</span>
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
          <h2>Dashboard</h2>
          <span class="page-subtitle">JAY KASTABHANJAN ENTERPRISE</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-ghost" onclick="syncAllGroups(event)" id="syncAllBtn">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
          Sync All History
        </button>
        <button class="btn btn-primary" onclick="openAddModal()" style="background: var(--accent); color: #fff; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);">
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
          <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> Canonical Products</div>
          <div class="stat-value" id="statProducts">—</div>
      </div>
      <div class="stat-card">
          <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> Multi-Group Matches</div>
          <div class="stat-value" id="statMultiGroup" style="color:#60a5fa">—</div>
      </div>
      <div class="stat-card">
          <div class="stat-label"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg> Total Group Offers</div>
          <div class="stat-value" id="statOffers">—</div>
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
        <input type="text" id="searchInput" placeholder="Search product name, brand, model, SKU…" oninput="debounceSearch()">
      </div>

      <div class="price-range-filter" title="Filter by Price Range">
        <input type="number" id="minPriceInput" placeholder="Min ₹" min="0" oninput="debounceSearch()" class="filter-num-input">
        <span class="filter-sep">–</span>
        <input type="number" id="maxPriceInput" placeholder="Max ₹" min="0" oninput="debounceSearch()" class="filter-num-input">
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
        <option value="latest">Latest Observed</option>
        <option value="cheapest">Price: Lowest First</option>
        <option value="highest">Price: Highest First</option>
        <option value="offers">Most Competing Groups</option>
        <option value="relevance">Best Match (Relevance)</option>
      </select>

      <button class="btn btn-ghost btn-sm" onclick="clearAllFilters()" title="Reset all filters">Reset</button>
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
  </div> <!-- /.main-wrapper -->
</div> <!-- /.app-layout -->

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

<!-- Smart Price Comparison Drawer (Rule A: 1 offer per group, Rule B: All groups shown) -->
<div class="drawer-overlay" id="compareOverlay" onclick="closeCompareDrawer()"></div>
<div class="drawer drawer-wide" id="compareDrawer">
  <div class="drawer-header" style="position:relative;">
    <button class="drawer-close" onclick="closeCompareDrawer()"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
    <div class="card-meta-chips" id="compareMetaChips"></div>
    <h3 class="drawer-title" id="compareTitle" style="padding-right:44px; margin-bottom:6px;">Product Comparison</h3>

    <!-- Hero Comparison Metrics -->
    <div class="compare-hero-grid" id="compareHeroGrid">
      <div class="compare-hero-card hero-cheapest">
        <span class="hero-label">Cheapest Offer</span>
        <span class="hero-val" id="heroCheapestPrice">₹—</span>
        <span class="hero-sub" id="heroCheapestGroup">in —</span>
      </div>
      <div class="compare-hero-card">
        <span class="hero-label">Highest Offer</span>
        <span class="hero-val" id="heroHighestPrice">₹—</span>
        <span class="hero-sub" id="heroOfferCount">— Groups</span>
      </div>
      <div class="compare-hero-card hero-savings">
        <span class="hero-label">Max Difference</span>
        <span class="hero-val" id="heroSpread">₹0</span>
        <span class="hero-sub" id="heroSpreadPercent">Spread between groups</span>
      </div>
    </div>
  </div>

  <div class="drawer-body">
    <div class="compare-body-intro">
      <div class="compare-body-title">Competing Group Offers</div>
      <div class="compare-rule-note">
        <svg width="14" height="14" fill="none" stroke="#10b981" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Rule A: 1 offer per group &bull; Rule B: All competing suppliers compared
      </div>
    </div>

    <div class="compare-offers-list" id="compareOffersList">
      <!-- Injected by JavaScript -->
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
let currentGroup = null;
let currentPhotoHash = null;
let searchTimer = null;
let allGroups = [];
let loadedCanonicalProducts = new Map();
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
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  }
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
  const name = product?.product_name || product?.name || 'Product';
  toast(action === 'new' ? `✦ Discovered: ${name.substring(0,35)}...` : `↑ Price updated: ${name.substring(0,35)}...`, action === 'new' ? 'success' : '');
  const canonicalId = product?.canonical_product_id || product?.id;
  const card = document.getElementById(`cp-${canonicalId}`);
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
    document.getElementById('statProducts').textContent = d.total_canonical_products ?? d.total_products ?? 0;
    document.getElementById('statMultiGroup').textContent = d.multi_group_products ?? 0;
    document.getElementById('statOffers').textContent = d.total_offers ?? 0;
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
  loadedCanonicalProducts.clear();
  document.getElementById('grid').innerHTML = '';
  if (infiniteObserver) { infiniteObserver.disconnect(); infiniteObserver = null; }
  loadMoreProducts();
  setupInfiniteScroll();
}

function clearAllFilters() {
  document.getElementById('searchInput').value = '';
  const minInput = document.getElementById('minPriceInput');
  const maxInput = document.getElementById('maxPriceInput');
  if (minInput) minInput.value = '';
  if (maxInput) maxInput.value = '';
  const gSelect = document.getElementById('groupSelect');
  if (gSelect) gSelect.value = '';
  currentGroup = null;
  clearPhotoFilter();
  const sortSelect = document.getElementById('sortSelect');
  if (sortSelect) sortSelect.value = 'latest';
  resetBoard();
}

async function loadMoreProducts() {
  if (infiniteLoading || infinitePage > infiniteLastPage) return;
  infiniteLoading = true;
  document.getElementById('scrollLoader').style.display = 'flex';

  const search = document.getElementById('searchInput').value.trim();
  const minPrice = document.getElementById('minPriceInput')?.value.trim();
  const maxPrice = document.getElementById('maxPriceInput')?.value.trim();

  let url = `${API}/board?per_page=24&page=${infinitePage}`;
  if (currentPhotoHash) {
    url += `&photo_hash=${currentPhotoHash}`;
  } else {
    if (currentGroup) url += `&group_id=${currentGroup}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (minPrice) url += `&min_price=${encodeURIComponent(minPrice)}`;
    if (maxPrice) url += `&max_price=${encodeURIComponent(maxPrice)}`;
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
      grid.innerHTML = `<div class="empty"><svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><h3>No products found</h3><p>Try adjusting your search terms, price range, or group filter.</p></div>`;
    } else {
      products.forEach(p => {
        loadedCanonicalProducts.set(p.id, p);
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
  const hasPrice = (p.cheapest_price != null && p.cheapest_price > 0);
  const cheapestPriceFmt = hasPrice ? `₹${Number(p.cheapest_price).toLocaleString('en-IN')}` : `Unlisted`;

  const imgUrl = p.primary_photo_url || (p.offers && p.offers[0] ? p.offers[0].latest_photo_url : null);
  const img = imgUrl
    ? `<img src="${escHtml(imgUrl)}" alt="" class="card-img" loading="lazy" onerror="this.parentElement.innerHTML='<span style=\\'color:var(--text-muted)\\'>Image Unavailable</span>'">`
    : `<span style="color:var(--text-muted)">No image</span>`;

  const time = p.last_observed_at ? timeAgo(p.last_observed_at) : '';

  // Multi-group vs Single-group badge
  const groupBadge = p.offers_count > 1
    ? `<span class="badge-multi-group"><svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> ${p.offers_count} Groups</span>`
    : `<span class="badge-single-group">${escHtml(p.cheapest_group?.title || '1 Group')}</span>`;

  // Download photo button
  const dlBtn = imgUrl
    ? `<a class="card-download-btn" href="${escHtml(imgUrl)}" download onclick="event.stopPropagation()" title="Download image"><svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M12 3v13M8 12l4 4 4-4M3 19h18" stroke-linecap="round" stroke-linejoin="round"/></svg>Download</a>`
    : '';

  // Meta chips (Brand & Model)
  let chips = '';
  if (p.brand) chips += `<span class="chip-pill chip-brand">${escHtml(p.brand)}</span>`;
  if (p.model) chips += `<span class="chip-pill chip-model">${escHtml(p.model)}</span>`;
  const chipsHtml = chips ? `<div class="card-meta-chips">${chips}</div>` : '';

  // Lowest in group indicator
  const bestGroupHtml = (p.offers_count > 1 && p.cheapest_group?.title)
    ? `<div class="card-best-group"><span class="best-dot"></span> Lowest in <strong>${escHtml(p.cheapest_group.title)}</strong></div>`
    : `<div class="card-best-group"><span class="best-dot"></span> ${escHtml(p.cheapest_group?.title || 'Single Group')}</div>`;

  // Price spread
  let spreadHtml = '';
  if (hasPrice && p.highest_price && p.highest_price > p.cheapest_price) {
    const diff = Math.round((p.highest_price - p.cheapest_price) * 100) / 100;
    spreadHtml = `<div class="card-price-spread">Spread: ₹${Number(p.cheapest_price).toLocaleString('en-IN')} – ₹${Number(p.highest_price).toLocaleString('en-IN')} <span class="spread-savings">(Save ₹${Number(diff).toLocaleString('en-IN')})</span></div>`;
  }

  return `
  <div class="card" id="cp-${p.id}" onclick="openCompareDrawer(${p.id})">
    <div class="card-img-wrap">
      ${img}
      ${groupBadge}
      ${dlBtn}
    </div>
    <div class="card-body">
      ${chipsHtml}
      <div class="card-name" title="${escHtml(p.product_name)}">${escHtml(p.product_name)}</div>
      ${bestGroupHtml}
      
      <div>
        <div class="card-price">
          ${hasPrice ? '<span class="curr">₹</span>' : ''}${hasPrice ? Number(p.cheapest_price).toLocaleString('en-IN') : '<span style="font-size:16px;color:var(--text-muted);font-weight:500">Unlisted</span>'}
          ${hasPrice ? '<span class="badge-best-price">BEST OFFER</span>' : ''}
        </div>
        ${spreadHtml}
      </div>

      <div class="card-footer" style="margin-top:14px;">
        <div class="card-time">${time}</div>
        <button class="btn-compare-action" onclick="event.stopPropagation(); openCompareDrawer(${p.id})">
          Compare (${p.offers_count}) &rarr;
        </button>
      </div>
    </div>
  </div>`;
}

// ── Smart Compare Drawer ──────────────────────────────────────────────────
async function openCompareDrawer(id) {
  let p = loadedCanonicalProducts.get(id);
  const overlay = document.getElementById('compareOverlay');
  const drawer = document.getElementById('compareDrawer');

  overlay.classList.add('open');
  drawer.classList.add('open');

  if (p) {
    renderCompareDrawer(p);
  } else {
    document.getElementById('compareTitle').textContent = 'Loading comparison...';
    document.getElementById('compareOffersList').innerHTML = '<div style="color:var(--text-muted);padding:24px;text-align:center;"><span class="spinner" style="width:16px;height:16px;"></span> Fetching competing offers...</div>';
  }

  // Always fetch fresh details in background or if not in cache
  try {
    const r = await fetch(`${API}/canonical-products/${id}`);
    if (r.ok) {
      const d = await r.json();
      if (d.product) {
        loadedCanonicalProducts.set(id, d.product);
        renderCompareDrawer(d.product);
      }
    }
  } catch(e) {}
}

function renderCompareDrawer(p) {
  document.getElementById('compareTitle').textContent = p.product_name;

  // Render chips
  let chips = '';
  if (p.brand) chips += `<span class="chip-pill chip-brand">${escHtml(p.brand)}</span>`;
  if (p.model) chips += `<span class="chip-pill chip-model">${escHtml(p.model)}</span>`;
  if (p.category) chips += `<span class="chip-pill" style="background:rgba(255,255,255,0.08);color:var(--text);">${escHtml(p.category)}</span>`;
  document.getElementById('compareMetaChips').innerHTML = chips;

  // Render Hero Metrics
  const cheapest = p.cheapest_price != null ? `₹${Number(p.cheapest_price).toLocaleString('en-IN')}` : 'Unlisted';
  const highest = p.highest_price != null ? `₹${Number(p.highest_price).toLocaleString('en-IN')}` : 'Unlisted';
  const spread = (p.cheapest_price && p.highest_price && p.highest_price > p.cheapest_price)
    ? Math.round((p.highest_price - p.cheapest_price) * 100) / 100
    : 0;
  const spreadPercent = (p.cheapest_price && spread > 0)
    ? Math.round((spread / p.highest_price) * 100)
    : 0;

  document.getElementById('heroCheapestPrice').textContent = cheapest;
  document.getElementById('heroCheapestGroup').textContent = p.cheapest_group?.title ? `in ${p.cheapest_group.title}` : '—';
  document.getElementById('heroHighestPrice').textContent = highest;
  document.getElementById('heroOfferCount').textContent = `${p.offers_count} Groups Monitored`;
  document.getElementById('heroSpread').textContent = spread > 0 ? `₹${Number(spread).toLocaleString('en-IN')}` : '₹0';
  document.getElementById('heroSpreadPercent').textContent = spreadPercent > 0 ? `Save up to ${spreadPercent}%` : 'Uniform price';

  // Render Offers List (Rule A: 1 offer per group, Rule B: show all competing groups)
  const offers = p.offers || [];
  if (!offers.length) {
    document.getElementById('compareOffersList').innerHTML = `<div style="color:var(--text-muted);padding:24px;text-align:center;">No offers found for this product.</div>`;
    return;
  }

  const offersHtml = offers.map(o => {
    const isCheapest = o.is_cheapest;
    const hasPrice = (o.latest_price != null && o.latest_price > 0);
    const priceText = hasPrice ? `₹${Number(o.latest_price).toLocaleString('en-IN')}` : 'Unlisted';

    let priceTag = '';
    if (isCheapest && hasPrice) {
      priceTag = `<span class="pill-cheapest-tag">🏆 CHEAPEST OFFER</span>`;
    } else if (o.price_diff != null && o.price_diff > 0) {
      priceTag = `<span class="pill-diff-tag">+₹${Number(o.price_diff).toLocaleString('en-IN')}</span>`;
    }

    const tgBtn = o.group_link
      ? `<a href="${escHtml(o.group_link)}" target="_blank" rel="noopener noreferrer" class="btn-tg-link">
          <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
          Open in Telegram &nearr;
         </a>`
      : '';

    return `
    <div class="compare-offer-row ${isCheapest ? 'is-cheapest-row' : ''}">
      <div class="offer-row-top">
        <div class="offer-group-meta">
          <div class="offer-group-title">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            <span>${escHtml(o.group_title)}</span>
          </div>
          <div class="offer-group-members">
            ${o.member_count ? `${Number(o.member_count).toLocaleString()} members &bull; ` : ''}Observed ${o.last_updated_at ? timeAgo(o.last_updated_at) : 'recently'}
          </div>
        </div>

        <div class="offer-price-block">
          <div class="offer-main-price ${isCheapest ? 'price-cheapest' : ''}">${priceText}</div>
          ${priceTag}
        </div>
      </div>

      <div class="offer-row-bottom">
        <div>Rule A: Verified single current offer for this group</div>
        <div class="offer-actions">
          <button class="btn-history-toggle" onclick="toggleOfferHistory(${o.id}, this)">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Price History
          </button>
          ${tgBtn}
        </div>
      </div>

      <div class="offer-history-box" id="offerHistory-${o.id}" style="display:none;"></div>
    </div>`;
  }).join('');

  document.getElementById('compareOffersList').innerHTML = offersHtml;
}

function closeCompareDrawer() {
  document.getElementById('compareDrawer').classList.remove('open');
  document.getElementById('compareOverlay').classList.remove('open');
}

async function toggleOfferHistory(offerId, btnEl) {
  const box = document.getElementById(`offerHistory-${offerId}`);
  if (!box) return;

  if (box.style.display !== 'none') {
    box.style.display = 'none';
    if (btnEl) btnEl.classList.remove('active');
    return;
  }

  box.style.display = 'block';
  box.innerHTML = '<div style="color:var(--text-muted);font-size:13px;padding:8px 0;"><span class="spinner" style="width:12px;height:12px;"></span> Loading price variations...</div>';
  if (btnEl) btnEl.classList.add('active');

  try {
    const r = await fetch(`${API}/products/${offerId}/history`);
    const d = await r.json();
    const items = d.history ?? [];

    if (!items.length) {
      box.innerHTML = '<div style="color:var(--text-muted);font-size:13px;padding:8px 0;">No historical price changes recorded for this group.</div>';
      return;
    }

    box.innerHTML = `
      <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;">
        Recorded Price History (${items.length} logs)
      </div>
      <div class="timeline" style="padding-left:20px;">
        ${items.map((h, idx) => {
          const priceFmt = h.price != null ? `₹${Number(h.price).toLocaleString('en-IN')}` : 'Unlisted';
          return `
            <div class="timeline-item" style="margin-bottom:16px;">
              <div class="timeline-dot" style="left:-20px;width:10px;height:10px;top:4px;"></div>
              <div class="timeline-content" style="padding:12px 14px;">
                <div class="timeline-header" style="margin-bottom:6px;">
                  <span style="font-weight:700;font-size:16px;color:#fff;">${priceFmt}</span>
                  <span class="timeline-date">${h.posted_at ? timeAgo(h.posted_at) : ''}</span>
                </div>
                ${h.message_text ? `<div class="timeline-text" style="font-size:12px;">${escHtml(h.message_text.substring(0, 80))}...</div>` : ''}
              </div>
            </div>`;
        }).join('')}
      </div>`;
  } catch(e) {
    box.innerHTML = '<div style="color:var(--red);font-size:13px;padding:8px 0;">Failed to load price history.</div>';
  }
}

// ── Legacy History Panel ──────────────────────────────────────────────────
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

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

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

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebarOverlay');
  if (sb) sb.classList.toggle('open');
  if (ov) ov.classList.toggle('open');
}
</script>
</body>
</html>
