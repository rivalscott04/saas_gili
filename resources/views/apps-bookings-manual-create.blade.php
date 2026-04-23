@extends('layouts.master')
@section('title')
    {{ __('translation.manual-booking') }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.tour-operations') }}
        @endslot
        @slot('title')
            {{ __('translation.create-manual-booking') }}
        @endslot
    @endcomponent

    <form method="POST" action="{{ route('bookings.manual.store') }}">
        @csrf
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body checkout-tab">
                        <div class="step-arrow-nav mt-n3 mx-n3 mb-3">
                            <ul class="nav nav-pills nav-justified custom-nav" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 active" id="pills-tour-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-tour" type="button" role="tab" aria-controls="pills-tour"
                                        aria-selected="true"><i
                                            class="ri-map-pin-time-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        {{ __('translation.tour-and-schedule') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-guest-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-guest" type="button" role="tab" aria-controls="pills-guest"
                                        aria-selected="false"><i
                                            class="ri-user-2-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        {{ __('translation.guest-data') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-ops-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-ops" type="button" role="tab" aria-controls="pills-ops"
                                        aria-selected="false"><i
                                            class="ri-file-list-3-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        {{ __('translation.logistics-and-notes') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-review-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-review" type="button" role="tab"
                                        aria-controls="pills-review" aria-selected="false"><i
                                            class="ri-checkbox-circle-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>{{ __('translation.review-and-save') }}</button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pills-tour" role="tabpanel"
                                aria-labelledby="pills-tour-tab">
                                <div>
                                    <h5 class="mb-1">{{ __('translation.product-and-departure') }}</h5>
                                    <p class="text-muted mb-4">{{ __('translation.product-and-departure-help') }}</p>
                                </div>
                                @if ($tenantOptions->isNotEmpty())
                                    <div class="mb-3">
                                        <label class="form-label" for="on_behalf_tenant_id">{{ __('translation.tenant') }} <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select w-100" id="on_behalf_tenant_id"
                                            name="on_behalf_tenant_id" required>
                                            <option value="">{{ __('translation.select-tenant-placeholder') }}</option>
                                            @foreach ($tenantOptions as $tenant)
                                                <option value="{{ $tenant->id }}"
                                                    {{ (string) old('on_behalf_tenant_id', '') === (string) $tenant->id ? 'selected' : '' }}>
                                                    {{ $tenant->name }} ({{ $tenant->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label" for="tour_id">{{ __('translation.master-tour-package') }} <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select w-100" id="tour_id" name="tour_id" required>
                                        <option value="">{{ __('translation.select-tour-placeholder') }}</option>
                                        @php
                                            $tourGroups = ($tourOptions ?? collect())->groupBy('tenant_id');
                                            $showTourTenantOptgroups = ($tenantOptions ?? collect())->isNotEmpty();
                                        @endphp
                                        @foreach ($tourGroups as $tourTenantId => $toursInTenant)
                                            @if ($showTourTenantOptgroups)
                                                <optgroup
                                                    label="{{ optional($toursInTenant->first()->tenant)->name ?? __('translation.tenant-prefix', ['id' => $tourTenantId]) }}">
                                                    @foreach ($toursInTenant as $tourOption)
                                                        <option value="{{ $tourOption->id }}"
                                                            data-tenant-id="{{ $tourOption->tenant_id }}"
                                                            @selected((string) old('tour_id', '') === (string) $tourOption->id)>
                                                            {{ $tourOption->name }}{{ $tourOption->code ? ' ('.$tourOption->code.')' : '' }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @else
                                                @foreach ($toursInTenant as $tourOption)
                                                    <option value="{{ $tourOption->id }}"
                                                        data-tenant-id="{{ $tourOption->tenant_id }}"
                                                        @selected((string) old('tour_id', '') === (string) $tourOption->id)>
                                                        {{ $tourOption->name }}{{ $tourOption->code ? ' ('.$tourOption->code.')' : '' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </select>
                                    @if (($tourOptions ?? collect())->isEmpty())
                                        <div class="form-text text-warning mb-0">
                                            {{ __('translation.no-active-tour-master') }}
                                            <a href="{{ route('tours.index') }}">{{ __('translation.tour-management') }}</a>.
                                        </div>
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-5 col-lg-4">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="tour_start_at">{{ __('translation.date-and-time') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="tour_start_at"
                                                name="tour_start_at" value="{{ old('tour_start_at') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="participants">{{ __('translation.pax') }} <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="participants"
                                                name="participants" min="1" max="999"
                                                value="{{ old('participants', '2') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 status-col">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="status">{{ __('translation.status') }} <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select w-100" id="status" name="status" required>
                                                @foreach (['pending', 'confirmed', 'standby', 'cancelled'] as $val)
                                                    <option value="{{ $val }}"
                                                        {{ old('status', 'confirmed') === $val ? 'selected' : '' }}>
                                                        {{ __('translation.'.$val) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-3">
                                    <a href="{{ url('apps-bookings') }}" class="btn btn-light btn-label"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>{{ __('translation.back-to-list') }}</a>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-guest-tab"><i
                                            class="ri-user-2-line label-icon align-middle fs-16 ms-2"></i>{{ __('translation.next-to-guest-data') }}</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-guest" role="tabpanel"
                                aria-labelledby="pills-guest-tab">
                                <div>
                                    <h5 class="mb-1">{{ __('translation.guest-information') }}</h5>
                                    <p class="text-muted mb-4">{{ __('translation.guest-information-help') }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="customer_name">{{ __('translation.full-name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name"
                                        value="{{ old('customer_name') }}" required maxlength="255">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="customer_email">{{ __('translation.email') }} <span
                                                    class="text-muted">({{ __('translation.optional') }})</span></label>
                                            <input type="email" class="form-control" id="customer_email"
                                                name="customer_email" value="{{ old('customer_email') }}"
                                                maxlength="255">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="customer_phone">{{ __('translation.phone-whatsapp') }}</label>
                                            <input type="text" class="form-control" id="customer_phone"
                                                name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-tour-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>{{ __('translation.back') }}</button>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-ops-tab"><i
                                            class="ri-file-list-3-line label-icon align-middle fs-16 ms-2"></i>{{ __('translation.next') }}</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-ops" role="tabpanel" aria-labelledby="pills-ops-tab">
                                <div>
                                    <h5 class="mb-1">{{ __('translation.logistics-and-notes') }}</h5>
                                    <p class="text-muted mb-4">{{ __('translation.logistics-and-notes-help') }}</p>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="location">{{ __('translation.meeting-pickup') }}</label>
                                            <input type="text" class="form-control" id="location" name="location"
                                                value="{{ old('location') }}" maxlength="500"
                                                placeholder="{{ __('translation.meeting-pickup-placeholder') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="guide_name">{{ __('translation.guide') }} ({{ __('translation.optional') }})</label>
                                            <select class="form-select w-100" id="guide_name" name="guide_name">
                                                <option value="" @selected(old('guide_name', '') === '')>{{ __('translation.not-set-yet') }}</option>
                                                @php
                                                    $guideGroups = ($guideUsers ?? collect())->groupBy('tenant_id');
                                                    $showTenantOptgroups = ($tenantOptions ?? collect())->isNotEmpty();
                                                @endphp
                                                @foreach ($guideGroups as $tenantId => $guidesInTenant)
                                                    @if ($showTenantOptgroups)
                                                        <optgroup
                                                            label="{{ optional($guidesInTenant->first()->tenant)->name ?? __('translation.tenant-prefix', ['id' => $tenantId]) }}">
                                                            @foreach ($guidesInTenant as $guideUser)
                                                                <option value="{{ $guideUser->name }}"
                                                                    @selected(old('guide_name') === $guideUser->name)>{{ $guideUser->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        @foreach ($guidesInTenant as $guideUser)
                                                            <option value="{{ $guideUser->name }}"
                                                                @selected(old('guide_name') === $guideUser->name)>{{ $guideUser->name }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if (($guideUsers ?? collect())->isEmpty())
                                                <div class="form-text text-warning mb-0">{{ __('translation.no-guide-role-user') }}
                                                    <strong>{{ __('translation.guide') }}</strong> {{ __('translation.in-this-tenant-add-via-tenant-users') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if (($canViewRevenue ?? false) === true)
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="net_amount">{{ __('translation.total-net-price-idr') }}</label>
                                                <input type="number" step="1" min="0" class="form-control" id="net_amount"
                                                    name="net_amount" value="{{ old('net_amount', '0') }}"
                                                    inputmode="numeric">
                                                <div class="form-text">{{ __('translation.total-net-price-help') }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="channel_order_id">{{ __('translation.reference-number') }}
                                                    ({{ __('translation.optional') }})</label>
                                                <input type="text" class="form-control" id="channel_order_id"
                                                    name="channel_order_id" value="{{ old('channel_order_id') }}"
                                                    maxlength="255" placeholder="{{ __('translation.reference-number-placeholder') }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label class="form-label" for="notes">{{ __('translation.guest-notes') }}</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"
                                        maxlength="5000" placeholder="{{ __('translation.guest-notes-placeholder') }}">{{ old('notes') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="internal_notes">{{ __('translation.internal-notes') }}</label>
                                    <textarea class="form-control" id="internal_notes" name="internal_notes" rows="2"
                                        maxlength="5000">{{ old('internal_notes') }}</textarea>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-guest-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>{{ __('translation.back') }}</button>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-review-tab"><i
                                            class="ri-checkbox-circle-line label-icon align-middle fs-16 ms-2"></i>{{ __('translation.review') }}</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-review" role="tabpanel"
                                aria-labelledby="pills-review-tab">
                                <div class="text-center py-3">
                                    <div class="mb-4">
                                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                            colors="primary:#0ab39c,secondary:#405189"
                                            style="width:100px;height:100px"></lord-icon>
                                    </div>
                                    <h5 class="mb-2">{{ __('translation.save-manual-booking') }}</h5>
                                    <p class="text-muted mb-0">{{ __('translation.save-manual-booking-help') }}</p>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4 justify-content-center flex-wrap">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-ops-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>{{ __('translation.back') }}</button>
                                    <button type="submit" class="btn btn-success btn-label">
                                        <i class="ri-save-line label-icon align-middle fs-16 me-2"></i>{{ __('translation.save-booking') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('translation.summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">{{ __('translation.tour') }}</td>
                                        <td class="text-end fw-medium">{{ __('translation.fill-in-step-1') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('translation.guest') }}</td>
                                        <td class="text-end fw-medium">{{ __('translation.step-2') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">{{ __('translation.logistics-and-notes') }}</td>
                                        <td class="text-end fw-medium">{{ __('translation.step-3') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info border-0 mt-3 mb-0" role="alert">
                            <i class="ri-information-line me-1 align-middle"></i>
                            {{ __('translation.manual-booking-customer-match-help') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <style>
        #pills-tour .status-col > div {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        #pills-tour .status-col .form-label {
            margin-left: 4rem !important;
        }
    </style>
    <script src="{{ URL::asset('build/js/pages/ecommerce-product-checkout.init.js') }}"></script>
    <script>
        (function () {
            var form = document.querySelector('.checkout-tab');
            if (!form) {
                return;
            }
            var validationI18n = {
                required: @json(__('translation.validation-required-field')),
                invalidEmail: @json(__('translation.validation-invalid-email')),
                invalidNumber: @json(__('translation.validation-invalid-number')),
                field: @json(__('translation.field')),
            };

            function getFieldLabel(el) {
                var id = el.getAttribute('id');
                if (!id) {
                    return '';
                }
                var label = form.querySelector('label[for="' + id + '"]');
                if (!label) {
                    return '';
                }
                return (label.textContent || '')
                    .replace('*', '')
                    .replace('(' + @json(__('translation.optional')) + ')', '')
                    .trim();
            }

            function getValidationMessage(el) {
                if (!el || !el.validity) {
                    return '';
                }
                var label = getFieldLabel(el);
                if (el.validity.valueMissing) {
                    return validationI18n.required.replace(':field', label || validationI18n.field);
                }
                if (el.validity.typeMismatch && el.type === 'email') {
                    return validationI18n.invalidEmail;
                }
                if (el.validity.badInput) {
                    return validationI18n.invalidNumber;
                }
                return '';
            }

            function applyValidationHooks(container) {
                if (!container) {
                    return;
                }
                var controls = container.querySelectorAll('input, select, textarea');
                controls.forEach(function (el) {
                    if (!el.willValidate) {
                        return;
                    }
                    el.addEventListener('invalid', function () {
                        var message = getValidationMessage(el);
                        if (message) {
                            el.setCustomValidity(message);
                        }
                    });
                    el.addEventListener('input', function () {
                        el.setCustomValidity('');
                    });
                    el.addEventListener('change', function () {
                        el.setCustomValidity('');
                    });
                });
            }

            function validatePane(pane) {
                if (!pane) {
                    return true;
                }
                var controls = pane.querySelectorAll('input, select, textarea');
                for (var i = 0; i < controls.length; i++) {
                    var el = controls[i];
                    if (!el.willValidate) {
                        continue;
                    }
                    if (!el.checkValidity()) {
                        el.reportValidity();
                        return false;
                    }
                }
                return true;
            }

            applyValidationHooks(form);

            // Blokir "Lanjut" kalau tab aktif belum valid (Velzon .nexttab hanya .click() tanpa cek form).
            form.querySelectorAll('.nexttab').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    var pane = form.querySelector('.tab-pane.active');
                    if (!validatePane(pane)) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);
            });

            // Blokir loncat tab lewat pill kalau melewati langkah yang wajib diisi.
            var nav = form.querySelector('.custom-nav');
            if (nav) {
                nav.addEventListener('show.bs.tab', function (e) {
                    var tabs = Array.from(nav.querySelectorAll('button[data-bs-toggle="pill"]'));
                    var toBtn = e.target;
                    var fromBtn = e.relatedTarget;
                    var toIdx = tabs.indexOf(toBtn);
                    if (toIdx < 0) {
                        return;
                    }
                    var fromIdx = fromBtn ? tabs.indexOf(fromBtn) : 0;
                    if (fromIdx < 0) {
                        fromIdx = 0;
                    }
                    if (toIdx <= fromIdx) {
                        return;
                    }
                    for (var i = fromIdx; i < toIdx; i++) {
                        var sel = tabs[i].getAttribute('data-bs-target');
                        var pane = sel ? form.querySelector(sel) : null;
                        if (!validatePane(pane)) {
                            e.preventDefault();
                            return;
                        }
                    }
                });
            }

            var tenantSelect = document.getElementById('on_behalf_tenant_id');
            var tourSelect = document.getElementById('tour_id');
            if (tenantSelect && tourSelect) {
                var syncToursByTenant = function () {
                    var tenantId = tenantSelect.value;
                    var selectedStillValid = false;
                    tourSelect.querySelectorAll('option[data-tenant-id]').forEach(function (option) {
                        var allowed = tenantId === '' || option.getAttribute('data-tenant-id') === tenantId;
                        option.hidden = !allowed;
                        if (!allowed && option.selected) {
                            option.selected = false;
                        }
                        if (allowed && option.selected) {
                            selectedStillValid = true;
                        }
                    });
                    if (!selectedStillValid) {
                        tourSelect.value = '';
                    }
                };

                tenantSelect.addEventListener('change', syncToursByTenant);
                syncToursByTenant();
            }
        })();
    </script>
@endsection
