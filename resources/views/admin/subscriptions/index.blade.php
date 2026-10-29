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

<!-- Customer Subscription Orders & Direct WhatsApp / Phone Contact -->
<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 28px;">
  <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div>
      <h3 style="font-size: 1.15rem; color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px;">
        <svg width="20" height="20" fill="none" stroke="#3b82f6" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Customer Subscription Orders & Contact Requests
      </h3>
      <p style="font-size: 0.8rem; color: var(--text-dim); margin: 4px 0 0 0;">
        Direct customer leads who requested Pro access (₹49). Contact them via WhatsApp or call to confirm and click Activate.
      </p>
    </div>
    @php
      $pendingCount = $recentOrders->where('status', 'pending')->count();
    @endphp
    @if($pendingCount > 0)
      <span class="badge badge-warning" style="font-size: 0.85rem; padding: 6px 12px;">{{ $pendingCount }} Pending Verification</span>
    @endif
  </div>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Order # / Time</th>
          <th>Customer Details</th>
          <th>Mobile / WhatsApp</th>
          <th>City / Market</th>
          <th>Plan & Amount</th>
          <th>Status</th>
          <th style="text-align: right; padding-right: 24px;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentOrders as $order)
          @php
            $cleanPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
            if (strlen($cleanPhone) === 10) {
                $waPhone = '91' . $cleanPhone;
            } else {
                $waPhone = $cleanPhone;
            }
          @endphp
          <tr>
            <td>
              <div style="font-weight: 600; color: #fff;">#ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</div>
              <div style="font-size: 0.78rem; color: var(--text-dim);">{{ $order->created_at->diffForHumans() }}</div>
            </td>
            <td>
              <div style="font-weight: 600; color: #fff;">{{ $order->customer_name }}</div>
              <div style="font-size: 0.78rem; color: var(--text-dim);">{{ $order->customer_email }}</div>
            </td>
            <td>
              <div style="display: flex; align-items: center; gap: 8px;">
                <a href="tel:{{ $order->customer_phone }}" style="font-weight: 600; color: #60a5fa; text-decoration: none; font-size: 0.9rem;">
                  {{ $order->customer_phone }}
                </a>
                <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('Hello ' . $order->customer_name . ', this is Sanjay regarding your Price Monitor Pro subscription request.') }}" target="_blank" rel="noopener noreferrer" class="badge badge-success" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; font-size: 0.75rem;" title="Chat on WhatsApp">
                  💬 WhatsApp
                </a>
              </div>
            </td>
            <td>
              <span style="color: var(--text-muted); font-size: 0.85rem;">{{ $order->customer_city ?: '—' }}</span>
            </td>
            <td>
              <div style="font-weight: 600; color: #34d399;">₹{{ number_format($order->amount, 2) }}</div>
              <div style="font-size: 0.75rem; color: var(--text-dim);">{{ $order->plan_duration_days }} Days Pro</div>
            </td>
            <td>
              @if($order->status === 'active')
                <span class="badge badge-success">Active Pro</span>
              @elseif($order->status === 'pending')
                <span class="badge badge-warning">Pending Payment</span>
              @else
                <span class="badge badge-muted">{{ ucfirst($order->status) }}</span>
              @endif
            </td>
            <td style="text-align: right; padding-right: 24px;">
              @if($order->status === 'pending')
                <form action="{{ route('admin.subscriptions.orders.activate', $order->id) }}" method="POST" style="display: inline-block;">
                  @csrf
                  <button type="submit" class="btn btn-primary btn-sm" style="background: #10b981; border-color: #10b981; font-weight: 600;" onclick="return confirm('Activate 30-day Pro plan for {{ addslashes($order->customer_name) }} ({{ $order->customer_phone }})?')">
                    ✓ Activate 30d Pro
                  </button>
                </form>
              @else
                <span style="font-size: 0.8rem; color: var(--text-dim);">
                  Activated {{ $order->activated_at ? $order->activated_at->format('M d, H:i') : '' }}
                </span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 32px;">
              No subscription customer orders placed yet.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
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
              @if($sub->phone)
                <div style="font-size: 0.75rem; color: #34d399; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                  <a href="tel:{{ $sub->phone }}" style="color: #34d399; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    {{ $sub->phone }}
                  </a>
                </div>
              @endif
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

