<?php

return [
    /*
    | Customer magic-link endpoints are public; throttle by booking+IP and by IP.
    | Tune via .env (see .env.example).
    */
    'magic_link_per_booking_ip' => (int) env('MAGIC_LINK_PER_BOOKING_IP', 20),
    'magic_link_per_ip' => (int) env('MAGIC_LINK_PER_IP', 100),
];
