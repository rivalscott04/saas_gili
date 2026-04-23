<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th><?php echo e(__('translation.type')); ?></th>
                <th><?php echo e(__('translation.name')); ?></th>
                <th><?php echo e(__('translation.code')); ?></th>
                <th><?php echo e(__('translation.capacity')); ?></th>
                <th><?php echo e(__('translation.used-by-tour')); ?></th>
                <th><?php echo e(__('translation.status')); ?></th>
                <th class="text-end"><?php echo e(__('translation.action')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $resources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($resourceTypes[$item->resource_type] ?? $item->resource_type); ?></td>
                    <td><?php echo e($item->name); ?></td>
                    <td><?php echo e($item->reference_code ?: '-'); ?></td>
                    <td><?php echo e($item->capacity ?: '-'); ?></td>
                    <td>
                        <?php
                            $linkedTours = (array) ($tourRequirementsByResourceType[$item->resource_type] ?? []);
                        ?>
                        <?php if($linkedTours === []): ?>
                            <span class="text-muted">-</span>
                        <?php else: ?>
                            <?php $__currentLoopData = $linkedTours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourInfo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $tourUrl = $showTenantSwitcher
                                        ? route('tours.index', ['tenant' => $tenant->code])
                                        : route('tours.index');
                                ?>
                                <a href="<?php echo e($tourUrl); ?>"
                                    class="badge text-decoration-none <?php echo e(!empty($tourInfo['is_active']) ? 'bg-info-subtle text-info' : 'bg-light text-muted'); ?> me-1 mb-1"
                                    title="<?php echo e(__('translation.open-tour-management-help')); ?>">
                                    <?php echo e($tourInfo['tour_name'] ?? 'Tour'); ?> (min <?php echo e($tourInfo['min_units'] ?? 1); ?>)
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?php echo e(route('operations-resources.block-out', $item)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php if($showTenantSwitcher): ?>
                                <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="blocked_from" value="<?php echo e(now()->format('Y-m-d H:i:s')); ?>">
                            <input type="hidden" name="block_reason" value="Set from operations panel">
                            <select name="status"
                                class="form-select form-select-sm border-0 fw-semibold <?php echo e($item->status === 'blocked' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'); ?>"
                                style="width: 130px; border-radius: .5rem;"
                                onchange="this.form.requestSubmit()">
                                <option value="available" <?php echo e($item->status === 'available' ? 'selected' : ''); ?>><?php echo e(__('translation.available')); ?></option>
                                <option value="blocked" <?php echo e($item->status === 'blocked' ? 'selected' : ''); ?>><?php echo e(__('translation.blocked')); ?></option>
                            </select>
                        </form>
                    </td>
                    <td class="text-end">
                        <div class="hstack gap-1 justify-content-end">
                            <button type="button" class="btn btn-sm btn-soft-info"
                                data-bs-toggle="modal" data-bs-target="#editResourceModal<?php echo e($item->id); ?>">
                                <?php echo e(__('translation.edit')); ?>

                            </button>
                            <form method="POST"
                                action="<?php echo e(route('operations-resources.destroy', $item)); ?>"
                                onsubmit="return confirm(<?php echo \Illuminate\Support\Js::from(__('translation.confirm-delete-resource'))->toHtml() ?>);">
                                <?php echo csrf_field(); ?>
                                <?php if($showTenantSwitcher): ?>
                                    <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-sm btn-soft-danger"><?php echo e(__('translation.delete')); ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="text-muted"><?php echo e(__('translation.no-resource-data')); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3">
    <?php echo e($resources->links()); ?>

</div>

<?php $__currentLoopData = $resources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="modal fade" id="editResourceModal<?php echo e($item->id); ?>" tabindex="-1"
        aria-labelledby="editResourceModalLabel<?php echo e($item->id); ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResourceModalLabel<?php echo e($item->id); ?>"><?php echo e(__('translation.edit-resource')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="<?php echo e(route('operations-resources.update', $item)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="sync_tour_usage" value="1">
                        <?php
                            $selectedTourIds = collect((array) ($tourRequirementsByResourceType[$item->resource_type] ?? []))
                                ->pluck('tour_id')
                                ->map(fn ($tourId) => (int) $tourId)
                                ->all();
                        ?>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label"><?php echo e(__('translation.resource-type')); ?></label>
                                <select class="form-select" name="resource_type" required>
                                    <?php $__currentLoopData = $resourceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resourceTypeKey => $resourceTypeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($resourceTypeKey); ?>"
                                            <?php echo e($item->resource_type === $resourceTypeKey ? 'selected' : ''); ?>>
                                            <?php echo e($resourceTypeLabel); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label"><?php echo e(__('translation.name')); ?></label>
                                <input type="text" class="form-control" name="name" value="<?php echo e($item->name); ?>" required>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label"><?php echo e(__('translation.reference-code')); ?></label>
                                <input type="text" class="form-control" name="reference_code"
                                    value="<?php echo e($item->reference_code); ?>" required>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label"><?php echo e(__('translation.capacity')); ?></label>
                                <input type="number" class="form-control" min="1" name="capacity"
                                    value="<?php echo e($item->capacity); ?>">
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label"><?php echo e(__('translation.notes')); ?></label>
                                <textarea class="form-control" rows="3" name="notes"><?php echo e($item->notes); ?></textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label"><?php echo e(__('translation.used-by-tour')); ?></label>
                                <?php if(isset($availableTours) && $availableTours->isNotEmpty()): ?>
                                    <div class="border rounded p-3 bg-light-subtle"
                                        style="max-height: 220px; overflow-y: auto;">
                                        <?php $__currentLoopData = $availableTours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    id="resourceTour<?php echo e($item->id); ?>_<?php echo e($tourOption->id); ?>"
                                                    name="tour_ids[]" value="<?php echo e($tourOption->id); ?>"
                                                    <?php echo e(in_array((int) $tourOption->id, $selectedTourIds, true) ? 'checked' : ''); ?>>
                                                <label class="form-check-label"
                                                    for="resourceTour<?php echo e($item->id); ?>_<?php echo e($tourOption->id); ?>">
                                                    <?php echo e($tourOption->name); ?>

                                                    <?php if(! $tourOption->is_active): ?>
                                                        <span class="text-muted">(arsip)</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Menentukan tour mana yang mewajibkan tipe resource ini saat alokasi/konfirmasi.
                                    </small>
                                <?php else: ?>
                                    <div class="alert alert-light border mb-0">
                                        Belum ada master tour. Tambahkan tour dulu untuk mengatur keterkaitan resource ini.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary"><?php echo e(__('translation.save-changes')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/partials/operations-resource-list.blade.php ENDPATH**/ ?>