<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Telegram Price Monitor</title>
<link rel="manifest" href="/manifest.json">
<link rel="icon" type="image/svg+xml" href="/icon.svg">
<meta name="theme-color" content="#3b82f6">
<meta name="mobile-web-app-capable" content="yes">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #09090b;
  --surface: #18181b;
  --surface-hover: #27272a;
  --border: rgba(255,255,255,0.08);
  --border-focus: rgba(59, 130, 246, 0.4);
  --accent: #3b82f6;
  --accent-glow: rgba(59, 130, 246, 0.35);
  --text: #fafafa;
  --text-muted: #a1a1aa;
  --radius: 16px;
  --green: #10b981;
  --red: #ef4444;
}
* { margin:0; padding:0; box-sizing:border-box; }
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
  top: -20%;
  left: 50%;
  transform: translateX(-50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}
.auth-card {
  width: 100%;
  max-width: 460px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 40px 36px;
  position: relative;
  z-index: 1;
  box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
.brand-header {
  text-align: center;
  margin-bottom: 28px;
}
.brand-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
  margin-bottom: 16px;
  box-shadow: 0 8px 24px var(--accent-glow);
}
.brand-title {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.5px;
  margin-bottom: 6px;
}
.brand-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  font-family: 'Inter', sans-serif;
}
.alert-error {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-family: 'Inter', sans-serif;
  margin-bottom: 20px;
}
.form-group {
  margin-bottom: 18px;
}
.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  margin-bottom: 8px;
  color: var(--text);
}
.form-input {
  width: 100%;
  background: #121215;
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
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.form-input.is-invalid {
  border-color: var(--red);
}
.error-text {
  font-size: 12px;
  color: var(--red);
  margin-top: 6px;
  font-family: 'Inter', sans-serif;
}
.btn-submit {
  width: 100%;
  padding: 13px;
  margin-top: 10px;
  border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: #ffffff;
  border: none;
  font-size: 15px;
  font-weight: 600;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  box-shadow: 0 4px 16px var(--accent-glow);
  transition: all 0.2s;
}
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(59, 130, 246, 0.5);
}
.auth-footer {
  text-align: center;
  margin-top: 26px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
  font-size: 13px;
  color: var(--text-muted);
  font-family: 'Inter', sans-serif;
}
.auth-footer a {
  color: var(--accent);
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover {
  text-decoration: underline;
}
</style>
</head>
<body>

<div class="auth-card">
  <div class="brand-header">
    <div class="brand-logo">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="8.5" cy="7.5" r="4"></circle>
        <line x1="20" y1="8" x2="20" y2="14"></line>
        <line x1="23" y1="11" x2="17" y2="11"></line>
      </svg>
    </div>
    <h1 class="brand-title">Create Account</h1>
    <p class="brand-subtitle">Start searching & comparing products across channels</p>
  </div>

  @if ($errors->any())
    <div class="alert-error">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="name">Full Name</label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus class="form-input @error('name') is-invalid @enderror" placeholder="Your Name">
      @error('name')
        <div class="error-text">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Email Address</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input @error('email') is-invalid @enderror" placeholder="name@company.com">
      @error('email')
        <div class="error-text">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" name="password" required class="form-input @error('password') is-invalid @enderror" placeholder="At least 8 characters">
      @error('password')
        <div class="error-text">{{ $message }}</div>
      @enderror
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirmation">Confirm Password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Repeat your password">
    </div>

    <button type="submit" class="btn-submit">Complete Registration</button>
  </form>

  <div class="auth-footer">
    Already have an account? <a href="{{ route('login') }}">Sign in instead</a>
  </div>
</div>

</body>
</html>

