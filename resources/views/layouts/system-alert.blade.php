@php
    $systemAlert = session('system_alert');
@endphp

@once
    <style>
        .sa-system-alert-stack ~ .page-title-box,
        .sa-system-alert-stack ~ .app-page-title-box {
            margin-top: 0 !important;
        }
    </style>
@endonce

@if (is_array($systemAlert) && ! empty($systemAlert['title']))
    <div class="sa-system-alert-stack">
        <div class="row">
            <div class="col-12">
                <div
                    class="alert alert-warning alert-border-left alert-dismissible fade show mb-3 js-auto-dismiss-alert"
                    role="alert"
                    data-auto-close-ms="5000"
                >
                    <i class="ri-alert-line me-2 align-middle"></i>
                    <strong>{{ $systemAlert['title'] }}</strong>
                    @if (! empty($systemAlert['message']))
                        <span class="ms-1">{{ $systemAlert['message'] }}</span>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>

    <div
        id="sa-system-alert"
        data-sa-reason="{{ $systemAlert['reason'] ?? 'ACCESS_DENIED' }}"
        data-sa-icon="{{ $systemAlert['icon'] ?? 'warning' }}"
        data-sa-title="{{ $systemAlert['title'] }}"
        data-sa-text="{{ $systemAlert['message'] ?? '' }}"
        class="d-none"
        aria-hidden="true"
    ></div>
@endif

@if ($errors->any())
    <div class="sa-system-alert-stack">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-border-left alert-dismissible fade show mb-3" role="alert">
                    <i class="ri-error-warning-line me-2 align-middle"></i>
                    <strong>Validasi gagal.</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach ($errors->all() as $errorMessage)
                            <li>{{ $errorMessage }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    <script>
        (function () {
            var initAutoDismissAlerts = function () {
                var alerts = document.querySelectorAll('.js-auto-dismiss-alert');

                alerts.forEach(function (alertEl) {
                    if (alertEl.dataset.autoDismissBound === '1') {
                        return;
                    }

                    alertEl.dataset.autoDismissBound = '1';
                    var closeDelay = parseInt(alertEl.getAttribute('data-auto-close-ms') || '5000', 10);

                    window.setTimeout(function () {
                        if (window.bootstrap && window.bootstrap.Alert) {
                            window.bootstrap.Alert.getOrCreateInstance(alertEl).close();
                            return;
                        }

                        alertEl.classList.remove('show');
                        window.setTimeout(function () {
                            alertEl.remove();
                        }, 200);
                    }, closeDelay);
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAutoDismissAlerts);
            } else {
                initAutoDismissAlerts();
            }
        })();
    </script>
@endonce
