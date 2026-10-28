<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — Telegram Price Monitor</title>
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
}
.auth-card {
  width: 100%;
  max-width: 440px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 40px 36px;
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
  line-height: 1.5;
}
.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-family: 'Inter', sans-serif;
  margin-bottom: 20px;
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
  margin-bottom: 20px;
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
}
.form-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.btn-submit {
  width: 100%;
  padding: 13px;
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
</style>
</head>
<body>

<div class="auth-card">
  <div class="brand-header">
    <div class="brand-logo">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
      </svg>
    </div>
    <h1 class="brand-title">Reset Password</h1>
    <p class="brand-subtitle">Enter your account email and we'll send you instructions to reset your password.</p>
  </div>

  @if (session('status'))
    <div class="alert alert-success">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="form-group">
      <label class="form-label" for="email">Email Address</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="form-input" placeholder="name@company.com">
    </div>

    <button type="submit" class="btn-submit">Send Password Reset Link</button>
  </form>

  <div class="auth-footer">
    Remembered your password? <a href="{{ route('login') }}">Back to sign in</a>
  </div>
</div>

</body>
</html>

