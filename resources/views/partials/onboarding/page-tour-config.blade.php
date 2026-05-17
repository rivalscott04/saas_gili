{{--
    JSON config for Shepherd page tours (resources/js/pages/onboarding/*).
    Steps use data-onboarding="..." selectors on the same page.
--}}
@props([
    'pageId',
    'steps' => [],
    'autoStart' => null,
])

@php
    $shouldAutoStart = $autoStart;
    if ($shouldAutoStart === null) {
        $viewer = auth()->user();
        $shouldAutoStart = $viewer !== null
            && $viewer->isTenantAdmin()
            && ! $viewer->isSuperAdmin();
    }
@endphp

<script type="application/json" id="onboarding-tour-config-{{ $pageId }}">
{!! json_encode([
    'pageId' => $pageId,
    'autoStart' => (bool) $shouldAutoStart,
    'labels' => [
        'next' => __('translation.onboarding-tour-next'),
        'back' => __('translation.onboarding-tour-back'),
        'done' => __('translation.onboarding-tour-done'),
    ],
    'steps' => $steps,
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}
</script>
