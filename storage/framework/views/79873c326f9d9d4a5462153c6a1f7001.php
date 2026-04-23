<?php $__env->startSection('title'); ?>
    Detail Invoice
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <style>
        @media print {
            .customizer-setting.d-none.d-md-block,
            .customizer-setting,
            #back-to-top,
            #preloader,
            #theme-settings-offcanvas,
            .offcanvas-backdrop {
                display: none !important;
            }
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $guestName = $booking->customer?->full_name ?? $booking->customer_name ?? '-';
        $invoiceNumber = 'INV-' . optional($booking->tour_start_at)->format('Ymd') . '-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
        $logoUrl = $tenant?->invoice_logo_path ? asset('storage/' . $tenant->invoice_logo_path) : URL::asset('build/images/logo-dark.png');
        $currency = strtoupper((string) ($booking->currency ?? 'IDR'));
        $gross = (float) ($booking->gross_amount ?? 0);
        $net = (float) ($booking->net_amount ?? 0);
        // Satu angka untuk tamu / cetak: total tagihan (bruto jika ada, selain itu net).
        $amountPayable = $gross > 0 ? $gross : $net;
        $hasAmount = $amountPayable > 0;
    ?>

    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Billing
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Invoice <?php echo e($invoiceNumber); ?>

        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row justify-content-center">
        <div class="col-xxl-9">
            <div class="card" id="invoiceDetail">
                <div class="card-header border-bottom-dashed p-4">
                    <div class="d-sm-flex">
                        <div class="flex-grow-1">
                            <img src="<?php echo e($logoUrl); ?>" alt="Invoice Logo" style="max-height: 52px;">
                            <div class="mt-sm-5 mt-4">
                                <h6 class="text-muted text-uppercase fw-semibold mb-1">Tenant</h6>
                                <p class="text-muted mb-0"><?php echo e($tenant?->name ?? 'Default Tenant'); ?></p>
                            </div>
                        </div>
                        <div class="flex-shrink-0 mt-sm-0 mt-3">
                            <h6 class="mb-1"><span class="text-muted fw-normal">Invoice No:</span> <span class="fw-semibold"><?php echo e($invoiceNumber); ?></span></h6>
                            <h6 class="mb-1"><span class="text-muted fw-normal">Booking ID:</span> #<?php echo e($booking->id); ?></h6>
                            <h6 class="mb-0"><span class="text-muted fw-normal">Tour Date:</span>
                                <?php echo e(optional($booking->tour_start_at)->format('d M Y H:i') ?? '-'); ?></h6>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 border-top border-top-dashed">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-uppercase text-muted fw-semibold">Bill To</h6>
                            <p class="mb-1 fw-semibold"><?php echo e($guestName); ?></p>
                            <p class="mb-1"><?php echo e($booking->customer_email ?? '-'); ?></p>
                            <p class="mb-0"><?php echo e($booking->customer_phone ?? '-'); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-uppercase text-muted fw-semibold">Booking Info</h6>
                            <p class="mb-1"><span class="text-muted">Tour:</span> <?php echo e($booking->tour_name ?? '-'); ?></p>
                            <p class="mb-1"><span class="text-muted">Participants:</span> <?php echo e($booking->participants ?? '-'); ?></p>
                            <p class="mb-0"><span class="text-muted">Guide:</span> <?php echo e($booking->guide_name ?? '-'); ?></p>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-borderless text-center table-nowrap align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 56px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 200px;">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td class="text-start">
                                        <div class="fw-semibold">Tour booking</div>
                                        <div class="text-muted small"><?php echo e($booking->tour_name ?? '-'); ?></div>
                                        <?php if($booking->participants): ?>
                                            <div class="text-muted small">Pax: <?php echo e($booking->participants); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if($hasAmount): ?>
                                            <span class="fw-semibold"><?php echo e($currency); ?> <?php echo e(number_format($amountPayable, 2)); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td class="text-start">
                                        <div class="fw-semibold">Lokasi penjemputan</div>
                                        <div class="text-muted small"><?php echo e($booking->location ?? '-'); ?></div>
                                    </td>
                                    <td class="text-end text-muted">—</td>
                                </tr>
                            </tbody>
                            <?php if($hasAmount): ?>
                                <tfoot>
                                    <tr class="border-top">
                                        <td colspan="2" class="text-end pt-3 fw-semibold text-uppercase text-muted">Jumlah pembayaran</td>
                                        <td class="text-end pt-3 fs-16 fw-bold"><?php echo e($currency); ?> <?php echo e(number_format($amountPayable, 2)); ?></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>

                    <?php if(! $hasAmount): ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            Total tagihan untuk booking ini belum tercatat. Perbarui nominal di data booking bila perlu.
                        </div>
                    <?php endif; ?>

                    <div class="hstack gap-2 justify-content-end d-print-none mt-4">
                        <a href="<?php echo e(route('tenant-invoices.index')); ?>" class="btn btn-light">Kembali</a>
                        <a href="javascript:window.print()" class="btn btn-primary">Print Invoice</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-invoices-show.blade.php ENDPATH**/ ?>