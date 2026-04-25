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
                    <h5 class="card-title mb-0">{{ __('translation.superadmin-impersonate-user-list') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>{{ __('translation.name') }}</th>
                                    <th>{{ __('translation.email') }}</th>
                                    <th>{{ __('translation.role') }}</th>
                                    <th>{{ __('translation.tenant') }}</th>
                                    <th class="text-end">{{ __('translation.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $item)
                                    <tr>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->email }}</td>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ $item->role }}</span></td>
                                        <td>
                                            @if ($item->tenant)
                                                {{ $item->tenant->name }} ({{ $item->tenant->code }})
                                            @else
                                                —
                                            @endif
                                        </td>
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
                                    <tr>
                                        <td colspan="5" class="text-muted text-center py-4">{{ __('translation.superadmin-impersonate-empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
