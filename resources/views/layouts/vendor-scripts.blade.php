<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/sweetalerts.init.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
<script src="{{ URL::asset('build/js/plugins.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var i18nValidation = {
            required: @json(__('translation.validation-required-field')),
            invalidEmail: @json(__('translation.validation-invalid-email')),
            invalidNumber: @json(__('translation.validation-invalid-number')),
        };

        function cleanupLabelText(value) {
            return (value || '')
                .replace('*', '')
                .replace(/\(opsional\)/ig, '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function resolveFieldLabel(el) {
            if (!el) {
                return '';
            }

            var fromAria = cleanupLabelText(el.getAttribute('aria-label') || '');
            if (fromAria) {
                return fromAria;
            }

            var id = el.getAttribute('id');
            if (id) {
                var linkedLabel = document.querySelector('label[for="' + id + '"]');
                if (linkedLabel) {
                    var linkedText = cleanupLabelText(linkedLabel.textContent || '');
                    if (linkedText) {
                        return linkedText;
                    }
                }
            }

            var parentLabel = el.closest('label');
            if (parentLabel) {
                var parentText = cleanupLabelText(parentLabel.textContent || '');
                if (parentText) {
                    return parentText;
                }
            }

            var nearestLabel = el.closest('.mb-3, .col, .col-sm-6, .col-md-6, .col-lg-6, .col-xl-6');
            if (nearestLabel) {
                var firstLabel = nearestLabel.querySelector('label');
                if (firstLabel) {
                    var firstLabelText = cleanupLabelText(firstLabel.textContent || '');
                    if (firstLabelText) {
                        return firstLabelText;
                    }
                }
            }

            var fromName = cleanupLabelText(el.getAttribute('name') || '');
            return fromName || 'field';
        }

        function resolveValidationMessage(el) {
            if (!el || !el.validity) {
                return '';
            }

            if (el.validity.valueMissing) {
                return i18nValidation.required.replace(':field', resolveFieldLabel(el));
            }
            if (el.validity.typeMismatch && el.type === 'email') {
                return i18nValidation.invalidEmail;
            }
            if (el.validity.badInput) {
                return i18nValidation.invalidNumber;
            }

            return '';
        }

        document.addEventListener('invalid', function (event) {
            var el = event.target;
            if (!el || typeof el.setCustomValidity !== 'function') {
                return;
            }

            var message = resolveValidationMessage(el);
            if (message) {
                el.setCustomValidity(message);
                el.dataset.i18nValidityApplied = '1';
            }
        }, true);

        function clearLocalizedValidity(event) {
            var el = event.target;
            if (!el || typeof el.setCustomValidity !== 'function') {
                return;
            }
            if (el.dataset.i18nValidityApplied === '1') {
                el.setCustomValidity('');
                delete el.dataset.i18nValidityApplied;
            }
        }

        document.addEventListener('input', clearLocalizedValidity, true);
        document.addEventListener('change', clearLocalizedValidity, true);
    });
</script>
@yield('script')
@yield('script-bottom')
