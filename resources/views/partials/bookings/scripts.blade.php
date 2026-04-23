<script>
    document.addEventListener('DOMContentLoaded', function () {
        var i18n = {
            locale: @json(str_replace('_', '-', app()->getLocale())),
            bookingPrefix: @json(__('translation.booking')),
            reminderDetailForBooking: @json(__('translation.reminder-detail-for-booking')),
            noReminderSentYet: @json(__('translation.no-reminder-sent-yet')),
            template: @json(__('translation.template')),
            destination: @json(__('translation.destination')),
            manageRescheduleForBooking: @json(__('translation.manage-reschedule-for-booking')),
            noRescheduleRequestYet: @json(__('translation.no-reschedule-request-yet')),
            by: @json(__('translation.by')),
            source: @json(__('translation.source')),
            old: @json(__('translation.old')),
            requested: @json(__('translation.requested')),
            final: @json(__('translation.final')),
            reason: @json(__('translation.reason')),
            notes: @json(__('translation.notes')),
            manageResourceAllocationForBooking: @json(__('translation.manage-resource-allocation-for-booking')),
            noResourceAllocationYet: @json(__('translation.no-resource-allocation-yet')),
            date: @json(__('translation.date')),
            units: @json(__('translation.units')),
            pax: @json(__('translation.pax')),
            unassign: @json(__('translation.unassign')),
            workflowLabels: {
                requested: @json(__('translation.requested')),
                reviewed: @json(__('translation.reviewed')),
                approved: @json(__('translation.approved')),
                rejected: @json(__('translation.rejected')),
                completed: @json(__('translation.completed')),
            },
        };

        var tabContainer = document.getElementById('bookingStatusTabs');
        if (!tabContainer) {
            return;
        }

        var tabLinks = tabContainer.querySelectorAll('[data-status-filter]');
        var rows = document.querySelectorAll('[data-booking-row]');
        var emptyState = document.getElementById('bookingTableEmptyState');
        var keywordInput = document.getElementById('bookingKeywordFilter');
        var dateRangeInput = document.getElementById('bookingDateRangeFilter');
        var workflowChipButtons = document.querySelectorAll('[data-workflow-chip]');
        var resetButton = document.getElementById('resetBookingFilters');
        var reminderHistoryModalEl = document.getElementById('bookingReminderHistoryModal');
        var reminderModalEl = document.getElementById('bookingReminderModal');
        var rescheduleModalEl = document.getElementById('bookingRescheduleModal');
        var resourceAllocationModalEl = document.getElementById('bookingResourceAllocationModal');
        var reminderForm = document.getElementById('bookingReminderForm');
        var rescheduleForm = document.getElementById('bookingRescheduleForm');
        var resourceAllocationForm = document.getElementById('bookingResourceAllocationForm');
        var reminderTargetText = document.getElementById('bookingReminderTargetText');
        var rescheduleTargetText = document.getElementById('bookingRescheduleTargetText');
        var resourceAllocationTargetText = document.getElementById('bookingResourceAllocationTargetText');
        var reminderHistoryTargetText = document.getElementById('bookingReminderHistoryTargetText');
        var reminderHistoryList = document.getElementById('bookingReminderHistoryList');
        var rescheduleHistoryList = document.getElementById('bookingRescheduleHistoryList');
        var resourceAllocationHistoryList = document.getElementById('bookingResourceAllocationHistoryList');
        var rescheduleIdInput = document.getElementById('rescheduleIdInput');
        var rescheduleCurrentDate = document.getElementById('bookingRescheduleCurrentDate');
        var rescheduleStatusInput = document.getElementById('rescheduleWorkflowStatus');
        var rescheduleRequestedDateInput = document.getElementById('rescheduleRequestedDate');
        var rescheduleFinalDateInput = document.getElementById('rescheduleFinalDate');
        var rescheduleReasonInput = document.getElementById('rescheduleReason');
        var rescheduleNotesInput = document.getElementById('rescheduleNotes');
        var saveRescheduleWorkflowBtn = document.getElementById('saveRescheduleWorkflowBtn');
        var allocationResourceId = document.getElementById('allocationResourceId');
        var allocationDateInput = document.getElementById('allocationDate');
        var reminderHistoryByBooking = @json($reminderHistoryByBooking ?? []);
        var rescheduleHistoryByBooking = @json($rescheduleHistoryByBooking ?? []);
        var bookingAllocationsByBooking = @json($bookingAllocationsByBooking ?? []);
        var reminderTemplateSelect = reminderForm ? reminderForm.querySelector('select[name="template_id"]') : null;
        var defaultReminderTemplateId = '{{ (string) ($defaultReminderTemplateId ?? '') }}';
        var csrfToken = '{{ csrf_token() }}';
        var activeStatus = 'all';
        var columnToggleMenu = document.getElementById('bookingColumnToggleMenu');
        var columnStorageKey = 'bookingTableColumnVisibility.v1';
        function applyColumnVisibilityMap(visibilityMap) {
            if (!visibilityMap || typeof visibilityMap !== 'object') {
                return;
            }
            Object.keys(visibilityMap).forEach(function (key) {
                var isVisible = visibilityMap[key] !== false;
                var cells = document.querySelectorAll('[data-col-key="' + key + '"]');
                cells.forEach(function (cell) {
                    cell.style.display = isVisible ? '' : 'none';
                });
                if (columnToggleMenu) {
                    var checkbox = columnToggleMenu.querySelector('input[data-col-toggle="' + key + '"]');
                    if (checkbox) {
                        checkbox.checked = isVisible;
                    }
                }
            });
        }

        function collectColumnVisibilityMap() {
            var visibilityMap = {};
            if (!columnToggleMenu) {
                return visibilityMap;
            }
            columnToggleMenu.querySelectorAll('input[data-col-toggle]').forEach(function (checkbox) {
                var key = checkbox.getAttribute('data-col-toggle');
                if (!key) {
                    return;
                }
                visibilityMap[key] = checkbox.checked;
            });
            return visibilityMap;
        }

        function saveColumnVisibilityMap() {
            try {
                localStorage.setItem(columnStorageKey, JSON.stringify(collectColumnVisibilityMap()));
            } catch (error) {}
        }

        function initColumnToggles() {
            if (!columnToggleMenu) {
                return;
            }
            var defaultMap = collectColumnVisibilityMap();
            var savedMap = null;
            try {
                savedMap = JSON.parse(localStorage.getItem(columnStorageKey) || 'null');
            } catch (error) {
                savedMap = null;
            }
            applyColumnVisibilityMap(savedMap && typeof savedMap === 'object' ? savedMap : defaultMap);

            columnToggleMenu.querySelectorAll('input[data-col-toggle]').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    var key = checkbox.getAttribute('data-col-toggle');
                    if (!key) {
                        return;
                    }
                    applyColumnVisibilityMap({ [key]: checkbox.checked });
                    saveColumnVisibilityMap();
                });
            });
        }

        function setActiveWorkflowChip(targetValue) {
            workflowChipButtons.forEach(function (button) {
                var chipValue = button.getAttribute('data-workflow-chip') || '';
                button.classList.toggle('active', chipValue === targetValue);
            });
        }

        function rowMatchesActiveStatusContext(row) {
            var rowStatus = row.getAttribute('data-status') || '';
            var rowResponse = row.getAttribute('data-response') || '';
            var statusMatch = activeStatus === 'all' || rowStatus === activeStatus;
            if (activeStatus === 'reschedule_requested') {
                statusMatch = rowResponse === 'reschedule_requested';
            }

            return statusMatch;
        }

        function updateWorkflowChipCounters() {
            var counters = {
                all: 0,
                requested: 0,
                reviewed: 0,
                approved: 0,
                rejected: 0,
                completed: 0,
                no_request: 0,
            };

            rows.forEach(function (row) {
                if (!rowMatchesActiveStatusContext(row)) {
                    return;
                }

                counters.all += 1;
                var rowWorkflow = (row.getAttribute('data-reschedule-workflow') || '').toLowerCase();
                if (rowWorkflow === '') {
                    counters.no_request += 1;
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(counters, rowWorkflow)) {
                    counters[rowWorkflow] += 1;
                }
            });

            workflowChipButtons.forEach(function (button) {
                var key = button.getAttribute('data-workflow-chip') || '';
                var countEl = button.querySelector('[data-workflow-chip-count]');
                if (!countEl || !Object.prototype.hasOwnProperty.call(counters, key)) {
                    return;
                }
                countEl.textContent = String(counters[key]);
            });
        }

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }
            try {
                return new Date(value).toLocaleString(i18n.locale, { dateStyle: 'medium', timeStyle: 'short' });
            } catch (error) {
                return value;
            }
        }

        function translateWorkflowStatus(value) {
            if (!value) {
                return '-';
            }
            var key = String(value).toLowerCase();
            return i18n.workflowLabels[key] || key.replace('_', ' ');
        }

        function toDateTimeLocal(value) {
            if (!value) {
                return '';
            }
            try {
                var date = new Date(value);
                var offset = date.getTimezoneOffset();
                date = new Date(date.getTime() - (offset * 60000));
                return date.toISOString().slice(0, 16);
            } catch (error) {
                return '';
            }
        }

        function parseDateRange(value) {
            if (!value || value.indexOf(' to ') === -1 || typeof flatpickr === 'undefined') {
                return { start: null, end: null };
            }

            var parts = value.split(' to ');
            var startDate = flatpickr.parseDate(parts[0], 'd M, Y');
            var endDate = flatpickr.parseDate(parts[1], 'd M, Y');

            if (!startDate || !endDate) {
                return { start: null, end: null };
            }

            return {
                start: flatpickr.formatDate(startDate, 'Y-m-d'),
                end: flatpickr.formatDate(endDate, 'Y-m-d'),
            };
        }

        function applyFilter() {
            var keyword = (keywordInput ? keywordInput.value : '').toLowerCase().trim();
            var dateRange = parseDateRange(dateRangeInput ? dateRangeInput.value : '');
            var activeWorkflowChipEl = document.querySelector('[data-workflow-chip].active');
            var workflowFilter = ((activeWorkflowChipEl && activeWorkflowChipEl.getAttribute('data-workflow-chip')) || 'all').toLowerCase();
            var visibleRows = 0;

            rows.forEach(function (row) {
                var rowWorkflow = (row.getAttribute('data-reschedule-workflow') || '').toLowerCase();
                var rowSearch = row.getAttribute('data-search') || '';
                var rowDate = row.getAttribute('data-start-date') || '';
                var statusMatch = rowMatchesActiveStatusContext(row);
                var keywordMatch = !keyword || rowSearch.indexOf(keyword) !== -1;
                var dateMatch = true;
                var workflowMatch = workflowFilter === 'all' ||
                    (workflowFilter === 'no_request' ? rowWorkflow === '' : rowWorkflow === workflowFilter);

                if (dateRange.start && dateRange.end) {
                    dateMatch = rowDate >= dateRange.start && rowDate <= dateRange.end;
                }

                var visible = statusMatch && keywordMatch && dateMatch && workflowMatch;
                row.style.display = visible ? '' : 'none';
                if (visible) {
                    visibleRows += 1;
                }
            });

            if (emptyState) {
                emptyState.style.display = visibleRows === 0 ? '' : 'none';
            }

            updateWorkflowChipCounters();
        }

        if (keywordInput) {
            keywordInput.addEventListener('input', function () {
                applyFilter();
            });
        }

        if (dateRangeInput) {
            dateRangeInput.addEventListener('change', function () {
                applyFilter();
            });
        }

        workflowChipButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetWorkflow = button.getAttribute('data-workflow-chip') || 'all';
                setActiveWorkflowChip(targetWorkflow);
                applyFilter();
            });
        });

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                if (keywordInput) {
                    keywordInput.value = '';
                }
                if (dateRangeInput) {
                    dateRangeInput.value = '';
                    if (dateRangeInput._flatpickr) {
                        dateRangeInput._flatpickr.clear();
                    }
                }
                setActiveWorkflowChip('all');

                activeStatus = 'all';
                tabLinks.forEach(function (item) {
                    item.classList.remove('active');
                });

                var firstTab = tabContainer.querySelector('[data-status-filter="all"]');
                if (firstTab) {
                    firstTab.classList.add('active');
                }

                applyFilter();
            });
        }

        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                tabLinks.forEach(function (item) {
                    item.classList.remove('active');
                });
                link.classList.add('active');

                activeStatus = link.getAttribute('data-status-filter') || 'all';
                applyFilter();
            });
        });

        if (reminderModalEl) {
            reminderModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !reminderForm) {
                    return;
                }
                var bookingId = trigger.getAttribute('data-booking-id');
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                reminderForm.action = '/apps-bookings/' + bookingId + '/send-reminder';
                if (reminderTargetText) {
                    reminderTargetText.textContent = i18n.bookingPrefix + ' ' + bookingCode + ' - ' + bookingName;
                }
                if (reminderTemplateSelect && defaultReminderTemplateId) {
                    reminderTemplateSelect.value = defaultReminderTemplateId;
                }
            });
        }

        if (reminderHistoryModalEl) {
            reminderHistoryModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !reminderHistoryList) {
                    return;
                }
                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var historyItems = reminderHistoryByBooking[bookingId] || [];
                if (reminderHistoryTargetText) {
                    reminderHistoryTargetText.textContent = i18n.reminderDetailForBooking + ' ' + bookingCode;
                }
                if (historyItems.length === 0) {
                    reminderHistoryList.innerHTML = '<div class="text-muted py-2">' + i18n.noReminderSentYet + '</div>';
                } else {
                    reminderHistoryList.innerHTML = historyItems.map(function (item) {
                        var sentAt = item.sent_at ? new Date(item.sent_at).toLocaleString(i18n.locale, { dateStyle: 'medium', timeStyle: 'short' }) : '-';
                        return '' +
                            '<div class="list-group-item px-0">' +
                                '<div class="fw-semibold">' + sentAt + '</div>' +
                                '<div class="text-muted small">' + i18n.template + ': ' + (item.template_name || '-') + '</div>' +
                                '<div class="text-muted small">' + i18n.destination + ': ' + (item.sent_to_phone || '-') + '</div>' +
                            '</div>';
                    }).join('');
                }
            });
        }

        if (rescheduleModalEl) {
            rescheduleModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !rescheduleForm || !rescheduleHistoryList) {
                    return;
                }

                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingStart = trigger.getAttribute('data-booking-start') || '';
                var historyItems = rescheduleHistoryByBooking[bookingId] || [];
                var latest = historyItems.length > 0 ? historyItems[0] : null;

                rescheduleForm.action = '/apps-bookings/' + bookingId + '/reschedule-workflow';
                if (rescheduleTargetText) {
                    rescheduleTargetText.textContent = i18n.manageRescheduleForBooking + ' ' + bookingCode + ' - ' + bookingName;
                }
                if (rescheduleCurrentDate) {
                    rescheduleCurrentDate.textContent = formatDateTime(bookingStart);
                }

                if (rescheduleIdInput) {
                    rescheduleIdInput.value = latest ? String(latest.id || '') : '';
                }
                if (rescheduleStatusInput) {
                    rescheduleStatusInput.value = latest ? (latest.workflow_status || 'reviewed') : 'reviewed';
                }
                if (rescheduleRequestedDateInput) {
                    rescheduleRequestedDateInput.value = latest ? toDateTimeLocal(latest.requested_tour_start_at) : '';
                }
                if (rescheduleFinalDateInput) {
                    rescheduleFinalDateInput.value = latest ? toDateTimeLocal(latest.final_tour_start_at) : '';
                }
                if (rescheduleReasonInput) {
                    rescheduleReasonInput.value = latest ? (latest.requested_reason || '') : '';
                }
                if (rescheduleNotesInput) {
                    rescheduleNotesInput.value = latest ? (latest.notes || '') : '';
                }

                if (historyItems.length === 0) {
                    rescheduleHistoryList.innerHTML = '<div class="text-muted py-2">' + i18n.noRescheduleRequestYet + '</div>';
                    if (saveRescheduleWorkflowBtn) {
                        saveRescheduleWorkflowBtn.disabled = true;
                    }
                    return;
                }

                if (saveRescheduleWorkflowBtn) {
                    saveRescheduleWorkflowBtn.disabled = false;
                }

                rescheduleHistoryList.innerHTML = historyItems.map(function (item) {
                    return '' +
                        '<div class="list-group-item px-0">' +
                            '<div class="d-flex justify-content-between flex-wrap gap-1">' +
                                '<div class="fw-semibold text-capitalize">' + translateWorkflowStatus(item.workflow_status) + '</div>' +
                                '<div class="small text-muted">' + formatDateTime(item.created_at) + '</div>' +
                            '</div>' +
                            '<div class="small text-muted">' + i18n.by + ': ' + (item.requested_by || '-') + ' | ' + i18n.source + ': ' + (item.request_source || '-') + '</div>' +
                            '<div class="small text-muted">' + i18n.old + ': ' + formatDateTime(item.old_tour_start_at) + '</div>' +
                            '<div class="small text-muted">' + i18n.requested + ': ' + formatDateTime(item.requested_tour_start_at) + '</div>' +
                            '<div class="small text-muted">' + i18n.final + ': ' + formatDateTime(item.final_tour_start_at) + '</div>' +
                            '<div class="small text-muted">' + i18n.reason + ': ' + (item.requested_reason || '-') + '</div>' +
                            '<div class="small text-muted">' + i18n.notes + ': ' + (item.notes || '-') + '</div>' +
                        '</div>';
                }).join('');
            });
        }

        if (resourceAllocationModalEl) {
            resourceAllocationModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !resourceAllocationForm || !resourceAllocationHistoryList) {
                    return;
                }

                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingDate = trigger.getAttribute('data-booking-date') || '';
                var allocations = bookingAllocationsByBooking[bookingId] || [];

                resourceAllocationForm.action = '/apps-bookings/' + bookingId + '/resource-allocations';
                if (resourceAllocationTargetText) {
                    resourceAllocationTargetText.textContent = i18n.manageResourceAllocationForBooking + ' ' + bookingCode + ' - ' + bookingName;
                }
                if (allocationDateInput) {
                    allocationDateInput.value = bookingDate || '';
                }

                var activeRow = document.querySelector('[data-booking-row] button[data-booking-id="' + bookingId + '"]');
                var activeRowElement = activeRow ? activeRow.closest('[data-booking-row]') : null;
                var bookingTenantId = activeRowElement ? activeRowElement.getAttribute('data-tenant-id') : null;
                if (allocationResourceId) {
                    allocationResourceId.value = '';
                    allocationResourceId.querySelectorAll('option[data-tenant-id]').forEach(function (option) {
                        var canShow = !bookingTenantId || option.getAttribute('data-tenant-id') === bookingTenantId;
                        option.hidden = !canShow;
                    });
                }

                if (allocations.length === 0) {
                    resourceAllocationHistoryList.innerHTML = '<div class="text-muted py-2">' + i18n.noResourceAllocationYet + '</div>';
                } else {
                    resourceAllocationHistoryList.innerHTML = allocations.map(function (item) {
                        return '' +
                            '<div class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">' +
                                '<div>' +
                                    '<div class="fw-semibold">' + (item.resource_name || '-') + '</div>' +
                                    '<div class="small text-muted">' + i18n.date + ': ' + (item.allocation_date || '-') + '</div>' +
                                    '<div class="small text-muted">' + i18n.pax + ': ' + (item.allocated_pax || '-') + ' | ' + i18n.units + ': ' + (item.allocated_units || '-') + '</div>' +
                                    '<div class="small text-muted">' + i18n.notes + ': ' + (item.notes || '-') + '</div>' +
                                '</div>' +
                                '<form method="POST" action="/apps-bookings/' + bookingId + '/resource-allocations/' + item.id + '/delete">' +
                                    '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                    '<button type="submit" class="btn btn-sm btn-soft-danger">' + i18n.unassign + '</button>' +
                                '</form>' +
                            '</div>';
                    }).join('');
                }
            });
        }

        applyFilter();
        setActiveWorkflowChip('all');
        initColumnToggles();
    });
</script>
