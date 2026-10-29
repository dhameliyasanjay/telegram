<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden — Administrator Privileges Required</title>
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
      --red: #ef4444;
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
      max-width: 500px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 40px 32px;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
      position: relative;
    }
    .shield-icon {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid rgba(239, 68, 68, 0.25);
      color: var(--red);
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
      color: var(--red);
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
      margin-bottom: 24px;
    }
    .account-box {
      background: #121215;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      text-align: left;
      font-size: 0.85rem;
    }
    .account-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
    }
    .account-row:last-child {
      margin-bottom: 0;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 11px 20px;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
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
      margin-bottom: 10px;
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
    .badge-user {
      background: rgba(255, 255, 255, 0.08);
      color: #fafafa;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <div class="error-card">
    <div class="shield-icon">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>

    <div class="error-code">403 Access Forbidden</div>
    <h1 class="error-title">Administrator Privileges Required</h1>
    <p class="error-desc">
      The page you attempted to access is restricted exclusively to Administrators. Regular customer accounts cannot access system administration.
    </p>

    <div class="account-box">
      <div class="account-row">
        <span style="color: var(--text-dim);">Currently Logged In:</span>
        <strong style="color: #fff;">{{ Auth::user()?->email ?? 'Guest' }}</strong>
      </div>
      <div class="account-row">
        <span style="color: var(--text-dim);">Your Account Role:</span>
        <span class="badge-user">{{ strtoupper(Auth::user()?->role ?? 'USER') }}</span>
      </div>
    </div>

    <a href="{{ route('admin.login') }}" class="btn btn-primary">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Sign In to Admin Portal
    </a>

    <a href="{{ route('board') }}" class="btn btn-secondary">
      Return to Customer Product Dashboard
    </a>
  </div>

</body>
</html>

