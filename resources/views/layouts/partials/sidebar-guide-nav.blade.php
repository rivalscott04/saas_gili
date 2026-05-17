{{-- Guide / operator: menu operasional saja (tanpa demo Velzon). --}}
<li class="nav-item">
    <a class="nav-link menu-link" href="#sidebarGuideOps" data-bs-toggle="collapse" role="button"
        aria-expanded="false" aria-controls="sidebarGuideOps">
        <i class="bx bx-briefcase-alt-2"></i> <span>{{ __('translation.operations-resources') }}</span>
    </a>
    <div class="collapse menu-dropdown" id="sidebarGuideOps">
        <ul class="nav nav-sm flex-column">
            @if ($sidebarUser?->hasTenantPermission('bookings.view'))
                <li class="nav-item">
                    <a href="{{ url('apps-bookings') }}" class="nav-link">{{ __('translation.bookings') }}</a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('apps-bookings-calendar') }}" class="nav-link">{{ __('translation.booking-calendar') }}</a>
                </li>
                @if ($sidebarUser?->hasTenantPermission('bookings.manage_reschedule'))
                    <li class="nav-item">
                        <a href="{{ url('apps-bookings#reschedule_requested') }}" class="nav-link">{{ __('translation.reschedule-requests') }}</a>
                    </li>
                @endif
            @endif
        </ul>
    </div>
</li>
