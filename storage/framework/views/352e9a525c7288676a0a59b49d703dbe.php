<?php
    $systemAlert = session('system_alert');
?>

<?php if(is_array($systemAlert) && ! empty($systemAlert['title'])): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning alert-border-left alert-dismissible fade show mb-3" role="alert">
                <i class="ri-alert-line me-2 align-middle"></i>
                <strong><?php echo e($systemAlert['title']); ?></strong>
                <?php if(! empty($systemAlert['message'])): ?>
                    <span class="ms-1"><?php echo e($systemAlert['message']); ?></span>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
<?php endif; ?>
<?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/layouts/system-alert.blade.php ENDPATH**/ ?>