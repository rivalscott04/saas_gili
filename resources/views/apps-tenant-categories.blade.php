@extends('layouts.master')

@section('title')
    Tenant Categories
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Customers & Settings
        @endslot
        @slot('title')
            Kategori tenant
        @endslot
    @endcomponent

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Segmentasi bisnis</h5>
            <p class="text-muted mb-0 small">Pilih satu atau lebih kategori untuk tenant <strong>{{ $tenant->name }}</strong>.</p>
        </div>
        <div class="card-body">
            @if ($showTenantSwitcher)
                <div class="mb-4">
                    <label class="form-label">Tenant</label>
                    <select class="form-select" id="tenantCategorySwitcher" style="max-width: 28rem;">
                        @foreach ($availableTenants as $tenantOption)
                            <option value="{{ $tenantOption->code }}"
                                {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                {{ $tenantOption->name }} ({{ $tenantOption->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <form method="POST" action="{{ route('tenant-categories.update') }}">
                @csrf
                @if ($showTenantSwitcher)
                    <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                @endif
                <div class="mb-3">
                    @foreach ($categories as $category)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category_ids[]"
                                value="{{ $category->id }}" id="cat-{{ $category->id }}"
                                {{ in_array((int) $category->id, $selectedCategoryIds, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cat-{{ $category->id }}">
                                <strong>{{ $category->name }}</strong>
                                <span class="text-muted">({{ $category->code }})</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary">Simpan kategori</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var switcher = document.getElementById('tenantCategorySwitcher');
            if (!switcher) {
                return;
            }
            switcher.addEventListener('change', function() {
                window.location.href = "{{ route('tenant-categories.index') }}" + '?tenant=' + encodeURIComponent(this.value);
            });
        })();
    </script>
@endsection
