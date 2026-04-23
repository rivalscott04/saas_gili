<?php
    $systemAlert = session('system_alert');
?>

<?php if (! $__env->hasRenderedOnce('6d051f13-e613-4e58-b39e-a31e424b36a8')): $__env->markAsRenderedOnce('6d051f13-e613-4e58-b39e-a31e424b36a8'); ?>
    <style>
        .sa-system-alert-stack ~ .page-title-box,
        .sa-system-alert-stack ~ .app-page-title-box {
            margin-top: 0 !important;
        }
    </style>
<?php endif; ?>

<?php if(is_array($systemAlert) && ! empty($systemAlert['title'])): ?>
    <div class="sa-system-alert-stack">
        <div class="row">
            <div class="col-12">
                <div
                    class="alert alert-warning alert-border-left alert-dismissible fade show mb-3 js-auto-dismiss-alert"
                    role="alert"
                    data-auto-close-ms="5000"
                >
                    <i class="ri-alert-line me-2 align-middle"></i>
                    <strong><?php echo e($systemAlert['title']); ?></strong>
                    <?php if(! empty($systemAlert['message'])): ?>
                        <span class="ms-1"><?php echo e($systemAlert['message']); ?></span>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="sa-system-alert"
        data-sa-reason="<?php echo e($systemAlert['reason'] ?? 'ACCESS_DENIED'); ?>"
        data-sa-icon="<?php echo e($systemAlert['icon'] ?? 'warning'); ?>"
        data-sa-title="<?php echo e($systemAlert['title']); ?>"
        data-sa-text="<?php echo e($systemAlert['message'] ?? ''); ?>"
        class="d-none"
        aria-hidden="true"
    ></div>
<?php endif; ?>

<?php if($errors->any()): ?>
    <div class="sa-system-alert-stack">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-border-left alert-dismissible fade show mb-3" role="alert">
                    <i class="ri-error-warning-line me-2 align-middle"></i>
                    <strong>Validasi gagal.</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $errorMessage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($errorMessage); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (! $__env->hasRenderedOnce('bd527784-53f9-4aac-8266-d3b678eb95a9')): $__env->markAsRenderedOnce('bd527784-53f9-4aac-8266-d3b678eb95a9'); ?>
    <script>
        (function () {
            var initAutoDismissAlerts = function () {
                var alerts = document.querySelectorAll('.js-auto-dismiss-alert');

                alerts.forEach(function (alertEl) {
                    if (alertEl.dataset.autoDismissBound === '1') {
                        return;
                    }

                    alertEl.dataset.autoDismissBound = '1';
                    var closeDelay = parseInt(alertEl.getAttribute('data-auto-close-ms') || '5000', 10);

                    window.setTimeout(function () {
                        if (window.bootstrap && window.bootstrap.Alert) {
                            window.bootstrap.Alert.getOrCreateInstance(alertEl).close();
                            return;
                        }

                        alertEl.classList.remove('show');
                        window.setTimeout(function () {
                            alertEl.remove();
                        }, 200);
                    }, closeDelay);
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAutoDismissAlerts);
            } else {
                initAutoDismissAlerts();
            }
        })();
    </script>
<?php endif; ?>
<?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/layouts/system-alert.blade.php ENDPATH**/ ?>