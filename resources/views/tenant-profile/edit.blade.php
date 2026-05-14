@extends('layouts.master')

@section('title')
    {{ __('translation.tenant-profile-title') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.onboarding-checklist') }}
        @endslot
        @slot('title')
            {{ __('translation.tenant-profile-title') }}
        @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-1">{{ __('translation.tenant-profile-title') }}</h5>
                    <p class="text-muted mb-0">{{ __('translation.tenant-profile-help') }}</p>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('tenant-profile.update') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="tenantProfileName" class="form-label">
                                {{ __('translation.tenant-profile-name-label') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="tenantProfileName"
                                name="name"
                                value="{{ old('name', $tenant->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="tenantProfileWa" class="form-label">
                                {{ __('translation.tenant-profile-wa-label') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control @error('whatsapp_sender_number') is-invalid @enderror"
                                id="tenantProfileWa"
                                name="whatsapp_sender_number"
                                value="{{ old('whatsapp_sender_number', $tenant->whatsapp_sender_number) }}"
                                placeholder="+628123456789"
                                required
                            >
                            <div class="form-text">{{ __('translation.tenant-profile-wa-help') }}</div>
                            @error('whatsapp_sender_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="tenantProfileAddress" class="form-label">
                                {{ __('translation.tenant-profile-address-label') }}
                            </label>
                            <input
                                type="text"
                                class="form-control @error('address') is-invalid @enderror"
                                id="tenantProfileAddress"
                                name="address"
                                value="{{ old('address', $tenant->address) }}"
                            >
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('translation.save') }}
                            </button>
                            <a href="{{ route('onboarding.index') }}" class="btn btn-light">
                                {{ __('translation.back-to-checklist') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
