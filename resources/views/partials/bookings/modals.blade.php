<div class="modal fade zoomIn" id="bookingReminderModal" tabindex="-1" aria-labelledby="bookingReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="bookingReminderForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingReminderModalLabel">{{ __('translation.send-whatsapp-reminder') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="bookingReminderTargetText">{{ __('translation.select-reminder-template') }}</p>
                    <div class="mb-0">
                        <label class="form-label">{{ __('translation.whatsapp-template') }}</label>
                        <select class="form-select" name="template_id" required>
                            @foreach($reminderTemplates as $template)
                                <option value="{{ $template->id }}" {{ (int) ($defaultReminderTemplateId ?? 0) === (int) $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($reminderTemplates->isEmpty())
                            <small class="text-danger d-block mt-2">
                                {{ __('translation.no-template-create-first') }}
                            </small>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                    <button type="submit" class="btn btn-success" {{ $reminderTemplates->isEmpty() ? 'disabled' : '' }}>
                        <i class="ri-send-plane-line align-bottom me-1"></i>{{ __('translation.open-whatsapp') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade zoomIn" id="bookingEditModal" tabindex="-1" aria-labelledby="bookingEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="bookingEditForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingEditModalLabel">{{ __('translation.edit-booking') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="bookingEditTargetText">{{ __('translation.save-booking') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.location') }}</label>
                            <input type="text" class="form-control" name="location" id="bookingEditLocation" maxlength="255" placeholder="-">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.guide') }}</label>
                            <input type="text" class="form-control" name="guide_name" id="bookingEditGuide" maxlength="255" placeholder="-">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('translation.notes') }}</label>
                            <textarea class="form-control" rows="3" name="notes" id="bookingEditNotes" maxlength="5000" placeholder="{{ __('translation.notes') }}"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('translation.internal-notes') }}</label>
                            <textarea class="form-control" rows="3" name="internal_notes" id="bookingEditInternalNotes" maxlength="5000" placeholder="{{ __('translation.internal-notes-placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line align-bottom me-1"></i>{{ __('translation.save-booking') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade zoomIn" id="bookingReminderHistoryModal" tabindex="-1" aria-labelledby="bookingReminderHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookingReminderHistoryModalLabel">{{ __('translation.reminder-history') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="bookingReminderHistoryTargetText">{{ __('translation.reminder-history-help') }}</p>
                <div id="bookingReminderHistoryList" class="list-group list-group-flush"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.close') }}</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade zoomIn" id="bookingRescheduleModal" tabindex="-1" aria-labelledby="bookingRescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="bookingRescheduleForm">
                @csrf
                <input type="hidden" name="reschedule_id" id="rescheduleIdInput">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingRescheduleModalLabel">{{ __('translation.manage-reschedule') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="bookingRescheduleTargetText">{{ __('translation.select-reschedule-request') }}</p>
                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted">{{ __('translation.current-departure') }}</div>
                        <div class="fw-semibold" id="bookingRescheduleCurrentDate">-</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('translation.workflow-status') }}</label>
                            <select class="form-select" name="workflow_status" id="rescheduleWorkflowStatus" required>
                                <option value="reviewed">{{ __('translation.reviewed') }}</option>
                                <option value="approved">{{ __('translation.approved') }}</option>
                                <option value="rejected">{{ __('translation.rejected') }}</option>
                                <option value="completed">{{ __('translation.completed') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('translation.requested-new-date') }}</label>
                            <input type="datetime-local" class="form-control" name="requested_tour_start_at" id="rescheduleRequestedDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('translation.final-approved-date') }}</label>
                            <input type="datetime-local" class="form-control" name="final_tour_start_at" id="rescheduleFinalDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.reason-optional') }}</label>
                            <input type="text" class="form-control" name="requested_reason" id="rescheduleReason" maxlength="255" placeholder="{{ __('translation.reason-placeholder') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.notes') }}</label>
                            <textarea class="form-control" rows="2" name="notes" id="rescheduleNotes" maxlength="1000" placeholder="{{ __('translation.internal-notes-placeholder') }}"></textarea>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-2">{{ __('translation.reschedule-timeline') }}</h6>
                    <div id="bookingRescheduleHistoryList" class="list-group list-group-flush"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                    <button type="submit" class="btn btn-warning" id="saveRescheduleWorkflowBtn">
                        <i class="ri-save-line align-bottom me-1"></i>{{ __('translation.save-workflow') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade zoomIn" id="bookingResourceAllocationModal" tabindex="-1" aria-labelledby="bookingResourceAllocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="bookingResourceAllocationForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingResourceAllocationModalLabel">{{ __('translation.resource-allocation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('translation.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="bookingResourceAllocationTargetText">{{ __('translation.manage-resource-for-booking') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('translation.resource') }}</label>
                            <select class="form-select" name="tenant_resource_id" id="allocationResourceId" required>
                                <option value="">{{ __('translation.select-resource') }}</option>
                                @php
                                    $resourceGroups = ($resourceOptions ?? collect())->groupBy('tenant_id');
                                    $showResourceTenantOptgroups = auth()->user()?->isSuperAdmin() ?? false;
                                @endphp
                                @foreach($resourceGroups as $resourceTenantId => $resourcesInTenant)
                                    @if($showResourceTenantOptgroups)
                                        <optgroup label="{{ optional($resourcesInTenant->first()->tenant)->name ?? __('translation.tenant-prefix', ['id' => $resourceTenantId]) }}">
                                            @foreach($resourcesInTenant as $resourceOption)
                                                <option
                                                    value="{{ $resourceOption->id }}"
                                                    data-tenant-id="{{ $resourceOption->tenant_id }}"
                                                >
                                                    {{ $resourceOption->name }} ({{ strtoupper((string) $resourceOption->resource_type) }}){{ $resourceOption->reference_code ? ' - '.$resourceOption->reference_code : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @else
                                        @foreach($resourcesInTenant as $resourceOption)
                                            <option
                                                value="{{ $resourceOption->id }}"
                                                data-tenant-id="{{ $resourceOption->tenant_id }}"
                                            >
                                                {{ $resourceOption->name }} ({{ strtoupper((string) $resourceOption->resource_type) }}){{ $resourceOption->reference_code ? ' - '.$resourceOption->reference_code : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('translation.allocation-date') }}</label>
                            <input type="date" class="form-control" name="allocation_date" id="allocationDate" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('translation.allocated-pax') }}</label>
                            <input type="number" class="form-control" name="allocated_pax" id="allocationPax" min="1" max="100000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('translation.allocated-units') }}</label>
                            <input type="number" class="form-control" name="allocated_units" id="allocationUnits" min="1" max="100000">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">{{ __('translation.notes') }}</label>
                            <input type="text" class="form-control" name="notes" id="allocationNotes" maxlength="500">
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-2">{{ __('translation.existing-allocations') }}</h6>
                    <div id="bookingResourceAllocationHistoryList" class="list-group list-group-flush"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line align-bottom me-1"></i>{{ __('translation.save-allocation') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
