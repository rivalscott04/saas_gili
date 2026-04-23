<?php $__env->startSection('title'); ?>
    Operations & Resources
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Operations & Resources
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Resource Management
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tambah Resource</h5>
                </div>
                <div class="card-body">
                    <?php if($showTenantSwitcher): ?>
                        <div class="mb-3">
                            <label class="form-label">Tenant</label>
                            <select class="form-select" id="resourceTenantSwitcher">
                                <?php $__currentLoopData = $availableTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($tenantOption->code); ?>"
                                        <?php echo e((int) $tenant->id === (int) $tenantOption->id ? 'selected' : ''); ?>>
                                        <?php echo e($tenantOption->name); ?> (<?php echo e($tenantOption->code); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('operations-resources.store')); ?>" id="resourceCreateForm">
                        <?php echo csrf_field(); ?>
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant_code" id="resourceCreateTenantCode" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Tipe Resource</label>
                            <select class="form-select" name="resource_type" required>
                                <?php $__currentLoopData = $resourceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resourceTypeKey => $resourceTypeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($resourceTypeKey); ?>"><?php echo e($resourceTypeLabel); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Referensi</label>
                            <input type="text" class="form-control" name="reference_code"
                                placeholder="Contoh: BUS-01 / GDR-02" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" class="form-control" min="1" name="capacity"
                                placeholder="Opsional">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" rows="3" name="notes"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Resource</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Resource Tenant</h5>
                        <span class="badge bg-primary-subtle text-primary"><?php echo e($tenant->name); ?></span>
                        <?php if(auth()->user()?->isSuperAdmin()): ?>
                            <span class="badge bg-info-subtle text-info">Superadmin Config Access</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('operations-resources.index')); ?>" class="row g-2 mb-3"
                        id="resourceFilterForm">
                        <?php if($showTenantSwitcher): ?>
                            <input type="hidden" name="tenant" value="<?php echo e($tenant->code); ?>">
                        <?php endif; ?>
                        <div class="col-lg-4">
                            <input type="text" class="form-control" name="q" id="resourceFilterQuery"
                                value="<?php echo e($filters['q'] ?? ''); ?>"
                                placeholder="Cari nama/kode/catatan">
                        </div>
                        <div class="col-lg-3">
                            <select class="form-select" name="resource_type" id="resourceFilterType">
                                <option value="">Semua tipe</option>
                                <?php $__currentLoopData = $resourceTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resourceTypeKey => $resourceTypeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($resourceTypeKey); ?>"
                                        <?php echo e(($filters['resource_type'] ?? '') === $resourceTypeKey ? 'selected' : ''); ?>>
                                        <?php echo e($resourceTypeLabel); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <select class="form-select" name="status" id="resourceFilterStatus">
                                <option value="">Semua status</option>
                                <option value="available" <?php echo e(($filters['status'] ?? '') === 'available' ? 'selected' : ''); ?>>
                                    Available
                                </option>
                                <option value="blocked" <?php echo e(($filters['status'] ?? '') === 'blocked' ? 'selected' : ''); ?>>
                                    Blocked
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select class="form-select" name="required_by_active_tour" id="resourceFilterRequired">
                                <option value="">Semua keterkaitan</option>
                                <option value="1" <?php echo e(($filters['required_by_active_tour'] ?? '') === '1' ? 'selected' : ''); ?>>
                                    Dipakai tour aktif
                                </option>
                            </select>
                        </div>
                    </form>

                    <div id="resourceListContainer">
                        <?php echo $__env->make('partials.operations-resource-list', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <script>
        (function() {
            const form = document.getElementById('resourceFilterForm');
            const listContainer = document.getElementById('resourceListContainer');
            const tenantSwitcher = document.getElementById('resourceTenantSwitcher');
            const createForm = document.getElementById('resourceCreateForm');
            if (!form) {
                return;
            }
            if (!listContainer) {
                return;
            }

            const typeSelect = document.getElementById('resourceFilterType');
            const statusSelect = document.getElementById('resourceFilterStatus');
            const requiredSelect = document.getElementById('resourceFilterRequired');
            const queryInput = document.getElementById('resourceFilterQuery');
            let debounceTimer = null;
            let inFlightRequest = null;

            const setLoadingState = function(isLoading) {
                listContainer.style.opacity = isLoading ? '0.92' : '1';
                listContainer.style.transition = 'opacity 60ms ease';
            };

            const fetchAndRender = function(url) {
                if (inFlightRequest) {
                    inFlightRequest.abort();
                }

                inFlightRequest = new AbortController();
                setLoadingState(true);

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        },
                        signal: inFlightRequest.signal
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Fetch resources failed');
                        }

                        return response.text();
                    })
                    .then(function(html) {
                        listContainer.innerHTML = html;
                        window.history.replaceState({}, '', url);
                    })
                    .catch(function(error) {
                        if (error.name !== 'AbortError') {
                            window.location.href = url;
                        }
                    })
                    .finally(function() {
                        setLoadingState(false);
                    });
            };

            const submitMutationForm = function(targetForm, onDone) {
                const method = (targetForm.method || 'POST').toUpperCase();
                fetch(targetForm.action, {
                        method: method,
                        body: new FormData(targetForm),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-Current-Url': window.location.href
                        }
                    })
                    .then(function(response) {
                        if (response.status === 422) {
                            return response.json().then(function(payload) {
                                const firstError = payload && payload.errors ?
                                    Object.values(payload.errors).flat()[0] :
                                    'Validasi gagal. Cek kembali input Anda.';
                                throw new Error(firstError);
                            });
                        }

                        if (!response.ok) {
                            throw new Error('Mutation failed');
                        }

                        return response.json();
                    })
                    .then(function(payload) {
                        const redirectUrl = payload && payload.redirect_url ? payload.redirect_url : window.location.href;
                        return fetchAndRender(redirectUrl);
                    })
                    .then(function() {
                        if (typeof onDone === 'function') {
                            onDone();
                        }
                    })
                    .catch(function(error) {
                        if (error && error.message && error.message !== 'Mutation failed') {
                            window.alert(error.message);
                            return;
                        }

                        targetForm.submit();
                    });
            };

            const submitFilter = function() {
                const params = new URLSearchParams(new FormData(form));
                const url = form.action + '?' + params.toString();
                fetchAndRender(url);
            };

            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    submitFilter();
                });
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    submitFilter();
                });
            }

            if (requiredSelect) {
                requiredSelect.addEventListener('change', function() {
                    submitFilter();
                });
            }

            if (queryInput) {
                queryInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        submitFilter();
                    }, 350);
                });
            }

            if (tenantSwitcher) {
                tenantSwitcher.addEventListener('change', function() {
                    const tenantCode = this.value;
                    const createTenantInput = document.getElementById('resourceCreateTenantCode');
                    if (createTenantInput) {
                        createTenantInput.value = tenantCode;
                    }

                    const filterTenantInput = form.querySelector('input[name="tenant"]');
                    if (filterTenantInput) {
                        filterTenantInput.value = tenantCode;
                    }

                    const url = form.action + '?tenant=' + encodeURIComponent(tenantCode);
                    fetchAndRender(url);
                });
            }

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                submitFilter();
            });

            if (createForm) {
                createForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitMutationForm(createForm, function() {
                        const tenantCodeSnapshot = tenantSwitcher ? tenantSwitcher.value : null;
                        createForm.reset();
                        if (tenantSwitcher && tenantCodeSnapshot) {
                            tenantSwitcher.value = tenantCodeSnapshot;
                        }
                        const createTenantInput = document.getElementById('resourceCreateTenantCode');
                        if (createTenantInput && tenantCodeSnapshot) {
                            createTenantInput.value = tenantCodeSnapshot;
                        }
                        const filterTenantInput = form.querySelector('input[name="tenant"]');
                        if (filterTenantInput && tenantCodeSnapshot) {
                            filterTenantInput.value = tenantCodeSnapshot;
                        }
                    });
                });
            }

            listContainer.addEventListener('click', function(event) {
                const paginationLink = event.target.closest('.pagination a');
                if (!paginationLink) {
                    return;
                }

                event.preventDefault();
                fetchAndRender(paginationLink.href);
            });

            listContainer.addEventListener('submit', function(event) {
                const targetForm = event.target;
                if (!(targetForm instanceof HTMLFormElement)) {
                    return;
                }

                // Filter form is handled separately.
                if (targetForm.id === 'resourceFilterForm') {
                    return;
                }

                event.preventDefault();
                submitMutationForm(targetForm, function() {
                    const modalElement = targetForm.closest('.modal');
                    if (modalElement && window.bootstrap && window.bootstrap.Modal) {
                        const modalInstance = window.bootstrap.Modal.getInstance(modalElement);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                });
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-operations-resources.blade.php ENDPATH**/ ?>