@extends('layouts.master')

@section('title')
    {{ __('translation.tour-management') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.operations-resources') }}
        @endslot
        @slot('title')
            {{ __('translation.tour-management') }}
        @endslot
    @endcomponent

    <div class="row" id="toursAjaxContainer">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.add-tour-master') }}</h5>
                </div>
                <div class="card-body">
                    @if ($showTenantSwitcher)
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.tenant') }}</label>
                            <select class="form-select" id="tourTenantSwitcher">
                                @foreach ($availableTenants as $tenantOption)
                                    <option value="{{ $tenantOption->code }}"
                                        {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                        {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tours.store') }}">
                        @csrf
                        @if ($showTenantSwitcher)
                            <input type="hidden" name="tenant_code" id="tourCreateTenantCode" value="{{ $tenant->code }}">
                        @endif
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.tour-name') }}</label>
                            <input type="text" class="form-control" name="name" required maxlength="190">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.tour-code') }}</label>
                            <input type="text" class="form-control" value="{{ __('translation.auto-generated-by-system') }}"
                                disabled readonly>
                            <small class="text-muted">{{ __('translation.tour-code-readonly-help') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.default-max-pax-day') }}</label>
                            <input type="number" class="form-control" name="default_max_pax_per_day" min="1"
                                max="100000" placeholder="{{ __('translation.optional') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.order') }}</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                max="100000" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.description') }}</label>
                            <textarea class="form-control" rows="3" name="description" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.resource-allocation-rule') }}</label>
                            <select class="form-select js-allocation-profile-select" name="allocation_requirement"
                                data-prefix="tourCreateReq">
                                <option value="none">{{ __('translation.no-special-rule') }}</option>
                                <option value="snorkeling">{{ __('translation.snorkeling-rule') }}</option>
                                <option value="land_activity">{{ __('translation.land-activity-rule') }}</option>
                            </select>
                            <small class="text-muted">{{ __('translation.resource-rule-help') }}</small>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-semibold mb-0">{{ __('translation.resource-requirement-per-tour') }}</label>
                                <button type="button" class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                    data-prefix="tourCreateReq">{{ __('translation.apply-preset-profile') }}</button>
                            </div>
                            @foreach (\App\Models\Tour::RESOURCE_TYPE_LABELS as $typeKey => $typeLabel)
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-7">
                                        <div class="form-check">
                                            <input type="hidden" name="requirements[{{ $typeKey }}][is_required]" value="0">
                                            <input class="form-check-input" type="checkbox"
                                                id="tourCreateReq_{{ $typeKey }}_required"
                                                name="requirements[{{ $typeKey }}][is_required]" value="1">
                                            <label class="form-check-label" for="tourCreateReq_{{ $typeKey }}_required">
                                                {{ __('translation.resource-required-label', ['label' => $typeLabel]) }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="number" class="form-control"
                                            id="tourCreateReq_{{ $typeKey }}_min_units"
                                            name="requirements[{{ $typeKey }}][min_units]" min="1"
                                            max="1000" value="1" placeholder="Min unit">
                                    </div>
                                </div>
                            @endforeach
                            <small class="text-muted d-block">{{ __('translation.required-min-units-help') }}</small>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="tourIsActive" value="1"
                                checked>
                                <label class="form-check-label" for="tourIsActive">{{ __('translation.active') }}</label>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('translation.save-tour') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">{{ __('translation.tour-master-list') }}</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $tenant->name }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($tours->isEmpty())
                        <div class="alert alert-info mb-0">
                            {{ __('translation.no-tour-master-for-tenant') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('translation.name') }}</th>
                                        <th>{{ __('translation.code') }}</th>
                                        <th>{{ __('translation.max-pax-day') }}</th>
                                        <th>{{ __('translation.resource-rule') }}</th>
                                        <th>{{ __('translation.status') }}</th>
                                        <th class="text-end">{{ __('translation.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tours as $tour)
                                        <tr>
                                            @php
                                                $requiredRows = $tour->resourceRequirements->where('is_required', true);
                                                $primaryRequiredType = optional($requiredRows->first())->resource_type;
                                                $resourceLinkParams = [];
                                                if ($showTenantSwitcher) {
                                                    $resourceLinkParams['tenant'] = $tenant->code;
                                                }
                                                if ($requiredRows->count() > 1) {
                                                    $resourceLinkParams['required_by_active_tour'] = '1';
                                                } elseif ($primaryRequiredType) {
                                                    $resourceLinkParams['resource_type'] = $primaryRequiredType;
                                                }
                                                $resourceLink = route('operations-resources.index', $resourceLinkParams);
                                            @endphp
                                            <td>{{ $tour->name }}</td>
                                            <td>{{ $tour->code ?: '-' }}</td>
                                            <td>{{ $tour->default_max_pax_per_day ?: '-' }}</td>
                                            <td>
                                                @if ($requiredRows->isEmpty())
                                                    <span class="badge bg-light text-muted">{{ __('translation.no-requirement') }}</span>
                                                @else
                                                    @foreach ($requiredRows as $requiredRow)
                                                        <span class="badge bg-warning-subtle text-warning me-1 mb-1">
                                                            {{ \App\Models\Tour::RESOURCE_TYPE_LABELS[$requiredRow->resource_type] ?? $requiredRow->resource_type }}
                                                            min {{ max(1, (int) $requiredRow->min_units) }}
                                                        </span>
                                                    @endforeach
                                                @endif
                                            </td>
                                            <td>
                                                @if ($tour->is_active)
                                                    <span class="badge bg-success-subtle text-success">{{ __('translation.active') }}</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ __('translation.archived') }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ $resourceLink }}" class="btn btn-sm btn-soft-dark">
                                                    {{ __('translation.resource') }}
                                                </a>
                                                <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal"
                                                    data-bs-target="#editTourModal{{ $tour->id }}">
                                                    {{ __('translation.edit') }}
                                                </button>
                                                @if ($tour->is_active)
                                                    <form action="{{ route('tours.archive', $tour) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        @if ($showTenantSwitcher)
                                                            <input type="hidden" name="tenant_code"
                                                                value="{{ $tenant->code }}">
                                                        @endif
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">
                                                            {{ __('translation.archive') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="editTourModal{{ $tour->id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('tours.update', $tour) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">{{ __('translation.edit-tour') }}</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @if ($showTenantSwitcher)
                                                                <input type="hidden" name="tenant_code"
                                                                    value="{{ $tenant->code }}">
                                                            @endif
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.tour-name') }}</label>
                                                                <input type="text" class="form-control" name="name"
                                                                    maxlength="190" required
                                                                    value="{{ $tour->name }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.tour-code') }}</label>
                                                                <input type="text" class="form-control"
                                                                    value="{{ $tour->code ?: __('translation.auto-generated-by-system') }}"
                                                                    disabled readonly>
                                                                <small class="text-muted">{{ __('translation.tour-code-readonly-help') }}</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.default-max-pax-day') }}</label>
                                                                <input type="number" class="form-control"
                                                                    name="default_max_pax_per_day" min="1"
                                                                    max="100000"
                                                                    value="{{ $tour->default_max_pax_per_day }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.order') }}</label>
                                                                <input type="number" class="form-control" name="sort_order"
                                                                    min="0" max="100000"
                                                                    value="{{ (int) $tour->sort_order }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.description') }}</label>
                                                                <textarea class="form-control" rows="3" name="description" maxlength="5000">{{ $tour->description }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">{{ __('translation.resource-allocation-rule') }}</label>
                                                                <select class="form-select js-allocation-profile-select"
                                                                    name="allocation_requirement"
                                                                    data-prefix="tourEditReq{{ $tour->id }}">
                                                                    <option value="none"
                                                                        {{ ($tour->allocation_requirement ?? 'none') === 'none' ? 'selected' : '' }}>
                                                                        {{ __('translation.no-special-rule') }}</option>
                                                                    <option value="snorkeling"
                                                                        {{ ($tour->allocation_requirement ?? '') === 'snorkeling' ? 'selected' : '' }}>
                                                                        {{ __('translation.snorkeling-short') }}</option>
                                                                    <option value="land_activity"
                                                                        {{ ($tour->allocation_requirement ?? '') === 'land_activity' ? 'selected' : '' }}>
                                                                        {{ __('translation.land-activity-short') }}</option>
                                                                </select>
                                                            </div>
                                                            @php
                                                                $requirementsByType = $tour->resourceRequirements
                                                                    ->keyBy('resource_type');
                                                            @endphp
                                                            <div class="border rounded p-3 mb-3 bg-light-subtle">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between mb-2">
                                                                    <label class="form-label fw-semibold mb-0">{{ __('translation.resource-requirement-per-tour') }}</label>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                                                        data-prefix="tourEditReq{{ $tour->id }}">{{ __('translation.apply-preset-profile') }}</button>
                                                                </div>
                                                                @foreach (\App\Models\Tour::RESOURCE_TYPE_LABELS as $typeKey => $typeLabel)
                                                                    @php
                                                                        $row = $requirementsByType->get($typeKey);
                                                                        $fallbackRequired =
                                                                            ($tour->allocation_requirement ?? 'none') === 'snorkeling'
                                                                            ? $typeKey === 'vehicle'
                                                                            : (($tour->allocation_requirement ?? 'none') === 'land_activity'
                                                                                ? in_array($typeKey, ['vehicle', 'guide_driver'], true)
                                                                                : false);
                                                                        $isRequired = (bool) ($row->is_required ?? $fallbackRequired);
                                                                        $minUnits = max(1, (int) ($row->min_units ?? 1));
                                                                    @endphp
                                                                    <div class="row g-2 align-items-center mb-2">
                                                                        <div class="col-md-7">
                                                                            <div class="form-check">
                                                                                <input type="hidden"
                                                                                    name="requirements[{{ $typeKey }}][is_required]"
                                                                                    value="0">
                                                                                <input class="form-check-input"
                                                                                    type="checkbox"
                                                                                    id="tourEditReq{{ $tour->id }}_{{ $typeKey }}_required"
                                                                                    name="requirements[{{ $typeKey }}][is_required]"
                                                                                    value="1"
                                                                                    {{ $isRequired ? 'checked' : '' }}>
                                                                                <label class="form-check-label"
                                                                                    for="tourEditReq{{ $tour->id }}_{{ $typeKey }}_required">
                                                                                    {{ __('translation.resource-required-label', ['label' => $typeLabel]) }}
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-5">
                                                                            <input type="number" class="form-control"
                                                                                id="tourEditReq{{ $tour->id }}_{{ $typeKey }}_min_units"
                                                                                name="requirements[{{ $typeKey }}][min_units]"
                                                                                min="1" max="1000"
                                                                                value="{{ $minUnits }}"
                                                                                placeholder="Min unit">
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_active"
                                                                    id="tourEditActive{{ $tour->id }}" value="1"
                                                                    {{ $tour->is_active ? 'checked' : '' }}>
                                                                <label class="form-check-label"
                                                                    for="tourEditActive{{ $tour->id }}">{{ __('translation.active') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light"
                                                                data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                                                            <button type="submit" class="btn btn-primary">{{ __('translation.save-changes') }}</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                {{ __('translation.showing-range-of-total', ['from' => $tours->firstItem(), 'to' => $tours->lastItem(), 'total' => $tours->total()]) }}
                            </small>
                            {{ $tours->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            const applyRequirementPreset = function(prefix, profile) {
                const profileValue = String(profile || 'none').toLowerCase();
                const map = {
                    vehicle: profileValue === 'snorkeling' || profileValue === 'land_activity',
                    guide_driver: profileValue === 'land_activity',
                    equipment: false,
                };
                ['vehicle', 'guide_driver', 'equipment'].forEach(function(typeKey) {
                    const requiredEl = document.getElementById(prefix + '_' + typeKey + '_required');
                    const minUnitsEl = document.getElementById(prefix + '_' + typeKey + '_min_units');
                    if (requiredEl) {
                        requiredEl.checked = !!map[typeKey];
                    }
                    if (minUnitsEl && (!minUnitsEl.value || Number(minUnitsEl.value) < 1)) {
                        minUnitsEl.value = '1';
                    }
                });
            };

            const initToursInteraction = function() {
                const ajaxContainer = document.getElementById('toursAjaxContainer');
                const tenantSwitcher = document.getElementById('tourTenantSwitcher');

                if (tenantSwitcher && !tenantSwitcher.dataset.ajaxBound) {
                    tenantSwitcher.dataset.ajaxBound = '1';
                    tenantSwitcher.addEventListener('change', function() {
                        const nextUrl = "{{ route('tours.index') }}" + '?tenant=' + encodeURIComponent(this.value);
                        tenantSwitcher.disabled = true;
                        if (ajaxContainer) {
                            ajaxContainer.classList.add('opacity-75');
                        }

                        fetch(nextUrl, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                if (!response.ok) {
                                    throw new Error('Failed to load tours page.');
                                }
                                return response.text();
                            })
                            .then(function(html) {
                                const doc = new DOMParser().parseFromString(html, 'text/html');
                                const freshContainer = doc.getElementById('toursAjaxContainer');
                                if (!freshContainer || !ajaxContainer) {
                                    throw new Error('Missing refreshed tours content.');
                                }
                                ajaxContainer.outerHTML = freshContainer.outerHTML;
                                history.pushState({}, '', nextUrl);
                                initToursInteraction();
                            })
                            .catch(function() {
                                window.location.href = nextUrl;
                            })
                            .finally(function() {
                                tenantSwitcher.disabled = false;
                                if (ajaxContainer) {
                                    ajaxContainer.classList.remove('opacity-75');
                                }
                            });
                    });
                }

                document.querySelectorAll('.js-apply-profile-preset').forEach(function(btn) {
                    if (btn.dataset.presetBound) {
                        return;
                    }
                    btn.dataset.presetBound = '1';
                    btn.addEventListener('click', function() {
                        const prefix = btn.getAttribute('data-prefix');
                        if (!prefix) {
                            return;
                        }
                        const select = document.querySelector('.js-allocation-profile-select[data-prefix="' + prefix + '"]');
                        applyRequirementPreset(prefix, select ? select.value : 'none');
                    });
                });
            };

            initToursInteraction();
        })();
    </script>
@endsection
