<?php $__env->startSection('title'); ?>
    Invoice Booking
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Billing
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Invoice Booking
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Branding Invoice Tenant</h5>
                        <div class="flex-shrink-0">
                    <?php if(auth()->user()?->isAdmin()): ?>
                        <form action="<?php echo e(route('tenant-invoices.branding')); ?>" method="POST" enctype="multipart/form-data"
                            class="d-flex gap-2 align-items-center">
                            <?php echo csrf_field(); ?>
                            <input type="file" class="form-control form-control-sm" name="invoice_logo"
                                accept=".png,.jpg,.jpeg,.webp" required>
                            <button class="btn btn-sm btn-primary" type="submit">Upload Logo</button>
                        </form>
                    <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1">Tenant</p>
                            <h6 class="mb-0"><?php echo e($tenant?->name ?? 'Default Tenant'); ?></h6>
                        </div>
                        <div class="ms-auto">
                            <?php if($tenant?->invoice_logo_path): ?>
                                <img src="<?php echo e(asset('storage/' . $tenant->invoice_logo_path)); ?>" alt="Tenant Logo"
                                    style="max-height: 48px;">
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning">Belum ada logo invoice</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php $__errorArgs = ['invoice_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="alert alert-danger mt-3 mb-0"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" id="invoiceList">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Invoice (Dari Booking)</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary"><?php echo e($bookings->count()); ?> Invoice</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>No Invoice</th>
                                    <th>Guest</th>
                                    <th>Tour</th>
                                    <th>Tanggal Tour</th>
                                    <th>Status Booking</th>
                                    <th class="text-end">Jumlah tagihan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $invoiceNumber = 'INV-' . optional($booking->tour_start_at)->format('Ymd') . '-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo e($invoiceNumber); ?></td>
                                        <td><?php echo e($booking->customer?->full_name ?? $booking->customer_name ?? '-'); ?></td>
                                        <td><?php echo e($booking->tour_name ?? '-'); ?></td>
                                        <td><?php echo e(optional($booking->tour_start_at)->format('d M Y H:i') ?? '-'); ?></td>
                                        <td><span class="badge bg-info-subtle text-info"><?php echo e(ucfirst((string) $booking->status)); ?></span></td>
                                        <td class="text-end">
                                            <?php
                                                $cur = strtoupper((string) ($booking->currency ?? 'IDR'));
                                                $gross = (float) ($booking->gross_amount ?? 0);
                                                $net = (float) ($booking->net_amount ?? 0);
                                                $payable = $gross > 0 ? $gross : $net;
                                            ?>
                                            <?php if($payable > 0): ?>
                                                <span class="fw-semibold"><?php echo e($cur); ?> <?php echo e(number_format($payable, 2)); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo e(route('tenant-invoices.show', $booking)); ?>" class="btn btn-sm btn-soft-primary">
                                                Lihat Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada booking untuk dibuat invoice.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-invoices.blade.php ENDPATH**/ ?>