@extends('admin.layouts.admin')

@section('title', 'System Dashboard Overview')

@section('header-actions')
  <form action="{{ route('admin.groups.sync-all') }}" method="POST" style="margin:0;">
    @csrf
    <button type="submit" class="btn btn-primary" onclick="return confirm('Trigger background synchronization across all active Telegram groups?')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Sync All Groups
    </button>
  </form>
@endsection

@section('content')
<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
  }
  .stat-card-admin {
    background: var(--surface-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 22px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }
  .stat-card-admin::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--card-accent, var(--accent));
  }
  .stat-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
  }
  .stat-title {
    font-size: 0.82rem;
    color: var(--text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.04);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--card-accent, var(--accent));
  }
  .stat-num {
    font-size: 1.85rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
  }
  .stat-sub {
    font-size: 0.78rem;
    color: var(--text-dim);
    margin-top: 8px;
  }

  .overview-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
  }
  @media (max-width: 1080px) {
    .overview-grid {
      grid-template-columns: 1fr;
    }
  }

  .quick-links-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 14px;
  }
  .quick-link-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border);
    padding: 14px 16px;
    border-radius: 10px;
    text-decoration: none;
    color: var(--text);
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.15s ease;
  }
  .quick-link-btn:hover {
    background: var(--surface-hover);
    border-color: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
  }
  .quick-link-btn svg {
    color: var(--accent);
  }
</style>

<!-- 5 REQUIRED STATISTICS CARDS (Real Database Records) -->
<div class="stats-grid">
  <!-- Card 1: Monitored Groups -->
  <div class="stat-card-admin" style="--card-accent: #3b82f6;">
    <div class="stat-meta">
      <span class="stat-title">Monitored Groups</span>
      <div class="stat-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
    </div>
    <div class="stat-num">{{ number_format($stats['monitored_groups']) }}</div>
    <div class="stat-sub">{{ $stats['total_groups'] }} total connected channels</div>
  </div>

  <!-- Card 2: Canonical Products -->
  <div class="stat-card-admin" style="--card-accent: #10b981;">
    <div class="stat-meta">
      <span class="stat-title">Canonical Products</span>
      <div class="stat-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      </div>
    </div>
    <div class="stat-num">{{ number_format($stats['canonical_products']) }}</div>
    <div class="stat-sub">Deduplicated master items</div>
  </div>

  <!-- Card 3: Multi-Group Matches -->
  <div class="stat-card-admin" style="--card-accent: #8b5cf6;">
    <div class="stat-meta">
      <span class="stat-title">Multi-Group Matches</span>
      <div class="stat-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      </div>
    </div>
    <div class="stat-num" style="color: #a78bfa;">{{ number_format($stats['multi_group_matches']) }}</div>
    <div class="stat-sub">Found across 2+ channels</div>
  </div>

  <!-- Card 4: Total Group Offers -->
  <div class="stat-card-admin" style="--card-accent: #f59e0b;">
    <div class="stat-meta">
      <span class="stat-title">Total Group Offers</span>
      <div class="stat-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
      </div>
    </div>
    <div class="stat-num">{{ number_format($stats['total_offers']) }}</div>
    <div class="stat-sub">Observed listings across groups</div>
  </div>

  <!-- Card 5: Parsed Prices -->
  <div class="stat-card-admin" style="--card-accent: #06b6d4;">
    <div class="stat-meta">
      <span class="stat-title">Parsed Prices</span>
      <div class="stat-icon">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
      </div>
    </div>
    <div class="stat-num">{{ number_format($stats['parsed_prices']) }}</div>
    <div class="stat-sub">Cleanly extracted numeric prices</div>
  </div>
</div>

