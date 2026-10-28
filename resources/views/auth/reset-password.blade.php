<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password — Telegram Price Monitor</title>
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
</style>
</head>
<body>

<div class="auth-card">
  <div class="brand-header">
    <h1 class="brand-title">New Password</h1>
    <p class="brand-subtitle">Enter your new secure password below.</p>
  </div>

  @if ($errors->any())
    <div class="alert-error">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group">
      <label class="form-label" for="email">Email Address</label>
      <input type="email" id="email" name="email" value="{{ $email ?? old('email') }}" required autofocus class="form-input">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">New Password</label>
      <input type="password" id="password" name="password" required class="form-input" placeholder="At least 8 characters">
    </div>

    <div class="form-group">
      <label class="form-label" for="password_confirmation">Confirm New Password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input" placeholder="Repeat new password">
    </div>

    <button type="submit" class="btn-submit">Reset Password</button>
  </form>
</div>

</body>
</html>

