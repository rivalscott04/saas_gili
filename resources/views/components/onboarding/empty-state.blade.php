{{--
    Komponen anonim untuk inline empty state ramah onboarding
    (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §6).

    Pakai sebagai:
        <x-onboarding.empty-state
            icon="bx-info-circle"
            title="..."
            description="..."
            :actions="[
                ['label' => 'Buka X', 'href' => route('x'), 'variant' => 'primary'],
                ['label' => 'Tinjau Y', 'href' => route('y'), 'variant' => 'secondary'],
            ]"
        />
--}}
@props([
    'icon' => 'bx-info-circle',
    'title' => '',
    'description' => '',
    'actions' => [],
    'tone' => 'info',
])

@php
    $toneClass = match ($tone) {
        'warning' => 'border-warning-subtle bg-warning-subtle',
        'danger' => 'border-danger-subtle bg-danger-subtle',
        default => 'border-info-subtle bg-info-subtle',
    };
    $toneIcon = match ($tone) {
        'warning' => 'text-warning',
        'danger' => 'text-danger',
        default => 'text-info',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-3 p-4 border ' . $toneClass]) }} data-onboarding="empty-state">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <div class="flex-shrink-0">
            <div class="avatar-sm">
                <div class="avatar-title rounded-circle bg-white {{ $toneIcon }} fs-3 border">
                    <i class="bx {{ $icon }}"></i>
                </div>
            </div>
        </div>
        <div class="flex-grow-1">
            @if (filled($title))
                <h5 class="mb-1">{{ $title }}</h5>
            @endif
            @if (filled($description))
                <p class="text-muted mb-0">{!! $description !!}</p>
            @endif
        </div>
        @if (count($actions) > 0)
            <div class="flex-shrink-0 d-flex gap-2 flex-wrap">
                @foreach ($actions as $action)
                    @php
                        $variant = $action['variant'] ?? 'primary';
                        $btnClass = $variant === 'primary' ? 'btn btn-primary' : 'btn btn-light';
                    @endphp
                    <a href="{{ $action['href'] }}" class="{{ $btnClass }}">
                        {{ $action['label'] }}
                        <i class="bx bx-right-arrow-alt ms-1"></i>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