<!-- Supplementary System Status Bar -->
<div class="card" style="padding: 16px 24px; background: rgba(255, 255, 255, 0.02); margin-bottom: 28px;">
  <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px;">
    <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
      <div>
        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Total Users:</span>
        <strong style="margin-left: 6px; font-size: 0.95rem; color: #fff;">{{ $stats['total_users'] }}</strong>
      </div>
      <div>
        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Active Subscriptions/Trials:</span>
        <span class="badge badge-success" style="margin-left: 6px;">{{ $stats['active_subscribers'] }}</span>
      </div>
      <div>
        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Suspended Users:</span>
        <span class="badge {{ $stats['blocked_users'] > 0 ? 'badge-danger' : 'badge-muted' }}" style="margin-left: 6px;">{{ $stats['blocked_users'] }}</span>
      </div>
      <div>
        <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Background Queue:</span>
        <span class="badge {{ $stats['failed_jobs'] > 0 ? 'badge-danger' : 'badge-success' }}" style="margin-left: 6px;">
          {{ $stats['pending_jobs'] }} pending / {{ $stats['failed_jobs'] }} failed
        </span>
      </div>
    </div>
    <div>
      <span class="badge badge-purple" style="font-size: 0.8rem; padding: 5px 12px;">
        <span style="width: 7px; height: 7px; border-radius: 50%; background: #a78bfa; display: inline-block;"></span>
        Automated Hourly Sync: Active
      </span>
    </div>
  </div>
</div>

