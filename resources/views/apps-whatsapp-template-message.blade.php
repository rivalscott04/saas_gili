@extends('layouts.master')

@section('title')
WhatsApp Template Message
@endsection

@section('content')
    <div class="mt-2">
        @component('components.breadcrumb')
            @slot('li_1')
                Apps
            @endslot
            @slot('title')
                WhatsApp Template Message
            @endslot
        @endcomponent
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
                                @forelse ($templates as $item)
                                    <tr>
                                        <td>
                                            <a
                                                href="{{ route('whatsapp-template-message.index', ['template' => $item->id]) }}"
                                                class="fw-medium js-template-link {{ (int) ($selectedTemplate?->id ?? 0) === (int) $item->id ? 'text-primary' : '' }}"
                                                data-template-id="{{ $item->id }}"
                                            >
                                                {{ $item->name }}
                                            </a>
                                            <div class="text-muted small">Update: {{ optional($item->updated_at)->format('d M Y H:i') }}</div>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('whatsapp-template-message.destroy', $item->id) }}" class="js-delete-template-form">
                                                @csrf
                                                <button class="btn btn-sm btn-soft-danger" type="submit" data-confirm-title="Hapus template ini?" data-confirm-text="Template yang dihapus tidak bisa dikembalikan.">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted px-3 py-3">Belum ada template.</td>
                                    </tr>
                                @endforelse
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
                            <h4 class="card-title mb-0" id="wa-form-title">{{ $isNewTemplate ? 'Tambah Template Pesan' : 'Edit Template Pesan' }}</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('whatsapp-template-message.update') }}">
                                @csrf
                        <input type="hidden" name="template_id" id="wa-template-id" value="{{ $selectedTemplate?->id }}">

                                <div class="mb-3">
                                    <label class="form-label">Nama Template</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="wa-template-name" name="name" value="{{ $formName }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pesan WhatsApp</label>
                                    <input type="hidden" name="content" id="wa-content-input" value="{{ $formContent }}">
                                    <div
                                        id="wa-template-editor"
                                        class="form-control @error('content') is-invalid @enderror"
                                        contenteditable="true"
                                        style="min-height: 180px; max-height: 260px; overflow-y: auto; white-space: pre-wrap;"
                                    ></div>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-2">
                                        Yang bisa kamu ubah hanya kata-kata pesannya. Bagian dalam kurung seperti nama, paket, jam, dan link akan terisi otomatis.
                                    </small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Field Otomatis (Read Only)</label>
                                    <div class="row g-2">
                                        @foreach ($requiredTokens as $token)
                                            <div class="col-sm-6">
                                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $token }}" readonly>
                                            </div>
                                        @endforeach
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
                                <p class="mb-0 text-body" style="white-space: pre-line; line-height: 1.6;" id="wa-preview-text">{{ $samplePreview }}</p>
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
@endsection

@section('script-bottom')
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
        var requiredTokens = @json($requiredTokens);
        var templateMap = @json($templateMap);
        var defaultTemplateContent = requiredTokens.join(' ');
        var lastValidContent = hiddenInput ? String(hiddenInput.value || '') : '';
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

        function hasAllRequiredTokens(content) {
            return requiredTokens.every(function (token) {
                return String(content || '').includes(token);
            });
        }

        function selectionTouchesToken(selection) {
            if (!selection || selection.rangeCount === 0) {
                return false;
            }

            var tokenNodes = editor.querySelectorAll('[data-token]');
            for (var i = 0; i < tokenNodes.length; i += 1) {
                if (selection.containsNode(tokenNodes[i], true)) {
                    return true;
                }
            }

            return false;
        }

        function isSelectingWholeEditor(selection) {
            if (!selection || selection.rangeCount === 0) {
                return false;
            }

            var range = selection.getRangeAt(0);
            if (range.collapsed) {
                return false;
            }

            var fullRange = document.createRange();
            fullRange.selectNodeContents(editor);

            var exactlySameBoundaries = range.compareBoundaryPoints(Range.START_TO_START, fullRange) === 0
                && range.compareBoundaryPoints(Range.END_TO_END, fullRange) === 0;

            if (exactlySameBoundaries) {
                return true;
            }

            // Some browsers normalize selection boundaries for contenteditable,
            // so fallback to textual coverage check for Cmd/Ctrl+A flows.
            var selectedText = String(selection.toString() || '').replace(/\u00a0/g, ' ').trim();
            var editorText = String(editor.textContent || '').replace(/\u00a0/g, ' ').trim();
            return selectedText.length > 0 && selectedText === editorText;
        }

        function keepOnlyPlaceholders() {
            var placeholderOnly = requiredTokens.join(' ');
            buildEditorFromContent(placeholderOnly);
            hiddenInput.value = placeholderOnly;
            lastValidContent = placeholderOnly;
            renderPreview(placeholderOnly);
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
                .replaceAll(magicLinkToken, 'https://demo.desma.test/booking/123/respond?token=abc123');
        }

        function sync() {
            var content = serializeEditorContent();
            if (!hasAllRequiredTokens(content)) {
                buildEditorFromContent(lastValidContent);
                hiddenInput.value = lastValidContent;
                renderPreview(lastValidContent);
                return;
            }
            hiddenInput.value = content;
            lastValidContent = content;
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
            lastValidContent = hiddenInput.value;
            buildEditorFromContent(hiddenInput.value);
            sync();
            setSelectedTemplateLink(template.id);
            if (formTitle) {
                formTitle.textContent = 'Edit Template Pesan';
            }
        }

        buildEditorFromContent(hiddenInput.value || '');
        sync();

        editor.addEventListener('beforeinput', function (event) {
            var inputType = String(event.inputType || '');
            var isDestructiveInput = inputType.indexOf('delete') === 0;
            if (!isDestructiveInput) {
                return;
            }

            var selection = window.getSelection();
            if (!selectionTouchesToken(selection)) {
                return;
            }

            var firstRange = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            if (isSelectingWholeEditor(selection)) {
                event.preventDefault();
                keepOnlyPlaceholders();
                return;
            }

            if (inputType.indexOf('delete') === 0 || (firstRange && !firstRange.collapsed)) {
                event.preventDefault();
            }
        });

        editor.addEventListener('cut', function (event) {
            if (selectionTouchesToken(window.getSelection())) {
                event.preventDefault();
            }
        });

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
                lastValidContent = defaultTemplateContent;
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
@endsection
