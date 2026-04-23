
<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.signup'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php
        $selectedPlanCode = $selectedPlanCode ?? null;
        $selectedPlan = $selectedPlan ?? null;
        $pricingPlans = $pricingPlans ?? collect();
        $activeCategories = $activeCategories ?? collect();
        $selectedPlanFromInput = $pricingPlans->firstWhere('code', old('selected_plan_code', $selectedPlanCode)) ?? $selectedPlan;
        $selectedPlanCodeForView = $selectedPlanFromInput?->code;
        $oldCategoryIds = collect(old('category_ids', []))->map(fn ($id) => (int) $id)->all();
    ?>

    <div class="auth-page-wrapper pt-5">
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
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <div>
                                <a href="index" class="d-inline-block auth-logo">
                                    <img src="<?php echo e(URL::asset('build/images/logo-light.png')); ?>" alt="" height="20">
                                </a>
                            </div>
                            <p class="mt-3 fs-15 fw-medium">Premium Admin & Dashboard Template</p>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">

                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Create New Account</h5>
                                    <p class="text-muted">Get your free velzon account now</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <form class="needs-validation" novalidate method="POST"
                                        action="<?php echo e(route('register')); ?>" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <?php if(isset($selectedPlanFromInput) && $selectedPlanFromInput): ?>
                                            <div class="alert alert-info" role="alert" id="selected-plan-alert">
                                                Kamu memilih paket <strong id="selected-plan-name"><?php echo e($selectedPlanFromInput->name); ?></strong>.
                                                Lanjutkan daftar untuk proses aktivasi paket.
                                            </div>
                                        <?php endif; ?>
                                        <div class="mb-3">
                                            <label for="selected_plan_code" class="form-label">Pilih Paket <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select <?php $__errorArgs = ['selected_plan_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                id="selected_plan_code" name="selected_plan_code" required>
                                                <?php $__currentLoopData = $pricingPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $planOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $allowedCategoryIds = $planOption->allowedCategories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
                                                    ?>
                                                    <option value="<?php echo e($planOption->code); ?>"
                                                        data-plan-name="<?php echo e($planOption->name); ?>"
                                                        data-restricts-categories="<?php echo e(count($allowedCategoryIds) > 0 ? '1' : '0'); ?>"
                                                        data-allowed-category-ids="<?php echo e(implode(',', $allowedCategoryIds)); ?>"
                                                        data-max-categories="<?php echo e(max(1, (int) $planOption->category_slots_included)); ?>"
                                                        <?php echo e(old('selected_plan_code', $selectedPlanCodeForView) === $planOption->code ? 'selected' : ''); ?>>
                                                        <?php echo e($planOption->name); ?> - $<?php echo e($planOption->price_monthly); ?>/bulan
                                                        atau $<?php echo e($planOption->price_yearly); ?>/tahun
                                                        <?php echo e($planOption->is_popular ? '(Popular)' : ''); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                            <?php $__errorArgs = ['selected_plan_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <small class="text-muted">Plan akan langsung tersimpan ke tenant saat akun dibuat.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Kategori Tenant <span class="text-danger">*</span></label>
                                            <div class="border rounded p-3" id="category-options">
                                                <?php $__empty_1 = true; $__currentLoopData = $activeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                    <div class="form-check mb-2" data-category-option data-category-id="<?php echo e((int) $category->id); ?>">
                                                        <input class="form-check-input"
                                                            type="checkbox"
                                                            value="<?php echo e((int) $category->id); ?>"
                                                            name="category_ids[]"
                                                            id="category-<?php echo e((int) $category->id); ?>"
                                                            <?php echo e(in_array((int) $category->id, $oldCategoryIds, true) ? 'checked' : ''); ?>>
                                                        <label class="form-check-label" for="category-<?php echo e((int) $category->id); ?>">
                                                            <?php echo e($category->name); ?>

                                                        </label>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                    <p class="text-muted mb-0">Kategori belum tersedia.</p>
                                                <?php endif; ?>
                                            </div>
                                            <?php $__errorArgs = ['category_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <?php $__errorArgs = ['category_ids.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback d-block" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <small class="text-muted" id="category-selection-hint">Pilih kategori sesuai batas paket yang dipilih.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tenant_name" class="form-label">Nama Bisnis/Tenant <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php $__errorArgs = ['tenant_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="tenant_name" value="<?php echo e(old('tenant_name')); ?>" id="tenant_name"
                                                placeholder="Contoh: Gili Tour Lombok" required>
                                            <?php $__errorArgs = ['tenant_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="invalid-feedback">
                                                Please enter business name
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="useremail" class="form-label">Email <span
                                                    class="text-danger">*</span></label>
                                            <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="email" value="<?php echo e(old('email')); ?>" id="useremail"
                                                placeholder="Enter email address" required>
                                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="invalid-feedback">
                                                Please enter email
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Nama Admin <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="name" value="<?php echo e(old('name')); ?>" id="username"
                                                placeholder="Enter admin name" required>
                                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="invalid-feedback">
                                                Please enter admin name
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="billing_cycle" class="form-label">Billing Cycle <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select <?php $__errorArgs = ['billing_cycle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                id="billing_cycle" name="billing_cycle" required>
                                                <option value="monthly" <?php echo e(old('billing_cycle', 'monthly') === 'monthly' ? 'selected' : ''); ?>>
                                                    Monthly
                                                </option>
                                                <option value="yearly" <?php echo e(old('billing_cycle') === 'yearly' ? 'selected' : ''); ?>>
                                                    Yearly
                                                </option>
                                            </select>
                                            <?php $__errorArgs = ['billing_cycle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>

                                        <div class="mb-3">
                                            <label for="userpassword" class="form-label">Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password"
                                                class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password"
                                                id="userpassword" placeholder="Enter password" required>
                                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="invalid-feedback">
                                                Please enter password
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="input-password">Confirm Password <span
                                                class="text-danger">*</span></label>
                                            <input type="password"
                                                class="form-control <?php $__errorArgs = ['password_confirmation'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="password_confirmation" id="input-password"
                                                placeholder="Enter Confirm Password" required>

                                            <div class="form-floating-icon">
                                                <i data-feather="lock"></i>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="input-avatar">Avatar <span class="text-muted">(optional)</span></label>
                                            <input type="file" class="form-control <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                name="avatar" id="input-avatar">
                                            <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                <span class="invalid-feedback" role="alert">
                                                    <strong><?php echo e($message); ?></strong>
                                                </span>
                                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="">
                                                <i data-feather="file"></i>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <p class="mb-0 fs-12 text-muted fst-italic">By registering you agree to the
                                                Velzon <a href="#"
                                                    class="text-primary text-decoration-underline fst-normal fw-medium">Terms
                                                    of Use</a></p>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit">Sign Up</button>
                                        </div>

                                        <div class="mt-4 text-center">
                                            <div class="signin-other-title">
                                                <h5 class="fs-13 mb-4 title text-muted">Create account with</h5>
                                            </div>

                                            <div>
                                                <button type="button"
                                                    class="btn btn-primary btn-icon waves-effect waves-light"><i
                                                        class="ri-facebook-fill fs-16"></i></button>
                                                <button type="button"
                                                    class="btn btn-danger btn-icon waves-effect waves-light"><i
                                                        class="ri-google-fill fs-16"></i></button>
                                                <button type="button"
                                                    class="btn btn-dark btn-icon waves-effect waves-light"><i
                                                        class="ri-github-fill fs-16"></i></button>
                                                <button type="button"
                                                    class="btn btn-info btn-icon waves-effect waves-light"><i
                                                        class="ri-twitter-fill fs-16"></i></button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                        <div class="mt-4 text-center">
                            <p class="mb-0">Already have an account ? <a id="signin-link" href="<?php echo e(route('login', !empty($selectedPlanCodeForView) ? ['plan' => $selectedPlanCodeForView] : [])); ?>"
                                    class="fw-semibold text-primary text-decoration-underline"> Signin </a> </p>
                        </div>

                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end container -->
        </div>
        <!-- end auth page content -->

        <!-- footer -->
        <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <script>
                                document.write(new Date().getFullYear())
                            </script> Velzon. Crafted with <i
                                    class="mdi mdi-heart text-danger"></i> by Themesbrand</p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
        <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <script src="<?php echo e(URL::asset('build/libs/particles.js/particles.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/pages/particles.app.js')); ?>"></script>
    <script src="<?php echo e(URL::asset('build/js/pages/form-validation.init.js')); ?>"></script>
    <script>
        (function() {
            const planSelect = document.getElementById('selected_plan_code');
            const selectedPlanName = document.getElementById('selected-plan-name');
            const signInLink = document.getElementById('signin-link');
            const loginBaseUrl = <?php echo json_encode(route('login'), 15, 512) ?>;
            const categoryRows = Array.from(document.querySelectorAll('[data-category-option]'));
            const categoryHint = document.getElementById('category-selection-hint');

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/auth/register.blade.php ENDPATH**/ ?>