<!-- Main Split: Groups & Activity -->
<div class="overview-grid">
  <!-- Left Column: Monitored Groups Status & Recent Products -->
  <div>
    <!-- Telegram Groups -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
          Telegram Groups Status
        </h2>
        <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary btn-sm">View All Groups ({{ $stats['total_groups'] }})</a>
      </div>

      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Channel Name</th>
              <th>Status</th>
              <th>Offers</th>
              <th>Last Synced</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($activeGroups as $group)
              <tr>
                <td>
                  <div style="font-weight: 600; color: #fff;">{{ $group->title }}</div>
                  <div style="font-size: 0.75rem; color: var(--text-dim);">ID: {{ $group->telegram_id }}</div>
                </td>
                <td>
                  @if(!$group->is_active)
                    <span class="badge badge-muted">Paused</span>
                  @elseif($group->sync_status === 'syncing')
                    <span class="badge badge-warning">Syncing...</span>
                  @elseif($group->sync_status === 'failed')
                    <span class="badge badge-danger">Failed</span>
                  @else
                    <span class="badge badge-success">Active</span>
                  @endif
                </td>
                <td><strong>{{ number_format($group->products_count) }}</strong></td>
                <td>
                  <div style="font-size: 0.82rem; color: var(--text-muted);">
                    {{ $group->last_synced_at ? $group->last_synced_at->diffForHumans() : 'Never' }}
                  </div>
                </td>
                <td style="text-align: right;">
                  <form action="{{ route('admin.groups.sync', $group->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" title="Sync this group">
                      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                      Sync
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align: center; color: var(--text-dim); padding: 24px;">No groups added yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Imported Products -->
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Recently Processed Products
        </h2>
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">Inspect Catalog</a>
      </div>

      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Product Name</th>
              <th>Group</th>
              <th>Price</th>
              <th>Last Observed</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentProducts as $prod)
              <tr>
                <td>
                  <div style="font-weight: 600; color: #fff; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $prod->product_name }}
                  </div>
                  @if($prod->canonicalProduct)
                    <div style="font-size: 0.72rem; color: #60a5fa;">
                      Matched to: {{ $prod->canonicalProduct->name }} ({{ $prod->canonicalProduct->offers_count }} offers)
                    </div>
                  @endif
                </td>
                <td>
                  <span class="badge badge-muted" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $prod->group?->title ?? 'Direct' }}
                  </span>
                </td>
                <td>
                  @if($prod->latest_price && $prod->latest_price > 0)
                    <strong style="color: var(--green);">₹{{ number_format($prod->latest_price, 2) }}</strong>
                  @else
                    <span style="color: var(--text-dim); font-size: 0.8rem;">Unpriced</span>
                  @endif
                </td>
                <td style="font-size: 0.8rem; color: var(--text-dim);">
                  {{ $prod->last_updated_at ? $prod->last_updated_at->diffForHumans() : '—' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" style="text-align: center; color: var(--text-dim); padding: 24px;">No products imported yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Column: Quick Links & Recent Administrative Logs -->
  <div>
    <!-- Quick Administrative Actions -->
    <div class="card">
      <h2 class="card-title" style="margin-bottom: 12px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Quick Shortcuts
      </h2>
      <div class="quick-links-grid">
        <a href="{{ route('admin.groups.index') }}" class="quick-link-btn">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
          Add Channel
        </a>
        <a href="{{ route('admin.users.index') }}" class="quick-link-btn">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          Manage Users
        </a>
        <a href="{{ route('admin.subscriptions.index') }}" class="quick-link-btn">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2"/></svg>
          Plan Pricing
        </a>
        <a href="{{ route('admin.settings.index') }}" class="quick-link-btn">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
          Settings
        </a>
      </div>
    </div>

    <!-- Recent Price Changes -->
    <div class="card">
      <div class="card-header" style="margin-bottom: 12px;">
        <h2 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          Recent Price Changes
        </h2>
      </div>

      <div style="display: flex; flex-direction: column; gap: 12px;">
        @forelse($recentPriceChanges as $change)
          <div style="padding: 10px 12px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); border-radius: 8px;">
            <div style="font-size: 0.85rem; font-weight: 600; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $change->product?->product_name ?? 'Product' }}
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px;">
              <span style="font-size: 0.88rem; font-weight: 700; color: var(--green);">₹{{ number_format($change->price, 2) }}</span>
              <span style="font-size: 0.72rem; color: var(--text-dim);">{{ $change->recorded_at?->diffForHumans() }}</span>
            </div>
          </div>
        @empty
          <div style="font-size: 0.85rem; color: var(--text-dim); text-align: center; padding: 16px;">No price changes recorded yet.</div>
        @endforelse
      </div>
    </div>

    <!-- Recent Admin Activity -->
    <div class="card">
      <div class="card-header" style="margin-bottom: 12px;">
        <h2 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Recent Audit Trail
        </h2>
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-secondary btn-sm">Logs</a>
      </div>

      <div style="display: flex; flex-direction: column; gap: 10px;">
        @forelse($recentLogs as $log)
          <div style="font-size: 0.82rem; border-left: 2px solid var(--accent); padding-left: 10px; margin-bottom: 4px;">
            <div style="color: #fff; font-weight: 500;">{{ $log->description ?: $log->action }}</div>
            <div style="color: var(--text-dim); font-size: 0.72rem; margin-top: 2px;">
              By <strong>{{ $log->admin_name }}</strong> &bull; {{ $log->created_at->diffForHumans() }}
            </div>
          </div>
        @empty
          <div style="font-size: 0.85rem; color: var(--text-dim); text-align: center; padding: 16px;">No admin activity recorded yet.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Intercept Sync All Groups form to use AJAX with 200 OK
    const syncAllForm = document.querySelector('form[action="{{ route('admin.groups.sync-all') }}"]');
    if (syncAllForm) {
        syncAllForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Trigger background synchronization across all active Telegram groups?')) {
                return;
            }
            const btn = this.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="spin-anim" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Syncing All Groups...`;

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.querySelector('input[name="_token"]').value
                }
            })
            .then(res => res.json())
            .then(data => {
                if (window.showAdminToast) {
                    window.showAdminToast(data.message || 'Batch sync started successfully in background!', 'success');
                }
                setTimeout(() => window.location.reload(), 1200);
            })
            .catch(err => {
                if (window.showAdminToast) {
                    window.showAdminToast('Sync request error: ' + err.message, 'error');
                }
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    }

    // Intercept individual row sync buttons to use AJAX with 200 OK
    document.querySelectorAll('form[action*="/telegram-groups/"][action$="/sync"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<svg class="spin-anim" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Syncing...`;

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.querySelector('input[name="_token"]').value
                }
            })
            .then(res => res.json())
            .then(data => {
                if (window.showAdminToast) {
                    window.showAdminToast(data.message || 'Group sync queued in background!', 'success');
                }
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(err => {
                if (window.showAdminToast) {
                    window.showAdminToast('Error: ' + err.message, 'error');
                }
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    });
});
</script>
@endsection

