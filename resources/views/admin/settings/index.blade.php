@extends('admin.layouts.admin')

@section('title', 'Application & System Settings')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST">
  @csrf

  @foreach($groupedSettings as $groupName => $settings)
    <div class="card">
      <div class="card-header" style="margin-bottom: 16px;">
        <h3 class="card-title" style="text-transform: capitalize;">
          @if($groupName === 'subscription')
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Subscription & Monetization Settings
          @elseif($groupName === 'system')
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
            System & Crawler Configurations
          @elseif($groupName === 'security')
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Security & Access Control
          @else
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            {{ ucfirst($groupName) }} Configurations
          @endif
        </h3>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        @foreach($settings as $setting)
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">{{ $setting->label ?: $setting->key }}</label>

            @if($setting->type === 'boolean')
              <div style="margin-top: 8px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer; color: #fff;">
                  <input type="hidden" name="form_submitted_security" value="1">
                  <input type="checkbox" name="{{ $setting->key }}" value="1" {{ $setting->value == '1' ? 'checked' : '' }} style="width: 18px; height: 18px;">
                  <span>Enabled</span>
                </label>
              </div>
            @elseif($setting->type === 'integer')
              <input type="number" name="{{ $setting->key }}" class="form-input" value="{{ $setting->value }}" required>
            @elseif($setting->type === 'float')
              <input type="number" step="0.01" name="{{ $setting->key }}" class="form-input" value="{{ $setting->value }}" required>
            @else
              <input type="text" name="{{ $setting->key }}" class="form-input" value="{{ $setting->value }}" required>
            @endif

            @if($setting->description)
              <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 5px;">
                {{ $setting->description }}
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endforeach

  <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.95rem;">
      Save All Configurations
    </button>
  </div>
</form>
@endsection

