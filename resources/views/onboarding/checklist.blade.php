@extends('layouts.master')

@section('title')
    {{ __('translation.onboarding-checklist') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.dashboards') }}
        @endslot
        @slot('title')
            {{ __('translation.onboarding-checklist') }}
        @endslot
    @endcomponent

    @php
        // Hanya tampilkan kartu yang tidak hidden (lihat OnboardingService::summaryFor).
        $visibleSteps = collect($steps)->reject(fn ($step) => $step['hidden'])->values();
        $stepNumber = 0;
        $progressPct = $mandatoryTotal > 0
            ? (int) round(($mandatoryDone / $mandatoryTotal) * 100)
            : 0;
        $mandatoryRemaining = max(0, $mandatoryTotal - $mandatoryDone);
    @endphp

    @if (session('status'))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible border-0 fade show" role="alert" data-onboarding="flash-status">
                    <i class="bx bx-info-circle me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    @if (! $isAllMandatoryDone)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning border-0 mb-3" role="alert" data-onboarding="gate-banner">
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <i class="bx bx-lock-alt fs-2 text-warning"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-2">{{ __('translation.onboarding-gate-banner-title') }}</h5>
                            <p class="mb-2">{{ __('translation.onboarding-gate-banner-body', ['total' => $mandatoryTotal]) }}</p>
                            @if ($mandatoryRemaining > 0)
                                <p class="mb-2 fw-medium">
                                    {{ __('translation.onboarding-gate-banner-remaining', ['remaining' => $mandatoryRemaining, 'total' => $mandatoryTotal]) }}
                                </p>
                            @endif
                            <p class="mb-0 small text-muted">{{ __('translation.onboarding-gate-banner-hint') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success border-0 mb-3" role="alert" data-onboarding="gate-complete-banner">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">
                                <i class="bx bx-check-circle me-1"></i>
                                {{ __('translation.onboarding-gate-complete-title') }}
                            </h5>
                            <p class="mb-0">{{ __('translation.onboarding-gate-complete-body') }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ url('/dashboard-analytics') }}" class="btn btn-success">
                                {{ __('translation.onboarding-gate-complete-cta') }}
                                <i class="bx bx-right-arrow-alt ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-none bg-transparent mb-3">
                <div class="card-body p-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h4 class="card-title mb-0 flex-grow-1">
                            {{ __('translation.onboarding-welcome', ['name' => $tenant->name]) }}
                        </h4>
                        @if (! $isDismissed && $isAllMandatoryDone)
                            <form method="POST" action="{{ route('onboarding.dismiss') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light" data-onboarding="dismiss-checklist">
                                    <i class="bx bx-hide me-1"></i>
                                    {{ __('translation.onboarding-dismiss') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <p class="text-muted mb-0">{{ __('translation.onboarding-subhead') }}</p>
                </div>
            </div>

            {{-- Banner pilih mode produk (sync 2 arah vs aplikasi saja). --}}
            <div class="card" data-onboarding="mode-banner">
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ __('translation.onboarding-mode-question') }}</h5>
                    <form method="POST" action="{{ route('onboarding.mode') }}" class="row g-3 align-items-center">
                        @csrf
                        <div class="col-md">
                            <div class="form-check form-check-primary">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="mode"
                                    id="onboardingModeTwoWay"
                                    value="two_way_sync"
                                    {{ $mode === 'two_way_sync' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="onboardingModeTwoWay">
                                    <strong>{{ __('translation.onboarding-mode-two-way-sync') }}</strong>
                                    <div class="text-muted small">{{ __('translation.onboarding-mode-two-way-sync-help') }}</div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md">
                            <div class="form-check form-check-primary">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="mode"
                                    id="onboardingModeAppOnly"
                                    value="app_only"
                                    {{ $mode === 'app_only' ? 'checked' : '' }}
                                >
                                <label class="form-check-label" for="onboardingModeAppOnly">
                                    <strong>{{ __('translation.onboarding-mode-app-only') }}</strong>
                                    <div class="text-muted small">{{ __('translation.onboarding-mode-app-only-help') }}</div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary">{{ __('translation.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Progress mandatory steps. --}}
            <div class="card" data-onboarding="progress">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <h5 class="card-title mb-0 flex-grow-1">
                            {{ __('translation.onboarding-progress', ['done' => $mandatoryDone, 'total' => $mandatoryTotal]) }}
                        </h5>
                        @if ($isAllMandatoryDone)
                            <span class="badge bg-success-subtle text-success">
                                <i class="bx bx-check-circle me-1"></i>
                                {{ __('translation.onboarding-all-mandatory-done') }}
                            </span>
                        @endif
                    </div>
                    <div class="progress" role="progressbar" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-primary" style="width: {{ $progressPct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Kartu langkah. --}}
            <div class="row">
                @foreach ($visibleSteps as $step)
                    @php
                        $stepNumber++;
                        $isDone = (bool) $step['done'];
                        $isMandatory = (bool) $step['mandatory'];
                        $iconClass = $isDone ? 'bx-check-circle text-success' : 'bx-circle text-muted';
                    @endphp
                    <div class="col-xl-6">
                        <div
                            class="card h-100"
                            data-onboarding-step="{{ $step['key'] }}"
                            data-onboarding-status="{{ $isDone ? 'done' : 'pending' }}"
                        >
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm">
                                            <div class="avatar-title rounded-circle bg-light text-primary fs-4">
                                                {{ $stepNumber }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="bx {{ $iconClass }} fs-5"></i>
                                            <h5 class="mb-0">{{ __($step['title_key']) }}</h5>
                                            @if (! $isMandatory)
                                                <span class="badge bg-light text-muted ms-1">
                                                    {{ __('translation.onboarding-optional') }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-muted mb-3">
                                            {{ __($step['title_key'] . '-desc') }}
                                        </p>
                                        <a
                                            href="{{ $step['href'] }}"
                                            class="btn btn-sm {{ $isDone ? 'btn-light' : 'btn-primary' }}"
                                            data-onboarding-action
                                        >
                                            {{ $isDone ? __('translation.onboarding-action-review') : __('translation.onboarding-action-do-now') }}
                                            <i class="bx bx-right-arrow-alt ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
