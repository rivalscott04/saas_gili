<?php $__env->startSection('title'); ?>
    Channel Sync Logs
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Sales Channels
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Sync Logs
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-3">
                        <?php if(auth()->user()?->isSuperAdmin()): ?>
                            <div class="col-lg-3">
                                <label class="form-label">Tenant</label>
                                <select class="form-select" name="tenant">
                                    <option value="">All Tenants</option>
                                    <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($tenantOption->code); ?>"
                                            <?php echo e(($filters['tenant'] ?? '') === (string) $tenantOption->code ? 'selected' : ''); ?>>
                                            <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-lg-3">
                            <label class="form-label">Travel Agent</label>
                            <select class="form-select" name="agent">
                                <option value="">All Agents</option>
                                <?php $__currentLoopData = $travelAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $agent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($agent->code); ?>" <?php echo e(($filters['agent'] ?? '') === (string) $agent->code ? 'selected' : ''); ?>>
                                        <?php echo e($agent->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="success" <?php echo e($filters['status'] === 'success' ? 'selected' : ''); ?>>Success</option>
                                <option value="error" <?php echo e($filters['status'] === 'error' ? 'selected' : ''); ?>>Error</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Event Type</label>
                            <input type="text" class="form-control" name="event_type" value="<?php echo e($filters['event_type']); ?>"
                                placeholder="e.g webhook.received">
                        </div>
                        <div class="col-lg-2 d-grid">
                            <label class="form-label d-none d-lg-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Time</th>
                                    <th>Agent</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($log->occurred_at?->format('d M Y H:i:s') ?? $log->created_at?->format('d M Y H:i:s')); ?></td>
                                        <td><?php echo e($log->travelAgent?->name ?? '-'); ?></td>
                                        <td><code><?php echo e($log->event_type); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo e($log->status === 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'); ?>">
                                                <?php echo e(strtoupper($log->status)); ?>

                                            </span>
                                        </td>
                                        <td><?php echo e($log->message); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada sync logs.</td>
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
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-channel-sync-logs.blade.php ENDPATH**/ ?>