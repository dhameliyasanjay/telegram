@extends('admin.layouts.admin')

@section('title', 'Canonical Products & Offers')

@section('content')
<!-- Search & Filters -->
<div class="card" style="padding: 16px 20px; margin-bottom: 24px;">
  <form action="{{ route('admin.products.index') }}" method="GET" style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; justify-content: space-between;">
    <div style="flex: 1; min-width: 280px; max-width: 450px;">
      <input type="text" name="search" class="form-input" placeholder="Search product name, brand, model, SKU..." value="{{ request('search') }}">
    </div>

    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
      <select name="group_id" class="form-select" style="width: 160px;" onchange="this.form.submit()">
        <option value="">All Channels</option>
        @foreach($groups as $grp)
          <option value="{{ $grp->id }}" {{ request('group_id') == $grp->id ? 'selected' : '' }}>
            {{ Str::limit($grp->title, 20) }}
          </option>
        @endforeach
      </select>

      <select name="filter_issue" class="form-select" style="width: 170px;" onchange="this.form.submit()">
        <option value="">All Products</option>
        <option value="zero_price" {{ request('filter_issue') === 'zero_price' ? 'selected' : '' }}>Unpriced / Zero Price</option>
        <option value="high_spread" {{ request('filter_issue') === 'high_spread' ? 'selected' : '' }}>High Price Spread (>50%)</option>
      </select>

      <label style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; color: var(--text-muted); cursor: pointer;">
        <input type="checkbox" name="multi_group_only" value="1" {{ request('multi_group_only') ? 'checked' : '' }} onchange="this.form.submit()">
        Multi-Group Only
      </label>

      <select name="sort" class="form-select" style="width: 160px;" onchange="this.form.submit()">
        <option value="last_observed_at" {{ request('sort') === 'last_observed_at' ? 'selected' : '' }}>Recently Observed</option>
        <option value="cheapest_price" {{ request('sort') === 'cheapest_price' ? 'selected' : '' }}>Lowest Price</option>
        <option value="offers_count" {{ request('sort') === 'offers_count' ? 'selected' : '' }}>Most Group Offers</option>
        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
      </select>

      <button type="submit" class="btn btn-secondary">Filter</button>
      @if(request()->anyFilled(['search', 'group_id', 'filter_issue', 'multi_group_only', 'sort']))
        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary" title="Reset Filters">✕</a>
      @endif
    </div>
  </form>
</div>

<!-- Products Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Canonical Product</th>
          <th>Brand / Category</th>
          <th>Cheapest Price</th>
          <th>Highest Price</th>
          <th>Group Offers</th>
          <th>Last Observed</th>
          <th style="text-align: right; padding-right: 24px;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($products as $prod)
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 12px;">
                @if($prod->primary_photo_url)
                  <img src="{{ $prod->primary_photo_url }}" alt="" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #27272a; flex-shrink: 0;">
                @else
                  <div style="width: 44px; height: 44px; border-radius: 8px; background: #27272a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--text-dim);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                @endif
                <div>
                  <div style="font-weight: 600; color: #fff; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    {{ $prod->name }}
                  </div>
                  <div style="font-size: 0.72rem; color: var(--text-dim); font-family: monospace;">
                    Key: {{ Str::limit($prod->canonical_key, 28) }}
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div style="font-size: 0.85rem; color: #fff;">{{ $prod->brand ?: '—' }}</div>
              <div style="font-size: 0.75rem; color: var(--text-dim);">{{ $prod->category ?: 'General' }}</div>
            </td>
            <td>
              @if($prod->cheapest_price && $prod->cheapest_price > 0)
                <strong style="color: var(--green); font-size: 0.95rem;">₹{{ number_format($prod->cheapest_price, 2) }}</strong>
                @if($prod->cheapestGroup)
                  <div style="font-size: 0.72rem; color: var(--text-dim);">in {{ Str::limit($prod->cheapestGroup->title, 16) }}</div>
                @endif
              @else
                <span style="color: var(--text-dim); font-size: 0.82rem;">Unpriced</span>
              @endif
            </td>
            <td>
              @if($prod->highest_price && $prod->highest_price > $prod->cheapest_price)
                <span style="color: var(--text-muted); font-size: 0.88rem;">₹{{ number_format($prod->highest_price, 2) }}</span>
                <div style="font-size: 0.72rem; color: #f87171;">
                  +₹{{ number_format($prod->highest_price - $prod->cheapest_price, 2) }} spread
                </div>
              @else
                <span style="color: var(--text-dim); font-size: 0.82rem;">—</span>
              @endif
            </td>
            <td>
              <span class="badge {{ $prod->offers_count > 1 ? 'badge-purple' : 'badge-muted' }}">
                {{ $prod->offers_count }} {{ Str::plural('group', $prod->offers_count) }}
              </span>
            </td>
            <td style="font-size: 0.82rem; color: var(--text-dim);">
              {{ $prod->last_observed_at ? $prod->last_observed_at->diffForHumans() : '—' }}
            </td>
            <td style="text-align: right; padding-right: 24px;">
              <a href="{{ route('admin.products.show', $prod->id) }}" class="btn btn-primary btn-sm">
                Inspect Offers ({{ $prod->offers_count }})
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-dim); padding: 40px;">
              No canonical products match the query criteria.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($products->hasPages())
    <div style="padding: 16px 20px; border-top: 1px solid var(--border);">
      {{ $products->links() }}
    </div>
  @endif
</div>
@endsection

