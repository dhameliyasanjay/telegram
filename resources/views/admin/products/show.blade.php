@extends('admin.layouts.admin')

@section('title', 'Canonical Product Inspector')

@section('header-actions')
  <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
    ← Back to Products
  </a>
@endsection

@section('content')
<!-- Canonical Master Card -->
<div class="card">
  <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">
    @if($canonical->primary_photo_url)
      <img src="{{ $canonical->primary_photo_url }}" alt="" style="width: 140px; height: 140px; border-radius: 12px; object-fit: cover; background: #27272a; border: 1px solid var(--border);">
    @else
      <div style="width: 140px; height: 140px; border-radius: 12px; background: #27272a; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-dim);">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
    @endif

    <div style="flex: 1; min-width: 280px;">
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
        <span class="badge badge-purple">{{ $canonical->offers_count }} {{ Str::plural('Group Offer', $canonical->offers_count) }}</span>
        @if($canonical->offers_count > 1)
          <span class="badge badge-success">Multi-Group Verified</span>
        @endif
      </div>
      <h2 style="font-size: 1.4rem; color: #fff; font-weight: 700; margin-bottom: 8px;">{{ $canonical->name }}</h2>
      <div style="font-size: 0.8rem; font-family: monospace; color: var(--text-dim); margin-bottom: 14px;">
        Deduplication Key: {{ $canonical->canonical_key }}
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border);">
        <div>
          <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Brand:</span>
          <div style="font-weight: 600; color: #fff;">{{ $canonical->brand ?: '—' }}</div>
        </div>
        <div>
          <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Model / SKU:</span>
          <div style="font-weight: 600; color: #fff;">{{ $canonical->model ?: '—' }}</div>
        </div>
        <div>
          <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Category:</span>
          <div style="font-weight: 600; color: #fff;">{{ $canonical->category ?: 'General' }}</div>
        </div>
        <div>
          <span style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase;">Cheapest Offer:</span>
          <div style="font-weight: 700; color: var(--green); font-size: 1.1rem;">
            ₹{{ number_format($canonical->cheapest_price, 2) }}
          </div>
        </div>
      </div>
    </div>

    <div>
      <button class="btn btn-secondary btn-sm" onclick="openEditCanonicalModal()">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        Edit Master Info
      </button>
    </div>
  </div>
</div>

<!-- Group Offers Breakdown -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
    <h3 style="font-size: 1.1rem; color: #fff; font-weight: 600;">
      Observed Group Offers & Price Comparison
    </h3>
    <span style="font-size: 0.82rem; color: var(--text-dim);">
      Deduplicated across {{ $canonical->offers->count() }} channels
    </span>
  </div>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Telegram Channel</th>
          <th>Raw Captured Name</th>
          <th>Price in Channel</th>
          <th>Price Difference</th>
          <th>Price History</th>
          <th>First Seen</th>
          <th>Last Update</th>
          <th style="text-align: right; padding-right: 24px;">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($canonical->offers as $offer)
          <tr>
            <td>
              <div style="font-weight: 600; color: #fff;">{{ $offer->group?->title ?? 'Direct Channel' }}</div>
              @if($offer->group?->invite_link)
                <a href="{{ $offer->group->invite_link }}" target="_blank" style="font-size: 0.75rem; color: #60a5fa; text-decoration: none;">
                  Visit Group ↗
                </a>
              @endif
            </td>
            <td>
              <div style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem;" title="{{ $offer->product_name }}">
                {{ $offer->product_name }}
              </div>
            </td>
            <td>
              @if($offer->latest_price && $offer->latest_price > 0)
                <strong style="color: {{ $offer->latest_price == $canonical->cheapest_price ? 'var(--green)' : '#fff' }}; font-size: 1rem;">
                  ₹{{ number_format($offer->latest_price, 2) }}
                </strong>
                @if($offer->latest_price == $canonical->cheapest_price)
                  <span class="badge badge-success" style="font-size: 0.65rem; margin-left: 4px;">Cheapest</span>
                @endif
              @else
                <span style="color: var(--text-dim); font-size: 0.82rem;">Unpriced</span>
              @endif
            </td>
            <td>
              @if($offer->latest_price > $canonical->cheapest_price)
                <span style="color: #f87171; font-weight: 600; font-size: 0.85rem;">
                  +₹{{ number_format($offer->latest_price - $canonical->cheapest_price, 2) }}
                </span>
              @else
                <span style="color: var(--green); font-size: 0.82rem;">Best Price</span>
              @endif
            </td>
            <td>
              <span class="badge badge-muted">
                {{ $offer->priceHistory->count() }} {{ Str::plural('record', $offer->priceHistory->count()) }}
              </span>
            </td>
            <td style="font-size: 0.8rem; color: var(--text-dim);">
              {{ $offer->first_seen_at ? $offer->first_seen_at->format('M d, Y') : '—' }}
            </td>
            <td style="font-size: 0.8rem; color: var(--text-dim);">
              {{ $offer->last_updated_at ? $offer->last_updated_at->diffForHumans() : '—' }}
            </td>
            <td style="text-align: right; padding-right: 24px;">
              <form action="{{ route('admin.products.offers.destroy', [$canonical->id, $offer->id]) }}" method="POST" onsubmit="return confirm('Remove this offer from canonical product? Metrics will recalculate automatically.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" title="Detach Offer">
                  Detach
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Edit Canonical Product -->
<div id="editCanonicalModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
  <div style="background: var(--surface-card); border: 1px solid var(--border); border-radius: var(--radius); max-width: 520px; width: 100%; padding: 28px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
      <h3 style="color: #fff; font-size: 1.15rem; font-weight: 600;">Edit Canonical Metadata</h3>
      <button type="button" onclick="closeEditCanonicalModal()" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>

    <form action="{{ route('admin.products.update', $canonical->id) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="form-group">
        <label class="form-label">Canonical Product Name</label>
        <input type="text" name="name" class="form-input" value="{{ $canonical->name }}" required>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label">Brand</label>
          <input type="text" name="brand" class="form-input" value="{{ $canonical->brand }}">
        </div>
        <div class="form-group">
          <label class="form-label">Model / SKU</label>
          <input type="text" name="model" class="form-input" value="{{ $canonical->model }}">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Category</label>
        <input type="text" name="category" class="form-input" value="{{ $canonical->category }}">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <button type="button" class="btn btn-secondary" onclick="closeEditCanonicalModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function openEditCanonicalModal() {
    document.getElementById('editCanonicalModal').style.display = 'flex';
  }
  function closeEditCanonicalModal() {
    document.getElementById('editCanonicalModal').style.display = 'none';
  }
</script>
@endsection
