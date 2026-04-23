<?php $__env->startSection('title'); ?>
    Booking manual
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            Tour Operations
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Buat booking manual
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <form method="POST" action="<?php echo e(route('bookings.manual.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body checkout-tab">
                        <div class="step-arrow-nav mt-n3 mx-n3 mb-3">
                            <ul class="nav nav-pills nav-justified custom-nav" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 active" id="pills-tour-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-tour" type="button" role="tab" aria-controls="pills-tour"
                                        aria-selected="true"><i
                                            class="ri-map-pin-time-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Tour &amp; jadwal</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-guest-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-guest" type="button" role="tab" aria-controls="pills-guest"
                                        aria-selected="false"><i
                                            class="ri-user-2-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Data tamu</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-ops-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-ops" type="button" role="tab" aria-controls="pills-ops"
                                        aria-selected="false"><i
                                            class="ri-file-list-3-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Logistik &amp; catatan</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-review-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-review" type="button" role="tab"
                                        aria-controls="pills-review" aria-selected="false"><i
                                            class="ri-checkbox-circle-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>Ringkas
                                        &amp; simpan</button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pills-tour" role="tabpanel"
                                aria-labelledby="pills-tour-tab">
                                <div>
                                    <h5 class="mb-1">Produk &amp; keberangkatan</h5>
                                    <p class="text-muted mb-4">Isi nama tur / paket dan waktu keberangkatan.</p>
                                </div>
                                <?php if($tenantOptions->isNotEmpty()): ?>
                                    <div class="mb-3">
                                        <label class="form-label" for="on_behalf_tenant_id">Tenant <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select w-100" id="on_behalf_tenant_id"
                                            name="on_behalf_tenant_id" required>
                                            <option value="">Pilih tenant...</option>
                                            <?php $__currentLoopData = $tenantOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($tenant->id); ?>"
                                                    <?php echo e((string) old('on_behalf_tenant_id', '') === (string) $tenant->id ? 'selected' : ''); ?>>
                                                    <?php echo e($tenant->name); ?> (<?php echo e($tenant->code); ?>)
                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label" for="tour_id">Master tour / paket <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select w-100" id="tour_id" name="tour_id" required>
                                        <option value="">Pilih tour...</option>
                                        <?php
                                            $tourGroups = ($tourOptions ?? collect())->groupBy('tenant_id');
                                            $showTourTenantOptgroups = ($tenantOptions ?? collect())->isNotEmpty();
                                        ?>
                                        <?php $__currentLoopData = $tourGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourTenantId => $toursInTenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($showTourTenantOptgroups): ?>
                                                <optgroup
                                                    label="<?php echo e(optional($toursInTenant->first()->tenant)->name ?? 'Tenant #'.$tourTenantId); ?>">
                                                    <?php $__currentLoopData = $toursInTenant; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($tourOption->id); ?>"
                                                            data-tenant-id="<?php echo e($tourOption->tenant_id); ?>"
                                                            <?php if((string) old('tour_id', '') === (string) $tourOption->id): echo 'selected'; endif; ?>>
                                                            <?php echo e($tourOption->name); ?><?php echo e($tourOption->code ? ' ('.$tourOption->code.')' : ''); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </optgroup>
                                            <?php else: ?>
                                                <?php $__currentLoopData = $toursInTenant; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tourOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($tourOption->id); ?>"
                                                        data-tenant-id="<?php echo e($tourOption->tenant_id); ?>"
                                                        <?php if((string) old('tour_id', '') === (string) $tourOption->id): echo 'selected'; endif; ?>>
                                                        <?php echo e($tourOption->name); ?><?php echo e($tourOption->code ? ' ('.$tourOption->code.')' : ''); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php if(($tourOptions ?? collect())->isEmpty()): ?>
                                        <div class="form-text text-warning mb-0">
                                            Belum ada master tour aktif. Tambahkan dulu di menu
                                            <a href="<?php echo e(route('tours.index')); ?>">Tour Management</a>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-5 col-lg-4">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="tour_start_at">Tanggal &amp; jam <span
                                                    class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="tour_start_at"
                                                name="tour_start_at" value="<?php echo e(old('tour_start_at')); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="participants">PAX <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="participants"
                                                name="participants" min="1" max="999"
                                                value="<?php echo e(old('participants', '2')); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 status-col">
                                        <div class="mb-0 mb-md-3">
                                            <label class="form-label" for="status">Status <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select w-100" id="status" name="status" required>
                                                <?php $__currentLoopData = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'standby' => 'Standby', 'cancelled' => 'Cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($val); ?>"
                                                        <?php echo e(old('status', 'confirmed') === $val ? 'selected' : ''); ?>>
                                                        <?php echo e($label); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-3">
                                    <a href="<?php echo e(url('apps-bookings')); ?>" class="btn btn-light btn-label"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Kembali ke
                                        daftar</a>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-guest-tab"><i
                                            class="ri-user-2-line label-icon align-middle fs-16 ms-2"></i>Lanjut ke data
                                        tamu</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-guest" role="tabpanel"
                                aria-labelledby="pills-guest-tab">
                                <div>
                                    <h5 class="mb-1">Informasi tamu</h5>
                                    <p class="text-muted mb-4">Walk-in, telepon, atau WhatsApp — samakan dengan data
                                        kontak tamu.</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="customer_name">Nama lengkap <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name"
                                        value="<?php echo e(old('customer_name')); ?>" required maxlength="255">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="customer_email">Email <span
                                                    class="text-muted">(opsional)</span></label>
                                            <input type="email" class="form-control" id="customer_email"
                                                name="customer_email" value="<?php echo e(old('customer_email')); ?>"
                                                maxlength="255">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="customer_phone">Telepon / WhatsApp</label>
                                            <input type="text" class="form-control" id="customer_phone"
                                                name="customer_phone" value="<?php echo e(old('customer_phone')); ?>" maxlength="50">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-tour-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Kembali</button>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-ops-tab"><i
                                            class="ri-file-list-3-line label-icon align-middle fs-16 ms-2"></i>Lanjut</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-ops" role="tabpanel" aria-labelledby="pills-ops-tab">
                                <div>
                                    <h5 class="mb-1">Logistik &amp; catatan</h5>
                                    <p class="text-muted mb-4">Lokasi jemput, guide, harga (kalau perlu), dan catatan.
                                        Booking manual selalu tersimpan dengan kanal <strong>MANUAL</strong> di sistem.
                                    </p>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="location">Meeting / pick-up</label>
                                            <input type="text" class="form-control" id="location" name="location"
                                                value="<?php echo e(old('location')); ?>" maxlength="500"
                                                placeholder="Hotel, dermaga, dll.">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="guide_name">Guide (opsional)</label>
                                            <select class="form-select w-100" id="guide_name" name="guide_name">
                                                <option value="" <?php if(old('guide_name', '') === ''): echo 'selected'; endif; ?>>— Belum ditentukan
                                                    —</option>
                                                <?php
                                                    $guideGroups = ($guideUsers ?? collect())->groupBy('tenant_id');
                                                    $showTenantOptgroups = ($tenantOptions ?? collect())->isNotEmpty();
                                                ?>
                                                <?php $__currentLoopData = $guideGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenantId => $guidesInTenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if($showTenantOptgroups): ?>
                                                        <optgroup
                                                            label="<?php echo e(optional($guidesInTenant->first()->tenant)->name ?? 'Tenant #'.$tenantId); ?>">
                                                            <?php $__currentLoopData = $guidesInTenant; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guideUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($guideUser->name); ?>"
                                                                    <?php if(old('guide_name') === $guideUser->name): echo 'selected'; endif; ?>><?php echo e($guideUser->name); ?>

                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </optgroup>
                                                    <?php else: ?>
                                                        <?php $__currentLoopData = $guidesInTenant; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guideUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($guideUser->name); ?>"
                                                                <?php if(old('guide_name') === $guideUser->name): echo 'selected'; endif; ?>><?php echo e($guideUser->name); ?>

                                                            </option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php endif; ?>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                            <?php if(($guideUsers ?? collect())->isEmpty()): ?>
                                                <div class="form-text text-warning mb-0">Belum ada user dengan role
                                                    <strong>guide</strong> di tenant ini. Tambahkan lewat Tenant Users.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if(($canViewRevenue ?? false) === true): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="net_amount">Total harga (net) IDR</label>
                                                <input type="number" step="1" min="0" class="form-control" id="net_amount"
                                                    name="net_amount" value="<?php echo e(old('net_amount', '0')); ?>"
                                                    inputmode="numeric">
                                                <div class="form-text">Opsional. Isi 0 jika belum ada harga pasti;
                                                    gross/komisi/FX tidak perlu diisi untuk booking manual.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="channel_order_id">No. referensi
                                                    (opsional)</label>
                                                <input type="text" class="form-control" id="channel_order_id"
                                                    name="channel_order_id" value="<?php echo e(old('channel_order_id')); ?>"
                                                    maxlength="255" placeholder="Kwitansi, invoice internal, dll.">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label" for="notes">Catatan tamu</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"
                                        maxlength="5000" placeholder="Permintaan khusus, alergi, dll."><?php echo e(old('notes')); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="internal_notes">Catatan internal</label>
                                    <textarea class="form-control" id="internal_notes" name="internal_notes" rows="2"
                                        maxlength="5000"><?php echo e(old('internal_notes')); ?></textarea>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-guest-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Kembali</button>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-review-tab"><i
                                            class="ri-checkbox-circle-line label-icon align-middle fs-16 ms-2"></i>Review</button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pills-review" role="tabpanel"
                                aria-labelledby="pills-review-tab">
                                <div class="text-center py-3">
                                    <div class="mb-4">
                                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                            colors="primary:#0ab39c,secondary:#405189"
                                            style="width:100px;height:100px"></lord-icon>
                                    </div>
                                    <h5 class="mb-2">Simpan booking manual</h5>
                                    <p class="text-muted mb-0">Pastikan tanggal, PAX, dan kontak tamu sudah benar. Setelah
                                        disimpan, booking muncul di daftar Bookings.</p>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4 justify-content-center flex-wrap">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-ops-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Kembali</button>
                                    <button type="submit" class="btn btn-success btn-label">
                                        <i class="ri-save-line label-icon align-middle fs-16 me-2"></i>Simpan booking
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ringkasan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Tur</td>
                                        <td class="text-end fw-medium">Isi di langkah 1</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tamu</td>
                                        <td class="text-end fw-medium">Langkah 2</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Logistik &amp; catatan</td>
                                        <td class="text-end fw-medium">Langkah 3</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info border-0 mt-3 mb-0" role="alert">
                            <i class="ri-information-line me-1 align-middle"></i>
                            Tamu akan dicocokkan ke profil Customer berdasarkan telepon atau email di tenant yang sama.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <style>
        #pills-tour .status-col > div {
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        #pills-tour .status-col .form-label {
            margin-left: 4rem !important;
        }
    </style>
    <script src="<?php echo e(URL::asset('build/js/pages/ecommerce-product-checkout.init.js')); ?>"></script>
    <script>
        (function () {
            var form = document.querySelector('.checkout-tab');
            if (!form) {
                return;
            }

            function validatePane(pane) {
                if (!pane) {
                    return true;
                }
                var controls = pane.querySelectorAll('input, select, textarea');
                for (var i = 0; i < controls.length; i++) {
                    var el = controls[i];
                    if (!el.willValidate) {
                        continue;
                    }
                    if (!el.checkValidity()) {
                        el.reportValidity();
                        return false;
                    }
                }
                return true;
            }

            // Blokir "Lanjut" kalau tab aktif belum valid (Velzon .nexttab hanya .click() tanpa cek form).
            form.querySelectorAll('.nexttab').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    var pane = form.querySelector('.tab-pane.active');
                    if (!validatePane(pane)) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);
            });

            // Blokir loncat tab lewat pill kalau melewati langkah yang wajib diisi.
            var nav = form.querySelector('.custom-nav');
            if (nav) {
                nav.addEventListener('show.bs.tab', function (e) {
                    var tabs = Array.from(nav.querySelectorAll('button[data-bs-toggle="pill"]'));
                    var toBtn = e.target;
                    var fromBtn = e.relatedTarget;
                    var toIdx = tabs.indexOf(toBtn);
                    if (toIdx < 0) {
                        return;
                    }
                    var fromIdx = fromBtn ? tabs.indexOf(fromBtn) : 0;
                    if (fromIdx < 0) {
                        fromIdx = 0;
                    }
                    if (toIdx <= fromIdx) {
                        return;
                    }
                    for (var i = fromIdx; i < toIdx; i++) {
                        var sel = tabs[i].getAttribute('data-bs-target');
                        var pane = sel ? form.querySelector(sel) : null;
                        if (!validatePane(pane)) {
                            e.preventDefault();
                            return;
                        }
                    }
                });
            }

            var tenantSelect = document.getElementById('on_behalf_tenant_id');
            var tourSelect = document.getElementById('tour_id');
            if (tenantSelect && tourSelect) {
                var syncToursByTenant = function () {
                    var tenantId = tenantSelect.value;
                    var selectedStillValid = false;
                    tourSelect.querySelectorAll('option[data-tenant-id]').forEach(function (option) {
                        var allowed = tenantId === '' || option.getAttribute('data-tenant-id') === tenantId;
                        option.hidden = !allowed;
                        if (!allowed && option.selected) {
                            option.selected = false;
                        }
                        if (allowed && option.selected) {
                            selectedStillValid = true;
                        }
                    });
                    if (!selectedStillValid) {
                        tourSelect.value = '';
                    }
                };

                tenantSelect.addEventListener('change', syncToursByTenant);
                syncToursByTenant();
            }
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-bookings-manual-create.blade.php ENDPATH**/ ?>