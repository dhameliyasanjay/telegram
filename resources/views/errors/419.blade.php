<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>419 Page Expired — Price Monitor</title>
  <link rel="icon" type="image/svg+xml" href="/icon.svg">
  <meta name="theme-color" content="#3b82f6">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #09090b;
      --surface: #18181b;
      --surface-hover: #27272a;
      --border: rgba(255, 255, 255, 0.08);
      --accent: #3b82f6;
      --accent-glow: rgba(59, 130, 246, 0.35);
      --text: #fafafa;
      --text-muted: #a1a1aa;
      --text-dim: #71717a;
      --amber: #f59e0b;
      --radius: 16px;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Outfit', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .error-card {
      width: 100%;
      max-width: 480px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 40px 32px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
    }
    .icon-box {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.25);
      color: var(--amber);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }
    .error-code {
      font-size: 0.82rem;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.1em;
      color: var(--amber);
      margin-bottom: 6px;
    }
    .error-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 12px;
    }
    .error-desc {
      font-size: 0.9rem;
      color: var(--text-muted);
      line-height: 1.5;
      margin-bottom: 28px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.92rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      width: 100%;
    }
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: #fff;
      border: none;
      box-shadow: 0 4px 14px var(--accent-glow);
      margin-bottom: 12px;
    }
    .btn-primary:hover {
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: var(--surface-hover);
      color: var(--text);
      border: 1px solid var(--border);
    }
    .btn-secondary:hover {
      background: #3f3f46;
    }
  </style>
</head>
<body>

  <div class="error-card">
    <div class="icon-box">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>

    <div class="error-code">419 Page Expired</div>
    <h1 class="error-title">Security Session Expired</h1>
    <p class="error-desc">
      Your page token expired due to inactivity or a new login in another tab. Simply refresh the page or sign in again to continue smoothly.
    </p>

    <button onclick="window.history.back(); setTimeout(()=>window.location.reload(), 150);" class="btn btn-primary">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Refresh & Try Again
    </button>

    <a href="{{ route('admin.login') }}" class="btn btn-secondary">
      Sign In to Admin Panel
    </a>
  </div>

</body>
</html>

