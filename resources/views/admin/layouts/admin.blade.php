<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Admin Dashboard') — Price Monitor Admin</title>
  <link rel="icon" type="image/svg+xml" href="/icon.svg">
  <meta name="theme-color" content="#3b82f6">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #09090b;
      --surface: #121215;
      --surface-card: #18181b;
      --surface-hover: #27272a;
      --border: rgba(255, 255, 255, 0.08);
      --border-focus: rgba(59, 130, 246, 0.5);
      --accent: #3b82f6;
      --accent-glow: rgba(59, 130, 246, 0.25);
      --text: #fafafa;
      --text-muted: #a1a1aa;
      --text-dim: #71717a;
      --green: #10b981;
      --green-bg: rgba(16, 185, 129, 0.12);
      --red: #ef4444;
      --red-bg: rgba(239, 68, 68, 0.12);
      --amber: #f59e0b;
      --amber-bg: rgba(245, 158, 11, 0.12);
      --purple: #8b5cf6;
      --purple-bg: rgba(139, 92, 246, 0.12);
      --radius: 12px;
      --sidebar-width: 260px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
    }

    /* Sidebar */
    .admin-sidebar {
      width: var(--sidebar-width);
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 100;
      transition: transform 0.25s ease;
    }

    .brand-area {
      padding: 24px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .brand-logo {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px var(--accent-glow);
    }
    .brand-text {
      font-size: 1.1rem;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .admin-badge {
      font-size: 0.65rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      background: var(--purple-bg);
      color: var(--purple);
      border: 1px solid rgba(139, 92, 246, 0.3);
      padding: 2px 7px;
      border-radius: 6px;
    }

    .nav-list {
      flex: 1;
      padding: 20px 12px;
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 4px;
      overflow-y: auto;
    }
    .nav-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-dim);
      padding: 12px 10px 4px;
      font-weight: 600;
    }
    .nav-item a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.92rem;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.15s ease;
    }
    .nav-item a:hover {
      color: var(--text);
      background: var(--surface-hover);
    }
    .nav-item.active a {
      color: #fff;
      background: var(--accent);
      box-shadow: 0 4px 12px var(--accent-glow);
    }
    .nav-item a svg {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
    }

    .user-footer {
      padding: 16px 20px;
      border-top: 1px solid var(--border);
      background: rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .admin-user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      overflow: hidden;
    }
    .admin-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #27272a;
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
      color: var(--accent);
    }
    .admin-meta {
      overflow: hidden;
      font-size: 0.82rem;
      line-height: 1.2;
    }
    .admin-name {
      color: #fff;
      font-weight: 600;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
    }
    .admin-role {
      color: var(--text-dim);
      font-size: 0.72rem;
    }

    /* Main Content Area */
    .admin-main {
      margin-left: var(--sidebar-width);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .admin-header {
      height: 70px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .header-title-wrap h1 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
    }
    .header-actions {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .admin-content {
      padding: 32px;
      flex: 1;
      max-width: 1400px;
      width: 100%;
      margin: 0 auto;
    }

    /* Alerts & Toasts */
    .alert-banner {
      padding: 12px 16px;
      border-radius: 10px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.9rem;
    }
    .alert-success {
      background: var(--green-bg);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34d399;
    }
    .alert-danger {
      background: var(--red-bg);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #f87171;
    }

    /* Reusable Cards & Components */
    .card {
      background: var(--surface-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
    }
    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .card-title {
      font-size: 1.05rem;
      font-weight: 600;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-family: inherit;
      font-size: 0.88rem;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
    }
    .btn-primary {
      background: var(--accent);
      color: #fff;
      box-shadow: 0 4px 12px var(--accent-glow);
    }
    .btn-primary:hover {
      background: #2563eb;
    }
    .btn-secondary {
      background: var(--surface-hover);
      color: var(--text);
      border: 1px solid var(--border);
    }
    .btn-secondary:hover {
      background: #3f3f46;
    }
    .btn-danger {
      background: var(--red-bg);
      color: var(--red);
      border: 1px solid rgba(239, 68, 68, 0.25);
    }
    .btn-danger:hover {
      background: rgba(239, 68, 68, 0.25);
    }
    .btn-sm {
      padding: 5px 10px;
      font-size: 0.78rem;
      border-radius: 6px;
    }

    /* Badges */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 9px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      letter-spacing: 0.02em;
    }
    .badge-success { background: var(--green-bg); color: var(--green); border: 1px solid rgba(16, 185, 129, 0.25); }
    .badge-danger  { background: var(--red-bg); color: var(--red); border: 1px solid rgba(239, 68, 68, 0.25); }
    .badge-warning { background: var(--amber-bg); color: var(--amber); border: 1px solid rgba(245, 158, 11, 0.25); }
    .badge-purple  { background: var(--purple-bg); color: var(--purple); border: 1px solid rgba(139, 92, 246, 0.25); }
    .badge-muted   { background: rgba(255, 255, 255, 0.06); color: var(--text-muted); border: 1px solid var(--border); }

    /* Tables */
    .table-container {
      width: 100%;
      overflow-x: auto;
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    table.data-table th {
      padding: 12px 16px;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-dim);
      font-weight: 600;
      border-bottom: 1px solid var(--border);
    }
    table.data-table td {
      padding: 14px 16px;
      font-size: 0.88rem;
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }
    table.data-table tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    /* Inputs & Forms */
    .form-group {
      margin-bottom: 18px;
    }
    .form-label {
      display: block;
      font-size: 0.85rem;
      font-weight: 500;
      color: var(--text-muted);
      margin-bottom: 6px;
    }
    .form-input, .form-select {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 10px 14px;
      color: #fff;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.15s ease;
    }
    .form-input:focus, .form-select:focus {
      border-color: var(--border-focus);
    }

    /* Responsive */
    @media (max-width: 900px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }
      .admin-sidebar.open {
        transform: translateX(0);
      }
      .admin-main {
        margin-left: 0;
      }
      .mobile-menu-btn {
        display: block !important;
      }
    }
    .mobile-menu-btn {
      display: none;
      background: transparent;
      border: none;
      color: #fff;
      cursor: pointer;
    }
  </style>
  @yield('styles')
</head>
<body>

  <!-- Sidebar -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="brand-area">
      <div class="brand-logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        </svg>
      </div>
      <div>
        <div class="brand-text">
          PriceMonitor
          <span class="admin-badge">ADMIN</span>
        </div>
      </div>
    </div>

    <ul class="nav-list">
      <div class="nav-label">Core Management</div>
      <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a href="{{ route('admin.dashboard') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Dashboard Overview
        </a>
      </li>
      <li class="nav-item {{ request()->routeIs('admin.groups.*') ? 'active' : '' }}">
        <a href="{{ route('admin.groups.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
          Telegram Groups
        </a>
      </li>
      <li class="nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <a href="{{ route('admin.products.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Products & Offers
        </a>
      </li>

      <div class="nav-label">Users & Monetization</div>
      <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <a href="{{ route('admin.users.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          User Accounts
        </a>
      </li>
      <li class="nav-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
        <a href="{{ route('admin.subscriptions.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Subscriptions & Plans
        </a>
      </li>

      <div class="nav-label">System & Security</div>
      <li class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <a href="{{ route('admin.settings.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
          System Settings
        </a>
      </li>
      <li class="nav-item {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
        <a href="{{ route('admin.activity-logs.index') }}">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Audit Activity Logs
        </a>
      </li>

      <div class="nav-label">Navigation</div>
      <li class="nav-item">
        <a href="{{ route('board') }}" target="_blank">
          <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          View Public Board ↗
        </a>
      </li>
    </ul>

    <div class="user-footer">
      <div class="admin-user-info">
        <div class="admin-avatar">
          {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </div>
        <div class="admin-meta">
          <div class="admin-name">{{ auth()->user()->name }}</div>
          <div class="admin-role">Administrator</div>
        </div>
      </div>
      <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="btn btn-secondary btn-sm" title="Log Out">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </button>
      </form>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="admin-main">
    <header class="admin-header">
      <div style="display: flex; align-items: center; gap: 16px;">
        <button class="mobile-menu-btn" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
          <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="header-title-wrap">
          <h1>@yield('title', 'Admin Dashboard')</h1>
        </div>
      </div>

      <div class="header-actions">
        @yield('header-actions')
      </div>
    </header>

    <div class="admin-content">
      @if (session('success'))
        <div class="alert-banner alert-success">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
          <div>{{ session('success') }}</div>
        </div>
      @endif

      @if ($errors->any())
        <div class="alert-banner alert-danger">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
          <div>
            @foreach ($errors->all() as $error)
              <div>{{ $error }}</div>
            @endforeach
          </div>
        </div>
      @endif

      @yield('content')
    </div>
  </div>

  @yield('scripts')
</body>
</html>

