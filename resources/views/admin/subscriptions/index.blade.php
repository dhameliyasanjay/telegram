@extends('admin.layouts.admin')

@section('title', 'Subscriptions & Pricing Plan')

@section('content')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px;">
  <div class="card" style="margin-bottom: 0; padding: 20px;">
    <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 8px;">Paid Subscribers</div>
    <div style="font-size: 1.8rem; font-weight: 700; color: var(--green);">{{ number_format($activePaidCount) }}</div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Active paid recurring accounts</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px;">
    <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 8px;">Active Trials</div>
    <div style="font-size: 1.8rem; font-weight: 700; color: var(--amber);">{{ number_format($activeTrialCount) }}</div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Users within {{ $trialDays }}-day trial</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px;">
    <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 8px;">Expired Users</div>
    <div style="font-size: 1.8rem; font-weight: 700; color: var(--red);">{{ number_format($expiredCount) }}</div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Trial or subscription ended</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px;">
    <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 8px;">Current Plan Price</div>
    <div style="font-size: 1.8rem; font-weight: 700; color: #60a5fa;">{{ $currencySymbol }}{{ number_format($planPrice, 2) }}</div>
    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Valid for {{ $planDuration }} days</div>
  </div>
</div>

<!-- Plan Configuration Box -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Configure Master Subscription Plan
    </h3>
  </div>

  <form action="{{ route('admin.subscriptions.plan') }}" method="POST">
    @csrf
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
      <div class="form-group">
        <label class="form-label">Free Trial Period (Days)</label>
        <input type="number" name="trial_days" class="form-input" value="{{ $trialDays }}" min="0" max="365" required>
        <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Granted automatically upon registration.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Subscription Price</label>
        <div style="position: relative;">
          <input type="number" step="0.01" name="subscription_price" class="form-input" value="{{ $planPrice }}" min="0" required>
        </div>
        <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Monthly subscription fee.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Plan Duration (Days)</label>
        <input type="number" name="subscription_duration_days" class="form-input" value="{{ $planDuration }}" min="1" max="365" required>
        <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Duration granted per subscription payment.</div>
      </div>

      <div class="form-group">
        <label class="form-label">Currency Symbol</label>
        <input type="text" name="currency_symbol" class="form-input" value="{{ $currencySymbol }}" maxlength="5" required>
        <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">Symbol displayed across UI.</div>
      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
      <button type="submit" class="btn btn-primary">Save Plan Settings</button>
    </div>
  </form>
</div>

<!-- Subscribers List -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <h3 style="font-size: 1.1rem; color: #fff; font-weight: 600;">Subscribers & Accounts</h3>

    <form action="{{ route('admin.subscriptions.index') }}" method="GET" style="display: flex; gap: 10px; align-items: center;">
      <select name="status" class="form-select" style="width: 140px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid Active</option>
        <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Free Trial</option>
        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
      </select>
      <input type="text" name="search" class="form-input" style="width: 200px;" placeholder="Search user..." value="{{ request('search') }}">
      <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
  </div>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Plan Type</th>
          <th>Trial Ends</th>
          <th>Subscription Ends</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @forelse($subscribers as $sub)
          <tr>
            <td>
              <div style="font-weight: 600; color: #fff;">{{ $sub->name }}</div>
              <div style="font-size: 0.78rem; color: var(--text-dim);">{{ $sub->email }}</div>
            </td>
            <td>
              @if($sub->isAdmin())
                <span class="badge badge-purple">Admin (Lifetime)</span>
              @elseif($sub->subscription_ends_at && $sub->subscription_ends_at->isFuture())
                <span class="badge badge-success">Paid Plan (₹{{ number_format($planPrice, 0) }})</span>
              @elseif($sub->trial_ends_at && $sub->trial_ends_at->isFuture())
                <span class="badge badge-warning">Free Trial</span>
              @else
                <span class="badge badge-danger">Expired</span>
              @endif
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">
              {{ $sub->trial_ends_at ? $sub->trial_ends_at->format('M d, Y') : '—' }}
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">
              {{ $sub->subscription_ends_at ? $sub->subscription_ends_at->format('M d, Y') : '—' }}
            </td>
            <td style="font-size: 0.82rem; color: var(--text-dim);">
              {{ $sub->created_at ? $sub->created_at->format('M d, Y') : '—' }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" style="text-align: center; color: var(--text-dim); padding: 36px;">
              No subscribers match this criteria.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($subscribers->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $subscribers->links() }}
    </div>
  @endif
</div>
@endsection
