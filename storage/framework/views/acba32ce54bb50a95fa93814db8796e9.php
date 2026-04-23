<?php $__env->startSection('title'); ?>
    Pricing
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Superadmin
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Pricing
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-primary border-0" role="alert">
                Kelola <strong>paket & harga</strong> landing (<code>/</code> saat belum login). Klik <strong>Edit</strong> untuk
                mengubah detail di modal. <strong>Max users</strong> pada paket bertanda <strong>Popular</strong> dipakai sebagai
                kuota seat untuk semua tenant (setelah simpan).
            </div>
        </div>
    </div>

    <?php if($plans->isEmpty()): ?>
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
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar paket</h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($plans->count()); ?> paket</span>
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
                                    <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="text-center"><?php echo e($plan->sort_order); ?></td>
                                            <td class="fw-semibold"><?php echo e($plan->name); ?></td>
                                            <td class="text-muted"><?php echo e($plan->subtitle ?: '—'); ?></td>
                                            <td class="text-end"><?php echo e($plan->price_monthly); ?></td>
                                            <td class="text-end"><?php echo e($plan->price_yearly); ?></td>
                                            <td class="text-center small text-muted">
                                                <span class="badge bg-body-secondary text-body"><?php echo e((int) ($plan->category_slots_included ?? 1)); ?> slot</span>
                                                <div class="mt-1">+<?php echo e((int) ($plan->extra_category_price_monthly ?? 0)); ?>/bln</div>
                                            </td>
                                            <td class="text-center">
                                                <?php if($plan->max_users === null): ?>
                                                    <span class="badge bg-info-subtle text-info">∞</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary"><?php echo e($plan->max_users); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($plan->is_popular): ?>
                                                    <span class="badge bg-warning-subtle text-warning">Ya</span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="d-inline-flex align-items-center gap-2">
                                                    <span
                                                        class="avatar-title bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        style="width: 2rem; height: 2rem;">
                                                        <i class="<?php echo e($plan->icon_class); ?> fs-16"></i>
                                                    </span>
                                                    <code class="fs-11 text-muted mb-0"><?php echo e($plan->icon_class); ?></code>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-soft-primary"
                                                    data-bs-toggle="modal" data-bs-target="#planModal<?php echo e($plan->id); ?>">
                                                    <i class="ri-pencil-line align-bottom"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $pid = $plan->id;
                $pkey = "plans.{$pid}";
            ?>
            <div class="modal fade" id="planModal<?php echo e($pid); ?>" tabindex="-1" aria-labelledby="planModalLabel<?php echo e($pid); ?>"
                aria-hidden="true">
                
                
                <div class="modal-dialog modal-xl my-3" style="height: calc(100vh - 2rem); max-height: calc(100vh - 2rem);">
                    <div class="modal-content border-0 shadow h-100 d-flex flex-column overflow-hidden">
                        <form method="POST" action="<?php echo e(route('superadmin-landing-pricing.update', $plan)); ?>"
                            class="d-flex flex-column h-100" style="min-height: 0;">
                            <?php echo csrf_field(); ?>
                            <div class="modal-header p-3 bg-info-subtle border-bottom flex-shrink-0">
                                <div>
                                    <h5 class="modal-title mb-1" id="planModalLabel<?php echo e($pid); ?>"><?php echo e($plan->name); ?></h5>
                                    <p class="text-muted mb-2">Edit paket landing &amp; daftar fitur.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-secondary-subtle text-secondary"><?php echo e($plan->code); ?></span>
                                        <?php if($plan->is_popular): ?>
                                            <span class="badge bg-warning-subtle text-warning">Popular</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 flex-grow-1 overflow-y-auto" style="min-height: 0;">
                                <?php
                                    $planHasErrors = $errors->any() && collect($errors->keys())->contains(fn ($k) => str_starts_with((string) $k, $pkey.'.'));
                                ?>
                                <?php if($planHasErrors): ?>
                                    <div class="alert alert-danger border-0 mb-4" role="alert">
                                        Ada field yang tidak valid. Perbaiki lalu simpan lagi.
                                    </div>
                                <?php endif; ?>
                                <div class="row g-3 mb-1">
                                    <div class="col-lg-6">
                                        <label for="planName<?php echo e($pid); ?>" class="form-label">Nama paket</label>
                                        <input type="text" id="planName<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][name]"
                                            class="form-control" value="<?php echo e(old($pkey.'.name', $plan->name)); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="planSubtitle<?php echo e($pid); ?>" class="form-label">Subtitle</label>
                                        <input type="text" id="planSubtitle<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][subtitle]"
                                            class="form-control" value="<?php echo e(old($pkey.'.subtitle', $plan->subtitle)); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planPriceMonth<?php echo e($pid); ?>" class="form-label">Harga / bulan (USD)</label>
                                        <input type="number" id="planPriceMonth<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][price_monthly]"
                                            class="form-control" min="0"
                                            value="<?php echo e(old($pkey.'.price_monthly', $plan->price_monthly)); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planPriceYear<?php echo e($pid); ?>" class="form-label">Harga / tahun (USD)</label>
                                        <input type="number" id="planPriceYear<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][price_yearly]"
                                            class="form-control" min="0"
                                            value="<?php echo e(old($pkey.'.price_yearly', $plan->price_yearly)); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="planSort<?php echo e($pid); ?>" class="form-label">Urutan tampil</label>
                                        <input type="number" id="planSort<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][sort_order]"
                                            class="form-control" min="0" max="255"
                                            value="<?php echo e(old($pkey.'.sort_order', $plan->sort_order)); ?>">
                                    </div>
                                    <?php
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
                                    ?>
                                    <div class="col-12">
                                        <div class="card border shadow-none mb-0 bg-body-secondary">
                                            <div class="card-body p-3 p-md-4">
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-lg-7">
                                                        <label for="planIcon<?php echo e($pid); ?>" class="form-label mb-1">Ikon di
                                                            landing</label>
                                                        <select id="planIcon<?php echo e($pid); ?>" name="plans[<?php echo e($pid); ?>][icon_class]"
                                                            class="form-select js-plan-icon-select"
                                                            data-preview-id="planIconPreview<?php echo e($pid); ?>" required>
                                                            <?php if($legacyIcon): ?>
                                                                <option value="<?php echo e($legacyIcon); ?>" <?php if($selectedIcon === $legacyIcon): echo 'selected'; endif; ?>>
                                                                    (Tersimpan, luar daftar) <?php echo e($legacyIcon); ?>

                                                                </option>
                                                            <?php endif; ?>
                                                            <?php $__currentLoopData = \App\Models\LandingPricingPlan::REMIX_ICON_OPTIONS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($opt['value']); ?>"
                                                                    <?php if($selectedIcon === strtolower($opt['value'])): echo 'selected'; endif; ?>>
                                                                    <?php echo e($opt['label']); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                                                <i id="planIconPreview<?php echo e($pid); ?>"
                                                                    class="<?php echo e($selectedIcon); ?> fs-24 lh-1 d-inline-block"></i>
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
                                                <input type="hidden" name="plans[<?php echo e($pid); ?>][is_popular]" value="0">
                                                <div class="form-check form-switch form-switch-lg">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="plans[<?php echo e($pid); ?>][is_popular]" value="1" id="popular<?php echo e($pid); ?>"
                                                        <?php if(old($pkey.'.is_popular', $plan->is_popular ? '1' : '0') === '1'): echo 'checked'; endif; ?>>
                                                    <label class="form-check-label" for="popular<?php echo e($pid); ?>">Tampilkan badge
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
                                                <input type="hidden" name="plans[<?php echo e($pid); ?>][max_users_unlimited]" value="0">
                                                <div class="form-check form-switch form-switch-lg mb-3">
                                                    <input class="form-check-input js-unlimited-users" type="checkbox"
                                                        name="plans[<?php echo e($pid); ?>][max_users_unlimited]" value="1"
                                                        id="unlimited<?php echo e($pid); ?>" data-target="#maxUsers<?php echo e($pid); ?>"
                                                        <?php if(old($pkey.'.max_users_unlimited', $plan->max_users === null ? '1' : '0') === '1'): echo 'checked'; endif; ?>>
                                                    <label class="form-check-label" for="unlimited<?php echo e($pid); ?>">Unlimited</label>
                                                </div>
                                                <label for="maxUsers<?php echo e($pid); ?>" class="form-label small">Jumlah (bila tidak
                                                    unlimited)</label>
                                                <input type="number" name="plans[<?php echo e($pid); ?>][max_users]" id="maxUsers<?php echo e($pid); ?>"
                                                    class="form-control" min="1" max="999999"
                                                    value="<?php echo e(old($pkey.'.max_users', $plan->max_users ?? 1)); ?>"
                                                    <?php if(old($pkey.'.max_users_unlimited', $plan->max_users === null ? '1' : '0') === '1'): echo 'disabled'; endif; ?>>
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
                                            Harga bulan/tahun di atas = hingga <strong><?php echo e((int) ($plan->category_slots_included ?? 1)); ?></strong> kategori bisnis.
                                            Setiap kategori tambahan: +<strong><?php echo e((int) ($plan->extra_category_price_monthly ?? 0)); ?></strong> USD/bulan atau
                                            +<strong><?php echo e((int) ($plan->extra_category_price_yearly ?? 0)); ?></strong> USD/tahun.
                                            Kosongkan semua centang di bawah agar <strong>semua</strong> kategori aktif diperbolehkan (mode terbuka).
                                        </p>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label" for="catSlots<?php echo e($pid); ?>">Slot kategori dalam harga dasar</label>
                                                <input type="number" id="catSlots<?php echo e($pid); ?>" class="form-control" min="1" max="50"
                                                    name="plans[<?php echo e($pid); ?>][category_slots_included]"
                                                    value="<?php echo e(old($pkey.'.category_slots_included', (int) ($plan->category_slots_included ?? 1))); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="catExtraMo<?php echo e($pid); ?>">Extra / kategori / bulan (USD)</label>
                                                <input type="number" id="catExtraMo<?php echo e($pid); ?>" class="form-control" min="0" max="999999"
                                                    name="plans[<?php echo e($pid); ?>][extra_category_price_monthly]"
                                                    value="<?php echo e(old($pkey.'.extra_category_price_monthly', (int) ($plan->extra_category_price_monthly ?? 0))); ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label" for="catExtraYr<?php echo e($pid); ?>">Extra / kategori / tahun (USD)</label>
                                                <input type="number" id="catExtraYr<?php echo e($pid); ?>" class="form-control" min="0" max="999999"
                                                    name="plans[<?php echo e($pid); ?>][extra_category_price_yearly]"
                                                    value="<?php echo e(old($pkey.'.extra_category_price_yearly', (int) ($plan->extra_category_price_yearly ?? 0))); ?>" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label d-block mb-2">Batasi ke kategori tertentu (opsional)</label>
                                                <?php
                                                    $selectedAllowedIds = collect(
                                                        old($pkey.'.allowed_category_ids', $plan->allowedCategories->pluck('id')->all()),
                                                    )
                                                        ->map(fn ($v) => (int) $v)
                                                        ->unique()
                                                        ->all();
                                                ?>
                                                <?php $__empty_1 = true; $__currentLoopData = $allCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="plans[<?php echo e($pid); ?>][allowed_category_ids][]" value="<?php echo e($category->id); ?>"
                                                            id="plan<?php echo e($pid); ?>cat<?php echo e($category->id); ?>"
                                                            <?php if(in_array((int) $category->id, $selectedAllowedIds, true)): echo 'checked'; endif; ?>>
                                                        <label class="form-check-label" for="plan<?php echo e($pid); ?>cat<?php echo e($category->id); ?>">
                                                            <?php echo e($category->name); ?> <span class="text-muted">(<?php echo e($category->code); ?>)</span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                                    <div class="alert alert-warning mb-0 py-2">Belum ada kategori aktif. Jalankan
                                                        <code>php artisan db:seed --class=TenantCategorySeeder</code> atau buat kategori dulu.
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border mt-4 mb-0">
                                    <div class="card-header d-flex flex-wrap align-items-center gap-3 px-4 py-3 border-bottom bg-light">
                                        <h6 class="card-title mb-0 flex-grow-1">Fitur di landing</h6>
                                        <button type="button" class="btn btn-sm btn-primary js-add-feature"
                                            data-table="#featureTable<?php echo e($pid); ?>" data-plan-id="<?php echo e($pid); ?>">
                                            <i class="ri-add-line align-bottom me-1"></i> Tambah baris
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-lg table-hover align-middle mb-0" id="featureTable<?php echo e($pid); ?>">
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
                                                    <?php
                                                        $oldFeatures = old($pkey.'.features');
                                                        $featureRows = is_array($oldFeatures)
                                                            ? collect($oldFeatures)
                                                            : $plan->features->map(fn ($f) => [
                                                                'display_text' => $f->display_text,
                                                                'is_included' => $f->is_included ? '1' : '0',
                                                            ]);
                                                    ?>
                                                    <?php $__currentLoopData = $featureRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td class="ps-4 pe-3 py-3">
                                                                <input type="text"
                                                                    name="plans[<?php echo e($pid); ?>][features][<?php echo e($idx); ?>][display_text]"
                                                                    class="form-control"
                                                                    value="<?php echo e($row['display_text'] ?? ''); ?>"
                                                                    placeholder="Contoh: Upto 15 Projects" required>
                                                            </td>
                                                            <td class="px-4 py-3 text-center">
                                                                <select name="plans[<?php echo e($pid); ?>][features][<?php echo e($idx); ?>][is_included]"
                                                                    class="form-select mx-auto" style="max-width: 240px;">
                                                                    <option value="1" <?php if(($row['is_included'] ?? '1') === '1' || ($row['is_included'] ?? true) === true): echo 'selected'; endif; ?>>Ya (hijau)</option>
                                                                    <option value="0" <?php if(($row['is_included'] ?? '1') === '0' || ($row['is_included'] ?? true) === false): echo 'selected'; endif; ?>>Tidak (merah)</option>
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
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

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

        <?php if(! empty($openPlanModalId)): ?>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('planModal<?php echo e($openPlanModalId); ?>');
                if (!el || !window.bootstrap || !bootstrap.Modal) return;
                var m = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el);
                m.show();
            });
        <?php endif; ?>
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-superadmin-landing-pricing.blade.php ENDPATH**/ ?>