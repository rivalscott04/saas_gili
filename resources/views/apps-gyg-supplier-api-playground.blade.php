@extends('layouts.master')

@section('title')
    {{ __('translation.gyg-supplier-api-playground') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.sales-channels') }}
        @endslot
        @slot('title')
            {{ __('translation.gyg-supplier-api-playground') }}
        @endslot
    @endcomponent

    <div class="alert alert-warning border-0 mb-4" role="alert">
        <strong>{{ __('translation.gyg-playground-warning-title') }}</strong>
        {{ __('translation.gyg-playground-warning-body') }}
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.gyg-playground-connection') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="pg_base_url">{{ __('translation.gyg-playground-base-url') }}</label>
                        <input type="url" class="form-control" id="pg_base_url" name="base_url"
                            placeholder="https://supplier.example.com"
                            value="{{ old('base_url', config('gyg_supplier_playground.default_gyg_supplier_api_base_url', config('app.url'))) }}" autocomplete="off">
                        <div class="form-text">{{ __('translation.gyg-playground-base-url-help') }}</div>
                        <button type="button" class="btn btn-sm btn-soft-primary mt-2" id="pg_fill_gyg_host">{{ __('translation.gyg-playground-set-gyg-host-url') }}</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pg_auth_user">{{ __('translation.gyg-playground-basic-user') }}</label>
                        <input type="text" class="form-control" id="pg_auth_user" autocomplete="username">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="pg_auth_password">{{ __('translation.gyg-playground-basic-password') }}</label>
                        <input type="password" class="form-control" id="pg_auth_password" autocomplete="current-password">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header align-items-center d-flex border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs flex-grow-1" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-gyg-host" role="tab">{{ __('translation.gyg-tab-gyg-host-api') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-availability" role="tab">{{ __('translation.gyg-tab-availability') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-reservations" role="tab">{{ __('translation.gyg-tab-reservations') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-bookings" role="tab">{{ __('translation.gyg-tab-bookings') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-products" role="tab">{{ __('translation.gyg-tab-products') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-notify" role="tab">{{ __('translation.gyg-tab-notify') }}</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body tab-content">
                    <div class="tab-pane fade show active" id="tab-gyg-host" role="tabpanel">
                        <p class="text-muted small">{{ __('translation.gyg-tab-gyg-host-intro') }}</p>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-list-deals') }}</h6>
                        <div class="row g-2 align-items-end mb-4">
                            <div class="col-md-6">
                                <label class="form-label">externalProductId</label>
                                <input type="text" class="form-control" id="gyg_deals_externalProductId" value="PPYM1U" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="gyg_host_get_deals">
                                    <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="text-danger mb-2">{{ __('translation.gyg-host-delete-deal') }}</h6>
                        <div class="row g-2 align-items-end mb-4">
                            <div class="col-md-6">
                                <label class="form-label">dealId</label>
                                <input type="text" class="form-control" id="gyg_deal_delete_id" value="36457" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-soft-danger btn-sm pg-send" data-operation="gyg_host_delete_deal">
                                    <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-patch-activate') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">gygOptionId</label>
                                <input type="text" class="form-control" id="gyg_patch_gygOptionId" value="3764930" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">externalProductId <span class="text-muted">(body)</span></label>
                                <input type="text" class="form-control" id="gyg_patch_externalProductId" value="prod123" autocomplete="off">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="gyg_host_patch_product_activate">
                                    <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-redeem-ticket') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">ticketCode</label>
                                <input type="text" class="form-control" id="gyg_rt_ticketCode" value="TICKET238" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="gyg_rt_gygBookingReference" value="GYG1B2D34GHI" autocomplete="off">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="gyg_host_post_redeem_ticket">
                                    <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-redeem-booking') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="gyg_rb_gygBookingReference" value="GYG1B2D34GHI" autocomplete="off">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="gyg_host_post_redeem_booking">
                                    <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                </button>
                            </div>
                        </div>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-post-deals') }}</h6>
                        <label class="form-label">{{ __('translation.gyg-field-json-body') }}</label>
                        <textarea class="form-control font-monospace small mb-2" id="gyg_body_post_deals" rows="10" spellcheck="false">{
  "data": {
    "externalProductId": "PPYM1U",
    "dealName": "Last minute deal",
    "dateRange": { "start": "2023-08-21", "end": "2023-08-31" },
    "dealType": "last_minute",
    "maxVacancies": 10,
    "discountPercentage": 10.5,
    "noticePeriodDays": 3
  }
}</textarea>
                        <button type="button" class="btn btn-primary btn-sm pg-send mb-4" data-operation="gyg_host_post_deals">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-notify-availability') }}</h6>
                        <label class="form-label">{{ __('translation.gyg-field-json-body') }}</label>
                        <textarea class="form-control font-monospace small mb-2" id="gyg_body_notify_availability" rows="10" spellcheck="false">{
  "data": {
    "productId": "prod123",
    "availabilities": [
      { "dateTime": "2020-12-01T10:00:00+02:00", "vacancies": 0 },
      { "dateTime": "2020-12-01T15:00:00+02:00", "vacancies": 1 }
    ]
  }
}</textarea>
                        <button type="button" class="btn btn-primary btn-sm pg-send mb-4" data-operation="gyg_host_post_notify_availability_update">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>

                        <h6 class="text-primary mb-2">{{ __('translation.gyg-host-post-suppliers') }}</h6>
                        <label class="form-label">{{ __('translation.gyg-field-json-body') }}</label>
                        <textarea class="form-control font-monospace small mb-2" id="gyg_body_post_suppliers" rows="12" spellcheck="false">{
  "data": {
    "externalSupplierId": "12345XYZ",
    "firstName": "John",
    "lastName": "Doe",
    "legalCompanyName": "Example LLC",
    "websiteUrl": "https://example.com",
    "country": "USA",
    "currency": "USD",
    "email": "contact@example.com",
    "legalStatus": "company"
  }
}</textarea>
                        <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="gyg_host_post_suppliers">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>
                    </div>

                    <div class="tab-pane fade" id="tab-availability" role="tabpanel">
                        <p class="text-muted small">GET <code>/1/get-availabilities/</code> — {{ __('translation.gyg-spec-query-params') }}</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">productId</label>
                                <input type="text" class="form-control" id="ga_productId" value="prod123">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">fromDateTime</label>
                                <input type="text" class="form-control" id="ga_from" value="2020-12-01T00:00:00+02:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">toDateTime</label>
                                <input type="text" class="form-control" id="ga_to" value="2020-12-01T23:59:59+02:00">
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="get_availabilities">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>
                    </div>

                    <div class="tab-pane fade" id="tab-reservations" role="tabpanel">
                        <p class="text-muted small mb-2">{{ __('translation.gyg-playground-form-hint') }}</p>

                        <h6 class="text-primary mb-3">POST <code>/1/reserve/</code></h6>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">productId</label>
                                <input type="text" class="form-control" id="rsv_productId" value="prod123">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">dateTime</label>
                                <input type="text" class="form-control" id="rsv_dateTime" value="2020-12-01T10:00:00+02:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="rsv_gygBookingReference" value="GYG189H3K1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygActivityReference <span class="text-muted">({{ __('translation.optional') }})</span></label>
                                <input type="text" class="form-control" id="rsv_gygActivityReference" value="">
                            </div>
                        </div>
                        <label class="form-label">{{ __('translation.gyg-field-booking-items') }}</label>
                        <div class="table-responsive border rounded mb-2">
                            <table class="table table-sm table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:9rem">{{ __('translation.gyg-field-category') }}</th>
                                        <th style="width:6rem">{{ __('translation.gyg-field-count') }}</th>
                                        <th style="width:8rem">{{ __('translation.gyg-field-group-size') }}</th>
                                        <th style="width:3rem"></th>
                                    </tr>
                                </thead>
                                <tbody id="rsv_bi_tbody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-soft-secondary btn-sm mb-3" id="rsv_bi_add">{{ __('translation.gyg-playground-add-line') }}</button>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="post_reserve">
                                <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                            </button>
                        </div>

                        <hr class="my-4">

                        <h6 class="text-danger mb-3">POST <code>/1/cancel-reservation/</code></h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">reservationReference</label>
                                <input type="text" class="form-control" id="crsv_reservationReference" value="res789">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="crsv_gygBookingReference" value="GYG189H3K1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygActivityReference <span class="text-muted">({{ __('translation.optional') }})</span></label>
                                <input type="text" class="form-control" id="crsv_gygActivityReference" value="">
                            </div>
                        </div>
                        <button type="button" class="btn btn-soft-danger btn-sm pg-send" data-operation="post_cancel_reservation">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>
                    </div>

                    <div class="tab-pane fade" id="tab-bookings" role="tabpanel">
                        <p class="text-muted small mb-2">{{ __('translation.gyg-playground-form-hint') }}</p>

                        <h6 class="text-primary mb-3">POST <code>/1/book/</code></h6>
                        <div class="row g-3 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">productId</label>
                                <input type="text" class="form-control" id="bk_productId" value="prod123">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">reservationReference</label>
                                <input type="text" class="form-control" id="bk_reservationReference" value="res789">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="bk_gygBookingReference" value="GYG1B2D34GHI">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygActivityReference <span class="text-muted">({{ __('translation.optional') }})</span></label>
                                <input type="text" class="form-control" id="bk_gygActivityReference" value="">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">currency</label>
                                <input type="text" class="form-control" id="bk_currency" value="USD" maxlength="3">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">dateTime</label>
                                <input type="text" class="form-control" id="bk_dateTime" value="2020-12-01T10:00:00+02:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">language <span class="text-muted">({{ __('translation.optional') }})</span></label>
                                <input type="text" class="form-control" id="bk_language" value="" maxlength="8" placeholder="en">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">travelerHotel <span class="text-muted">({{ __('translation.optional') }})</span></label>
                                <input type="text" class="form-control" id="bk_travelerHotel" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">comment</label>
                                <textarea class="form-control" id="bk_comment" rows="2">Please confirm your meeting point
Hotel ABC.</textarea>
                            </div>
                        </div>
                        <label class="form-label">{{ __('translation.gyg-field-booking-items') }}</label>
                        <div class="table-responsive border rounded mb-2">
                            <table class="table table-sm table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:9rem">{{ __('translation.gyg-field-category') }}</th>
                                        <th style="width:6rem">{{ __('translation.gyg-field-count') }}</th>
                                        <th style="width:8rem">{{ __('translation.gyg-field-group-size') }}</th>
                                        <th style="width:9rem">{{ __('translation.gyg-field-retail-price') }}</th>
                                        <th style="width:3rem"></th>
                                    </tr>
                                </thead>
                                <tbody id="bk_bi_tbody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-soft-secondary btn-sm mb-3" id="bk_bi_add">{{ __('translation.gyg-playground-add-line') }}</button>

                        <label class="form-label">{{ __('translation.gyg-field-addon-items') }} <span class="text-muted">({{ __('translation.optional') }})</span></label>
                        <div class="table-responsive border rounded mb-2">
                            <table class="table table-sm table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:7rem">addonType</th>
                                        <th>{{ __('translation.description') }}</th>
                                        <th style="width:6rem">{{ __('translation.gyg-field-count') }}</th>
                                        <th style="width:9rem">{{ __('translation.gyg-field-retail-price') }}</th>
                                        <th style="width:3rem"></th>
                                    </tr>
                                </thead>
                                <tbody id="bk_addon_tbody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-soft-secondary btn-sm mb-3" id="bk_addon_add">{{ __('translation.gyg-playground-add-line') }}</button>

                        <label class="form-label">{{ __('translation.gyg-field-traveler') }}</label>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label small text-muted">firstName</label>
                                <input type="text" class="form-control" id="bk_t_firstName" value="John">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">lastName</label>
                                <input type="text" class="form-control" id="bk_t_lastName" value="Smith">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">email</label>
                                <input type="email" class="form-control" id="bk_t_email" value="john@john-smith.com">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">phoneNumber</label>
                                <input type="text" class="form-control" id="bk_t_phone" value="+49 030 1231231">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm pg-send mb-4" data-operation="post_book">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>

                        <hr class="my-4">

                        <h6 class="text-danger mb-3">POST <code>/1/cancel-booking/</code></h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">bookingReference</label>
                                <input type="text" class="form-control" id="cbk_bookingReference" value="bk456">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">gygBookingReference</label>
                                <input type="text" class="form-control" id="cbk_gygBookingReference" value="GYG1B2D34GHI">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">productId</label>
                                <input type="text" class="form-control" id="cbk_productId" value="bk456">
                            </div>
                        </div>
                        <button type="button" class="btn btn-soft-danger btn-sm pg-send" data-operation="post_cancel_booking">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>
                    </div>

                    <div class="tab-pane fade" id="tab-products" role="tabpanel">
                        <div class="mb-4">
                            <p class="text-muted small mb-2">GET <code>/1/suppliers/{supplierId}/products/</code></p>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">supplierId</label>
                                    <input type="text" class="form-control" id="pp_supplierId" value="Abc123">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="get_supplier_products">
                                        <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <p class="text-muted small mb-2">GET <code>/1/products/{productId}</code></p>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">productId</label>
                                    <input type="text" class="form-control" id="pd_productId" value="PPYM1U">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="get_product_details">
                                        <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <p class="text-muted small mb-2">GET <code>/1/products/{productId}/pricing-categories/</code></p>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-6">
                                    <label class="form-label">productId</label>
                                    <input type="text" class="form-control" id="pc_productId" value="prod123">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="get_pricing_categories">
                                        <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <p class="text-muted small mb-2">GET <code>/1/products/{productId}/addons/</code></p>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label">productId</label>
                                    <input type="text" class="form-control" id="ad_productId" value="PPYM1U">
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="get_addons">
                                        <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-notify" role="tabpanel">
                        <p class="text-muted small mb-2">{{ __('translation.gyg-playground-form-hint') }}</p>
                        <h6 class="text-primary mb-3">POST <code>/1/notify/</code></h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">notificationType</label>
                                <select class="form-select" id="ntf_notificationType">
                                    <option value="PRODUCT_DEACTIVATION" selected>PRODUCT_DEACTIVATION</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">dateTime</label>
                                <input type="text" class="form-control" id="ntf_dateTime" value="2022-09-28T16:34:33+02:00">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">description</label>
                                <input type="text" class="form-control" id="ntf_description" value="GetYourGuide - Product ID - prod123 is deactivated due to API Error">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">supplierName</label>
                                <input type="text" class="form-control" id="ntf_supplierName" value="Mock Travel Agency">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">integrationName</label>
                                <input type="text" class="form-control" id="ntf_integrationName" value="Mock Reservation System">
                            </div>
                        </div>

                        <p class="fw-medium mb-2">productDetails</p>
                        <div class="row g-3 mb-3 ps-2 border-start border-2">
                            <div class="col-md-3">
                                <label class="form-label small">productId</label>
                                <input type="text" class="form-control" id="ntf_pd_productId" value="prod123">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">gygTourOptionId</label>
                                <input type="text" class="form-control" id="ntf_pd_gygTourOptionId" value="123456">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">tourTitle</label>
                                <input type="text" class="form-control" id="ntf_pd_tourTitle" value="Mock Walking Tour in a Mock City">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">tourOptionTitle</label>
                                <input type="text" class="form-control" id="ntf_pd_tourOptionTitle" value="Mock Walking Tour in a Mock City">
                            </div>
                        </div>

                        <p class="fw-medium mb-2">{{ __('translation.gyg-field-notification-details') }}</p>
                        <div class="row g-3 mb-3 ps-2 border-start border-2">
                            <div class="col-md-3">
                                <label class="form-label small">failedRequestType</label>
                                <input type="text" class="form-control" id="ntf_nd_failedRequestType" value="reserve">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">deactivationTimestamp</label>
                                <input type="text" class="form-control" id="ntf_nd_deactivationTimestamp" value="2022-09-28T14:54:10+02:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">gygBookingReference</label>
                                <input type="text" class="form-control" id="ntf_nd_gygBookingReference" value="GYG2RA9W579V">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">travellers</label>
                                <input type="text" class="form-control" id="ntf_nd_travellers" value="5x adult, 1x child, ">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">errorType</label>
                                <input type="text" class="form-control" id="ntf_nd_errorType" value="no_availability">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">errorMessageReceived</label>
                                <input type="text" class="form-control" id="ntf_nd_errorMessageReceived" value="Requested timeslot is not available">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">vacanciesReceivedAfterGetAvailability</label>
                                <input type="text" class="form-control" id="ntf_nd_vacancies" value="99">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">activityStartTime</label>
                                <input type="text" class="form-control" id="ntf_nd_activityStartTime" value="2022-09-30T00:00:00-04:00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">activityTimeZone</label>
                                <input type="text" class="form-control" id="ntf_nd_activityTimeZone" value="America/New_York">
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm pg-send" data-operation="post_notify">
                            <i class="ri-send-plane-fill me-1"></i> {{ __('translation.gyg-send-request') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ __('translation.gyg-playground-response') }}</h5>
                    <span class="badge bg-secondary-subtle text-secondary" id="pg_meta"></span>
                </div>
                <div class="card-body">
                    <details class="mb-3" id="pg_last_request_wrap">
                        <summary style="cursor: pointer">{{ __('translation.gyg-playground-last-request') }}</summary>
                        <pre class="bg-light border rounded p-3 mt-2 mb-0 font-monospace small" style="max-height: 14rem; overflow: auto;" id="pg_last_request"></pre>
                    </details>
                    <pre class="bg-light border rounded p-3 mb-0 font-monospace small" style="max-height: 28rem; overflow: auto;" id="pg_output">{{ __('translation.gyg-playground-response-placeholder') }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const invokeUrl = @json(route('gyg-supplier-playground.invoke'));
            const defaultGygHostUrl = @json(config('gyg_supplier_playground.default_gyg_supplier_api_base_url', 'https://supplier-api.getyourguide.com/sandbox'));

            document.getElementById('pg_fill_gyg_host').addEventListener('click', function () {
                document.getElementById('pg_base_url').value = defaultGygHostUrl;
            });

            const CATEGORY_OPTIONS = ['ADULT', 'CHILD', 'YOUTH', 'INFANT', 'SENIOR', 'STUDENT', 'EU_CITIZEN', 'MILITARY', 'EU_CITIZEN_STUDENT', 'GROUP'];
            const ADDON_TYPES = ['FOOD', 'DRINKS', 'SAFETY', 'TRANSPORT', 'DONATION', 'OTHERS'];

            function categorySelectHtml(selected) {
                let h = '';
                CATEGORY_OPTIONS.forEach(function (c) {
                    h += '<option value="' + c + '"' + (c === selected ? ' selected' : '') + '>' + c + '</option>';
                });
                return h;
            }

            function addonTypeSelectHtml(selected) {
                let h = '';
                ADDON_TYPES.forEach(function (c) {
                    h += '<option value="' + c + '"' + (c === selected ? ' selected' : '') + '>' + c + '</option>';
                });
                return h;
            }

            function syncGroupRow(tr) {
                const cat = tr.querySelector('.pg-bi-cat');
                if (!cat) return;
                const isGroup = cat.value === 'GROUP';
                const countInp = tr.querySelector('.pg-bi-count');
                const gsInp = tr.querySelector('.pg-bi-gsize');
                if (isGroup) {
                    countInp.value = '1';
                    countInp.readOnly = true;
                    countInp.classList.add('bg-light');
                    gsInp.disabled = false;
                    gsInp.required = true;
                } else {
                    countInp.readOnly = false;
                    countInp.classList.remove('bg-light');
                    gsInp.value = '';
                    gsInp.disabled = true;
                    gsInp.required = false;
                }
            }

            function newReserveBiRow(cat, count, groupSize) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td><select class="form-select form-select-sm pg-bi-cat">' + categorySelectHtml(cat) + '</select></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-bi-count" min="1" value="' + count + '"></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-bi-gsize" min="1" placeholder="—" disabled></td>' +
                    '<td><button type="button" class="btn btn-link btn-sm text-danger p-0 pg-bi-remove" title="{{ __('translation.gyg-playground-remove-line') }}">&times;</button></td>';
                const gs = tr.querySelector('.pg-bi-gsize');
                if (cat === 'GROUP' && groupSize) {
                    gs.disabled = false;
                    gs.value = String(groupSize);
                }
                tr.querySelector('.pg-bi-cat').addEventListener('change', function () { syncGroupRow(tr); });
                syncGroupRow(tr);
                return tr;
            }

            function newBookBiRow(cat, count, retail, groupSize) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td><select class="form-select form-select-sm pg-bi-cat">' + categorySelectHtml(cat) + '</select></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-bi-count" min="1" value="' + count + '"></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-bi-gsize" min="1" placeholder="—" disabled></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-bi-price" min="0" value="' + retail + '"></td>' +
                    '<td><button type="button" class="btn btn-link btn-sm text-danger p-0 pg-bi-remove" title="{{ __('translation.gyg-playground-remove-line') }}">&times;</button></td>';
                const gs = tr.querySelector('.pg-bi-gsize');
                if (cat === 'GROUP' && groupSize) {
                    gs.disabled = false;
                    gs.value = String(groupSize);
                }
                tr.querySelector('.pg-bi-cat').addEventListener('change', function () { syncGroupRow(tr); });
                syncGroupRow(tr);
                return tr;
            }

            function newAddonRow(type, desc, count, price) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td><select class="form-select form-select-sm pg-ad-type">' + addonTypeSelectHtml(type) + '</select></td>' +
                    '<td><input type="text" class="form-control form-control-sm pg-ad-desc" maxlength="50"></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-ad-count" min="1"></td>' +
                    '<td><input type="number" class="form-control form-control-sm pg-ad-price" min="0"></td>' +
                    '<td><button type="button" class="btn btn-link btn-sm text-danger p-0 pg-ad-remove" title="{{ __('translation.gyg-playground-remove-line') }}">&times;</button></td>';
                tr.querySelector('.pg-ad-desc').value = desc;
                tr.querySelector('.pg-ad-count').value = String(count);
                tr.querySelector('.pg-ad-price').value = String(price);
                return tr;
            }

            function wireRemove(tbody, btnClass) {
                tbody.addEventListener('click', function (e) {
                    const btn = e.target.closest(btnClass);
                    if (!btn) return;
                    const tr = btn.closest('tr');
                    if (tbody.querySelectorAll('tr').length <= 1) return;
                    tr.remove();
                });
            }

            const rsvTbody = document.getElementById('rsv_bi_tbody');
            rsvTbody.appendChild(newReserveBiRow('ADULT', 2, null));
            rsvTbody.appendChild(newReserveBiRow('CHILD', 1, null));
            document.getElementById('rsv_bi_add').addEventListener('click', function () {
                rsvTbody.appendChild(newReserveBiRow('ADULT', 1, null));
            });
            wireRemove(rsvTbody, '.pg-bi-remove');

            const bkTbody = document.getElementById('bk_bi_tbody');
            bkTbody.appendChild(newBookBiRow('ADULT', 2, 1560, null));
            bkTbody.appendChild(newBookBiRow('CHILD', 1, 1300, null));
            document.getElementById('bk_bi_add').addEventListener('click', function () {
                bkTbody.appendChild(newBookBiRow('ADULT', 1, 0, null));
            });
            wireRemove(bkTbody, '.pg-bi-remove');

            const bkAddonTbody = document.getElementById('bk_addon_tbody');
            document.getElementById('bk_addon_add').addEventListener('click', function () {
                bkAddonTbody.appendChild(newAddonRow('FOOD', 'Dinner at a local restaurant', 2, 1050));
            });
            wireRemove(bkAddonTbody, '.pg-ad-remove');

            function collectReserveBookingItems() {
                const items = [];
                rsvTbody.querySelectorAll('tr').forEach(function (tr) {
                    const cat = tr.querySelector('.pg-bi-cat').value;
                    const count = parseInt(tr.querySelector('.pg-bi-count').value, 10) || 0;
                    if (count < 1) return;
                    if (cat === 'GROUP') {
                        const gs = parseInt(tr.querySelector('.pg-bi-gsize').value, 10) || 0;
                        if (gs < 1) return;
                        items.push({ category: 'GROUP', count: 1, groupSize: gs });
                    } else {
                        items.push({ category: cat, count: count });
                    }
                });
                return items;
            }

            function collectBookBookingItems() {
                const items = [];
                bkTbody.querySelectorAll('tr').forEach(function (tr) {
                    const cat = tr.querySelector('.pg-bi-cat').value;
                    const count = parseInt(tr.querySelector('.pg-bi-count').value, 10) || 0;
                    const price = parseInt(tr.querySelector('.pg-bi-price').value, 10);
                    if (count < 1) return;
                    if (cat === 'GROUP') {
                        const gs = parseInt(tr.querySelector('.pg-bi-gsize').value, 10) || 0;
                        if (gs < 1) return;
                        items.push({ category: 'GROUP', count: 1, groupSize: gs, retailPrice: isNaN(price) ? 0 : price });
                    } else {
                        items.push({ category: cat, count: count, retailPrice: isNaN(price) ? 0 : price });
                    }
                });
                return items;
            }

            function collectAddonItems() {
                const items = [];
                bkAddonTbody.querySelectorAll('tr').forEach(function (tr) {
                    const t = tr.querySelector('.pg-ad-type').value;
                    const desc = tr.querySelector('.pg-ad-desc').value.trim();
                    const c = parseInt(tr.querySelector('.pg-ad-count').value, 10) || 0;
                    const p = parseInt(tr.querySelector('.pg-ad-price').value, 10);
                    if (c < 1) return;
                    const row = { addonType: t, count: c, retailPrice: isNaN(p) ? 0 : p };
                    if (desc !== '') row.addonDescription = desc;
                    items.push(row);
                });
                return items;
            }

            function val(id) {
                return document.getElementById(id).value.trim();
            }

            function buildBody(operation) {
                if (operation === 'post_reserve') {
                    const data = {
                        productId: val('rsv_productId'),
                        dateTime: val('rsv_dateTime'),
                        gygBookingReference: val('rsv_gygBookingReference'),
                        bookingItems: collectReserveBookingItems()
                    };
                    const act = val('rsv_gygActivityReference');
                    if (act !== '') data.gygActivityReference = act;
                    return JSON.stringify({ data: data });
                }
                if (operation === 'post_cancel_reservation') {
                    const data = {
                        reservationReference: val('crsv_reservationReference'),
                        gygBookingReference: val('crsv_gygBookingReference')
                    };
                    const act = val('crsv_gygActivityReference');
                    if (act !== '') data.gygActivityReference = act;
                    return JSON.stringify({ data: data });
                }
                if (operation === 'post_book') {
                    let comment = document.getElementById('bk_comment').value;
                    if (comment === '') comment = '\n';
                    const data = {
                        productId: val('bk_productId'),
                        reservationReference: val('bk_reservationReference'),
                        gygBookingReference: val('bk_gygBookingReference'),
                        currency: val('bk_currency') || 'USD',
                        dateTime: val('bk_dateTime'),
                        bookingItems: collectBookBookingItems(),
                        travelers: [{
                            firstName: val('bk_t_firstName'),
                            lastName: val('bk_t_lastName'),
                            email: val('bk_t_email'),
                            phoneNumber: val('bk_t_phone')
                        }],
                        comment: comment
                    };
                    const act = val('bk_gygActivityReference');
                    if (act !== '') data.gygActivityReference = act;
                    const lang = val('bk_language');
                    if (lang !== '') data.language = lang;
                    const hotel = val('bk_travelerHotel');
                    if (hotel !== '') data.travelerHotel = hotel;
                    const addons = collectAddonItems();
                    if (addons.length) data.addonItems = addons;
                    return JSON.stringify({ data: data });
                }
                if (operation === 'post_cancel_booking') {
                    return JSON.stringify({
                        data: {
                            bookingReference: val('cbk_bookingReference'),
                            gygBookingReference: val('cbk_gygBookingReference'),
                            productId: val('cbk_productId')
                        }
                    });
                }
                if (operation === 'post_notify') {
                    return JSON.stringify({
                        data: {
                            notificationType: val('ntf_notificationType'),
                            description: val('ntf_description'),
                            supplierName: val('ntf_supplierName'),
                            integrationName: val('ntf_integrationName'),
                            dateTime: val('ntf_dateTime'),
                            productDetails: {
                                productId: val('ntf_pd_productId'),
                                gygTourOptionId: val('ntf_pd_gygTourOptionId'),
                                tourTitle: val('ntf_pd_tourTitle'),
                                tourOptionTitle: val('ntf_pd_tourOptionTitle')
                            },
                            notificationDetails: {
                                failedRequestType: val('ntf_nd_failedRequestType'),
                                deactivationTimestamp: val('ntf_nd_deactivationTimestamp'),
                                gygBookingReference: val('ntf_nd_gygBookingReference'),
                                travellers: val('ntf_nd_travellers'),
                                errorType: val('ntf_nd_errorType'),
                                errorMessageReceived: val('ntf_nd_errorMessageReceived'),
                                vacanciesReceivedAfterGetAvailability: val('ntf_nd_vacancies'),
                                activityStartTime: val('ntf_nd_activityStartTime'),
                                activityTimeZone: val('ntf_nd_activityTimeZone')
                            }
                        }
                    });
                }
                return null;
            }

            function setOutput(obj, metaText) {
                document.getElementById('pg_output').textContent = JSON.stringify(obj, null, 2);
                document.getElementById('pg_meta').textContent = metaText || '';
            }

            function setLastRequest(jsonStr) {
                const wrap = document.getElementById('pg_last_request_wrap');
                const pre = document.getElementById('pg_last_request');
                if (!jsonStr) {
                    pre.textContent = '';
                    wrap.open = false;
                    return;
                }
                try {
                    pre.textContent = JSON.stringify(JSON.parse(jsonStr), null, 2);
                } catch (e) {
                    pre.textContent = jsonStr;
                }
                wrap.open = true;
            }

            document.querySelectorAll('.pg-send').forEach(function (btn) {
                btn.addEventListener('click', async function () {
                    const operation = btn.getAttribute('data-operation');
                    const baseUrl = document.getElementById('pg_base_url').value.trim();
                    const auth_user = document.getElementById('pg_auth_user').value;
                    const auth_password = document.getElementById('pg_auth_password').value;

                    let path_params = {};
                    let query = {};
                    let body = null;

                    if (operation.startsWith('gyg_host_')) {
                        if (operation === 'gyg_host_get_deals') {
                            query = { externalProductId: val('gyg_deals_externalProductId') };
                        } else if (operation === 'gyg_host_delete_deal') {
                            path_params = { dealId: val('gyg_deal_delete_id') };
                        } else if (operation === 'gyg_host_patch_product_activate') {
                            path_params = { gygOptionId: val('gyg_patch_gygOptionId') };
                            body = JSON.stringify({
                                data: { externalProductId: val('gyg_patch_externalProductId') }
                            });
                        } else if (operation === 'gyg_host_post_redeem_ticket') {
                            body = JSON.stringify({
                                data: {
                                    ticketCode: val('gyg_rt_ticketCode'),
                                    gygBookingReference: val('gyg_rt_gygBookingReference')
                                }
                            });
                        } else if (operation === 'gyg_host_post_redeem_booking') {
                            body = JSON.stringify({
                                data: { gygBookingReference: val('gyg_rb_gygBookingReference') }
                            });
                        } else if (operation === 'gyg_host_post_deals') {
                            body = document.getElementById('gyg_body_post_deals').value.trim();
                            if (body === '') body = null;
                        } else if (operation === 'gyg_host_post_notify_availability_update') {
                            body = document.getElementById('gyg_body_notify_availability').value.trim();
                            if (body === '') body = null;
                        } else if (operation === 'gyg_host_post_suppliers') {
                            body = document.getElementById('gyg_body_post_suppliers').value.trim();
                            if (body === '') body = null;
                        }
                    } else if (operation === 'get_availabilities') {
                        query = {
                            productId: document.getElementById('ga_productId').value.trim(),
                            fromDateTime: document.getElementById('ga_from').value.trim(),
                            toDateTime: document.getElementById('ga_to').value.trim()
                        };
                    } else if (operation === 'get_supplier_products') {
                        path_params = { supplierId: document.getElementById('pp_supplierId').value.trim() };
                    } else if (operation === 'get_product_details') {
                        path_params = { productId: document.getElementById('pd_productId').value.trim() };
                    } else if (operation === 'get_pricing_categories') {
                        path_params = { productId: document.getElementById('pc_productId').value.trim() };
                    } else if (operation === 'get_addons') {
                        path_params = { productId: document.getElementById('ad_productId').value.trim() };
                    } else {
                        body = buildBody(operation);
                    }

                    setLastRequest(body);
                    setOutput({ status: 'loading' }, '…');

                    try {
                        const res = await fetch(invokeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                operation,
                                base_url: baseUrl,
                                auth_user,
                                auth_password,
                                path_params,
                                query,
                                body
                            })
                        });
                        const data = await res.json().catch(function () { return { parse_error: true, raw: '' }; });
                        const meta = (data.duration_ms != null)
                            ? ('HTTP ' + (data.status ?? res.status) + ' · ' + data.duration_ms + ' ms')
                            : ('HTTP ' + res.status);
                        setOutput(data, meta);
                    } catch (e) {
                        setOutput({ error: String(e) }, '');
                    }
                });
            });
        });
    </script>
@endsection
