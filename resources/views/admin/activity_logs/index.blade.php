@extends('admin.layouts.admin')

@section('title', 'Administrative Activity Audit Logs')

@section('content')
<!-- Search & Filter -->
<div class="card" style="padding: 16px 20px; margin-bottom: 24px;">
  <form action="{{ route('admin.activity-logs.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div style="flex: 1; min-width: 280px; max-width: 450px;">
      <input type="text" name="search" class="form-input" placeholder="Search by admin name, description, IP, or action..." value="{{ request('search') }}">
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
      <select name="action" class="form-select" style="width: 200px;" onchange="this.form.submit()">
        <option value="">All Action Types</option>
        @foreach($distinctActions as $act)
          <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
            {{ ucwords(str_replace('_', ' ', $act)) }}
          </option>
        @endforeach
      </select>

      <button type="submit" class="btn btn-secondary">Filter</button>
      @if(request()->anyFilled(['search', 'action']))
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-secondary" title="Reset Filters">✕</a>
      @endif
    </div>
  </form>
</div>

<!-- Logs Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Admin</th>
          <th>Action</th>
          <th>Description</th>
          <th>Target Entity</th>
          <th>IP Address</th>
          <th style="text-align: right; padding-right: 24px;">Details</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          <tr>
            <td style="font-size: 0.82rem; color: var(--text-dim); white-space: nowrap;">
              {{ $log->created_at->format('Y-m-d H:i:s') }}
              <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $log->created_at->diffForHumans() }}</div>
            </td>
            <td>
              <div style="font-weight: 600; color: #fff;">{{ $log->admin_name }}</div>
              @if($log->user_id)
                <div style="font-size: 0.72rem; color: var(--text-dim);">UID: #{{ $log->user_id }}</div>
              @endif
            </td>
            <td>
              <span class="badge badge-purple" style="font-family: monospace; font-size: 0.75rem;">
                {{ $log->action }}
              </span>
            </td>
            <td>
              <div style="font-size: 0.88rem; color: #fff; max-width: 320px;">
                {{ $log->description ?: '—' }}
              </div>
            </td>
            <td style="font-size: 0.8rem; color: var(--text-dim);">
              @if($log->entity_type)
                {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
              @else
                System
              @endif
            </td>
            <td style="font-size: 0.8rem; font-family: monospace; color: var(--text-dim);">
              {{ $log->ip_address ?: '127.0.0.1' }}
            </td>
            <td style="text-align: right; padding-right: 24px;">
              @if(!empty($log->details))
                <button type="button" class="btn btn-secondary btn-sm" onclick="showLogDetails({{ json_encode($log->details, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }})">
                  View Diff
                </button>
              @else
                <span style="color: var(--text-dim); font-size: 0.78rem;">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 40px;">
              No administrative logs recorded.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($logs->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $logs->links() }}
    </div>
  @endif
</div>

<!-- Modal: View Log Details JSON -->
<div id="logDetailsModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 600px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Log Details Payload</h3>
      <button type="button" onclick="closeLogDetailsModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <pre id="logDetailsPre" style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 16px; color: #34d399; font-family: monospace; font-size: 0.85rem; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"></pre>

    <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
      <button type="button" class="btn btn-secondary" onclick="closeLogDetailsModal()">Close</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function showLogDetails(details) {
    document.getElementById('logDetailsPre').textContent = JSON.stringify(details, null, 2);
    document.getElementById('logDetailsModal').style.display = 'flex';
  }
  function closeLogDetailsModal() {
    document.getElementById('logDetailsModal').style.display = 'none';
  }
</script>
@endsection
