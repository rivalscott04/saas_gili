<?php $__env->startSection('title'); ?>
    Tour Management
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Operations & Resources
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Tour Management
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Master Tour</h5>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="mb-3">
                            <label class="form-label">Tenant</label>
                            <select class="form-select" id="tourTenantSwitcher">
                                <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tenantOption->code); ?>"
                                        <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                        <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('tours.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant_code" id="tourCreateTenantCode" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Nama Tour</label>
                            <input type="text" class="form-control" name="name" required maxlength="190">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Tour</label>
                            <input type="text" class="form-control" name="code" maxlength="80"
                                placeholder="Opsional, unik per tenant">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Default max pax / hari</label>
                            <input type="number" class="form-control" name="default_max_pax_per_day" min="1"
                                max="100000" placeholder="Opsional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                max="100000" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="3" name="description" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Aturan alokasi resource</label>
                            <select class="form-select js-allocation-profile-select" name="allocation_requirement"
                                data-prefix="tourCreateReq">
                                <option value="none">Tanpa aturan khusus</option>
                                <option value="snorkeling">Snorkeling (wajib Vehicle / armada pada tanggal tour)</option>
                                <option value="land_activity">Aktivitas darat (wajib Vehicle + Guide/Driver)</option>
                            </select>
                            <small class="text-muted">Mempengaruhi jenis resource yang boleh di-assign dan syarat status
                                Confirmed.</small>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-semibold mb-0">Requirement resource per tour</label>
                                <button type="button" class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                    data-prefix="tourCreateReq">Terapkan preset profile</button>
                            </div>
                            <?php $__currentLoopData = \App\Models\Tour::RESOURCE_TYPE_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-7">
                                        <div class="form-check">
                                            <input type="hidden" name="requirements[<?php echo e($typeKey); ?>][is_required]" value="0">
                                            <input class="form-check-input" type="checkbox"
                                                id="tourCreateReq_<?php echo e($typeKey); ?>_required"
                                                name="requirements[<?php echo e($typeKey); ?>][is_required]" value="1">
                                            <label class="form-check-label" for="tourCreateReq_<?php echo e($typeKey); ?>_required">
                                                <?php echo e($typeLabel); ?> wajib
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="number" class="form-control"
                                            id="tourCreateReq_<?php echo e($typeKey); ?>_min_units"
                                            name="requirements[<?php echo e($typeKey); ?>][min_units]" min="1"
                                            max="1000" value="1" placeholder="Min unit">
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <small class="text-muted d-block">Jika required dicentang, booking harus punya alokasi minimal
                                sesuai min unit pada tanggal keberangkatan sebelum bisa Confirmed.</small>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="tourIsActive" value="1"
                                checked>
                            <label class="form-check-label" for="tourIsActive">Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Tour</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Master Tour</h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($tenant->name); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($tours->isEmpty()): ?>
                        <div class="alert alert-info mb-0">
                            Belum ada master tour untuk tenant ini.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Kode</th>
                                        <th>Max Pax/Hari</th>
                                        <th>Resource Rule</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($tour->name); ?></td>
                                            <td><?php echo e($tour->code ?: '-'); ?></td>
                                            <td><?php echo e($tour->default_max_pax_per_day ?: '-'); ?></td>
                                            <td>
                                                <?php
                                                    $requiredRows = $tour->resourceRequirements->where('is_required', true);
                                                ?>
                                                <?php if($requiredRows->isEmpty()): ?>
                                                    <span class="badge bg-light text-muted">Tanpa requirement</span>
                                                <?php else: ?>
                                                    <?php $__currentLoopData = $requiredRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requiredRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="badge bg-warning-subtle text-warning me-1 mb-1">
                                                            <?php echo e(\App\Models\Tour::RESOURCE_TYPE_LABELS[$requiredRow->resource_type] ?? $requiredRow->resource_type); ?>

                                                            min <?php echo e(max(1, (int) $requiredRow->min_units)); ?>

                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($tour->is_active): ?>
                                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary">Arsip</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal"
                                                    data-bs-target="#editTourModal<?php echo e($tour->id); ?>">
                                                    Edit
                                                </button>
                                                <?php if($tour->is_active): ?>
                                                    <form action="<?php echo e(route('tours.archive', $tour)); ?>" method="POST"
                                                        class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <?php if($showTenantSwitcher): ?>
                                                            <input type="hidden" name="tenant_code"
                                                                value="<?php echo e($tenant->code); ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">
                                                            Arsipkan
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="editTourModal<?php echo e($tour->id); ?>" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="<?php echo e(route('tours.update', $tour)); ?>">
                                                        <?php echo csrf_field(); ?>
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Tour</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php if($showTenantSwitcher): ?>
                                                                <input type="hidden" name="tenant_code"
                                                                    value="<?php echo e($tenant->code); ?>">
                                                            <?php endif; ?>
                                                            <div class="mb-3">
                                                                <label class="form-label">Nama Tour</label>
                                                                <input type="text" class="form-control" name="name"
                                                                    maxlength="190" required
                                                                    value="<?php echo e($tour->name); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Kode Tour</label>
                                                                <input type="text" class="form-control" name="code"
                                                                    maxlength="80" value="<?php echo e($tour->code); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Default max pax / hari</label>
                                                                <input type="number" class="form-control"
                                                                    name="default_max_pax_per_day" min="1"
                                                                    max="100000"
                                                                    value="<?php echo e($tour->default_max_pax_per_day); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Urutan</label>
                                                                <input type="number" class="form-control" name="sort_order"
                                                                    min="0" max="100000"
                                                                    value="<?php echo e((int) $tour->sort_order); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Deskripsi</label>
                                                                <textarea class="form-control" rows="3" name="description" maxlength="5000"><?php echo e($tour->description); ?></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Aturan alokasi resource</label>
                                                                <select class="form-select js-allocation-profile-select"
                                                                    name="allocation_requirement"
                                                                    data-prefix="tourEditReq<?php echo e($tour->id); ?>">
                                                                    <option value="none"
                                                                        <?php echo e(($tour->allocation_requirement ?? 'none') === 'none' ? 'selected' : ''); ?>>
                                                                        Tanpa aturan khusus</option>
                                                                    <option value="snorkeling"
                                                                        <?php echo e(($tour->allocation_requirement ?? '') === 'snorkeling' ? 'selected' : ''); ?>>
                                                                        Snorkeling (Vehicle)</option>
                                                                    <option value="land_activity"
                                                                        <?php echo e(($tour->allocation_requirement ?? '') === 'land_activity' ? 'selected' : ''); ?>>
                                                                        Aktivitas darat (Vehicle + Guide/Driver)</option>
                                                                </select>
                                                            </div>
                                                            <?php
                                                                $requirementsByType = $tour->resourceRequirements
                                                                    ->keyBy('resource_type');
                                                            ?>
                                                            <div class="border rounded p-3 mb-3 bg-light-subtle">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between mb-2">
                                                                    <label class="form-label fw-semibold mb-0">Requirement resource
                                                                        per tour</label>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                                                        data-prefix="tourEditReq<?php echo e($tour->id); ?>">Terapkan preset
                                                                        profile</button>
                                                                </div>
                                                                <?php $__currentLoopData = \App\Models\Tour::RESOURCE_TYPE_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <?php
                                                                        $row = $requirementsByType->get($typeKey);
                                                                        $fallbackRequired =
                                                                            ($tour->allocation_requirement ?? 'none') === 'snorkeling'
                                                                            ? $typeKey === 'vehicle'
                                                                            : (($tour->allocation_requirement ?? 'none') === 'land_activity'
                                                                                ? in_array($typeKey, ['vehicle', 'guide_driver'], true)
                                                                                : false);
                                                                        $isRequired = (bool) ($row->is_required ?? $fallbackRequired);
                                                                        $minUnits = max(1, (int) ($row->min_units ?? 1));
                                                                    ?>
                                                                    <div class="row g-2 align-items-center mb-2">
                                                                        <div class="col-md-7">
                                                                            <div class="form-check">
                                                                                <input type="hidden"
                                                                                    name="requirements[<?php echo e($typeKey); ?>][is_required]"
                                                                                    value="0">
                                                                                <input class="form-check-input"
                                                                                    type="checkbox"
                                                                                    id="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_required"
                                                                                    name="requirements[<?php echo e($typeKey); ?>][is_required]"
                                                                                    value="1"
                                                                                    <?php echo e($isRequired ? 'checked' : ''); ?>>
                                                                                <label class="form-check-label"
                                                                                    for="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_required">
                                                                                    <?php echo e($typeLabel); ?> wajib
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-5">
                                                                            <input type="number" class="form-control"
                                                                                id="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_min_units"
                                                                                name="requirements[<?php echo e($typeKey); ?>][min_units]"
                                                                                min="1" max="1000"
                                                                                value="<?php echo e($minUnits); ?>"
                                                                                placeholder="Min unit">
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_active"
                                                                    id="tourEditActive<?php echo e($tour->id); ?>" value="1"
                                                                    <?php echo e($tour->is_active ? 'checked' : ''); ?>>
                                                                <label class="form-check-label"
                                                                    for="tourEditActive<?php echo e($tour->id); ?>">Aktif</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan
                                                                Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                Menampilkan <?php echo e($tours->firstItem()); ?> - <?php echo e($tours->lastItem()); ?> dari
                                <?php echo e($tours->total()); ?> tour
                            </small>
                            <?php echo e($tours->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <script>
        (function() {
            const tenantSwitcher = document.getElementById('tourTenantSwitcher');
            if (!tenantSwitcher) {
                // keep running to register requirement presets.
            } else {
                tenantSwitcher.addEventListener('change', function() {
                    window.location.href = "<?php echo e(route('tours.index')); ?>" + '?tenant=' + encodeURIComponent(this.value);
                });
            }

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

            document.querySelectorAll('.js-apply-profile-preset').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const prefix = btn.getAttribute('data-prefix');
                    if (!prefix) {
                        return;
                    }
                    const select = document.querySelector('.js-allocation-profile-select[data-prefix="' + prefix + '"]');
                    applyRequirementPreset(prefix, select ? select.value : 'none');
                });
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-tours.blade.php ENDPATH**/ ?>