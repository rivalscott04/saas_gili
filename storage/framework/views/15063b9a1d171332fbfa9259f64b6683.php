<?php $__env->startSection('title'); ?>
    <?php echo app('translator')->get('translation.slope'); ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Apexcharts
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Slope Charts
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Basic Chart</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <div id="basic_charts" data-colors='["--vz-primary", "--vz-success", "--vz-danger"]' class="apex-charts"
                        dir="ltr"></div>
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div>
        <!-- end col -->

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Multi Group</h4>
                </div><!-- end card header -->

                <div class="card-body">
                    <div>
                        <div id="multi_charts" data-colors='["--vz-primary", "--vz-success", "--vz-warning", "--vz-danger"]'
                            class="apex-charts" dir="ltr"></div>
                    </div>
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div>
        <!-- end col -->
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <!-- apexcharts -->
    <script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
    <!-- slope charts init -->
    <script src="<?php echo e(URL::asset('build/js/pages/apexcharts-slope.init.js')); ?>"></script>
    <!-- App js -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/charts-apex-slope.blade.php ENDPATH**/ ?>