<?php $__env->startSection('title'); ?>
    <?php echo e(__('translation.tour-daily-capacity')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            <?php echo e(__('translation.operations-resources')); ?>

        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo e(__('translation.set-daily-quota-per-date')); ?>

        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row" id="tourDayCapacityAjaxContainer">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo e(__('translation.choose-tour')); ?></h5>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.tenant')); ?></label>
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
                        <label class="form-label"><?php echo e(__('translation.main-tour')); ?></label>
                        <select class="form-select mb-3" name="tour_id" id="tourCapacityPicker" required>
                            <option value=""><?php echo e(__('translation.select-placeholder')); ?></option>
                            <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($t->id); ?>"
                                    <?php echo e($selectedTour && (int) $selectedTour->id === (int) $t->id ? 'selected' : ''); ?>>
                                    <?php echo e($t->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </form>
                    <p class="text-muted small mb-0">
                        <?php echo e(__('translation.tour-daily-capacity-help')); ?>

                    </p>
                </div>
            </div>
            <?php if($selectedTour): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo e(__('translation.add-update-special-date-quota')); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('tour-day-capacities.store')); ?>">
                            <?php echo csrf_field(); ?>
                            <?php if($showTenantSwitcher): ?>
                                <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="tour_id" value="<?php echo e($selectedTour->id); ?>">
                            <div class="mb-3">
                                <label class="form-label"><?php echo e(__('translation.service-date')); ?></label>
                                <input type="date" class="form-control" name="service_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?php echo e(__('translation.max-participants')); ?></label>
                                <input type="number" class="form-control" name="max_pax" min="1" max="100000" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?php echo e(__('translation.save-this-date-quota')); ?></button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo e(__('translation.special-date-quota-list')); ?></h5>
                    <?php if($selectedTour): ?>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($selectedTour->name); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if(! $selectedTour): ?>
                        <div class="alert alert-info mb-0"><?php echo e(__('translation.choose-tour-to-manage-daily-capacity')); ?></div>
                    <?php elseif($capacities->isEmpty()): ?>
                        <div class="alert alert-light border mb-0"><?php echo e(__('translation.no-special-date-quota-yet')); ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th><?php echo e(__('translation.date')); ?></th>
                                        <th><?php echo e(__('translation.max-participants')); ?></th>
                                        <th class="text-end"><?php echo e(__('translation.action')); ?></th>
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
                                                    onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('translation.confirm-delete-special-date-quota'))->toHtml() ?>);">
                                                    <?php echo csrf_field(); ?>
                                                    <?php if($showTenantSwitcher): ?>
                                                        <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-sm btn-soft-danger"><?php echo e(__('translation.delete')); ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                <?php echo e(__('translation.showing-range-of-total-capacity-overrides', ['from' => $capacities->firstItem(), 'to' => $capacities->lastItem(), 'total' => $capacities->total()])); ?>

                            </small>
                            <?php echo e($capacities->links()); ?>

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
            var initCapacityAjax = function() {
                var switcher = document.getElementById('capacityTenantSwitcher');
                var picker = document.getElementById('tourCapacityPicker');
                var ajaxContainer = document.getElementById('tourDayCapacityAjaxContainer');
                var baseUrl = "<?php echo e(route('tour-day-capacities.index')); ?>";

                var buildUrl = function(tenantCode, tourId) {
                    var params = new URLSearchParams();
                    if (tenantCode) {
                        params.set('tenant', tenantCode);
                    }
                    if (tourId) {
                        params.set('tour_id', tourId);
                    }
                    var query = params.toString();
                    return query ? (baseUrl + '?' + query) : baseUrl;
                };

                var refreshPageSection = function(nextUrl) {
                    if (!ajaxContainer) {
                        window.location.href = nextUrl;
                        return;
                    }

                    if (switcher) {
                        switcher.disabled = true;
                    }
                    if (picker) {
                        picker.disabled = true;
                    }
                    ajaxContainer.classList.add('opacity-75');

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load capacity page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshContainer = doc.getElementById('tourDayCapacityAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed capacity content.');
                            }
                            ajaxContainer.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initCapacityAjax();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            if (switcher) {
                                switcher.disabled = false;
                            }
                            if (picker) {
                                picker.disabled = false;
                            }
                            ajaxContainer.classList.remove('opacity-75');
                        });
                };

                if (switcher && !switcher.dataset.ajaxBound) {
                    switcher.dataset.ajaxBound = '1';
                    switcher.addEventListener('change', function() {
                        var selectedTour = picker ? picker.value : '';
                        refreshPageSection(buildUrl(this.value, selectedTour));
                    });
                }

                if (picker && !picker.dataset.ajaxBound) {
                    picker.dataset.ajaxBound = '1';
                    picker.addEventListener('change', function() {
                        var selectedTenant = switcher ? switcher.value : '';
                        refreshPageSection(buildUrl(selectedTenant, this.value));
                    });
                }
            };

            initCapacityAjax();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-tour-day-capacities.blade.php ENDPATH**/ ?>