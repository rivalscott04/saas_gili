@extends('layouts.master')

@section('title')
    {{ __('translation.tenant-categories') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.customers-settings') }}
        @endslot
        @slot('title')
            {{ __('translation.tenant-categories') }}
        @endslot
    @endcomponent

    <div class="card" id="tenantCategoriesPanel">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ __('translation.business-segmentation') }}</h5>
            <p class="text-muted mb-0 small">{{ __('translation.choose-category-for-tenant', ['tenant' => $tenant->name]) }}</p>
        </div>
        <div class="card-body">
            @if ($showTenantSwitcher)
                <div class="mb-4">
                    <label class="form-label">{{ __('translation.tenant') }}</label>
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
                <button type="submit" class="btn btn-primary">{{ __('translation.save-categories') }}</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var initTenantCategoriesAjax = function() {
                var switcher = document.getElementById('tenantCategorySwitcher');
                var panel = document.getElementById('tenantCategoriesPanel');
                if (!switcher || !panel || switcher.dataset.ajaxBound) {
                    return;
                }

                switcher.dataset.ajaxBound = '1';
                var applyLoadingState = function(isLoading) {
                    switcher.disabled = isLoading;
                    panel.classList.toggle('opacity-75', isLoading);
                };

                switcher.addEventListener('change', function() {
                    var nextUrl = "{{ route('tenant-categories.index') }}" + '?tenant=' + encodeURIComponent(this.value);
                    applyLoadingState(true);

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load tenant categories.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshPanel = doc.getElementById('tenantCategoriesPanel');
                            if (!freshPanel) {
                                throw new Error('Missing refreshed panel.');
                            }
                            panel.outerHTML = freshPanel.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initTenantCategoriesAjax();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            applyLoadingState(false);
                        });
                });
            };

            initTenantCategoriesAjax();
        })();
    </script>
@endsection
