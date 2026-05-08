@extends('layouts.master-without-nav')
@section('title')
    @lang('translation.signup')
@endsection
@section('content')
    @php
        $selectedPlanCode = $selectedPlanCode ?? null;
        $selectedPlan = $selectedPlan ?? null;
        $pricingPlans = $pricingPlans ?? collect();
        $activeCategories = $activeCategories ?? collect();
        $selectedPlanFromInput = $pricingPlans->firstWhere('code', old('selected_plan_code', $selectedPlanCode)) ?? $selectedPlan;
        $selectedPlanCodeForView = $selectedPlanFromInput?->code;
        $oldCategoryIds = collect(old('category_ids', []))->map(fn ($id) => (int) $id)->all();
    @endphp

    <div class="auth-page-wrapper py-4">
        <!-- auth page bg -->
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>

            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
                    viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>

        <!-- auth page content -->
        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-4 mb-3 text-white-50">
                            <div>
                                <a href="{{ route('root') }}" class="d-inline-block auth-logo">
                                    <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="65" width="225">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">Destination Manager Apps</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-3">

                            <div class="card-body p-3">
                                <div class="text-center mt-0">
                                    <h5 class="text-primary">Buat Akun</h5>
                                    <p class="text-muted mb-0">Daftar cepat, lanjutkan pengaturan setelahnya.</p>
                                </div>
                                <div class="p-2 mt-3">
                                    <form class="needs-validation" novalidate method="POST"
                                        action="{{ route('register') }}" enctype="multipart/form-data">
                                        @csrf
                                        @if (isset($selectedPlanFromInput) && $selectedPlanFromInput)
                                            <div class="alert alert-info" role="alert" id="selected-plan-alert">
                                                Kamu memilih paket <strong id="selected-plan-name">{{ $selectedPlanFromInput->name }}</strong>.
                                                Lanjutkan pendaftaran untuk aktivasi paket.
                                            </div>
                                        @endif
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="flex-grow-1">
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar" role="progressbar" style="width: 50%;" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" id="register-progress"></div>
                                                </div>
                                            </div>
                                            <span class="badge bg-light text-muted border" id="register-step-label">Langkah 1 dari 2</span>
                                        </div>

                                        <div class="row g-2" id="register-wizard">
                                            <div class="col-12" data-step="1">
                                                <div class="row g-2">
                                                    <div class="col-12 col-md-6">
                                                        <div class="mb-2">
                                                            <label for="useremail" class="form-label">Email <span class="text-danger">*</span></label>
                                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                                name="email" value="{{ old('email') }}" id="useremail"
                                                                placeholder="nama@perusahaan.com" autocomplete="email" required>
                                                            @error('email')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                            <div class="invalid-feedback">
                                                                Please enter email
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6">
                                                        <div class="mb-2">
                                                            <label for="username" class="form-label">Nama lengkap <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                                name="name" value="{{ old('name') }}" id="username"
                                                                placeholder="Contoh: Wayan S." autocomplete="name" required>
                                                            @error('name')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                            <div class="invalid-feedback">
                                                                Please enter your name
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6">
                                                        <div class="mb-2">
                                                            <label for="userpassword" class="form-label">Password <span class="text-danger">*</span></label>
                                                            <input type="password"
                                                                class="form-control @error('password') is-invalid @enderror"
                                                                name="password"
                                                                id="userpassword"
                                                                placeholder="Minimal 8 karakter"
                                                                autocomplete="new-password"
                                                                required>
                                                            @error('password')
                                                                <span class="invalid-feedback" role="alert">
                                                                    <strong>{{ $message }}</strong>
                                                                </span>
                                                            @enderror
                                                            <div class="invalid-feedback">
                                                                Please enter password
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6">
                                                        <div class="mb-2">
                                                            <label for="input-password" class="form-label">Konfirmasi password <span class="text-danger">*</span></label>
                                                            <input type="password"
                                                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                                                name="password_confirmation"
                                                                id="input-password"
                                                                placeholder="Ulangi password"
                                                                autocomplete="new-password"
                                                                required>

                                                            <div class="form-floating-icon">
                                                                <i data-feather="lock"></i>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="d-grid mt-2">
                                                            <button class="btn btn-primary" type="button" id="wizard-next">
                                                                Lanjut <i class="ri-arrow-right-line align-middle ms-1"></i>
                                                            </button>
                                                        </div>
                                                        <p class="text-muted fs-12 mt-2 mb-0 text-center">
                                                            Sudah punya akun?
                                                            <a id="signin-link" href="{{ route('login', !empty($selectedPlanCodeForView) ? ['plan' => $selectedPlanCodeForView] : []) }}"
                                                                class="fw-semibold text-primary text-decoration-underline">Masuk</a>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 d-none" data-step="2">
                                            <div class="col-12 col-md-7">
                                                <div class="mb-2">
                                                    <label for="selected_plan_code" class="form-label">Pilih paket <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select @error('selected_plan_code') is-invalid @enderror"
                                                        id="selected_plan_code" name="selected_plan_code" required>
                                                        @foreach ($pricingPlans as $planOption)
                                                            @php
                                                                $allowedCategoryIds = $planOption->allowedCategories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
                                                            @endphp
                                                            <option value="{{ $planOption->code }}"
                                                                data-plan-name="{{ $planOption->name }}"
                                                                data-restricts-categories="{{ count($allowedCategoryIds) > 0 ? '1' : '0' }}"
                                                                data-allowed-category-ids="{{ implode(',', $allowedCategoryIds) }}"
                                                                data-max-categories="{{ max(1, (int) $planOption->category_slots_included) }}"
                                                                {{ old('selected_plan_code', $selectedPlanCodeForView) === $planOption->code ? 'selected' : '' }}>
                                                                {{ $planOption->name }} — ${{ $planOption->price_monthly }}/bln atau ${{ $planOption->price_yearly }}/thn
                                                                {{ $planOption->is_popular ? '(Popular)' : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('selected_plan_code')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-5">
                                                <div class="mb-2">
                                                    <label for="billing_cycle" class="form-label">Tagihan <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select @error('billing_cycle') is-invalid @enderror"
                                                        id="billing_cycle" name="billing_cycle" required>
                                                        <option value="monthly" {{ old('billing_cycle', 'monthly') === 'monthly' ? 'selected' : '' }}>
                                                            Bulanan
                                                        </option>
                                                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>
                                                            Tahunan
                                                        </option>
                                                    </select>
                                                    @error('billing_cycle')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="mb-2">
                                                    <label class="form-label">Kategori usaha <span class="text-danger">*</span></label>
                                                    <div class="border rounded p-2" id="category-options" style="max-height: 160px; overflow: auto;">
                                                        <div class="row g-2">
                                                            @forelse ($activeCategories as $category)
                                                                <div class="col-12 col-sm-6" data-category-option data-category-id="{{ (int) $category->id }}">
                                                                    <div class="form-check mb-0">
                                                                        <input class="form-check-input"
                                                                            type="checkbox"
                                                                            value="{{ (int) $category->id }}"
                                                                            name="category_ids[]"
                                                                            id="category-{{ (int) $category->id }}"
                                                                            {{ in_array((int) $category->id, $oldCategoryIds, true) ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="category-{{ (int) $category->id }}">
                                                                            {{ $category->name }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="col-12">
                                                                    <p class="text-muted mb-0">Kategori belum tersedia.</p>
                                                                </div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                    @error('category_ids')
                                                        <span class="invalid-feedback d-block" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                    @error('category_ids.*')
                                                        <span class="invalid-feedback d-block" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                    <small class="text-muted" id="category-selection-hint">Pilih sesuai limit kategori paket.</small>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="mb-2">
                                                    <label for="tenant_name" class="form-label">Nama perusahaan <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('tenant_name') is-invalid @enderror"
                                                        name="tenant_name" value="{{ old('tenant_name') }}" id="tenant_name"
                                                        placeholder="Contoh: Gili Snorkeling" required>
                                                    @error('tenant_name')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                    <div class="invalid-feedback">
                                                        Please enter business name
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="accordion" id="optionalFields">
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="headingOptional">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse"
                                                                data-bs-target="#collapseOptional" aria-expanded="false" aria-controls="collapseOptional">
                                                                Opsi tambahan (opsional)
                                                            </button>
                                                        </h2>
                                                        <div id="collapseOptional" class="accordion-collapse collapse" aria-labelledby="headingOptional"
                                                            data-bs-parent="#optionalFields">
                                                            <div class="accordion-body pt-2">
                                                                <div class="mb-0">
                                                                    <label for="input-avatar" class="form-label">Avatar <span class="text-muted">(optional)</span></label>
                                                                    <input type="file" class="form-control @error('avatar') is-invalid @enderror"
                                                                        name="avatar" id="input-avatar">
                                                                    @error('avatar')
                                                                        <span class="invalid-feedback" role="alert">
                                                                            <strong>{{ $message }}</strong>
                                                                        </span>
                                                                    @enderror
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3 mt-2">
                                            <p class="mb-0 fs-12 text-muted">
                                                Dengan mendaftar, kamu menyetujui <b>Desma Apps</b>
                                                <a href="#" class="text-primary text-decoration-underline fst-normal fw-medium">Syarat & Ketentuan</a>.
                                            </p>
                                        </div>

                                        <div class="d-flex gap-2 mt-2">
                                            <button class="btn btn-light w-100" type="button" id="wizard-back">
                                                <i class="ri-arrow-left-line align-middle me-1"></i> Kembali
                                            </button>
                                            <button class="btn btn-success w-100" type="submit">Buat akun</button>
                                        </div>
                                            </div>
                                        </div>

                                    </form>

                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <div class="mt-3 text-center">
                            <p class="mb-0">Sudah punya akun? <a href="{{ route('login', !empty($selectedPlanCodeForView) ? ['plan' => $selectedPlanCodeForView] : []) }}"
                                    class="fw-semibold text-primary text-decoration-underline">Masuk</a></p>
                        </div>

                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer py-3">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                              <p class="mb-0 text-muted">
                                  &copy; <script>document.write(new Date().getFullYear())</script> <b>DESMA</b> | Destination Manager Apps.
                                  <br>Powered by Lestari Informatika
                              </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->
@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/particles.js/particles.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/particles.app.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/form-validation.init.js') }}"></script>
    <script>
        (function() {
            const wizardRoot = document.getElementById('register-wizard');
            const stepLabel = document.getElementById('register-step-label');
            const progressBar = document.getElementById('register-progress');
            const nextBtn = document.getElementById('wizard-next');
            const backBtn = document.getElementById('wizard-back');
            const step1 = wizardRoot?.querySelector('[data-step="1"]');
            const step2 = wizardRoot?.querySelector('[data-step="2"]');

            const planSelect = document.getElementById('selected_plan_code');
            const selectedPlanName = document.getElementById('selected-plan-name');
            const signInLink = document.getElementById('signin-link');
            const loginBaseUrl = @json(route('login'));
            const categoryRows = Array.from(document.querySelectorAll('[data-category-option]'));
            const categoryHint = document.getElementById('category-selection-hint');

            const setRequiredWithin = (root, required) => {
                if (!root) return;
                const fields = Array.from(root.querySelectorAll('input, select, textarea'));
                fields.forEach((el) => {
                    const isOriginallyRequired = el.hasAttribute('required');
                    if (required) {
                        if (el.dataset.wasRequired === '1') el.setAttribute('required', '');
                    } else {
                        if (isOriginallyRequired) el.dataset.wasRequired = '1';
                        el.removeAttribute('required');
                    }
                });
            };

            const showStep = (step) => {
                if (!step1 || !step2) return;
                const isStep1 = step === 1;
                step1.classList.toggle('d-none', !isStep1);
                step2.classList.toggle('d-none', isStep1);
                stepLabel.textContent = isStep1 ? 'Langkah 1 dari 2' : 'Langkah 2 dari 2';
                progressBar.style.width = isStep1 ? '50%' : '100%';
                progressBar.setAttribute('aria-valuenow', isStep1 ? '50' : '100');

                // prevent hidden required fields from blocking validation
                setRequiredWithin(step1, isStep1);
                setRequiredWithin(step2, !isStep1);

                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const validateStep1 = () => {
                if (!step1) return true;
                const form = step1.closest('form');
                if (!form) return true;

                const requiredEls = Array.from(step1.querySelectorAll('[required]'));
                let ok = true;
                requiredEls.forEach((el) => {
                    if (!el.checkValidity()) ok = false;
                });
                if (!ok) {
                    form.classList.add('was-validated');
                }
                return ok;
            };

            if (nextBtn && backBtn && wizardRoot) {
                showStep(1);
                nextBtn.addEventListener('click', () => {
                    if (validateStep1()) showStep(2);
                });
                backBtn.addEventListener('click', () => showStep(1));
            }

            if (!planSelect) {
                return;
            }

            const getMaxCategories = () => {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                if (!selectedOption) {
                    return 1;
                }
                const maxCategories = parseInt(selectedOption.getAttribute('data-max-categories') || '1', 10);

                return Number.isInteger(maxCategories) && maxCategories > 0 ? maxCategories : 1;
            };

            const enforceMaxSelectedCategories = () => {
                const maxCategories = getMaxCategories();
                const visibleChecked = categoryRows
                    .map((row) => row.querySelector('input[type="checkbox"]'))
                    .filter((checkbox) => checkbox && !checkbox.disabled && checkbox.checked);

                if (visibleChecked.length <= maxCategories) {
                    return;
                }

                for (let i = maxCategories; i < visibleChecked.length; i += 1) {
                    visibleChecked[i].checked = false;
                }
            };

            const updateCategoryHint = () => {
                if (!categoryHint) {
                    return;
                }
                const maxCategories = getMaxCategories();
                categoryHint.textContent = maxCategories === 1
                    ? 'Paket ini hanya mengizinkan 1 kategori.'
                    : `Paket ini mengizinkan maksimal ${maxCategories} kategori.`;
            };

            const syncCategoryOptions = () => {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                if (!selectedOption || categoryRows.length === 0) {
                    return;
                }

                const restrictsCategories = selectedOption.getAttribute('data-restricts-categories') === '1';
                const allowedIds = (selectedOption.getAttribute('data-allowed-category-ids') || '')
                    .split(',')
                    .map((value) => parseInt(value, 10))
                    .filter((value) => Number.isInteger(value) && value > 0);
                const allowedSet = new Set(allowedIds);
                const maxCategories = getMaxCategories();
                let checkedVisibleCount = 0;
                let firstVisibleCheckbox = null;

                categoryRows.forEach((row) => {
                    const categoryId = parseInt(row.getAttribute('data-category-id') || '', 10);
                    const checkbox = row.querySelector('input[type="checkbox"]');
                    if (!checkbox || !Number.isInteger(categoryId)) {
                        return;
                    }

                    const isAllowed = !restrictsCategories || allowedSet.has(categoryId);
                    row.style.display = isAllowed ? '' : 'none';
                    checkbox.disabled = !isAllowed;
                    if (!isAllowed) {
                        checkbox.checked = false;
                    }
                    if (isAllowed && firstVisibleCheckbox === null) {
                        firstVisibleCheckbox = checkbox;
                    }
                    if (isAllowed && checkbox.checked) {
                        checkedVisibleCount += 1;
                        if (checkedVisibleCount > maxCategories) {
                            checkbox.checked = false;
                        }
                    }
                });

                if (checkedVisibleCount === 0 && firstVisibleCheckbox) {
                    firstVisibleCheckbox.checked = true;
                }

                enforceMaxSelectedCategories();
                updateCategoryHint();
            };

            const syncSelectedPlanInfo = () => {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                const planCode = selectedOption ? selectedOption.value : '';
                const planName = selectedOption ? selectedOption.getAttribute('data-plan-name') : '';

                if (selectedPlanName && planName) {
                    selectedPlanName.textContent = planName;
                }
                if (signInLink) {
                    signInLink.href = planCode ? `${loginBaseUrl}?plan=${encodeURIComponent(planCode)}` : loginBaseUrl;
                }
                syncCategoryOptions();
            };

            planSelect.addEventListener('change', syncSelectedPlanInfo);
            categoryRows.forEach((row) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (!checkbox) {
                    return;
                }
                checkbox.addEventListener('change', () => {
                    if (!checkbox.checked) {
                        return;
                    }
                    const maxCategories = getMaxCategories();
                    const visibleChecked = categoryRows
                        .map((candidateRow) => candidateRow.querySelector('input[type="checkbox"]'))
                        .filter((candidate) => candidate && !candidate.disabled && candidate.checked);
                    if (visibleChecked.length <= maxCategories) {
                        return;
                    }

                    if (maxCategories === 1) {
                        visibleChecked.forEach((candidate) => {
                            if (candidate !== checkbox) {
                                candidate.checked = false;
                            }
                        });

                        return;
                    }

                    checkbox.checked = false;
                });
            });
            syncSelectedPlanInfo();
        })();
    </script>
@endsection
