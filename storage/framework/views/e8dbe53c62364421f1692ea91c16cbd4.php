<?php $__env->startSection('title'); ?>
Booking Response
<?php $__env->stopSection(); ?>

<?php $__env->startSection('body'); ?>
<body class="magic-link-page">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<style>
    body.magic-link-page > .container-fluid.pt-3 {
        display: none !important;
    }
</style>
<div class="auth-page-wrapper auth-bg-cover py-0 d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-overlay"></div>
    <div class="auth-page-content overflow-hidden pt-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-5">
                                <div class="p-lg-5 p-4 auth-one-bg h-100">
                                    <div class="bg-overlay"></div>
                                    <div class="position-relative h-100 d-flex flex-column">
                                        <div class="mb-4">
                                            <span class="d-inline-block">
                                                <img src="<?php echo e(URL::asset('build/images/logo-light.png')); ?>" alt="Logo" height="18">
                                            </span>
                                        </div>
                                        <div class="mt-auto text-white">
                                            <h5 class="text-white mb-2">Booking Confirmation</h5>
                                            <p class="mb-0 text-white-75">Please review your booking details and choose one response.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="p-lg-5 p-4">
                                    <?php
                                        $guestName = trim((string) ($booking->customer?->full_name ?? $booking->customer_name ?? ''));
                                        if ($guestName === '') {
                                            $guestName = 'Guest';
                                        }
                                    ?>
                                    <h5 class="text-primary mb-1"><?php echo e($booking->tour_name ?? 'Booking'); ?></h5>
                                    <p class="text-muted mb-1">Hi <?php echo e($guestName); ?>, please review your booking details below.</p>
                                    <p class="text-muted mb-4">
                                        If everything looks good and you do not have any obstacles, you can confirm attendance.
                                        Otherwise, please choose reschedule or cancel. Please pick one option below.
                                    </p>

                                    <?php
                                        $flash = session('magic_link_alert');
                                        $icon = data_get($flash, 'icon', 'info');
                                        $alertClass = match ($icon) {
                                            'success' => 'alert-success',
                                            'warning' => 'alert-warning',
                                            'danger' => 'alert-danger',
                                            default => 'alert-info',
                                        };
                                    ?>
                                    <?php if($flash): ?>
                                        <div class="alert <?php echo e($alertClass); ?> mb-3">
                                            <?php echo e(data_get($flash, 'message')); ?>

                                        </div>
                                    <?php endif; ?>

                                    <?php if($message && ! $flash): ?>
                                        <div class="alert alert-info mb-3"><?php echo e($message); ?></div>
                                    <?php endif; ?>

                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <th class="text-muted">PAX</th>
                                                    <td><?php echo e($booking->participants ?? '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Tour Date</th>
                                                    <td><?php echo e($booking->tour_start_at?->format('d M Y, H:i') ?? '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Status</th>
                                                    <td><span class="badge bg-primary-subtle text-primary text-uppercase"><?php echo e(str_replace('_', ' ', (string) $booking->status)); ?></span></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Guide</th>
                                                    <td><?php echo e($booking->guide_name ?? '-'); ?></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Location</th>
                                                    <td><?php echo e($booking->location ?? '-'); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if($state === 'form'): ?>
                                        <form method="POST" action="<?php echo e(route('bookings.magic-link.submit', $booking->id)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="token" value="<?php echo e($token); ?>">
                                            <div class="d-grid gap-2">
                                                <button type="submit" name="action" value="confirm" class="btn btn-success">
                                                    <i class="ri-checkbox-circle-line align-bottom me-1"></i> Confirm Attendance
                                                </button>
                                                <button type="submit" name="action" value="reschedule" class="btn btn-warning">
                                                    <i class="ri-calendar-event-line align-bottom me-1"></i> Request Reschedule
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger">
                                                    <i class="ri-close-circle-line align-bottom me-1"></i> Cancel Booking
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-light border mb-0">
                                            This response page is no longer actionable.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master-without-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/booking-magic-link.blade.php ENDPATH**/ ?>