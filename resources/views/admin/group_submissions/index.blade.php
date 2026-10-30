@extends('admin.layouts.admin')

@section('title', 'Sent Group Links')

@section('header-actions')
  <form action="{{ route('admin.groups.sync-all') }}" method="POST" style="margin:0; display:inline-block;">
    @csrf
    <button type="submit" class="btn btn-secondary" onclick="return confirm('Trigger automated synchronization across all active Telegram groups?')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Sync All Groups
    </button>
  </form>
  <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">
    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Manage Telegram Groups
  </a>
@endsection

@section('content')
<!-- KPI Metric Cards -->
<div class="metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
  <div class="card" style="margin-bottom: 0; padding: 20px;">
    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim); font-weight: 600; letter-spacing: 0.5px;">Total Submissions</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: #fff; margin-top: 6px;">{{ number_format($metrics['total'] ?? 0) }}</div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">User suggested channels</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px; border-left: 3px solid #f59e0b;">
    <div style="font-size: 0.75rem; text-transform: uppercase; color: #fbbf24; font-weight: 600; letter-spacing: 0.5px;">Pending Review</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: #fbbf24; margin-top: 6px;">{{ number_format($metrics['pending'] ?? 0) }}</div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">Awaiting admin approval</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px; border-left: 3px solid #10b981;">
    <div style="font-size: 0.75rem; text-transform: uppercase; color: #34d399; font-weight: 600; letter-spacing: 0.5px;">Approved & Connected</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: #34d399; margin-top: 6px;">{{ number_format($metrics['approved'] ?? 0) }}</div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">Added to Price Monitor</div>
  </div>

  <div class="card" style="margin-bottom: 0; padding: 20px; border-left: 3px solid #ef4444;">
    <div style="font-size: 0.75rem; text-transform: uppercase; color: #f87171; font-weight: 600; letter-spacing: 0.5px;">Rejected</div>
    <div style="font-size: 1.8rem; font-weight: 800; color: #f87171; margin-top: 6px;">{{ number_format($metrics['rejected'] ?? 0) }}</div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 4px;">Not product related</div>
  </div>
</div>

<!-- Filter & Search Toolbar -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
  <form action="{{ route('admin.group-submissions.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div style="display: flex; gap: 12px; flex: 1; min-width: 280px; max-width: 500px;">
      <input type="text" name="search" class="form-input" placeholder="Search by link, user name, email, or notes..." value="{{ request('search') }}">
    </div>

    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <select name="status" class="form-select" style="width: 170px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Pending ({{ $metrics['pending'] ?? 0 }})</option>
        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>✓ Approved ({{ $metrics['approved'] ?? 0 }})</option>
        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>✕ Rejected ({{ $metrics['rejected'] ?? 0 }})</option>
      </select>

      <button type="submit" class="btn btn-secondary">Filter</button>
      @if(request()->anyFilled(['search', 'status']))
        <a href="{{ route('admin.group-submissions.index') }}" class="btn btn-secondary" title="Reset Filters">✕</a>
      @endif
    </div>
  </form>
</div>

