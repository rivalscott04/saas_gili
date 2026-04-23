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

    var bookingEvents = rawEvents
        .filter(function (eventItem) {
            return Boolean(eventItem && eventItem.start);
        })
        .map(function (eventItem) {
            var status = eventItem.status || (eventItem.extendedProps ? eventItem.extendedProps.status : "");
            return {
                id: eventItem.id,
                title: eventItem.title,
                start: eventItem.start,
                className: resolveClassName(status),
                extendedProps: eventItem.extendedProps || {}
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
        return new Date(value).toLocaleString("id-ID", {
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
            var tooltipHtml = ""
                + "<div class='text-start'>"
                + "<div><strong>Guest:</strong> " + escapeHtml(booking.guestName) + "</div>"
                + "<div><strong>Package:</strong> " + escapeHtml(booking.packageName) + "</div>"
                + "<div><strong>PAX:</strong> " + escapeHtml(booking.pax) + "</div>"
                + "<div><strong>Guide:</strong> " + escapeHtml(booking.guide) + "</div>"
                + "<div><strong>Pickup:</strong> " + escapeHtml(booking.pickupPoint) + "</div>"
                + "<div><strong>Status:</strong> " + escapeHtml(booking.status) + "</div>"
                + "<div><strong>Notes:</strong> " + escapeHtml(booking.notes || "-") + "</div>"
                + "</div>";

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
                + "<span class='badge " + badgeClass + "'>" + escapeHtml(props.status || "Unknown") + "</span>"
                + "</div>"
                + "<h6 class='card-title fs-15 mb-1'>" + escapeHtml(props.guestName || "-") + "</h6>"
                + "<p class='text-muted mb-1'>" + escapeHtml(props.packageName || "-") + "</p>"
                + "<p class='text-muted mb-0'>Guide: " + escapeHtml(props.guide || "-") + " | Pickup: " + escapeHtml(props.pickupPoint || "-") + "</p>"
                + "</div>"
                + "</div>";
        });
});
