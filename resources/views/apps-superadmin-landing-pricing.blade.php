@extends('layouts.master')

@section('title')
    Pricing
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Superadmin
        @endslot
        @slot('title')
            Pricing
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="alert alert-primary border-0" role="alert">
                Kelola <strong>paket & harga</strong> landing (<code>/</code> saat belum login). Klik <strong>Edit</strong> untuk
                mengubah detail di modal. <strong>Max users</strong> pada paket bertanda <strong>Popular</strong> dipakai sebagai
                kuota seat untuk semua tenant (setelah simpan).
            </div>
        </div>
    </div>

    @if ($plans->isEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        Belum ada paket landing. Jalankan migrasi lalu
                        <code>php artisan db:seed --class=LandingPricingSeeder</code>.
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar paket</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $plans->count() }} paket</span>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive table-card">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light text-muted text-uppercase fs-12">
                                    <tr>
                                        <th class="text-center" style="width: 72px;">Urut</th>
                                        <th>Paket</th>
                                        <th>Subtitle</th>
                                        <th class="text-end">$/bln</th>
                                        <th class="text-end">$/thn</th>
                                        <th class="text-center">Kategori</th>
                                        <th class="text-center">Max users</th>
                                        <th class="text-center">Popular</th>
                                        <th>Icon</th>
                                        <th class="text-end" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($plans as $plan)
                                        <tr>
                                            <td class="text-center">{{ $plan->sort_order }}</td>
                                            <td class="fw-semibold">{{ $plan->name }}</td>
                                            <td class="text-muted">{{ $plan->subtitle ?: '—' }}</td>
                                            <td class="text-end">{{ $plan->price_monthly }}</td>
                                            <td class="text-end">{{ $plan->price_yearly }}</td>
                                            <td class="text-center small text-muted">
                                                <span class="badge bg-body-secondary text-body">{{ (int) ($plan->category_slots_included ?? 1) }} slot</span>
                                                <div class="mt-1">+{{ (int) ($plan->extra_category_price_monthly ?? 0) }}/bln</div>
                                            </td>
                                            <td class="text-center">
                                                @if ($plan->max_users === null)
                                                    <span class="badge bg-info-subtle text-info">∞</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $plan->max_users }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($plan->is_popular)
                                                    <span class="badge bg-warning-subtle text-warning">Ya</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <span
                                                        class="avatar-title bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        style="width: 2rem; height: 2rem;">
                                                        <i class="{{ $plan->icon_class }} fs-16"></i>
                                                    </span>
                                                    <code class="fs-11 text-muted mb-0">{{ $plan->icon_class }}</code>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-soft-primary"
                                                    data-bs-toggle="modal" data-bs-target="#planModal{{ $plan->id }}">
                                                    <i class="ri-pencil-line align-bottom"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($plans as $plan)
            @php
                $pid = $plan->id;
                $pkey = "plans.{$pid}";
            @endphp
            <div class="modal fade" id="planModal{{ $pid }}" tabindex="-1" aria-labelledby="planModalLabel{{ $pid }}"
                aria-hidden="true">
                {{-- Dialog dibatasi tinggi viewport; hanya modal-body yang scroll supaya footer selalu terlihat --}}
                {{-- Tinggi dialog ~viewport; isi scroll di modal-body; footer tetap di bawah modal --}}
                <div class="modal-dialog modal-xl my-3" style="height: calc(100vh - 2rem); max-height: calc(100vh - 2rem);">
                    <div class="modal-content border-0 shadow h-100 d-flex flex-column overflow-hidden">
                        <form method="POST" action="{{ route('superadmin-landing-pricing.update', $plan) }}"
                            class="d-flex flex-column h-100" style="min-height: 0;">
                            @csrf
                            <div class="modal-header p-3 bg-info-subtle border-bottom flex-shrink-0">
                                <div>
                                    <h5 class="modal-title mb-1" id="planModalLabel{{ $pid }}">{{ $plan->name }}</h5>
                                    <p class="text-muted mb-2">Edit paket landing &amp; daftar fitur.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-secondary-subtle text-secondary">{{ $plan->code }}</span>
                                        @if ($plan->is_popular)
                                            <span class="badge bg-warning-subtle text-warning">Popular</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="min-height: 0;">
                                @php
                                    $planHasErrors = $errors->any() && collect($errors->keys())->contains(fn ($k) => str_starts_with((string) $k, $pkey.'.'));
                                @endphp
                                @if ($planHasErrors)
                                    <div class="alert alert-danger border-0 mb-4" role="alert">
                                        Ada field yang tidak valid. Perbaiki lalu simpan lagi.
                                    </div>
                                @endif
                                <div class="row g-3 mb-1">
                                    <div class="col-lg-6">
                                        <label for="planName{{ $pid }}" class="form-label">Nama paket</label>
                                        <input type="text" id="planName{{ $pid }}" name="plans[{{ $pid }}][name]"
                                            class="form-control" value="{{ old($pkey.'.name', $plan->name) }}" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="planSubtitle{{ $pid }}" class="form-label">Subtitle</label>
                                        <input type="text" id="planSubtitle{{ $pid }}" name="plans[{{ $pid }}][subtitle]"
                                            class="form-control" value="{{ old($pkey.'.subtitle', $plan->subtitle) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planPriceMonth{{ $pid }}" class="form-label">Harga / bulan (USD)</label>
                                        <input type="number" id="planPriceMonth{{ $pid }}" name="plans[{{ $pid }}][price_monthly]"
                                            class="form-control" min="0"
                                            value="{{ old($pkey.'.price_monthly', $plan->price_monthly) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planPriceYear{{ $pid }}" class="form-label">Harga / tahun (USD)</label>
                                        <input type="number" id="planPriceYear{{ $pid }}" name="plans[{{ $pid }}][price_yearly]"
                                            class="form-control" min="0"
                                            value="{{ old($pkey.'.price_yearly', $plan->price_yearly) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planSort{{ $pid }}" class="form-label">Urutan tampil</label>
                                        <input type="number" id="planSort{{ $pid }}" name="plans[{{ $pid }}][sort_order]"
                                            class="form-control" min="0" max="255"
                                            value="{{ old($pkey.'.sort_order', $plan->sort_order) }}">
                                    </div>
                                    @php
                                        $catalogIconValues = array_map(
                                            static fn (array $row): string => strtolower($row['value']),
                                            \App\Models\LandingPricingPlan::REMIX_ICON_OPTIONS,
                                        );
                                        $selectedIcon = strtolower(
                                            trim((string) old($pkey.'.icon_class', $plan->icon_class ?? '')),
                                        );
                                        if ($selectedIcon === '') {
                                            $selectedIcon = 'ri-book-line';
                                        }
                                        $legacyIcon =
                                            $selectedIcon !== '' && ! in_array($selectedIcon, $catalogIconValues, true)
                                                ? $selectedIcon
                                                : null;
                                    @endphp
                                    <div class="col-12">
                                        <div class="card border shadow-none mb-0 bg-body-secondary">
                                            <div class="card-body p-3 p-md-4">
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-lg-7">
                                                        <label for="planIcon{{ $pid }}" class="form-label mb-1">Ikon di
                                                            landing</label>
                                                        <select id="planIcon{{ $pid }}" name="plans[{{ $pid }}][icon_class]"
                                                            class="form-select js-plan-icon-select"
                                                            data-preview-id="planIconPreview{{ $pid }}" required>
                                                            @if ($legacyIcon)
                                                                <option value="{{ $legacyIcon }}" @selected($selectedIcon === $legacyIcon)>
                                                                    (Tersimpan, luar daftar) {{ $legacyIcon }}
                                                                </option>
                                                            @endif
                                                            @foreach (\App\Models\LandingPricingPlan::REMIX_ICON_OPTIONS as $opt)
                                                                <option value="{{ $opt['value'] }}"
                                                                    @selected($selectedIcon === strtolower($opt['value']))>
                                                                    {{ $opt['label'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <p class="text-muted fs-12 mb-0 mt-2">Tampil di kartu paket halaman
                                                            utama (belum login).</p>
                                                    </div>
                                                    <div class="col-lg-5">
                                                        <div
                                                            class="d-flex flex-row flex-lg-column align-items-center align-items-lg-end gap-3 justify-content-lg-end pt-1 pt-lg-0">
                                                            <span
                                                                class="text-muted text-uppercase fs-11 fw-semibold mb-lg-1">Pratinjau</span>
                                                            <span
                                                                class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center shadow-sm flex-shrink-0"
                                                                style="width: 3.25rem; height: 3.25rem;">
                                                                <i id="planIconPreview{{ $pid }}"
                                                                    class="{{ $selectedIcon }} fs-24 lh-1 d-inline-block"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card border shadow-none h-100 mb-0">
                                            <div class="card-body p-3 p-md-4">
                                                <label class="form-label fw-semibold mb-3">Badge Popular</label>
                                                <input type="hidden" name="plans[{{ $pid }}][is_popular]" value="0">
                                                <div class="form-check form-switch form-switch-lg">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="plans[{{ $pid }}][is_popular]" value="1" id="popular{{ $pid }}"
                                                        @checked(old($pkey.'.is_popular', $plan->is_popular ? '1' : '0') === '1')>
                                                    <label class="form-check-label" for="popular{{ $pid }}">Tampilkan badge
                                                        Popular di landing</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card border shadow-none h-100 mb-0">
                                            <div class="card-body p-3 p-md-4">
                                                <label class="form-label fw-semibold mb-2">Max users</label>
                                                <p class="text-muted fs-12 mb-3">Landing + kuota seat tenant jika paket ini
                                                    <strong>Popular</strong>.</p>
                                                <input type="hidden" name="plans[{{ $pid }}][max_users_unlimited]" value="0">
                                                <div class="form-check form-switch form-switch-lg mb-3">
                                                    <input class="form-check-input js-unlimited-users" type="checkbox"
                                                        name="plans[{{ $pid }}][max_users_unlimited]" value="1"
                                                        id="unlimited{{ $pid }}" data-target="#maxUsers{{ $pid }}"
                                                        @checked(old($pkey.'.max_users_unlimited', $plan->max_users === null ? '1' : '0') === '1')>
                                                    <label class="form-check-label" for="unlimited{{ $pid }}">Unlimited</label>
                                                </div>
                                                <label for="maxUsers{{ $pid }}" class="form-label small">Jumlah (bila tidak
                                                    unlimited)</label>
                                                <input type="number" name="plans[{{ $pid }}][max_users]" id="maxUsers{{ $pid }}"
                                                    class="form-control" min="1" max="999999"
                                                    value="{{ old($pkey.'.max_users', $plan->max_users ?? 1) }}"
                                                    @disabled(old($pkey.'.max_users_unlimited', $plan->max_users === null ? '1' : '0') === '1')>
                                                <p class="text-muted fs-12 mb-0 mt-2">Popular: min. jumlah user yang sudah ada
                                                    di tenant. Unlimited di sini = cap <strong>500</strong> seat.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border shadow-none mt-4 mb-0">
                                    <div class="card-body p-3 p-md-4">
                                        <h6 class="fw-semibold mb-2">Kategori tenant &amp; harga tambahan</h6>
                                        <p class="text-muted fs-12 mb-3">
                                            Harga bulan/tahun di atas = hingga <strong>{{ (int) ($plan->category_slots_included ?? 1) }}</strong> kategori bisnis.
                                            Setiap kategori tambahan: +<strong>{{ (int) ($plan->extra_category_price_monthly ?? 0) }}</strong> USD/bulan atau
                                            +<strong>{{ (int) ($plan->extra_category_price_yearly ?? 0) }}</strong> USD/tahun.
                                            Kosongkan semua centang di bawah agar <strong>semua</strong> kategori aktif diperbolehkan (mode terbuka).
                                        </p>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="catSlots{{ $pid }}">Slot kategori dalam harga dasar</label>
                                                <input type="number" id="catSlots{{ $pid }}" class="form-control" min="1" max="50"
                                                    name="plans[{{ $pid }}][category_slots_included]"
                                                    value="{{ old($pkey.'.category_slots_included', (int) ($plan->category_slots_included ?? 1)) }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="catExtraMo{{ $pid }}">Extra / kategori / bulan (USD)</label>
                                                <input type="number" id="catExtraMo{{ $pid }}" class="form-control" min="0" max="999999"
                                                    name="plans[{{ $pid }}][extra_category_price_monthly]"
                                                    value="{{ old($pkey.'.extra_category_price_monthly', (int) ($plan->extra_category_price_monthly ?? 0)) }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="catExtraYr{{ $pid }}">Extra / kategori / tahun (USD)</label>
                                                <input type="number" id="catExtraYr{{ $pid }}" class="form-control" min="0" max="999999"
                                                    name="plans[{{ $pid }}][extra_category_price_yearly]"
                                                    value="{{ old($pkey.'.extra_category_price_yearly', (int) ($plan->extra_category_price_yearly ?? 0)) }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label d-block mb-2">Batasi ke kategori tertentu (opsional)</label>
                                                @php
                                                    $selectedAllowedIds = collect(
                                                        old($pkey.'.allowed_category_ids', $plan->allowedCategories->pluck('id')->all()),
                                                    )
                                                        ->map(fn ($v) => (int) $v)
                                                        ->unique()
                                                        ->all();
                                                @endphp
                                                @forelse ($allCategories as $category)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="plans[{{ $pid }}][allowed_category_ids][]" value="{{ $category->id }}"
                                                            id="plan{{ $pid }}cat{{ $category->id }}"
                                                            @checked(in_array((int) $category->id, $selectedAllowedIds, true))>
                                                        <label class="form-check-label" for="plan{{ $pid }}cat{{ $category->id }}">
                                                            {{ $category->name }} <span class="text-muted">({{ $category->code }})</span>
                                                        </label>
                                                    </div>
                                                @empty
                                                    <div class="alert alert-warning mb-0 py-2">Belum ada kategori aktif. Jalankan
                                                        <code>php artisan db:seed --class=TenantCategorySeeder</code> atau buat kategori dulu.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border mt-4 mb-0">
                                    <div class="card-header d-flex flex-wrap align-items-center gap-3 px-4 py-3 border-bottom bg-light">
                                        <h6 class="card-title mb-0 flex-grow-1">Fitur di landing</h6>
                                        <button type="button" class="btn btn-sm btn-primary js-add-feature"
                                            data-table="#featureTable{{ $pid }}" data-plan-id="{{ $pid }}">
                                            <i class="ri-add-line align-bottom me-1"></i> Tambah baris
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-lg table-hover align-middle mb-0" id="featureTable{{ $pid }}">
                                                <thead class="table-light text-muted text-uppercase fs-12">
                                                    <tr>
                                                        <th class="ps-4 pe-3 py-3 fw-semibold text-start" scope="col"
                                                            style="width: 56%;">Teks</th>
                                                        <th class="px-4 py-3 fw-semibold text-center" scope="col"
                                                            style="width: 34%; min-width: 220px;">Termasuk?</th>
                                                        <th class="ps-3 pe-4 py-3 fw-semibold text-center" scope="col"
                                                            style="width: 10%; min-width: 5.5rem;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="border-top-0">
                                                    @php
                                                        $oldFeatures = old($pkey.'.features');
                                                        $featureRows = is_array($oldFeatures)
                                                            ? collect($oldFeatures)
                                                            : $plan->features->map(fn ($f) => [
                                                                'display_text' => $f->display_text,
                                                                'is_included' => $f->is_included ? '1' : '0',
                                                            ]);
                                                    @endphp
                                                    @foreach ($featureRows as $idx => $row)
                                                        <tr>
                                                            <td class="ps-4 pe-3 py-3">
                                                                <input type="text"
                                                                    name="plans[{{ $pid }}][features][{{ $idx }}][display_text]"
                                                                    class="form-control"
                                                                    value="{{ $row['display_text'] ?? '' }}"
                                                                    placeholder="Contoh: Upto 15 Projects" required>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <select name="plans[{{ $pid }}][features][{{ $idx }}][is_included]"
                                                                    class="form-select mx-auto" style="max-width: 240px;">
                                                                    <option value="1" @selected(($row['is_included'] ?? '1') === '1' || ($row['is_included'] ?? true) === true)>Ya (hijau)</option>
                                                                    <option value="0" @selected(($row['is_included'] ?? '1') === '0' || ($row['is_included'] ?? true) === false)>Tidak (merah)</option>
                                                                </select>
                                                            </td>
                                                            <td class="ps-3 pe-4 py-3 text-center">
                                                                <div class="d-flex justify-content-center align-items-center">
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-icon btn-soft-danger js-remove-row"
                                                                        title="Hapus baris">
                                                                        <i class="ri-delete-bin-line"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer flex-shrink-0 border-top bg-light">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-save-line align-bottom me-1"></i> Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <template id="featureRowTemplate">
        <tr>
            <td class="ps-4 pe-3 py-3">
                <input type="text" name="plans[__PLAN_ID__][features][__INDEX__][display_text]" class="form-control"
                    required>
            </td>
            <td class="px-4 py-3 text-center">
                <select name="plans[__PLAN_ID__][features][__INDEX__][is_included]" class="form-select mx-auto"
                    style="max-width: 240px;">
                    <option value="1">Ya (hijau)</option>
                    <option value="0">Tidak (merah)</option>
                </select>
            </td>
            <td class="ps-3 pe-4 py-3 text-center">
                <div class="d-flex justify-content-center align-items-center">
                    <button type="button" class="btn btn-sm btn-icon btn-soft-danger js-remove-row"
                        title="Hapus baris">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </td>
        </tr>
    </template>

    <script>
        document.addEventListener('click', function (e) {
            if (e.target.closest('.js-remove-row')) {
                const row = e.target.closest('tr');
                const tbody = row && row.parentElement;
                if (tbody && tbody.rows.length > 1) {
                    row.remove();
                }
            }
        });

        document.querySelectorAll('.js-add-feature').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const tableSelector = btn.getAttribute('data-table');
                const planId = btn.getAttribute('data-plan-id');
                const table = document.querySelector(tableSelector);
                if (!table || !planId) return;
                const tbody = table.querySelector('tbody');
                const tpl = document.getElementById('featureRowTemplate');
                const index = tbody.querySelectorAll('tr').length;
                const html = tpl.innerHTML
                    .replaceAll('__PLAN_ID__', String(planId))
                    .replaceAll('__INDEX__', String(index));
                tbody.insertAdjacentHTML('beforeend', html);
            });
        });

        document.querySelectorAll('.js-plan-icon-select').forEach(function (sel) {
            var previewId = sel.getAttribute('data-preview-id');
            var iconEl = previewId ? document.getElementById(previewId) : null;
            function sync() {
                if (!iconEl) {
                    return;
                }
                var v = sel.value || 'ri-book-line';
                iconEl.className = v + ' fs-24 lh-1 d-inline-block';
            }
            sel.addEventListener('change', sync);
            sync();
        });

        document.querySelectorAll('.js-unlimited-users').forEach(function (cb) {
            const toggle = function () {
                const input = document.querySelector(cb.getAttribute('data-target'));
                if (!input) return;
                input.disabled = cb.checked;
                if (cb.checked) {
                    input.removeAttribute('required');
                } else {
                    input.setAttribute('required', 'required');
                }
            };
            cb.addEventListener('change', toggle);
            toggle();
        });

        @if (! empty($openPlanModalId))
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('planModal{{ $openPlanModalId }}');
                if (!el || !window.bootstrap || !bootstrap.Modal) return;
                var m = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el);
                m.show();
            });
        @endif
    </script>
@endsection
