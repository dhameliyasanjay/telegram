<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Price Monitor Control Panel</title>
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<meta name="theme-color" content="#8b5cf6">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #09090b;
  --surface: #141417;
  --surface-hover: #1e1e24;
  --border: rgba(255, 255, 255, 0.08);
  --border-focus: rgba(139, 92, 246, 0.5);
  --accent: #8b5cf6;
  --accent-glow: rgba(139, 92, 246, 0.35);
  --text: #fafafa;
  --text-muted: #a1a1aa;
  --text-dim: #71717a;
  --radius: 16px;
  --red: #ef4444;
  --green: #10b981;
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
  position: relative;
  overflow-x: hidden;
}
body::before {
  content: '';
  position: fixed;
  top: -15%;
  left: 50%;
  transform: translateX(-50%);
  width: 650px;
  height: 650px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}
.auth-card {
  width: 100%;
  max-width: 440px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 40px 36px;
  position: relative;
  z-index: 1;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
}
.brand-header {
  text-align: center;
  margin-bottom: 28px;
}
.admin-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.2px;
  background: rgba(139, 92, 246, 0.15);
  color: #a78bfa;
  border: 1px solid rgba(139, 92, 246, 0.3);
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 12px;
}
.brand-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 8px 24px var(--accent-glow);
}
.brand-title {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  color: #fff;
}
.brand-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 6px;
  font-family: 'Inter', sans-serif;
}
.alert {
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 13px;
  font-family: 'Inter', sans-serif;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.alert-error {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
}
.alert-success {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #6ee7b7;
}
.form-group {
  margin-bottom: 18px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 6px;
  color: var(--text);
}
.form-input {
  width: 100%;
  background: #0d0d0f;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 14px;
  color: var(--text);
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  transition: all 0.2s;
}
.form-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.25);
}
.btn-submit {
  width: 100%;
  padding: 13px 20px;
  border-radius: 10px;
  background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
  color: #ffffff;
  border: none;
  font-size: 15px;
  font-weight: 600;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  box-shadow: 0 4px 16px var(--accent-glow);
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 10px;
}
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(139, 92, 246, 0.5);
}
.security-footer {
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
  text-align: center;
  font-size: 12px;
  color: var(--text-dim);
  font-family: 'Inter', sans-serif;
}
.switch-portal-link {
  color: #60a5fa;
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 12px;
}
.switch-portal-link:hover {
  text-decoration: underline;
}
</style>
</head>
<body>

<div class="auth-card">
  <div class="brand-header">
    <div class="brand-icon">
      <svg width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" viewBox="0 0 24 24">
        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>
    <div class="admin-badge">
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      Administrator Portal
    </div>
    <h1 class="brand-title">Control Panel Login</h1>
    <p class="brand-subtitle">Restricted access for system administrators</p>
  </div>

  @if (session('status'))
    <div class="alert alert-success">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error">
      <div>{{ $errors->first() }}</div>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.login') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="email">Administrator Email</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="form-input" placeholder="admin@example.com">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" name="password" required class="form-input" placeholder="••••••••">
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; font-size: 13px; color: var(--text-muted); font-family: 'Inter', sans-serif;">
      <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
        <input type="checkbox" name="remember" style="accent-color: var(--accent);">
        <span>Remember this device</span>
      </label>
    </div>

    <button type="submit" class="btn-submit">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
      Sign In to Admin Panel
    </button>
  </form>

  <div class="security-footer">
    <div>
      <a href="{{ route('login') }}" class="switch-portal-link">
        Looking for Customer Login? Go to User Portal →
      </a>
    </div>
    <div style="margin-top: 6px; font-size: 11px;">
      🔒 Authorized Personnel Only. All access is audited.
    </div>
  </div>
</div>

</body>
</html>

