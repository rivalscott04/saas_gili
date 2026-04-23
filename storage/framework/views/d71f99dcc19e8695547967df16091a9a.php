<?php $__env->startSection('title'); ?>
    Audit Logs
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Operations & Resources
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Audit Logs
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="<?php echo e(route('tenant-audit-logs.index')); ?>" class="row g-3 mb-4">
                <?php if($showTenantSwitcher): ?>
                    <div class="col-md-3">
                        <label class="form-label">Tenant</label>
                        <select name="tenant" class="form-select">
                            <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tenantOption->code); ?>" <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                    <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Tour</label>
                    <select name="tour_id" class="form-select">
                        <option value="">Semua tour</option>
                        <?php $__currentLoopData = $tours; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($tour->id); ?>" <?php echo e((string) $filters['tour_id'] === (string) $tour->id ? 'selected' : ''); ?>>
                                <?php echo e($tour->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="<?php echo e($filters['from']); ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="<?php echo e($filters['to']); ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Event</label>
                    <select name="event_type" class="form-select">
                        <option value="">Semua event</option>
                        <?php $__currentLoopData = $eventTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eventType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($eventType); ?>" <?php echo e($filters['event_type'] === $eventType ? 'selected' : ''); ?>>
                                <?php echo e($eventType); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Event</th>
                            <th>Tour</th>
                            <th>Service Date</th>
                            <th>Actor</th>
                            <th>Entity</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><?php echo e(optional($log->occurred_at)->format('d M Y H:i:s')); ?></td>
                                <td><span class="badge bg-info-subtle text-info"><?php echo e($log->event_type); ?></span></td>
                                <td><?php echo e($log->tour?->name ?? '-'); ?></td>
                                <td><?php echo e(optional($log->service_date)->format('Y-m-d') ?? '-'); ?></td>
                                <td><?php echo e($log->actor?->name ?? 'system'); ?></td>
                                <td><?php echo e($log->entity_type); ?><?php echo e($log->entity_id ? '#'.$log->entity_id : ''); ?></td>
                                <td><code><?php echo e(json_encode($log->context, JSON_UNESCAPED_UNICODE)); ?></code></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada audit log.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <?php echo e($logs->links()); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-audit-logs.blade.php ENDPATH**/ ?>