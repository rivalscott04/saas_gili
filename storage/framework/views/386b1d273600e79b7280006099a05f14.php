<?php $__env->startSection('title'); ?>
WhatsApp Template Message
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="mt-2">
        <?php $__env->startComponent('components.breadcrumb'); ?>
            <?php $__env->slot('li_1'); ?>
                Apps
            <?php $__env->endSlot(); ?>
            <?php $__env->slot('title'); ?>
                WhatsApp Template Message
            <?php $__env->endSlot(); ?>
        <?php echo $__env->renderComponent(); ?>
    </div>

    <div class="row g-4 pb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ri-information-line text-info fs-18"></i>
                        <p class="mb-0 text-muted">
                            Ubah hanya kata-kata pesan. Field dinamis seperti nama customer, paket, jam, dan magic link akan diisi otomatis dari booking.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Daftar Template</h4>
                    <button type="button" id="btn-new-template" class="btn btn-sm btn-soft-success">
                        <i class="ri-add-line align-bottom me-1"></i> Tambah
                    </button>
                </div>
                <div class="card-body p-0" style="max-height: 420px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <a
                                                href="<?php echo e(route('whatsapp-template-message.index', ['template' => $item->id])); ?>"
                                                class="fw-medium js-template-link <?php echo e((int) ($selectedTemplate?->id ?? 0) === (int) $item->id ? 'text-primary' : ''); ?>"
                                                data-template-id="<?php echo e($item->id); ?>"
                                            >
                                                <?php echo e($item->name); ?>

                                            </a>
                                            <div class="text-muted small">Update: <?php echo e(optional($item->updated_at)->format('d M Y H:i')); ?></div>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="<?php echo e(route('whatsapp-template-message.destroy', $item->id)); ?>" class="js-delete-template-form">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-soft-danger" type="submit" data-confirm-title="Hapus template ini?" data-confirm-text="Template yang dihapus tidak bisa dikembalikan.">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td class="text-muted px-3 py-3">Belum ada template.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-9">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0" id="wa-form-title"><?php echo e($isNewTemplate ? 'Tambah Template Pesan' : 'Edit Template Pesan'); ?></h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?php echo e(route('whatsapp-template-message.update')); ?>">
                                <?php echo csrf_field(); ?>
                        <input type="hidden" name="template_id" id="wa-template-id" value="<?php echo e($selectedTemplate?->id); ?>">

                                <div class="mb-3">
                                    <label class="form-label">Nama Template</label>
                            <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="wa-template-name" name="name" value="<?php echo e($formName); ?>" required>
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

                                <div class="mb-3">
                                    <label class="form-label">Pesan WhatsApp</label>
                                    <input type="hidden" name="content" id="wa-content-input" value="<?php echo e($formContent); ?>">
                                    <div
                                        id="wa-template-editor"
                                        class="form-control <?php $__errorArgs = ['content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        contenteditable="true"
                                        style="min-height: 180px; max-height: 260px; overflow-y: auto; white-space: pre-wrap;"
                                    ></div>
                                    <?php $__errorArgs = ['content'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="text-muted d-block mt-2">
                                        Yang bisa kamu ubah hanya kata-kata pesannya. Bagian dalam kurung seperti nama, paket, jam, dan link akan terisi otomatis.
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Field Otomatis (Read Only)</label>
                                    <div class="row g-2">
                                        <?php $__currentLoopData = $requiredTokens; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $token): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control form-control-sm bg-light" value="<?php echo e($token); ?>" readonly>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-success" type="submit">
                                        <i class="ri-save-line align-bottom me-1"></i> Simpan Template
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card position-sticky" style="top: 88px;">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Preview Pesan</h4>
                        </div>
                        <div class="card-body">
                            <div class="border rounded p-3 bg-light-subtle">
                                <div class="d-flex mb-2">
                                    <span class="badge bg-success-subtle text-success">Live Preview</span>
                                </div>
                                <p class="mb-0 text-body" style="white-space: pre-line; line-height: 1.6;" id="wa-preview-text"><?php echo e($samplePreview); ?></p>
                            </div>
                            <p class="text-muted mt-3 mb-0">
                                Preview ini contoh hasil akhir saat pesan dikirim ke customer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script-bottom'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editor = document.getElementById('wa-template-editor');
        var hiddenInput = document.getElementById('wa-content-input');
        var preview = document.getElementById('wa-preview-text');
        var templateIdInput = document.getElementById('wa-template-id');
        var templateNameInput = document.getElementById('wa-template-name');
        var newTemplateButton = document.getElementById('btn-new-template');
        var formTitle = document.getElementById('wa-form-title');
        var templateLinks = document.querySelectorAll('.js-template-link');
        var requiredTokens = <?php echo json_encode($requiredTokens, 15, 512) ?>;
        var templateMap = <?php echo json_encode($templateMap, 15, 512) ?>;
        var defaultTemplateContent = requiredTokens.join(' ');
        if (!editor || !preview || !hiddenInput) {
            return;
        }

        function escapeRegExp(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function buildEditorFromContent(content) {
            var tokenPattern = new RegExp('(' + requiredTokens.map(escapeRegExp).join('|') + ')', 'g');
            var pieces = String(content || '').split(tokenPattern);
            editor.innerHTML = '';

            pieces.forEach(function (piece) {
                if (!piece) {
                    return;
                }

                if (requiredTokens.includes(piece)) {
                    var tokenEl = document.createElement('span');
                    tokenEl.className = 'badge bg-primary-subtle text-primary me-1';
                    tokenEl.textContent = piece;
                    tokenEl.setAttribute('contenteditable', 'false');
                    tokenEl.setAttribute('data-token', piece);
                    editor.appendChild(tokenEl);
                } else {
                    editor.appendChild(document.createTextNode(piece));
                }
            });
        }

        function serializeEditorContent() {
            var result = '';
            editor.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    result += node.textContent || '';
                    return;
                }

                if (node.nodeType === Node.ELEMENT_NODE && node.getAttribute('data-token')) {
                    result += node.getAttribute('data-token');
                    return;
                }

                result += node.textContent || '';
            });

            return result;
        }

        function renderPreview(content) {
            var customerToken = requiredTokens[0] || '';
            var tourToken = requiredTokens[1] || '';
            var startTimeToken = requiredTokens[2] || '';
            var magicLinkToken = requiredTokens[3] || '';
            preview.textContent = String(content || '')
                .replaceAll(customerToken, 'James Carter')
                .replaceAll(tourToken, 'Gili Trawangan Snorkeling Escape')
                .replaceAll(startTimeToken, '08:00 AM')
                .replaceAll(magicLinkToken, 'https://demo.gilitour.test/booking/123/respond?token=abc123');
        }

        function sync() {
            var content = serializeEditorContent();
            hiddenInput.value = content;
            renderPreview(content);
        }

        function setSelectedTemplateLink(templateId) {
            templateLinks.forEach(function (link) {
                if (String(link.getAttribute('data-template-id')) === String(templateId)) {
                    link.classList.add('text-primary');
                } else {
                    link.classList.remove('text-primary');
                }
            });
        }

        function setEditMode(template) {
            if (!template) {
                return;
            }
            if (templateIdInput) {
                templateIdInput.value = String(template.id);
            }
            if (templateNameInput) {
                templateNameInput.value = template.name || '';
            }
            hiddenInput.value = template.content || '';
            buildEditorFromContent(hiddenInput.value);
            sync();
            setSelectedTemplateLink(template.id);
            if (formTitle) {
                formTitle.textContent = 'Edit Template Pesan';
            }
        }

        buildEditorFromContent(hiddenInput.value || '');
        sync();

        editor.addEventListener('input', sync);
        editor.closest('form').addEventListener('submit', sync);

        if (newTemplateButton) {
            newTemplateButton.addEventListener('click', function () {
                if (templateIdInput) {
                    templateIdInput.value = '';
                }
                if (templateNameInput) {
                    templateNameInput.value = '';
                }
                hiddenInput.value = defaultTemplateContent;
                buildEditorFromContent(defaultTemplateContent);
                sync();
                setSelectedTemplateLink('');
                if (formTitle) {
                    formTitle.textContent = 'Tambah Template Pesan';
                }
                if (templateNameInput) {
                    templateNameInput.focus();
                }
            });
        }

        templateLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var templateId = String(link.getAttribute('data-template-id') || '');
                var template = templateMap[templateId];
                if (!template) {
                    return;
                }
                event.preventDefault();
                setEditMode(template);
                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('template', templateId);
                    url.searchParams.delete('new');
                    window.history.replaceState({}, '', url.toString());
                }
            });
        });

        var deleteTemplateForms = document.querySelectorAll('.js-delete-template-form');
        deleteTemplateForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (typeof Swal === 'undefined') {
                    form.submit();
                    return;
                }

                var triggerButton = form.querySelector('button[type="submit"]');
                Swal.fire({
                    title: (triggerButton && triggerButton.getAttribute('data-confirm-title')) || 'Hapus template ini?',
                    text: (triggerButton && triggerButton.getAttribute('data-confirm-text')) || '',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger w-xs me-2 mt-2',
                        cancelButton: 'btn btn-light w-xs mt-2'
                    },
                    buttonsStyling: false,
                    showCloseButton: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/rival/Documents/code/gilitour/resources/views/apps-whatsapp-template-message.blade.php ENDPATH**/ ?>