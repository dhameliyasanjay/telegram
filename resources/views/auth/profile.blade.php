<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Settings — Telegram Price Monitor</title>
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
  padding: 40px 20px;
}
.container {
  max-width: 640px;
  margin: 0 auto;
}
.header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
}
.header-title {
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.5px;
}
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  border-radius: 10px;
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}
.btn-back:hover {
  background: var(--surface-hover);
}
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 32px;
  margin-bottom: 24px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}
.card-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 6px;
}
.card-subtitle {
  font-size: 13px;
  color: var(--text-muted);
  font-family: 'Inter', sans-serif;
  margin-bottom: 24px;
}
.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 13px;
  font-family: 'Inter', sans-serif;
  margin-bottom: 20px;
}
.alert-success {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #6ee7b7;
}
.alert-error {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #fca5a5;
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
.form-input.is-invalid {
  border-color: var(--red);
}
.error-text {
  font-size: 12px;
  color: var(--red);
  margin-top: 6px;
  font-family: 'Inter', sans-serif;
}
.btn-primary {
  padding: 11px 22px;
  border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: #ffffff;
  border: none;
  font-size: 14px;
  font-weight: 600;
  font-family: 'Outfit', sans-serif;
  cursor: pointer;
  box-shadow: 0 4px 14px var(--accent-glow);
  transition: all 0.2s;
}
.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(59, 130, 246, 0.5);
}
.section-divider {
  border-top: 1px solid var(--border);
  margin: 28px 0;
  padding-top: 24px;
}
</style>
</head>
<body>

<div class="container">
  <div class="header-bar">
    <h1 class="header-title">Account Settings</h1>
    <a href="{{ route('board') }}" class="btn-back">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M19 12H5M12 19l-7-7 7-7"/>
      </svg>
      Dashboard
    </a>
  </div>

  @if (session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-error">
      {{ $errors->first() }}
    </div>
  @endif

  <!-- Membership & Subscription Card -->
  <div class="card" style="margin-bottom: 24px;">
    <h2 class="card-title">Membership & Subscription</h2>
    <p class="card-subtitle">Your current account plan and price monitor access details.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; background: #121215; border: 1px solid var(--border); border-radius: 12px; padding: 18px 20px;">
      <div>
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; letter-spacing: 0.05em; margin-bottom: 4px;">Account Role</div>
        @if($user->isAdmin())
          <span style="font-size: 14px; font-weight: 700; color: #a78bfa; background: rgba(139,92,246,0.12); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(139,92,246,0.25);">Administrator</span>
        @else
          <span style="font-size: 14px; font-weight: 600; color: #fff;">Standard User</span>
        @endif
      </div>

      <div>
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; letter-spacing: 0.05em; margin-bottom: 4px;">Subscription Status</div>
        @if($user->isAdmin())
          <span style="font-size: 14px; font-weight: 600; color: #a78bfa;">Lifetime Admin Access</span>
        @elseif($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
          <span style="font-size: 14px; font-weight: 700; color: #34d399; background: rgba(16,185,129,0.12); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(16,185,129,0.25);">Pro Plan Active</span>
        @elseif($user->trial_ends_at && $user->trial_ends_at->isFuture())
          <span style="font-size: 14px; font-weight: 700; color: #fbbf24; background: rgba(245,158,11,0.12); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(245,158,11,0.25);">30-Day Free Trial</span>
        @else
          <span style="font-size: 14px; font-weight: 700; color: #f87171; background: rgba(239,68,68,0.12); padding: 3px 10px; border-radius: 6px; border: 1px solid rgba(239,68,68,0.25);">Expired</span>
        @endif
      </div>

      <div>
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; letter-spacing: 0.05em; margin-bottom: 4px;">Valid Until</div>
        <div style="font-size: 14px; font-weight: 600; color: #fff;">
          @if($user->isAdmin())
            Unlimited
          @elseif($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
            {{ $user->subscription_ends_at->format('M d, Y') }}
          @elseif($user->trial_ends_at && $user->trial_ends_at->isFuture())
            {{ $user->trial_ends_at->format('M d, Y') }} ({{ (int) ceil(max(0, now()->diffInDays($user->trial_ends_at))) }} days left)
          @else
            Access Expired
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <form method="POST" action="{{ route('profile.update') }}">
      @csrf
      @method('PUT')

      <h2 class="card-title">Profile Information</h2>
      <p class="card-subtitle">Update your personal account credentials.</p>

      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input @error('name') is-invalid @enderror">
        @error('name')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-input @error('email') is-invalid @enderror">
        @error('email')
          <div class="error-text">{{ $message }}</div>
        @enderror
      </div>

      <div class="section-divider">
        <h2 class="card-title">Change Password</h2>
        <p class="card-subtitle">Leave blank if you do not wish to change your password.</p>

        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-input @error('current_password') is-invalid @enderror" placeholder="••••••••">
          @error('current_password')
            <div class="error-text">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" class="form-input @error('new_password') is-invalid @enderror" placeholder="••••••••">
          @error('new_password')
            <div class="error-text">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
          <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-input" placeholder="••••••••">
        </div>
      </div>

      <button type="submit" class="btn-primary">Save Changes</button>
    </form>
  </div>
</div>

</body>
</html>

