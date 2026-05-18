@extends('layouts.master')

@section('title')
    {{ __('translation.superadmin-impersonate') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.customers-settings') }}
        @endslot
        @slot('title')
            {{ __('translation.superadmin-impersonate') }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning border-0 mb-3" role="alert">
                <strong>{{ __('translation.superadmin-impersonate-dev-only') }}</strong>
                {{ __('translation.superadmin-impersonate-help') }}
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">{{ __('translation.superadmin-impersonate-tenant-list') }}</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('superadmin.impersonation.index') }}" class="row g-2 mb-3">
                        <div class="col-md-8">
                            <input
                                type="search"
                                name="q"
                                class="form-control"
                                value="{{ $filters['q'] ?? '' }}"
                                placeholder="{{ __('translation.search-tenant-placeholder') }}"
                            >
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">{{ __('translation.apply-filter') }}</button>
                            <a href="{{ route('superadmin.impersonation.index') }}" class="btn btn-light">{{ __('translation.reset') }}</a>
                        </div>
                    </form>
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0 impersonate-tenant-table">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th class="ps-4">{{ __('translation.name') }}</th>
                                    <th>{{ __('translation.email') }}</th>
                                    <th>{{ __('translation.role') }}</th>
                                    <th class="text-end">{{ __('translation.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tenants as $tenant)
                                    <tr class="impersonate-tenant-group">
                                        <td colspan="4" class="bg-primary-subtle border-top border-primary border-opacity-25 py-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-xs flex-shrink-0">
                                                    <span class="avatar-title rounded-circle bg-primary text-white fs-16">
                                                        <i class="bx bx-buildings"></i>
                                                    </span>
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="text-muted text-uppercase d-block impersonate-tenant-label">
                                                        {{ __('translation.tenant') }}
                                                    </span>
                                                    <span class="fw-semibold text-dark">{{ $tenant->name }}</span>
                                                    <span class="badge bg-white text-primary border border-primary-subtle ms-1">{{ $tenant->code }}</span>
                                                    @if (! $tenant->is_active)
                                                        <span class="badge bg-warning-subtle text-warning ms-1">{{ __('translation.inactive') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @forelse ($tenant->users as $item)
                                        <tr class="impersonate-user-row">
                                            <td class="ps-5 border-start border-3 border-primary-subtle">{{ $item->name }}</td>
                                            <td>{{ $item->email }}</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->role }}</span></td>
                                            <td class="text-end">
                                                <form method="post" action="{{ route('superadmin.impersonation.store') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="user_id" value="{{ $item->id }}">
                                                    <button type="submit" class="btn btn-sm btn-soft-primary">
                                                        {{ __('translation.superadmin-impersonate-action') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr class="impersonate-user-row">
                                            <td colspan="4" class="text-muted ps-5 py-3 border-start border-3 border-primary-subtle bg-light">
                                                <i class="bx bx-info-circle me-1 align-middle"></i>
                                                {{ __('translation.superadmin-impersonate-tenant-no-users') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center py-4">
                                            {{ __('translation.superadmin-impersonate-empty-tenants') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $tenants->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .impersonate-tenant-table .impersonate-tenant-label {
            font-size: 0.68rem;
            letter-spacing: 0.04em;
        }

        .impersonate-tenant-table .impersonate-tenant-group + .impersonate-user-row td {
            background-color: var(--vz-body-bg, #fff);
        }

        .impersonate-tenant-table .impersonate-user-row:hover td {
            background-color: var(--vz-light, #f3f6f9);
        }
    </style>
@endsection
