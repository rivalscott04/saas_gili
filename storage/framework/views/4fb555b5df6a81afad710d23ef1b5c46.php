<?php $__env->startSection('title'); ?>
    Kapasitas harian tour
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Operations & Resources
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Atur kuota peserta per tanggal
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pilih tour</h5>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="mb-3">
                            <label class="form-label">Tenant</label>
                            <select class="form-select" id="capacityTenantSwitcher">
                                <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tenantOption->code); ?>"
                                        <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                        <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <form method="GET" action="<?php echo e(route('tour-day-capacities.index')); ?>" id="tourPickForm">
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <label class="form-label">Tour utama</label>
                        <select class="form-select mb-3" name="tour_id" required onchange="this.form.submit()">
                            <option value="">— Pilih —</option>
                            <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($t->id); ?>"
                                    <?php echo e($selectedTour && (int) $selectedTour->id === (int) $t->id ? 'selected' : ''); ?>>
                                    <?php echo e($t->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </form>
                    <p class="text-muted small mb-0">
                        Jika belum diatur untuk tanggal tertentu, sistem memakai <strong>kuota standar per hari</strong> dari tour utama.
                    </p>
                </div>
            </div>
            <?php if($selectedTour): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tambah / ubah kuota tanggal khusus</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('tour-day-capacities.store')); ?>">
                            <?php echo csrf_field(); ?>
                            <?php if($showTenantSwitcher): ?>
                                <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="tour_id" value="<?php echo e($selectedTour->id); ?>">
                            <div class="mb-3">
                                <label class="form-label">Tanggal layanan</label>
                                <input type="date" class="form-control" name="service_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maksimal peserta</label>
                                <input type="number" class="form-control" name="max_pax" min="1" max="100000" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan kuota tanggal ini</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar kuota tanggal khusus</h5>
                    <?php if($selectedTour): ?>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($selectedTour->name); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if(! $selectedTour): ?>
                        <div class="alert alert-info mb-0">Pilih tour di kiri untuk mengelola kapasitas harian.</div>
                    <?php elseif($capacities->isEmpty()): ?>
                        <div class="alert alert-light border mb-0">Belum ada pengaturan tanggal khusus; semua tanggal memakai kuota standar tour.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Maksimal peserta</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $capacities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($row->service_date?->format('Y-m-d')); ?></td>
                                            <td><?php echo e($row->max_pax); ?></td>
                                            <td class="text-end">
                                                <form method="POST"
                                                    action="<?php echo e(route('tour-day-capacities.destroy', $row)); ?>"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Hapus kuota khusus untuk tanggal ini?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php if($showTenantSwitcher): ?>
                                                        <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
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
            var switcher = document.getElementById('capacityTenantSwitcher');
            if (!switcher) {
                return;
            }
            switcher.addEventListener('change', function() {
                var url = "<?php echo e(route('tour-day-capacities.index')); ?>" + '?tenant=' + encodeURIComponent(this.value);
                window.location.href = url;
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-tour-day-capacities.blade.php ENDPATH**/ ?>