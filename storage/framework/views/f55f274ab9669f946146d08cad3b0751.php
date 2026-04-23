<?php $__env->startSection('title'); ?>
    <?php echo e(__('translation.tour-management')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            <?php echo e(__('translation.operations-resources')); ?>

        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo e(__('translation.tour-management')); ?>

        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row" id="toursAjaxContainer">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo e(__('translation.add-tour-master')); ?></h5>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.tenant')); ?></label>
                            <select class="form-select" id="tourTenantSwitcher">
                                <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tenantOption->code); ?>"
                                        <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                        <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('tours.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant_code" id="tourCreateTenantCode" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.tour-name')); ?></label>
                            <input type="text" class="form-control" name="name" required maxlength="190">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.tour-code')); ?></label>
                            <input type="text" class="form-control" name="code" maxlength="80"
                                placeholder="<?php echo e(__('translation.optional-unique-per-tenant')); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.default-max-pax-day')); ?></label>
                            <input type="number" class="form-control" name="default_max_pax_per_day" min="1"
                                max="100000" placeholder="<?php echo e(__('translation.optional')); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.order')); ?></label>
                            <input type="number" class="form-control" name="sort_order" min="0"
                                max="100000" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.description')); ?></label>
                            <textarea class="form-control" rows="3" name="description" maxlength="5000"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('translation.resource-allocation-rule')); ?></label>
                            <select class="form-select js-allocation-profile-select" name="allocation_requirement"
                                data-prefix="tourCreateReq">
                                <option value="none"><?php echo e(__('translation.no-special-rule')); ?></option>
                                <option value="snorkeling"><?php echo e(__('translation.snorkeling-rule')); ?></option>
                                <option value="land_activity"><?php echo e(__('translation.land-activity-rule')); ?></option>
                            </select>
                            <small class="text-muted"><?php echo e(__('translation.resource-rule-help')); ?></small>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-semibold mb-0"><?php echo e(__('translation.resource-requirement-per-tour')); ?></label>
                                <button type="button" class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                    data-prefix="tourCreateReq"><?php echo e(__('translation.apply-preset-profile')); ?></button>
                            </div>
                            <?php $__currentLoopData = \App\Models\Tour::RESOURCE_TYPE_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-7">
                                        <div class="form-check">
                                            <input type="hidden" name="requirements[<?php echo e($typeKey); ?>][is_required]" value="0">
                                            <input class="form-check-input" type="checkbox"
                                                id="tourCreateReq_<?php echo e($typeKey); ?>_required"
                                                name="requirements[<?php echo e($typeKey); ?>][is_required]" value="1">
                                            <label class="form-check-label" for="tourCreateReq_<?php echo e($typeKey); ?>_required">
                                                <?php echo e(__('translation.resource-required-label', ['label' => $typeLabel])); ?>

                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="number" class="form-control"
                                            id="tourCreateReq_<?php echo e($typeKey); ?>_min_units"
                                            name="requirements[<?php echo e($typeKey); ?>][min_units]" min="1"
                                            max="1000" value="1" placeholder="Min unit">
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <small class="text-muted d-block"><?php echo e(__('translation.required-min-units-help')); ?></small>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="tourIsActive" value="1"
                                checked>
                                <label class="form-check-label" for="tourIsActive"><?php echo e(__('translation.active')); ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo e(__('translation.save-tour')); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1"><?php echo e(__('translation.tour-master-list')); ?></h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($tenant->name); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($tours->isEmpty()): ?>
                        <div class="alert alert-info mb-0">
                            <?php echo e(__('translation.no-tour-master-for-tenant')); ?>

                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th><?php echo e(__('translation.name')); ?></th>
                                        <th><?php echo e(__('translation.code')); ?></th>
                                        <th><?php echo e(__('translation.max-pax-day')); ?></th>
                                        <th><?php echo e(__('translation.resource-rule')); ?></th>
                                        <th><?php echo e(__('translation.status')); ?></th>
                                        <th class="text-end"><?php echo e(__('translation.action')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <?php
                                                $requiredRows = $tour->resourceRequirements->where('is_required', true);
                                                $primaryRequiredType = optional($requiredRows->first())->resource_type;
                                                $resourceLinkParams = [];
                                                if ($showTenantSwitcher) {
                                                    $resourceLinkParams['tenant'] = $tenant->code;
                                                }
                                                if ($requiredRows->count() > 1) {
                                                    $resourceLinkParams['required_by_active_tour'] = '1';
                                                } elseif ($primaryRequiredType) {
                                                    $resourceLinkParams['resource_type'] = $primaryRequiredType;
                                                }
                                                $resourceLink = route('operations-resources.index', $resourceLinkParams);
                                            ?>
                                            <td><?php echo e($tour->name); ?></td>
                                            <td><?php echo e($tour->code ?: '-'); ?></td>
                                            <td><?php echo e($tour->default_max_pax_per_day ?: '-'); ?></td>
                                            <td>
                                                <?php if($requiredRows->isEmpty()): ?>
                                                    <span class="badge bg-light text-muted"><?php echo e(__('translation.no-requirement')); ?></span>
                                                <?php else: ?>
                                                    <?php $__currentLoopData = $requiredRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requiredRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="badge bg-warning-subtle text-warning me-1 mb-1">
                                                            <?php echo e(\App\Models\Tour::RESOURCE_TYPE_LABELS[$requiredRow->resource_type] ?? $requiredRow->resource_type); ?>

                                                            min <?php echo e(max(1, (int) $requiredRow->min_units)); ?>

                                                        </span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($tour->is_active): ?>
                                                    <span class="badge bg-success-subtle text-success"><?php echo e(__('translation.active')); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary"><?php echo e(__('translation.archived')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo e($resourceLink); ?>" class="btn btn-sm btn-soft-dark">
                                                    <?php echo e(__('translation.resource')); ?>

                                                </a>
                                                <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal"
                                                    data-bs-target="#editTourModal<?php echo e($tour->id); ?>">
                                                    <?php echo e(__('translation.edit')); ?>

                                                </button>
                                                <?php if($tour->is_active): ?>
                                                    <form action="<?php echo e(route('tours.archive', $tour)); ?>" method="POST"
                                                        class="d-inline">
                                                        <?php echo csrf_field(); ?>
                                                        <?php if($showTenantSwitcher): ?>
                                                            <input type="hidden" name="tenant_code"
                                                                value="<?php echo e($tenant->code); ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">
                                                            <?php echo e(__('translation.archive')); ?>

                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="editTourModal<?php echo e($tour->id); ?>" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="<?php echo e(route('tours.update', $tour)); ?>">
                                                        <?php echo csrf_field(); ?>
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo e(__('translation.edit-tour')); ?></h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php if($showTenantSwitcher): ?>
                                                                <input type="hidden" name="tenant_code"
                                                                    value="<?php echo e($tenant->code); ?>">
                                                            <?php endif; ?>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.tour-name')); ?></label>
                                                                <input type="text" class="form-control" name="name"
                                                                    maxlength="190" required
                                                                    value="<?php echo e($tour->name); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.tour-code')); ?></label>
                                                                <input type="text" class="form-control" name="code"
                                                                    maxlength="80" value="<?php echo e($tour->code); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.default-max-pax-day')); ?></label>
                                                                <input type="number" class="form-control"
                                                                    name="default_max_pax_per_day" min="1"
                                                                    max="100000"
                                                                    value="<?php echo e($tour->default_max_pax_per_day); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.order')); ?></label>
                                                                <input type="number" class="form-control" name="sort_order"
                                                                    min="0" max="100000"
                                                                    value="<?php echo e((int) $tour->sort_order); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.description')); ?></label>
                                                                <textarea class="form-control" rows="3" name="description" maxlength="5000"><?php echo e($tour->description); ?></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label"><?php echo e(__('translation.resource-allocation-rule')); ?></label>
                                                                <select class="form-select js-allocation-profile-select"
                                                                    name="allocation_requirement"
                                                                    data-prefix="tourEditReq<?php echo e($tour->id); ?>">
                                                                    <option value="none"
                                                                        <?php echo e(($tour->allocation_requirement ?? 'none') === 'none' ? 'selected' : ''); ?>>
                                                                        <?php echo e(__('translation.no-special-rule')); ?></option>
                                                                    <option value="snorkeling"
                                                                        <?php echo e(($tour->allocation_requirement ?? '') === 'snorkeling' ? 'selected' : ''); ?>>
                                                                        <?php echo e(__('translation.snorkeling-short')); ?></option>
                                                                    <option value="land_activity"
                                                                        <?php echo e(($tour->allocation_requirement ?? '') === 'land_activity' ? 'selected' : ''); ?>>
                                                                        <?php echo e(__('translation.land-activity-short')); ?></option>
                                                                </select>
                                                            </div>
                                                            <?php
                                                                $requirementsByType = $tour->resourceRequirements
                                                                    ->keyBy('resource_type');
                                                            ?>
                                                            <div class="border rounded p-3 mb-3 bg-light-subtle">
                                                                <div
                                                                    class="d-flex align-items-center justify-content-between mb-2">
                                                                    <label class="form-label fw-semibold mb-0"><?php echo e(__('translation.resource-requirement-per-tour')); ?></label>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-soft-secondary js-apply-profile-preset"
                                                                        data-prefix="tourEditReq<?php echo e($tour->id); ?>"><?php echo e(__('translation.apply-preset-profile')); ?></button>
                                                                </div>
                                                                <?php $__currentLoopData = \App\Models\Tour::RESOURCE_TYPE_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <?php
                                                                        $row = $requirementsByType->get($typeKey);
                                                                        $fallbackRequired =
                                                                            ($tour->allocation_requirement ?? 'none') === 'snorkeling'
                                                                            ? $typeKey === 'vehicle'
                                                                            : (($tour->allocation_requirement ?? 'none') === 'land_activity'
                                                                                ? in_array($typeKey, ['vehicle', 'guide_driver'], true)
                                                                                : false);
                                                                        $isRequired = (bool) ($row->is_required ?? $fallbackRequired);
                                                                        $minUnits = max(1, (int) ($row->min_units ?? 1));
                                                                    ?>
                                                                    <div class="row g-2 align-items-center mb-2">
                                                                        <div class="col-md-7">
                                                                            <div class="form-check">
                                                                                <input type="hidden"
                                                                                    name="requirements[<?php echo e($typeKey); ?>][is_required]"
                                                                                    value="0">
                                                                                <input class="form-check-input"
                                                                                    type="checkbox"
                                                                                    id="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_required"
                                                                                    name="requirements[<?php echo e($typeKey); ?>][is_required]"
                                                                                    value="1"
                                                                                    <?php echo e($isRequired ? 'checked' : ''); ?>>
                                                                                <label class="form-check-label"
                                                                                    for="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_required">
                                                                                    <?php echo e(__('translation.resource-required-label', ['label' => $typeLabel])); ?>

                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-5">
                                                                            <input type="number" class="form-control"
                                                                                id="tourEditReq<?php echo e($tour->id); ?>_<?php echo e($typeKey); ?>_min_units"
                                                                                name="requirements[<?php echo e($typeKey); ?>][min_units]"
                                                                                min="1" max="1000"
                                                                                value="<?php echo e($minUnits); ?>"
                                                                                placeholder="Min unit">
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="is_active"
                                                                    id="tourEditActive<?php echo e($tour->id); ?>" value="1"
                                                                    <?php echo e($tour->is_active ? 'checked' : ''); ?>>
                                                                <label class="form-check-label"
                                                                    for="tourEditActive<?php echo e($tour->id); ?>"><?php echo e(__('translation.active')); ?></label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light"
                                                                data-bs-dismiss="modal"><?php echo e(__('translation.cancel')); ?></button>
                                                            <button type="submit" class="btn btn-primary"><?php echo e(__('translation.save-changes')); ?></button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                <?php echo e(__('translation.showing-range-of-total', ['from' => $tours->firstItem(), 'to' => $tours->lastItem(), 'total' => $tours->total()])); ?>

                            </small>
                            <?php echo e($tours->links()); ?>

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
            const applyRequirementPreset = function(prefix, profile) {
                const profileValue = String(profile || 'none').toLowerCase();
                const map = {
                    vehicle: profileValue === 'snorkeling' || profileValue === 'land_activity',
                    guide_driver: profileValue === 'land_activity',
                    equipment: false,
                };
                ['vehicle', 'guide_driver', 'equipment'].forEach(function(typeKey) {
                    const requiredEl = document.getElementById(prefix + '_' + typeKey + '_required');
                    const minUnitsEl = document.getElementById(prefix + '_' + typeKey + '_min_units');
                    if (requiredEl) {
                        requiredEl.checked = !!map[typeKey];
                    }
                    if (minUnitsEl && (!minUnitsEl.value || Number(minUnitsEl.value) < 1)) {
                        minUnitsEl.value = '1';
                    }
                });
            };

            const initToursInteraction = function() {
                const ajaxContainer = document.getElementById('toursAjaxContainer');
                const tenantSwitcher = document.getElementById('tourTenantSwitcher');

                if (tenantSwitcher && !tenantSwitcher.dataset.ajaxBound) {
                    tenantSwitcher.dataset.ajaxBound = '1';
                    tenantSwitcher.addEventListener('change', function() {
                        const nextUrl = "<?php echo e(route('tours.index')); ?>" + '?tenant=' + encodeURIComponent(this.value);
                        tenantSwitcher.disabled = true;
                        if (ajaxContainer) {
                            ajaxContainer.classList.add('opacity-75');
                        }

                        fetch(nextUrl, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(function(response) {
                                if (!response.ok) {
                                    throw new Error('Failed to load tours page.');
                                }
                                return response.text();
                            })
                            .then(function(html) {
                                const doc = new DOMParser().parseFromString(html, 'text/html');
                                const freshContainer = doc.getElementById('toursAjaxContainer');
                                if (!freshContainer || !ajaxContainer) {
                                    throw new Error('Missing refreshed tours content.');
                                }
                                ajaxContainer.outerHTML = freshContainer.outerHTML;
                                history.pushState({}, '', nextUrl);
                                initToursInteraction();
                            })
                            .catch(function() {
                                window.location.href = nextUrl;
                            })
                            .finally(function() {
                                tenantSwitcher.disabled = false;
                                if (ajaxContainer) {
                                    ajaxContainer.classList.remove('opacity-75');
                                }
                            });
                    });
                }

                document.querySelectorAll('.js-apply-profile-preset').forEach(function(btn) {
                    if (btn.dataset.presetBound) {
                        return;
                    }
                    btn.dataset.presetBound = '1';
                    btn.addEventListener('click', function() {
                        const prefix = btn.getAttribute('data-prefix');
                        if (!prefix) {
                            return;
                        }
                        const select = document.querySelector('.js-allocation-profile-select[data-prefix="' + prefix + '"]');
                        applyRequirementPreset(prefix, select ? select.value : 'none');
                    });
                });
            };

            initToursInteraction();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-tours.blade.php ENDPATH**/ ?>