<!-- Group Submissions Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Submitted By</th>
          <th>Telegram Group Link</th>
          <th>Preview / Channel</th>
          <th>Status</th>
          <th>Date Submitted</th>
          <th style="text-align: right; padding-right: 24px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($submissions as $sub)
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #60a5fa; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                  {{ strtoupper(substr($sub->user?->name ?? 'G', 0, 1)) }}
                </div>
                <div>
                  <div style="font-weight: 600; color: #fff;">{{ $sub->user?->name ?? 'Guest / Unknown' }}</div>
                  <div style="font-size: 0.75rem; color: var(--text-dim);">{{ $sub->user?->email ?? '—' }}</div>
                  @if($sub->user?->phone)
                    <div style="font-size: 0.72rem; color: #34d399;">📞 {{ $sub->user->phone }}</div>
                  @endif
                </div>
              </div>
            </td>

            <td>
              <div style="max-width: 300px;">
                <div style="display: flex; align-items: center; gap: 6px;">
                  <a href="{{ $sub->invite_link }}" target="_blank" style="color: #60a5fa; font-weight: 600; text-decoration: none; font-size: 0.85rem; word-break: break-all; display: inline-flex; align-items: center; gap: 4px;">
                    {{ $sub->invite_link }}
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                  </a>
                </div>
                @if($sub->notes)
                  <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 4px;">
                    💬 <em>{{ $sub->notes }}</em>
                  </div>
                @endif
              </div>
            </td>

            <td>
              @if($sub->status === 'approved' && $sub->telegramGroup)
                <div>
                  <a href="{{ route('admin.groups.index', ['search' => $sub->telegramGroup->title]) }}" style="font-weight: 600; color: #34d399; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                    ✓ {{ $sub->telegramGroup->title }}
                  </a>
                  <div style="font-size: 0.72rem; color: var(--text-dim); margin-top: 2px;">
                    {{ number_format($sub->telegramGroup->member_count) }} members • Active Group
                  </div>
                </div>
              @else
                <div style="display: flex; gap: 6px; align-items: center;">
                  <a href="{{ $sub->invite_link }}" target="_blank" class="btn btn-secondary btn-xs" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; color: #60a5fa;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Preview in Telegram ↗
                  </a>
                  <button type="button" class="btn btn-secondary btn-xs" onclick="previewGroupInfo({{ $sub->id }}, '{{ addslashes($sub->invite_link) }}')" title="Fetch info">
                    Info
                  </button>
                </div>
              @endif
            </td>

            <td>
              @if($sub->status === 'pending')
                <span class="badge badge-warning" style="display: inline-flex; align-items: center; gap: 4px;">
                  <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span>
                  Pending Review
                </span>
              @elseif($sub->status === 'approved')
                <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 4px;">
                  <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                  Approved & Connected
                </span>
              @elseif($sub->status === 'rejected')
                <div>
                  <span class="badge badge-danger">Rejected</span>
                  @if($sub->admin_notes)
                    <div style="font-size: 0.72rem; color: #f87171; margin-top: 3px; max-width: 180px;" title="{{ $sub->admin_notes }}">
                      {{ Str::limit($sub->admin_notes, 30) }}
                    </div>
                  @endif
                </div>
              @endif
            </td>

            <td style="font-size: 0.82rem; color: var(--text-muted);">
              <div>{{ $sub->created_at->diffForHumans() }}</div>
              <div style="font-size: 0.72rem; color: var(--text-dim);">{{ $sub->created_at->format('d M Y, h:i A') }}</div>
            </td>

            <td style="text-align: right; padding-right: 24px;">
              <div style="display: inline-flex; align-items: center; gap: 6px;">
                @if($sub->status === 'pending')
                  <!-- Accept & Auto-Connect Button -->
                  <form action="{{ route('admin.group-submissions.accept', $sub->id) }}" method="POST" style="margin: 0;" onsubmit="handleAcceptSubmit(this)">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; font-weight: 600;" title="Accept & Add to Telegram Groups">
                      ✓ Accept & Connect
                    </button>
                  </form>

                  <!-- Reject Button -->
                  <button type="button" class="btn btn-danger btn-sm" onclick="openRejectModal({{ $sub->id }}, '{{ addslashes($sub->invite_link) }}')">
                    ✕ Reject
                  </button>
                @elseif($sub->status === 'approved')
                  <a href="{{ route('admin.groups.index', ['search' => $sub->group_title ?: $sub->invite_link]) }}" class="btn btn-secondary btn-sm" title="Manage in Telegram Groups">
                    View in Groups ↗
                  </a>
                @elseif($sub->status === 'rejected')
                  <!-- Option to re-accept if previously rejected -->
                  <form action="{{ route('admin.group-submissions.accept', $sub->id) }}" method="POST" style="margin: 0;" onsubmit="handleAcceptSubmit(this)">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm" title="Re-evaluate & Connect">
                      Re-Accept
                    </button>
                  </form>
                @endif

                <!-- Delete Record Button -->
                <form action="{{ route('admin.group-submissions.destroy', $sub->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Delete this submission record?')">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--text-dim);" title="Delete Record">
                    ✕
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 48px;">
              <div style="font-size: 1.8rem; margin-bottom: 8px;">📬</div>
              <div style="font-size: 1rem; color: #fff; font-weight: 600;">No group submissions found</div>
              <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;">Users can submit wholesale group links from their dashboard.</div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($submissions->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $submissions->links() }}
    </div>
  @endif
</div>

