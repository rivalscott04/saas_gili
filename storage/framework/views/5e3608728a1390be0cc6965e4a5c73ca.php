<?php $__env->startSection('title'); ?>
    Tenant Users
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Tenant
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            User Management
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Tenant</p>
                    <?php if($showTenantSwitcher ?? false): ?>
                        <label class="visually-hidden" for="tenantUserTenantSelect">Pilih tenant</label>
                        <select id="tenantUserTenantSelect" class="form-select form-select-sm"
                            onchange="window.location.href='<?php echo e(route('tenant-users.index')); ?>?tenant=' + encodeURIComponent(this.value)">
                            <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($tenantOption->code); ?>" <?php echo e((int) $selectedTenantId === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                    <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <p class="text-muted small mb-0 mt-2">Ganti tenant di dropdown ini.</p>
                    <?php else: ?>
                        <h5 class="mb-0"><?php echo e($tenant?->name ?? 'Tenant'); ?></h5>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Paket Seat</p>
                    <h5 class="mb-0"><?php echo e($totalUsers); ?>/<?php echo e($maxUsers); ?> User</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Sisa Seat</p>
                    <h5 class="mb-0"><?php echo e($remainingSeats); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Role Custom</p>
                    <h5 class="mb-0"><?php echo e($customRolesCount); ?>/<?php echo e($maxCustomRoles); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" id="tenantUserList">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">Daftar User Tenant</h5>
                            <p class="text-muted mb-0">Kelola user dan role tenant sesuai kuota paket.</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-soft-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantRoleModal">
                                <i class="ri-shield-user-line align-bottom me-1"></i> Tambah Role
                            </button>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantUserModal">
                                <i class="ri-user-add-line align-bottom me-1"></i> Tambah User
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $status = strtolower((string) ($item->status ?? 'active'));
                                        $isActive = $status !== 'suspended';
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo e($item->name); ?></td>
                                        <td><?php echo e($item->email); ?></td>
                                        <td><span class="badge bg-info-subtle text-info"><?php echo e(str($item->role)->replace('_', ' ')->title()); ?></span></td>
                                        <td>
                                            <?php if($isActive): ?>
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e(optional($item->created_at)->format('d M Y H:i')); ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="<?php echo e(route('tenant-users.update-status', $item)); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php if($showTenantSwitcher ?? false): ?>
                                                    <input type="hidden" name="tenant_code" value="<?php echo e($tenant?->code); ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="status" value="<?php echo e($isActive ? 'suspended' : 'active'); ?>">
                                                <button class="btn btn-sm <?php echo e($isActive ? 'btn-soft-danger' : 'btn-soft-success'); ?> js-tenant-user-status-confirm"
                                                    type="submit"
                                                    data-confirm-title="<?php echo e($isActive ? 'Suspend user ini?' : 'Aktifkan kembali user ini?'); ?>"
                                                    data-confirm-text="<?php echo e($isActive ? 'User tidak bisa login sampai diaktifkan kembali.' : 'User akan bisa login kembali.'); ?>"
                                                    data-confirm-button="<?php echo e($isActive ? 'Ya, Suspend' : 'Ya, Aktifkan'); ?>">
                                                    <?php echo e($isActive ? 'Suspend' : 'Activate'); ?>

                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                                                Belum ada user di tenant ini. Tambahkan user pertama untuk mulai kolaborasi.
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($users->count() > 0): ?>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                Menampilkan <?php echo e($users->firstItem()); ?> - <?php echo e($users->lastItem()); ?> dari
                                <?php echo e($users->total()); ?> user
                            </small>
                            <?php echo e($users->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantUserModal" tabindex="-1" aria-labelledby="createTenantUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="createTenantUserModalLabel">Tambah User Tenant</h5>
                        <p class="text-muted mb-0">Buat akun baru dan atur role aksesnya.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo e(route('tenant-users.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if($showTenantSwitcher ?? false): ?>
                        <input type="hidden" name="tenant_code" value="<?php echo e($tenant?->code); ?>">
                    <?php endif; ?>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    value="<?php echo e(old('name')); ?>" placeholder="Contoh: Budi Santoso" autocomplete="name" required>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    value="<?php echo e(old('email')); ?>" placeholder="nama@perusahaan.com" autocomplete="email" required>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select id="roleSelect" name="role" class="form-select <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roleItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($roleItem->code); ?>" <?php echo e(old('role') === $roleItem->code ? 'selected' : ''); ?>><?php echo e($roleItem->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['role'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input id="passwordInput" type="password" name="password"
                                        class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('password')); ?>" minlength="8"
                                        placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                    <button class="btn btn-soft-primary" type="button" id="togglePasswordBtn"
                                        aria-label="Tampilkan atau sembunyikan password">Lihat</button>
                                    <button class="btn btn-soft-info" type="button" id="generatePasswordBtn">Generate</button>
                                </div>
                                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-user-add-line align-bottom me-1"></i> Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantRoleModal" tabindex="-1" aria-labelledby="createTenantRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTenantRoleModalLabel">Tambah Role Custom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo e(route('tenant-users.store-role')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if($showTenantSwitcher ?? false): ?>
                        <input type="hidden" name="tenant_code" value="<?php echo e($tenant?->code); ?>">
                    <?php endif; ?>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Batas role custom mengikuti sisa seat paket: <strong><?php echo e($customRolesCount); ?>/<?php echo e($maxCustomRoles); ?></strong>.
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nama Role</label>
                            <input type="text" name="role_name" class="form-control <?php $__errorArgs = ['role_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" maxlength="120"
                                placeholder="Contoh: Sales Coordinator" required>
                            <?php $__errorArgs = ['role_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="form-text">Setelah role dibuat, atur permission di menu Role Permissions.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> Simpan Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const generatePasswordBtn = document.getElementById('generatePasswordBtn');
            const userModalElement = document.getElementById('createTenantUserModal');
            const roleModalElement = document.getElementById('createTenantRoleModal');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    togglePasswordBtn.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
                });
            }

            if (generatePasswordBtn && passwordInput) {
                generatePasswordBtn.addEventListener('click', function() {
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
                    let generated = '';
                    for (let i = 0; i < 12; i++) {
                        generated += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    passwordInput.value = generated;
                    passwordInput.type = 'text';
                    if (togglePasswordBtn) {
                        togglePasswordBtn.textContent = 'Sembunyikan';
                    }
                });
            }

            <?php if($errors->has('name') || $errors->has('email') || $errors->has('role') || $errors->has('password')): ?>
                if (userModalElement && window.bootstrap) {
                    const userModal = new bootstrap.Modal(userModalElement);
                    userModal.show();
                }
            <?php endif; ?>

            <?php if($errors->has('role_name')): ?>
                if (roleModalElement && window.bootstrap) {
                    const roleModal = new bootstrap.Modal(roleModalElement);
                    roleModal.show();
                }
            <?php endif; ?>

            const statusButtons = document.querySelectorAll('.js-tenant-user-status-confirm');
            statusButtons.forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const form = button.closest('form');
                    if (!form || typeof Swal === 'undefined') {
                        if (form) {
                            form.submit();
                        }
                        return;
                    }

                    Swal.fire({
                        title: button.getAttribute('data-confirm-title') || 'Anda yakin?',
                        text: button.getAttribute('data-confirm-text') || '',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: button.getAttribute('data-confirm-button') || 'Ya',
                        cancelButtonText: 'Batal',
                        customClass: {
                            confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                            cancelButton: 'btn btn-light w-xs mt-2',
                        },
                        buttonsStyling: false,
                        showCloseButton: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-tenant-users.blade.php ENDPATH**/ ?>