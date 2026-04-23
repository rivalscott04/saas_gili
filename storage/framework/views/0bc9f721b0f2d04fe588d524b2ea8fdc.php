<?php $__env->startSection('title'); ?>
    <?php echo e(__('translation.travel-agents')); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            <?php echo e(__('translation.sales-channels')); ?>

        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            <?php echo e(__('translation.travel-agents')); ?>

        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div id="travelAgentsAjaxContainer">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1"><?php echo e(__('translation.ota-reseller-connections')); ?></h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($tenant->name); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="row mb-4">
                            <div class="col-lg-4">
                                <label class="form-label"><?php echo e(__('translation.tenant')); ?></label>
                                <select class="form-select" id="travelAgentTenantSwitcher">
                                    <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($tenantOption->code); ?>"
                                            <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                            <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php $__empty_1 = true; $__currentLoopData = $travelAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $travelAgent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $connection = $travelAgent->tenantConnections->first();
                                $status = $connection?->status ?? 'disconnected';
                                $statusMap = [
                                    'connected' => ['label' => __('translation.connected'), 'class' => 'bg-success-subtle text-success'],
                                    'error' => ['label' => __('translation.error-status'), 'class' => 'bg-danger-subtle text-danger'],
                                    'disconnected' => ['label' => __('translation.disconnected'), 'class' => 'bg-warning-subtle text-warning'],
                                ];
                                $badge = $statusMap[$status] ?? $statusMap['disconnected'];
                                $logo = $brandingMap[$travelAgent->code] ?? [
                                    'label' => strtoupper(substr($travelAgent->code, 0, 2)),
                                    'class' => 'bg-light text-secondary',
                                    'brand_color' => '#6C757D',
                                    'image' => null,
                                ];
                            ?>
                            <div class="col-xl-4 col-lg-6">
                                <div class="card border h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title rounded-circle <?php echo e($logo['class']); ?> fw-semibold overflow-hidden border"
                                                    style="border-color: <?php echo e($logo['brand_color'] ?? '#6C757D'); ?> !important; color: <?php echo e($logo['brand_color'] ?? '#6C757D'); ?>;">
                                                    <?php if(! empty($logo['image'])): ?>
                                                        <img src="<?php echo e($logo['image']); ?>" alt="<?php echo e($travelAgent->name); ?> logo"
                                                            style="max-width: 70%; max-height: 70%; object-fit: contain;"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                                        <span style="display: none;"><?php echo e($logo['label']); ?></span>
                                                    <?php else: ?>
                                                        <?php echo e($logo['label']); ?>

                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-0"><?php echo e($travelAgent->name); ?></h5>
                                                <small class="text-muted text-uppercase"><?php echo e($travelAgent->code); ?></small>
                                            </div>
                                            <span class="badge <?php echo e($badge['class']); ?>"><?php echo e($badge['label']); ?></span>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted"><?php echo e(__('translation.account-ref')); ?></small>
                                                <small class="fw-medium"><?php echo e($connection?->account_reference ?: '-'); ?></small>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted"><?php echo e(__('translation.last-checked')); ?></small>
                                                <small class="fw-medium"><?php echo e($connection?->last_checked_at?->format('d M Y H:i') ?: '-'); ?></small>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex gap-2 flex-wrap">
                                            <?php if($travelAgent->signup_url): ?>
                                                <a href="<?php echo e($travelAgent->signup_url); ?>" target="_blank" rel="noopener"
                                                    class="btn btn-soft-primary btn-sm">
                                                    <?php echo e(__('translation.sign-up')); ?>

                                                </a>
                                            <?php endif; ?>
                                            <?php if($travelAgent->docs_url): ?>
                                                <a href="<?php echo e($travelAgent->docs_url); ?>" target="_blank" rel="noopener"
                                                    class="btn btn-soft-info btn-sm">
                                                    <?php echo e(__('translation.docs')); ?>

                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-primary btn-sm ms-auto"
                                                data-bs-toggle="modal" data-bs-target="#travelAgentModal<?php echo e($travelAgent->id); ?>">
                                                <?php echo e(__('translation.manage')); ?>

                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="col-12">
                                <div class="text-center text-muted py-4">
                                    <?php echo e(__('translation.no-active-travel-agents')); ?>

                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $__currentLoopData = $travelAgents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $travelAgent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $connection = $travelAgent->tenantConnections->first();
        ?>
        <div class="modal fade" id="travelAgentModal<?php echo e($travelAgent->id); ?>" tabindex="-1"
            aria-labelledby="travelAgentModalLabel<?php echo e($travelAgent->id); ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="travelAgentModalLabel<?php echo e($travelAgent->id); ?>">
                            <?php echo e(__('translation.manage-connection-for')); ?> <?php echo e($travelAgent->name); ?>

                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="<?php echo e(route('travel-agents.connect', $travelAgent)); ?>"
                            id="travelAgentConnectForm<?php echo e($travelAgent->id); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <label class="form-label"><?php echo e(__('translation.api-key-label')); ?></label>
                                    <input type="text" class="form-control" name="api_key" placeholder="Paste API key" required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label"><?php echo e(__('translation.api-secret')); ?></label>
                                    <input type="text" class="form-control" name="api_secret" placeholder="Optional secret">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label"><?php echo e(__('translation.account-ref')); ?></label>
                                    <input type="text" class="form-control" name="account_reference"
                                        value="<?php echo e($connection?->account_reference); ?>" placeholder="Merchant / Supplier ID" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
                                <span></span>
                                <div class="hstack gap-2">
                                    <button type="submit" formaction="<?php echo e(route('travel-agents.test', $travelAgent)); ?>"
                                        class="btn btn-soft-info"><?php echo e(__('translation.test-connection')); ?></button>
                                    <button type="submit" class="btn btn-success"><?php echo e(__('translation.save-connect')); ?></button>
                                </div>
                            </div>
                        </form>
                        <?php if($connection): ?>
                            <form method="POST" action="<?php echo e(route('travel-agents.disconnect', $travelAgent)); ?>" class="mt-2">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="tenant_code" value="<?php echo e($tenant->code); ?>">
                                <button type="submit" class="btn btn-soft-danger"><?php echo e(__('translation.disconnect')); ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <script>
        (function() {
            var initTenantSwitcher = function() {
                var switcher = document.getElementById('travelAgentTenantSwitcher');
                var ajaxContainer = document.getElementById('travelAgentsAjaxContainer');
                if (!switcher || !ajaxContainer) {
                    return;
                }

                switcher.addEventListener('change', function() {
                    var nextUrl = "<?php echo e(route('travel-agents.index')); ?>" + '?tenant=' + encodeURIComponent(this.value);
                    switcher.disabled = true;
                    ajaxContainer.classList.add('opacity-75');

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load travel agents page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshContainer = doc.getElementById('travelAgentsAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed travel agents content.');
                            }
                            ajaxContainer.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initTenantSwitcher();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            switcher.disabled = false;
                            ajaxContainer.classList.remove('opacity-75');
                        });
                });
            };

            initTenantSwitcher();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/saas_gili/resources/views/apps-travel-agents.blade.php ENDPATH**/ ?>