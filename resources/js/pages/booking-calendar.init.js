/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Booking calendar init
*/

document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("booking-calendar");
    var upcomingContainer = document.getElementById("upcoming-booking-list");

    if (!calendarEl || !upcomingContainer) {
        return;
    }

    var rawEvents = Array.isArray(window.bookingCalendarEvents) ? window.bookingCalendarEvents : [];
    var i18n = Object.assign({
        locale: "id-ID",
        guest: "Guest",
        package: "Package",
        guide: "Guide",
        pickup: "Pickup",
        status: "Status",
        source: "Source",
        notes: "Notes",
        pax: "PAX",
        unknown: "Unknown",
        manual: "MANUAL",
        ota: "OTA",
        statusLabels: {}
    }, window.bookingCalendarI18n || {});

    function translateStatus(status) {
        var normalized = String(status || "").toLowerCase();
        if (normalized === "confirmed") {
            return i18n.statusLabels.confirmed || "Confirmed";
        }
        if (normalized === "on tour" || normalized === "on_tour") {
            return i18n.statusLabels.on_tour || "On Tour";
        }
        if (normalized === "standby") {
            return i18n.statusLabels.standby || "Standby";
        }
        if (normalized === "pending") {
            return i18n.statusLabels.pending || "Pending";
        }
        if (normalized === "cancelled") {
            return i18n.statusLabels.cancelled || "Cancelled";
        }
        return status || i18n.unknown;
    }

    function resolveClassName(status) {
        var normalized = String(status || "").toLowerCase();
        if (normalized === "confirmed") {
            return "bg-success-subtle";
        }
        if (normalized === "standby") {
            return "bg-secondary-subtle";
        }
        if (normalized === "pending") {
            return "bg-warning-subtle";
        }
        if (normalized === "on tour" || normalized === "on_tour") {
            return "bg-info-subtle";
        }
        if (normalized === "cancelled") {
            return "bg-danger-subtle";
        }
        return "bg-primary-subtle";
    }

    function resolveSourceBadge(source) {
        var normalized = String(source || "manual").toLowerCase();
        if (normalized === "ota") {
            return {
                label: i18n.ota,
                className: "bg-info-subtle text-info"
            };
        }

        return {
            label: i18n.manual,
            className: "bg-primary-subtle text-primary"
        };
    }

    var bookingEvents = rawEvents
        .filter(function (eventItem) {
            return Boolean(eventItem && eventItem.start);
        })
        .map(function (eventItem) {
            var status = eventItem.status || (eventItem.extendedProps ? eventItem.extendedProps.status : "");
            var source = eventItem.extendedProps ? eventItem.extendedProps.bookingSource : "manual";
            return {
                id: eventItem.id,
                title: eventItem.title,
                start: eventItem.start,
                className: resolveClassName(status),
                extendedProps: Object.assign({}, eventItem.extendedProps || {}, {
                    bookingSource: source
                })
            };
        });

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(value) {
        return new Date(value).toLocaleString(i18n.locale, {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: "local",
        themeSystem: "bootstrap",
        initialView: "dayGridMonth",
        navLinks: true,
        editable: false,
        selectable: false,
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listMonth"
        },
        events: bookingEvents,
        eventDidMount: function (info) {
            var booking = info.event.extendedProps;
            var sourceBadge = resolveSourceBadge(booking.bookingSource);
            var tooltipHtml = ""
                + "<div class='text-start'>"
                + "<div><strong>" + escapeHtml(i18n.guest) + ":</strong> " + escapeHtml(booking.guestName) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.package) + ":</strong> " + escapeHtml(booking.packageName) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.pax) + ":</strong> " + escapeHtml(booking.pax) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.guide) + ":</strong> " + escapeHtml(booking.guide) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.pickup) + ":</strong> " + escapeHtml(booking.pickupPoint) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.status) + ":</strong> " + escapeHtml(translateStatus(booking.status)) + "</div>"
                + "<div><strong>" + escapeHtml(i18n.source) + ":</strong> " + escapeHtml(sourceBadge.label)
                + (booking.channelLabel ? " (" + escapeHtml(booking.channelLabel) + ")" : "") + "</div>"
                + "<div><strong>" + escapeHtml(i18n.notes) + ":</strong> " + escapeHtml(booking.notes || "-") + "</div>"
                + "</div>";

            var titleNode = info.el.querySelector(".fc-event-title");
            if (titleNode && !titleNode.querySelector(".booking-source-badge")) {
                titleNode.innerHTML = ""
                    + titleNode.innerHTML
                    + " <span class='badge booking-source-badge ms-1 " + sourceBadge.className + "'>"
                    + escapeHtml(sourceBadge.label)
                    + "</span>";
            }

            new bootstrap.Tooltip(info.el, {
                title: tooltipHtml,
                html: true,
                placement: "top",
                trigger: "hover",
                container: "body"
            });
        }
    });

    calendar.render();

    bookingEvents
        .slice()
        .sort(function (a, b) { return new Date(a.start) - new Date(b.start); })
        .forEach(function (eventItem) {
            var props = eventItem.extendedProps || {};
            var sourceBadge = resolveSourceBadge(props.bookingSource);
            var badgeClass = "bg-primary-subtle text-primary";
            var statusText = String(props.status || "").toLowerCase();
            if (statusText === "confirmed") {
                badgeClass = "bg-success-subtle text-success";
            } else if (statusText === "on tour" || statusText === "on_tour") {
                badgeClass = "bg-info-subtle text-info";
            } else if (statusText === "standby") {
                badgeClass = "bg-secondary-subtle text-secondary";
            } else if (statusText === "pending") {
                badgeClass = "bg-warning-subtle text-warning";
            } else if (statusText === "cancelled") {
                badgeClass = "bg-danger-subtle text-danger";
            }

            upcomingContainer.innerHTML += ""
                + "<div class='card mb-3'>"
                + "<div class='card-body'>"
                + "<div class='d-flex justify-content-between align-items-center mb-2'>"
                + "<span class='fw-medium'>" + escapeHtml(formatDate(eventItem.start)) + "</span>"
                + "<span class='badge " + badgeClass + "'>" + escapeHtml(translateStatus(props.status || i18n.unknown)) + "</span>"
                + "</div>"
                + "<h6 class='card-title fs-15 mb-1'>" + escapeHtml(props.guestName || "-") + "</h6>"
                + "<p class='text-muted mb-1'>" + escapeHtml(props.packageName || "-") + "</p>"
                + "<p class='mb-1'><span class='badge " + sourceBadge.className + "'>" + sourceBadge.label + "</span>"
                + (props.channelLabel ? " <span class='text-muted'>(" + escapeHtml(props.channelLabel) + ")</span>" : "")
                + "</p>"
                + "<p class='text-muted mb-0'>" + escapeHtml(i18n.guide) + ": " + escapeHtml(props.guide || "-")
                + " | " + escapeHtml(i18n.pickup) + ": " + escapeHtml(props.pickupPoint || "-") + "</p>"
                + "</div>"
                + "</div>";
        });
});
