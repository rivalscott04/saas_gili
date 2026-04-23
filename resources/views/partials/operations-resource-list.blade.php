<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th>{{ __('translation.type') }}</th>
                <th>{{ __('translation.name') }}</th>
                <th>{{ __('translation.code') }}</th>
                <th>{{ __('translation.capacity') }}</th>
                <th>{{ __('translation.used-by-tour') }}</th>
                <th>{{ __('translation.status') }}</th>
                <th class="text-end">{{ __('translation.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resources as $item)
                <tr>
                    <td>{{ $resourceTypes[$item->resource_type] ?? $item->resource_type }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->reference_code ?: '-' }}</td>
                    <td>{{ $item->capacity ?: '-' }}</td>
                    <td>
                        @php
                            $linkedTours = (array) ($tourRequirementsByResourceType[$item->resource_type] ?? []);
                        @endphp
                        @if ($linkedTours === [])
                            <span class="text-muted">-</span>
                        @else
                            @foreach ($linkedTours as $tourInfo)
                                @php
                                    $tourUrl = $showTenantSwitcher
                                        ? route('tours.index', ['tenant' => $tenant->code])
                                        : route('tours.index');
                                @endphp
                                <a href="{{ $tourUrl }}"
                                    class="badge text-decoration-none {{ !empty($tourInfo['is_active']) ? 'bg-info-subtle text-info' : 'bg-light text-muted' }} me-1 mb-1"
                                    title="{{ __('translation.open-tour-management-help') }}">
                                    {{ $tourInfo['tour_name'] ?? 'Tour' }} (min {{ $tourInfo['min_units'] ?? 1 }})
                                </a>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('operations-resources.block-out', $item) }}">
                            @csrf
                            @if ($showTenantSwitcher)
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                            @endif
                            <input type="hidden" name="blocked_from" value="{{ now()->format('Y-m-d H:i:s') }}">
                            <input type="hidden" name="block_reason" value="Set from operations panel">
                            <select name="status"
                                class="form-select form-select-sm border-0 fw-semibold {{ $item->status === 'blocked' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}"
                                style="width: 130px; border-radius: .5rem;"
                                onchange="this.form.requestSubmit()">
                                <option value="available" {{ $item->status === 'available' ? 'selected' : '' }}>{{ __('translation.available') }}</option>
                                <option value="blocked" {{ $item->status === 'blocked' ? 'selected' : '' }}>{{ __('translation.blocked') }}</option>
                            </select>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="hstack gap-1 justify-content-end">
                            <button type="button" class="btn btn-sm btn-soft-info"
                                data-bs-toggle="modal" data-bs-target="#editResourceModal{{ $item->id }}">
                                {{ __('translation.edit') }}
                            </button>
                            <form method="POST"
                                action="{{ route('operations-resources.destroy', $item) }}"
                                onsubmit="return confirm(@js(__('translation.confirm-delete-resource')));">
                                @csrf
                                @if ($showTenantSwitcher)
                                    <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                @endif
                                <button type="submit" class="btn btn-sm btn-soft-danger">{{ __('translation.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-muted">{{ __('translation.no-resource-data') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $resources->links() }}
</div>

@foreach ($resources as $item)
    <div class="modal fade" id="editResourceModal{{ $item->id }}" tabindex="-1"
        aria-labelledby="editResourceModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResourceModalLabel{{ $item->id }}">{{ __('translation.edit-resource') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('operations-resources.update', $item) }}">
                        @csrf
                        @if ($showTenantSwitcher)
                            <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                        @endif
                        <input type="hidden" name="sync_tour_usage" value="1">
                        @php
                            $selectedTourIds = collect((array) ($tourRequirementsByResourceType[$item->resource_type] ?? []))
                                ->pluck('tour_id')
                                ->map(fn ($tourId) => (int) $tourId)
                                ->all();
                        @endphp
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label">{{ __('translation.resource-type') }}</label>
                                <select class="form-select" name="resource_type" required>
                                    @foreach ($resourceTypes as $resourceTypeKey => $resourceTypeLabel)
                                        <option value="{{ $resourceTypeKey }}"
                                            {{ $item->resource_type === $resourceTypeKey ? 'selected' : '' }}>
                                            {{ $resourceTypeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">{{ __('translation.name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $item->name }}" required>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">{{ __('translation.reference-code') }}</label>
                                <input type="text" class="form-control" name="reference_code"
                                    value="{{ $item->reference_code }}" required>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label">{{ __('translation.capacity') }}</label>
                                <input type="number" class="form-control" min="1" name="capacity"
                                    value="{{ $item->capacity }}">
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label">{{ __('translation.notes') }}</label>
                                <textarea class="form-control" rows="3" name="notes">{{ $item->notes }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label">{{ __('translation.used-by-tour') }}</label>
                                @if (isset($availableTours) && $availableTours->isNotEmpty())
                                    <div class="border rounded p-3 bg-light-subtle"
                                        style="max-height: 220px; overflow-y: auto;">
                                        @foreach ($availableTours as $tourOption)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    id="resourceTour{{ $item->id }}_{{ $tourOption->id }}"
                                                    name="tour_ids[]" value="{{ $tourOption->id }}"
                                                    {{ in_array((int) $tourOption->id, $selectedTourIds, true) ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="resourceTour{{ $item->id }}_{{ $tourOption->id }}">
                                                    {{ $tourOption->name }}
                                                    @if (! $tourOption->is_active)
                                                        <span class="text-muted">(arsip)</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Menentukan tour mana yang mewajibkan tipe resource ini saat alokasi/konfirmasi.
                                    </small>
                                @else
                                    <div class="alert alert-light border mb-0">
                                        Belum ada master tour. Tambahkan tour dulu untuk mengatur keterkaitan resource ini.
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{ __('translation.save-changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
