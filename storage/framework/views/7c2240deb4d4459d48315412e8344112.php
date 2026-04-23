<?php $__env->startSection('title'); ?> Candidate Grid View <?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Candidate Lists <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Grid View <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row g-4 mb-3">
    <div class="col-sm-auto">
        <div>
            <a href="#!" class="btn btn-success"><i class="ri-add-line align-bottom me-1"></i> Add Candidate</a>
        </div>
    </div>
    <div class="col-sm">
        <div class="d-md-flex justify-content-sm-end gap-2">
            <div class="search-box ms-md-2 flex-shrink-0 mb-3 mb-md-0">
                <input type="text" class="form-control" id="searchJob" placeholder="Search for candidate name or designation..." autocomplete="off">
                <i class="ri-search-line search-icon"></i>
            </div>

            <select class="form-control w-md" data-choices data-choices-search-false>
                <option value="All">All</option>
                <option value="Today">Today</option>
                <option value="Yesterday" selected>Yesterday</option>
                <option value="Last 7 Days">Last 7 Days</option>
                <option value="Last 30 Days">Last 30 Days</option>
                <option value="This Month">This Month</option>
                <option value="Last Year">Last Year</option>
            </select>
        </div>
    </div>
</div>

<div class="row job-list-row" id="candidate-list">

</div>

<div class="row g-0 justify-content-end mb-4" id="pagination-element">
    <!-- end col -->
    <div class="col-sm-6">
        <div class="pagination-block pagination pagination-separated justify-content-center justify-content-sm-end mb-sm-0">
            <div class="page-item">
                <a href="javascript:void(0);" class="page-link" id="page-prev">Previous</a>
            </div>
            <span id="page-num" class="pagination"></span>
            <div class="page-item">
                <a href="javascript:void(0);" class="page-link" id="page-next">Next</a>
            </div>
        </div>
    </div><!-- end col -->
</div>
<!-- end row -->

<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
<!-- job-candidate-grid js -->
<script src="<?php echo e(URL::asset('build/js/pages/job-candidate-grid.init.js')); ?>"></script>

<!-- App js -->
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-job-candidate-grid.blade.php ENDPATH**/ ?>