<?php $__env->startSection('title'); ?>
    <?php echo e(__('translation.tenant-categories')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            <?php echo e(__('translation.customers-settings')); ?>

        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo e(__('translation.tenant-categories')); ?>

        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="card" id="tenantCategoriesPanel">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo e(__('translation.business-segmentation')); ?></h5>
            <p class="text-muted mb-0 small"><?php echo e(__('translation.choose-category-for-tenant', ['tenant' => $tenant->name])); ?></p>
        </div>
        <div class="card-body">
            <?php if($showTenantSwitcher): ?>
                <div class="mb-4">
                    <label class="form-label"><?php echo e(__('translation.tenant')); ?></label>
                    <select class="form-select" id="tenantCategorySwitcher" style="max-width: 28rem;">
                        <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($tenantOption->code); ?>"
                                <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('tenant-categories.update')); ?>">
                <?php echo csrf_field(); ?>
                <?php if($showTenantSwitcher): ?>
                    <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category_ids[]"
                                value="<?php echo e($category->id); ?>" id="cat-<?php echo e($category->id); ?>"
                                <?php echo e(in_array((int) $category->id, $selectedCategoryIds, true) ? 'checked' : ''); ?>>
                            <label class="form-check-label" for="cat-<?php echo e($category->id); ?>">
                                <strong><?php echo e($category->name); ?></strong>
                                <span class="text-muted">(<?php echo e($category->code); ?>)</span>
                            </label>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo e(__('translation.save-categories')); ?></button>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <script>
        (function() {
            var initTenantCategoriesAjax = function() {
                var switcher = document.getElementById('tenantCategorySwitcher');
                var panel = document.getElementById('tenantCategoriesPanel');
                if (!switcher || !panel || switcher.dataset.ajaxBound) {
                    return;
                }

                switcher.dataset.ajaxBound = '1';
                var applyLoadingState = function(isLoading) {
                    switcher.disabled = isLoading;
                    panel.classList.toggle('opacity-75', isLoading);
                };

                switcher.addEventListener('change', function() {
                    var nextUrl = "<?php echo e(route('tenant-categories.index')); ?>" + '?tenant=' + encodeURIComponent(this.value);
                    applyLoadingState(true);

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load tenant categories.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshPanel = doc.getElementById('tenantCategoriesPanel');
                            if (!freshPanel) {
                                throw new Error('Missing refreshed panel.');
                            }
                            panel.outerHTML = freshPanel.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initTenantCategoriesAjax();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            applyLoadingState(false);
                        });
                });
            };

            initTenantCategoriesAjax();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-tenant-categories.blade.php ENDPATH**/ ?>