<!-- Rejection Modal -->
<div id="rejectModalBg" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 500; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
  <div style="background: #18181b; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 16px; padding: 24px; max-width: 460px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.8);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">Reject Group Submission</div>
      <button onclick="closeRejectModal()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.3rem;">✕</button>
    </div>
    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">
      You are rejecting link: <strong id="rejectLinkTarget" style="color:#60a5fa; word-break:break-all;"></strong>
    </p>

    <form id="rejectForm" method="POST" action="">
      @csrf
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim); margin-bottom: 6px; font-weight: 600;">Rejection Reason (Optional)</label>
        <textarea name="admin_notes" id="rejectReasonInput" rows="3" class="form-input" style="width: 100%; box-sizing: border-box; resize: vertical;" placeholder="e.g. Not related to wholesale products, spam channel, or link has expired."></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
      </div>
    </form>
  </div>
</div>

<!-- Info Preview Modal -->
<div id="previewModalBg" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 500; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
  <div style="background: #18181b; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px; padding: 24px; max-width: 480px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,0.8);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <div style="font-size: 1.1rem; font-weight: 700; color: #fff;">Telegram Channel Preview</div>
      <button onclick="closePreviewModal()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:1.3rem;">✕</button>
    </div>
    <div id="previewModalContent" style="font-size: 0.85rem; color: var(--text-muted);">
      <div style="text-align: center; padding: 20px;">Fetching metadata from Telegram...</div>
    </div>
    <div style="margin-top: 16px; text-align: right;">
      <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Close</button>
    </div>
  </div>
</div>

<script>
function handleAcceptSubmit(form) {
  const btn = form.querySelector('button[type="submit"]');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:12px;height:12px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;display:inline-block;animation:spin 0.6s linear infinite;margin-right:6px;"></span> Connecting...';
  }
}

function openRejectModal(id, link) {
  const modal = document.getElementById('rejectModalBg');
  const target = document.getElementById('rejectLinkTarget');
  const form = document.getElementById('rejectForm');
  if (target) target.innerText = link;
  if (form) form.action = `/admin/sent-links/${id}/reject`;
  if (modal) modal.style.display = 'flex';
}

function closeRejectModal() {
  const modal = document.getElementById('rejectModalBg');
  if (modal) modal.style.display = 'none';
}

function openPreviewModal() {
  const modal = document.getElementById('previewModalBg');
  if (modal) modal.style.display = 'flex';
}

function closePreviewModal() {
  const modal = document.getElementById('previewModalBg');
  if (modal) modal.style.display = 'none';
}

async function previewGroupInfo(id, link) {
  openPreviewModal();
  const content = document.getElementById('previewModalContent');
  content.innerHTML = '<div style="text-align:center; padding: 20px;"><span class="spinner" style="width:16px;height:16px;display:inline-block;margin-right:8px;"></span> Checking link status...</div>';

  try {
    const res = await fetch(`/admin/sent-links/${id}/preview`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();

    if (data.success && data.info) {
      const info = data.info;
      content.innerHTML = `
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 16px;">
          <div style="font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 6px;">${info.title || 'Telegram Group'}</div>
          <div style="font-size: 0.8rem; color: #60a5fa; margin-bottom: 10px; word-break: break-all;">${link}</div>
          <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 6px;">
            <span>Members:</span>
            <strong style="color: #34d399;">${info.member_count ? Number(info.member_count).toLocaleString() : 'Hidden / Private'}</strong>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 0.82rem;">
            <span>Telegram ID:</span>
            <span style="color: var(--text-dim);">${info.telegram_id || 'Will resolve on join'}</span>
          </div>
        </div>
      `;
    } else {
      content.innerHTML = `
        <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px; color: #f87171;">
          <strong>Notice:</strong> ${data.message || 'Cannot fetch preview directly without active Telegram session.'}
          <div style="margin-top: 10px;">
            <a href="${link}" target="_blank" class="btn btn-primary btn-sm" style="display: inline-block;">Open directly in Telegram ↗</a>
          </div>
        </div>
      `;
    }
  } catch (err) {
    content.innerHTML = `
      <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px;">
        <div style="color: #fff; margin-bottom: 8px;">Direct link ready for preview:</div>
        <a href="${link}" target="_blank" class="btn btn-primary btn-sm" style="display: inline-block;">Open directly in Telegram ↗</a>
      </div>
    `;
  }
}
</script>
@endsection

