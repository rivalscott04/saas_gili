<?php $__env->startSection('title'); ?>
    Role Permissions
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Tenant
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Role Permissions
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Permission Dinamis Per Role</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary"><?php echo e($tenant?->name ?? 'Tenant'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('tenant-role-permissions.update')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="row mb-4">
                            <?php if(auth()->user()?->isSuperAdmin()): ?>
                                <div class="col-lg-4">
                                    <label class="form-label">Tenant</label>
                                    <select class="form-select"
                                        onchange="window.location.href='<?php echo e(route('tenant-role-permissions.index')); ?>?tenant=' + encodeURIComponent(this.value) + '&role=<?php echo e($selectedRole); ?>'">
                                        <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($tenantOption->code); ?>" <?php echo e(($tenant?->code ?? '') === (string) $tenantOption->code ? 'selected' : ''); ?>>
                                                <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-lg-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select"
                                    onchange="window.location.href='<?php echo e(route('tenant-role-permissions.index')); ?>?tenant=<?php echo e($tenant?->code); ?>&role=' + encodeURIComponent(this.value)">
                                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($role->code); ?>" <?php echo e($selectedRole === $role->code ? 'selected' : ''); ?>>
                                            <?php echo e($role->name); ?> (<?php echo e($role->code); ?>)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive table-card mt-2">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light text-muted text-uppercase">
                                    <tr>
                                        <th>Akses</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Allow</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo e($item['label']); ?></td>
                                            <td>Izin untuk <?php echo e(strtolower($item['label'])); ?>.</td>
                                            <td class="text-end">
                                                <div class="form-check form-switch d-inline-flex justify-content-end">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                                        id="permission_<?php echo e(str_replace('.', '_', $item['key'])); ?>"
                                                        value="<?php echo e($item['key']); ?>" <?php echo e($item['is_allowed'] ? 'checked' : ''); ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <?php $__errorArgs = ['permissions.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="alert alert-danger mt-3 mb-0"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <?php if(auth()->user()?->isSuperAdmin()): ?>
                            <input type="hidden" name="tenant_code" value="<?php echo e($tenant?->code); ?>">
                        <?php endif; ?>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line align-bottom me-1"></i> Simpan Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-tenant-role-permissions.blade.php ENDPATH**/ ?>