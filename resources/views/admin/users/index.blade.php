@extends('admin.layouts.admin')

@section('title', 'User Accounts & Roles')

@section('content')
<!-- Search & Filter Bar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 24px;">
  <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div style="flex: 1; min-width: 280px; max-width: 450px;">
      <input type="text" name="search" class="form-input" placeholder="Search by name or email address..." value="{{ request('search') }}">
    </div>

    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <select name="role" class="form-select" style="width: 130px;" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
      </select>

      <select name="status" class="form-select" style="width: 140px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Suspended</option>
      </select>

      <select name="subscription_status" class="form-select" style="width: 160px;" onchange="this.form.submit()">
        <option value="">All Subscriptions</option>
        <option value="trial" {{ request('subscription_status') === 'trial' ? 'selected' : '' }}>Free Trial</option>
        <option value="active" {{ request('subscription_status') === 'active' ? 'selected' : '' }}>Active Paid</option>
        <option value="expired" {{ request('subscription_status') === 'expired' ? 'selected' : '' }}>Expired</option>
      </select>

      <button type="submit" class="btn btn-secondary">Filter</button>
      @if(request()->anyFilled(['search', 'role', 'status', 'subscription_status']))
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" title="Reset Filters">✕</a>
      @endif
    </div>
  </form>
</div>

<!-- Users Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Account Status</th>
          <th>Subscription / Access</th>
          <th>Expiry Date</th>
          <th>Registered</th>
          <th style="text-align: right; padding-right: 24px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #27272a; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--accent); font-size: 0.85rem;">
                  {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                  <div style="font-weight: 600; color: #fff;">{{ $user->name }}</div>
                  <div style="font-size: 0.78rem; color: var(--text-dim);">{{ $user->email }}</div>
                </div>
              </div>
            </td>
            <td>
              @if($user->isAdmin())
                <span class="badge badge-purple">Admin</span>
              @else
                <span class="badge badge-muted">User</span>
              @endif
            </td>
            <td>
              @if($user->isBlocked())
                <span class="badge badge-danger">Suspended</span>
              @else
                <span class="badge badge-success">Active</span>
              @endif
            </td>
            <td>
              @if($user->isAdmin())
                <span style="color: #a78bfa; font-size: 0.85rem; font-weight: 500;">Admin Access</span>
              @elseif($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
                <span class="badge badge-success">Paid Subscriber</span>
              @elseif($user->trial_ends_at && $user->trial_ends_at->isFuture())
                <span class="badge badge-warning">Trial ({{ now()->diffInDays($user->trial_ends_at) }}d left)</span>
              @else
                <span class="badge badge-danger">Expired</span>
              @endif
            </td>
            <td style="font-size: 0.82rem; color: var(--text-muted);">
              @if($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
                {{ $user->subscription_ends_at->format('M d, Y') }}
              @elseif($user->trial_ends_at && $user->trial_ends_at->isFuture())
                {{ $user->trial_ends_at->format('M d, Y') }}
              @elseif($user->isAdmin())
                Unlimited
              @else
                Ended
              @endif
            </td>
            <td style="font-size: 0.82rem; color: var(--text-dim);">
              {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
            </td>
            <td style="text-align: right; padding-right: 24px;">
              <div style="display: inline-flex; align-items: center; gap: 6px;">
                <!-- Subscription Adjust Button -->
                <button type="button" class="btn btn-secondary btn-sm" onclick="openSubscriptionModal({{ $user->id }}, '{{ addslashes($user->name) }}')">
                  Subscription
                </button>

                <!-- Role Adjust Button -->
                @if($user->id !== auth()->id())
                  <button type="button" class="btn btn-secondary btn-sm" onclick="openRoleModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->role }}')">
                    Role
                  </button>

                  <!-- Block / Unblock Toggle -->
                  <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn {{ $user->isBlocked() ? 'btn-secondary' : 'btn-danger' }} btn-sm">
                      {{ $user->isBlocked() ? 'Unblock' : 'Suspend' }}
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 40px;">
              No users found matching your search criteria.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($users->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $users->links() }}
    </div>
  @endif
</div>

<!-- Modal: Adjust Subscription -->
<div id="subModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 480px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Manage User Subscription</h3>
      <button type="button" onclick="closeSubscriptionModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <form id="subForm" method="POST">
      @csrf
      <p id="subModalUserName" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;"></p>

      <div class="form-group">
        <label class="form-label">Subscription Action</label>
        <select name="action" class="form-select" required>
          <option value="grant_subscription">Grant / Extend Paid Subscription</option>
          <option value="extend_trial">Grant / Extend Free Trial</option>
          <option value="expire_access">Expire Access Immediately</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Days to Add</label>
        <input type="number" name="days" class="form-input" value="30" min="1" max="365">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" class="btn btn-secondary" onclick="closeSubscriptionModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Apply Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Change Role -->
<div id="roleModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 440px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Assign User Role</h3>
      <button type="button" onclick="closeRoleModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <form id="roleForm" method="POST">
      @csrf
      <p id="roleModalUserName" style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px;"></p>

      <div class="form-group">
        <label class="form-label">Select Role (Strictly Two Roles Allowed)</label>
        <select id="roleSelect" name="role" class="form-select" required>
          <option value="user">User (Standard Access)</option>
          <option value="admin">Admin (Full Administrative Access)</option>
        </select>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Role</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function openSubscriptionModal(id, name) {
    document.getElementById('subForm').action = `/admin/users/${id}/subscription`;
    document.getElementById('subModalUserName').textContent = `Updating account access for: ${name}`;
    document.getElementById('subModal').style.display = 'flex';
  }
  function closeSubscriptionModal() {
    document.getElementById('subModal').style.display = 'none';
  }
  function openRoleModal(id, name, currentRole) {
    document.getElementById('roleForm').action = `/admin/users/${id}/role`;
    document.getElementById('roleModalUserName').textContent = `Changing role for: ${name}`;
    document.getElementById('roleSelect').value = currentRole;
    document.getElementById('roleModal').style.display = 'flex';
  }
  function closeRoleModal() {
    document.getElementById('roleModal').style.display = 'none';
  }
</script>
@endsection
