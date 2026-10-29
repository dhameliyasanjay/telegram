@extends('admin.layouts.admin')

@section('title', 'Telegram Group Management')

@section('header-actions')
  <button class="btn btn-primary" onclick="openAddGroupModal()">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
    Connect Group / Channel
  </button>
@endsection

@section('content')
<!-- Filter & Search Toolbar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
  <form action="{{ route('admin.groups.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 12px; flex: 1; min-width: 280px; max-width: 500px;">
      <input type="text" name="search" class="form-input" placeholder="Search by channel title, invite link, or ID..." value="{{ request('search') }}">
    </div>

    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <select name="status" class="form-select" style="width: 140px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Monitoring)</option>
        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Sync Errors</option>
      </select>

      <select name="sort" class="form-select" style="width: 170px;" onchange="this.form.submit()">
        <option value="last_synced_at" {{ request('sort', 'last_synced_at') === 'last_synced_at' ? 'selected' : '' }}>Last Synced</option>
        <option value="products_count" {{ request('sort') === 'products_count' ? 'selected' : '' }}>Products Count</option>
        <option value="member_count" {{ request('sort') === 'member_count' ? 'selected' : '' }}>Member Count</option>
        <option value="title" {{ request('sort') === 'title' ? 'selected' : '' }}>Title (A-Z)</option>
      </select>

      <button type="submit" class="btn btn-secondary">Filter</button>
      @if(request()->anyFilled(['search', 'status', 'sort']))
        <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary" title="Reset Filters">✕</a>
      @endif
    </div>
  </form>
</div>

<!-- Groups Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Channel / Group</th>
          <th>Invite Link</th>
          <th>Members</th>
          <th>Offers</th>
          <th>Status</th>
          <th>Last Synced</th>
          <th style="text-align: right; padding-right: 24px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($groups as $group)
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                @if($group->photo_url)
                  <img src="{{ $group->photo_url }}" alt="" style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: #27272a;">
                @else
                  <div style="width: 36px; height: 36px; border-radius: 8px; background: #27272a; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--accent); font-size: 0.85rem;">
                    {{ strtoupper(substr($group->title, 0, 1)) }}
                  </div>
                @endif
                <div>
                  <div style="font-weight: 600; color: #fff;">{{ $group->title }}</div>
                  <div style="font-size: 0.75rem; color: var(--text-dim);">ID: {{ $group->telegram_id }}</div>
                  @if($group->last_error)
                    <div style="font-size: 0.72rem; color: #f87171; margin-top: 2px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $group->last_error }}">
                      ⚠ {{ $group->last_error }}
                    </div>
                  @endif
                </div>
              </div>
            </td>
            <td>
              @if($group->invite_link)
                <a href="{{ $group->invite_link }}" target="_blank" style="color: #60a5fa; text-decoration: none; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 4px;">
                  {{ Str::limit($group->invite_link, 26) }}
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
              @else
                <span style="color: var(--text-dim); font-size: 0.8rem;">—</span>
              @endif
            </td>
            <td>{{ number_format($group->member_count) }}</td>
            <td>
              <span class="badge badge-purple">{{ number_format($group->products_count) }} offers</span>
            </td>
            <td>
              @if(!$group->is_active)
                <span class="badge badge-muted">Monitoring Paused</span>
              @elseif($group->sync_status === 'syncing')
                <span class="badge badge-warning">Syncing...</span>
              @elseif($group->sync_status === 'failed')
                <span class="badge badge-danger">Sync Error</span>
              @else
                <span class="badge badge-success">Active</span>
              @endif
            </td>
            <td style="font-size: 0.82rem; color: var(--text-muted);">
              {{ $group->last_synced_at ? $group->last_synced_at->diffForHumans() : 'Never' }}
            </td>
            <td style="text-align: right; padding-right: 24px;">
              <div style="display: inline-flex; align-items: center; gap: 6px;">
                <!-- Sync Button -->
                <form action="{{ route('admin.groups.sync', $group->id) }}" method="POST" style="margin: 0;">
                  @csrf
                  <button type="submit" class="btn btn-secondary btn-sm" title="Dispatch Sync Job">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Sync
                  </button>
                </form>

                <!-- Pause/Resume Toggle -->
                <form action="{{ route('admin.groups.toggle', $group->id) }}" method="POST" style="margin: 0;">
                  @csrf
                  <button type="submit" class="btn btn-secondary btn-sm" title="{{ $group->is_active ? 'Pause monitoring' : 'Resume monitoring' }}">
                    {{ $group->is_active ? 'Pause' : 'Resume' }}
                  </button>
                </form>

                <!-- Edit Button -->
                <button type="button" class="btn btn-secondary btn-sm" onclick="openEditGroupModal({{ $group->id }}, '{{ addslashes($group->title) }}', '{{ addslashes($group->invite_link ?? '') }}')">
                  Edit
                </button>

                <!-- Remove/Deactivate Form -->
                <form action="{{ route('admin.groups.destroy', $group->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Deactivate this channel? Historical product offers will remain safely in the catalog.')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-danger btn-sm" title="Deactivate Group">
                    ✕
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 40px;">
              No Telegram channels match the current filter.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($groups->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $groups->links() }}
    </div>
  @endif
</div>

<!-- Modal: Connect / Add New Group -->
<div id="addGroupModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 500px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Connect Telegram Group / Channel</h3>
      <button type="button" onclick="closeAddGroupModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <form action="{{ route('admin.groups.store') }}" method="POST">
      @csrf
      <div class="form-group">
        <label class="form-label">Telegram Invite Link or Public Username</label>
        <input type="text" name="invite_link" class="form-input" placeholder="https://t.me/examplechannel or https://t.me/+joinchat..." required>
        <div style="font-size: 0.78rem; color: var(--text-dim); margin-top: 6px;">
          The system will join the channel via the MTProto client, fetch its metadata, and queue product ingestion.
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" class="btn btn-secondary" onclick="closeAddGroupModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Connect & Sync</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Group -->
<div id="editGroupModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 500px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Edit Telegram Group Details</h3>
      <button type="button" onclick="closeEditGroupModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <form id="editGroupForm" method="POST">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label class="form-label">Group Title</label>
        <input type="text" id="editGroupTitle" name="title" class="form-input" required>
      </div>
      <div class="form-group">
        <label class="form-label">Invite Link</label>
        <input type="text" id="editGroupLink" name="invite_link" class="form-input">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" class="btn btn-secondary" onclick="closeEditGroupModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function openAddGroupModal() {
    const modal = document.getElementById('addGroupModal');
    modal.style.display = 'flex';
  }
  function closeAddGroupModal() {
    document.getElementById('addGroupModal').style.display = 'none';
  }
  function openEditGroupModal(id, title, link) {
    const modal = document.getElementById('editGroupModal');
    document.getElementById('editGroupForm').action = `/admin/telegram-groups/${id}`;
    document.getElementById('editGroupTitle').value = title;
    document.getElementById('editGroupLink').value = link;
    modal.style.display = 'flex';
  }
  function closeEditGroupModal() {
    document.getElementById('editGroupModal').style.display = 'none';
  }
</script>
@endsection

