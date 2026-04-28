<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GygSupplierApiService
{
    /**
     * @var array<int, string>
     */
    private const COUNTED_BOOKING_STATUSES = ['pending', 'confirmed', 'standby'];

    /**
     * @var array<int, string>
     */
    private const INDIVIDUAL_TICKET_CATEGORIES = [
        'ADULT',
        'CHILD',
        'YOUTH',
        'INFANT',
        'SENIOR',
        'STUDENT',
        'EU_CITIZEN',
        'MILITARY',
        'EU_CITIZEN_STUDENT',
    ];

    /**
     * @return array<string, mixed>
     */
    public function getAvailabilities(string $productId, string $fromDateTime, string $toDateTime): array
    {
        $tour = $this->resolveTourByProductId($productId);
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'Invalid productId');
        }

        $from = $this->parseDateTime($fromDateTime);
        $to = $this->parseDateTime($toDateTime);

        if ($from->greaterThan($to)) {
            return $this->error('VALIDATION_FAILURE', 'fromDateTime must be before or equal to toDateTime');
        }

        $currency = (string) config('gyg_supplier_api.default_currency', 'EUR');
        $profile = $this->resolveProductProfile($tour);
        $supportedCategories = $this->supportedTicketCategories($profile);
        $includePrices = (bool) config('gyg_supplier_api.include_prices', true);
        $pricesByCategory = $this->defaultRetailPricesByCategory($supportedCategories);
        $availabilityType = (string) config('gyg_supplier_api.availability_type', 'total');
        $effectiveProductId = (string) ($tour->code ?: $tour->id);
        $availabilities = [];
        $cursorDate = $from->startOfDay();
        $endDate = $to->startOfDay();
        $slots = ['09:00:00', '14:00:00'];

        while ($cursorDate->lessThanOrEqualTo($endDate)) {
            $date = $cursorDate->toDateString();
            $maxPax = TourDayCapacity::query()
                ->where('tour_id', $tour->id)
                ->whereDate('service_date', $date)
                ->value('max_pax');
            if ($maxPax === null) {
                $maxPax = $tour->default_max_pax_per_day ?? 50;
            }
            $bookedPax = Booking::query()
                ->where('tour_id', $tour->id)
                ->whereDate('tour_start_at', $date)
                ->whereIn('status', self::COUNTED_BOOKING_STATUSES)
                ->sum('participants');
            $vacancies = max(0, (int) $maxPax - (int) $bookedPax);

            $addedForDate = false;
            foreach ($slots as $slotTime) {
                $slot = CarbonImmutable::parse($date.' '.$slotTime, $from->getTimezone());
                if ($slot->lessThan($from) || $slot->greaterThan($to)) {
                    continue;
                }
                $addedForDate = true;
                $availabilities[] = [
                    'dateTime' => $profile['time_mode'] === 'time_period'
                        ? $cursorDate->startOfDay()->format(DATE_ATOM)
                        : $slot->format(DATE_ATOM),
                    'productId' => $effectiveProductId,
                    'cutoffSeconds' => 3600,
                    'currency' => $currency,
                ];
                if ($profile['time_mode'] === 'time_period') {
                    $availabilities[array_key_last($availabilities)]['openingTimes'] = [[
                        'fromTime' => '09:00',
                        'toTime' => '18:00',
                    ]];
                }
                if ($includePrices) {
                    $availabilities[array_key_last($availabilities)]['pricesByCategory'] = $pricesByCategory;
                }
                if ($availabilityType === 'by_category') {
                    $availabilities[array_key_last($availabilities)]['vacanciesByCategory'] = $this->vacanciesByCategory($supportedCategories, $vacancies);
                } else {
                    $availabilities[array_key_last($availabilities)]['vacancies'] = $vacancies;
                }
            }

            if (! $addedForDate && $date === $from->toDateString()) {
                $slot = $from;
                if ($slot->lessThanOrEqualTo($to)) {
                    $availabilities[] = [
                        'dateTime' => $profile['time_mode'] === 'time_period'
                            ? $cursorDate->startOfDay()->format(DATE_ATOM)
                            : $slot->format(DATE_ATOM),
                        'productId' => $effectiveProductId,
                        'cutoffSeconds' => 3600,
                        'currency' => $currency,
                    ];
                    if ($profile['time_mode'] === 'time_period') {
                        $availabilities[array_key_last($availabilities)]['openingTimes'] = [[
                            'fromTime' => '09:00',
                            'toTime' => '18:00',
                        ]];
                    }
                    if ($includePrices) {
                        $availabilities[array_key_last($availabilities)]['pricesByCategory'] = $pricesByCategory;
                    }
                    if ($availabilityType === 'by_category') {
                        $availabilities[array_key_last($availabilities)]['vacanciesByCategory'] = $this->vacanciesByCategory($supportedCategories, $vacancies);
                    } else {
                        $availabilities[array_key_last($availabilities)]['vacancies'] = $vacancies;
                    }
                }
            }

            $cursorDate = $cursorDate->addDay();
        }

        return ['data' => ['availabilities' => $availabilities]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function reserve(array $data): array
    {
        $tour = $this->resolveTourByProductId((string) ($data['productId'] ?? ''));
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'Invalid productId');
        }
        $profile = $this->resolveProductProfile($tour);
        $unsupportedCategory = $this->firstUnsupportedCategory((array) ($data['bookingItems'] ?? []), $profile);
        if ($unsupportedCategory !== null) {
            return $this->invalidTicketCategoryError($unsupportedCategory);
        }
        $dateTime = $this->parseDateTime((string) $data['dateTime']);
        $requestedParticipants = $this->requestedParticipantsFromBookingItems((array) ($data['bookingItems'] ?? []));
        $remainingVacancies = $this->remainingVacancies($tour, $dateTime);
        if ($requestedParticipants > $remainingVacancies) {
            return $this->error('NO_AVAILABILITY', 'Requested timeslot is not available');
        }
        $maxParticipants = $this->effectiveMaxParticipants($tour, $dateTime);
        if ($maxParticipants !== null && $requestedParticipants > $maxParticipants) {
            return $this->participantsConfigError($maxParticipants);
        }

        $reservationReference = 'res'.Str::upper(Str::random(8));
        $reservationExpiration = now()->addHour()->toIso8601String();

        Cache::put($this->reservationCacheKey($reservationReference), [
            'reservationReference' => $reservationReference,
            'reservationExpiration' => $reservationExpiration,
            'productId' => (string) ($tour->code ?: $tour->id),
            'tour_id' => (int) $tour->id,
            'tenant_id' => (int) $tour->tenant_id,
            'tour_name' => (string) $tour->name,
            'dateTime' => $dateTime->toIso8601String(),
            'gygBookingReference' => (string) $data['gygBookingReference'],
            'bookingItems' => $data['bookingItems'],
        ], now()->addHours(3));

        return [
            'data' => [
                'reservationReference' => $reservationReference,
                'reservationExpiration' => $reservationExpiration,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelReservation(string $reservationReference): array
    {
        Cache::forget($this->reservationCacheKey($reservationReference));

        return ['data' => (object) []];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function book(array $payload): array
    {
        $tour = $this->resolveTourByProductId((string) $payload['productId']);
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'Invalid productId');
        }
        $profile = $this->resolveProductProfile($tour);
        $unsupportedCategory = $this->firstUnsupportedCategory((array) ($payload['bookingItems'] ?? []), $profile);
        if ($unsupportedCategory !== null) {
            return $this->invalidTicketCategoryError($unsupportedCategory);
        }
        $dateTime = $this->parseDateTime((string) $payload['dateTime']);
        $requestedParticipants = $this->requestedParticipantsFromBookingItems((array) ($payload['bookingItems'] ?? []));
        $remainingVacancies = $this->remainingVacancies($tour, $dateTime);
        if ($requestedParticipants > $remainingVacancies) {
            return $this->error('NO_AVAILABILITY', 'Requested timeslot is not available');
        }
        $maxParticipants = $this->effectiveMaxParticipants($tour, $dateTime);
        if ($maxParticipants !== null && $requestedParticipants > $maxParticipants) {
            return $this->participantsConfigError($maxParticipants);
        }

        $reservationReference = (string) $payload['reservationReference'];
        $reservation = Cache::get($this->reservationCacheKey($reservationReference));
        if (! is_array($reservation)) {
            return $this->error('INVALID_RESERVATION', 'Reservation reference is invalid or expired');
        }

        $bookingReference = $this->generateBookingReference();
        $tickets = [];
        $participants = 0;

        foreach ($payload['bookingItems'] as $item) {
            $category = (string) ($item['category'] ?? 'ADULT');
            $count = (int) ($item['count'] ?? 1);
            $participants += $category === 'GROUP'
                ? (int) (($item['groupSize'] ?? 1) * $count)
                : $count;
            for ($i = 0; $i < $count; $i++) {
                $tickets[] = [
                    'category' => $category,
                    'ticketCode' => Str::upper(Str::random(12)),
                    'ticketCodeType' => 'QR_CODE',
                ];
            }
        }

        $leadTraveler = $payload['travelers'][0] ?? null;
        if (! is_array($leadTraveler)) {
            return $this->error('VALIDATION_FAILURE', 'Missing lead traveler');
        }

        DB::transaction(function () use ($payload, $leadTraveler, $tour, $bookingReference, $participants, $dateTime): void {
            $customer = $this->resolveCustomer(
                (int) $tour->tenant_id,
                (string) ($leadTraveler['firstName'] ?? '').' '.(string) ($leadTraveler['lastName'] ?? ''),
                (string) ($leadTraveler['email'] ?? ''),
                (string) ($leadTraveler['phoneNumber'] ?? '')
            );

            Booking::query()->create([
                'tenant_id' => (int) $tour->tenant_id,
                'customer_id' => $customer->id,
                'tour_id' => $tour->id,
                'tour_name' => $tour->name,
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'tour_start_at' => $dateTime->toDateTimeString(),
                'status' => 'pending',
                'booking_source' => 'ota',
                'channel' => 'GETYOURGUIDE',
                'channel_order_id' => (string) $payload['gygBookingReference'],
                'supplier_booking_reference' => $bookingReference,
                'external_booking_ref' => (string) $payload['gygBookingReference'],
                'external_option_id' => (string) ($tour->code ?: $tour->id),
                'currency' => strtoupper((string) $payload['currency']),
                'participants' => max(1, $participants),
                'notes' => (string) $payload['comment'],
            ]);
        });

        Cache::put($this->bookingCacheKey($bookingReference), [
            'bookingReference' => $bookingReference,
            'gygBookingReference' => (string) $payload['gygBookingReference'],
            'productId' => (string) ($tour->code ?: $tour->id),
        ], now()->addDays(7));

        return [
            'data' => [
                'bookingReference' => $bookingReference,
                'tickets' => $tickets,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelBooking(string $bookingReference): array
    {
        $booking = Booking::query()
            ->where('supplier_booking_reference', $bookingReference)
            ->first();
        if (! $booking) {
            return $this->error('INVALID_BOOKING', 'Booking not found');
        }

        $booking->update([
            'status' => 'cancelled',
            'external_status' => 'cancelled',
        ]);

        Cache::forget($this->bookingCacheKey($bookingReference));

        return ['data' => (object) []];
    }

    /**
     * @return array<string, mixed>
     */
    public function notify(): array
    {
        return ['data' => (object) []];
    }

    /**
     * @return array<string, mixed>
     */
    public function pricingCategories(string $productId): array
    {
        $tour = $this->resolveTourByProductId($productId);
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'This product does not exist');
        }

        $profile = $this->resolveProductProfile($tour);

        return [
            'data' => [
                'pricingCategories' => $this->buildPricingCategories($profile),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supplierProducts(string $supplierId): array
    {
        $tenant = Tenant::query()
            ->where('code', $supplierId)
            ->first();
        if (! $tenant) {
            return $this->error('INVALID_SUPPLIER', 'Supplier ID not found');
        }

        $products = Tour::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(static fn (Tour $tour): array => [
                'productId' => (string) ($tour->code ?: $tour->id),
                'productTitle' => (string) $tour->name,
            ])
            ->values()
            ->all();

        return [
            'data' => [
                'supplierId' => $supplierId,
                'supplierName' => (string) $tenant->name,
                'products' => $products,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addons(string $productId): array
    {
        $tour = $this->resolveTourByProductId($productId);
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'This product does not exist');
        }

        return [
            'data' => [
                'addons' => [
                    [
                        'addonType' => 'FOOD',
                        'retailPrice' => 1050,
                        'currency' => (string) config('gyg_supplier_api.default_currency', 'EUR'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productDetails(string $productId): array
    {
        $tour = $this->resolveTourByProductId($productId);
        if (! $tour) {
            return $this->error('INVALID_PRODUCT', 'This product does not exist');
        }

        return [
            'data' => [
                'supplierId' => (string) $tour->tenant?->code,
                'productTitle' => (string) $tour->name,
                'productDescription' => (string) ($tour->description ?: 'Tour on saas_gili'),
                'destinationLocation' => [
                    'city' => 'Bali',
                    'country' => 'IDN',
                ],
                'configuration' => [
                    'participantsConfiguration' => [
                        'min' => 1,
                        'max' => $tour->default_max_pax_per_day ?? 999,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function error(string $code, string $message): array
    {
        return [
            'errorCode' => $code,
            'errorMessage' => $message,
        ];
    }

    private function isValidProductId(string $productId): bool
    {
        return in_array($productId, $this->validProductIds(), true);
    }

    /**
     * @return array<int, string>
     */
    private function validProductIds(): array
    {
        return (array) config('gyg_supplier_api.valid_product_ids', []);
    }

    private function resolveTourByProductId(string $productId): ?Tour
    {
        $tour = Tour::query()
            ->with('tenant')
            ->where('is_active', true)
            ->where(function ($query) use ($productId): void {
                $query->where('code', $productId);
                if (ctype_digit($productId)) {
                    $query->orWhere('id', (int) $productId);
                }
            })
            ->first();

        if ($tour) {
            return $tour;
        }

        if (! $this->isValidProductId($productId)) {
            return null;
        }

        return Tour::query()->with('tenant')->where('is_active', true)->orderBy('id')->first();
    }

    private function reservationCacheKey(string $reservationReference): string
    {
        return 'gyg_supplier_api:reservation:'.$reservationReference;
    }

    private function bookingCacheKey(string $bookingReference): string
    {
        return 'gyg_supplier_api:booking:'.$bookingReference;
    }

    private function parseDateTime(string $value): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'dateTime' => 'Invalid datetime format.',
            ]);
        }
    }

    private function generateBookingReference(): string
    {
        do {
            $candidate = 'bk'.Str::upper(Str::random(10));
        } while (Booking::query()->where('supplier_booking_reference', $candidate)->exists());

        return $candidate;
    }

    private function resolveCustomer(int $tenantId, string $fullName, string $email, string $phone): Customer
    {
        $name = trim($fullName) !== '' ? trim($fullName) : 'GetYourGuide Traveler';
        $email = trim($email);
        $phone = trim($phone);

        if ($email !== '') {
            $existing = Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('email', $email)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return Customer::query()->create([
            'tenant_id' => $tenantId,
            'external_source' => 'getyourguide',
            'external_customer_ref' => (string) Str::uuid(),
            'full_name' => $name,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
    }

    /**
     * @return list<string>
     */
    /**
     * @param  array{pricing_mode: string, time_mode: string}  $profile
     * @return list<string>
     */
    private function supportedTicketCategories(array $profile): array
    {
        $pricingMode = $profile['pricing_mode'];
        if ($pricingMode === 'group') {
            return ['GROUP'];
        }

        $raw = (array) config('gyg_supplier_api.supported_ticket_categories', ['ADULT', 'CHILD']);
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn ($category): string => strtoupper(trim((string) $category)),
            $raw
        ), static fn (string $category): bool => in_array($category, self::INDIVIDUAL_TICKET_CATEGORIES, true))));

        if ($normalized === []) {
            return ['ADULT', 'CHILD'];
        }
        if (! in_array('ADULT', $normalized, true)) {
            array_unshift($normalized, 'ADULT');
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  list<string>  $categories
     * @return array{retailPrices: array<int, array{category: string, price: int}>}
     */
    private function defaultRetailPricesByCategory(array $categories): array
    {
        $retailPrices = [];
        foreach ($categories as $index => $category) {
            $retailPrices[] = [
                'category' => $category,
                'price' => $category === 'GROUP' ? 5000 : (1500 - ($index * 100)),
            ];
        }

        return ['retailPrices' => $retailPrices];
    }

    /**
     * @param  list<string>  $categories
     * @return array<int, array{category: string, vacancies: int}>
     */
    private function vacanciesByCategory(array $categories, int $totalVacancies): array
    {
        $count = max(1, count($categories));
        $base = intdiv($totalVacancies, $count);
        $remainder = $totalVacancies % $count;
        $result = [];
        foreach ($categories as $index => $category) {
            $result[] = [
                'category' => $category,
                'vacancies' => $base + ($index < $remainder ? 1 : 0),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bookingItems
     */
    /**
     * @param  array<int, array<string, mixed>>  $bookingItems
     * @param  array{pricing_mode: string, time_mode: string}  $profile
     */
    private function bookingItemsHaveSupportedCategories(array $bookingItems, array $profile): bool
    {
        return $this->firstUnsupportedCategory($bookingItems, $profile) === null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bookingItems
     * @param  array{pricing_mode: string, time_mode: string}  $profile
     */
    private function firstUnsupportedCategory(array $bookingItems, array $profile): ?string
    {
        $supported = $this->supportedTicketCategories($profile);
        $pricingMode = $profile['pricing_mode'];
        foreach ($bookingItems as $item) {
            $category = strtoupper((string) ($item['category'] ?? ''));
            if (! in_array($category, $supported, true)) {
                return $category === '' ? 'UNKNOWN' : $category;
            }
            if ($pricingMode === 'group') {
                $count = (int) ($item['count'] ?? 0);
                $groupSize = (int) ($item['groupSize'] ?? 0);
                if ($category !== 'GROUP' || $count !== 1 || $groupSize < 1) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param  array{pricing_mode: string, time_mode: string}  $profile
     * @return array<int, array<string, mixed>>
     */
    private function buildPricingCategories(array $profile): array
    {
        $categories = $this->supportedTicketCategories($profile);
        $result = [];
        $globalMax = max(1, (int) config('gyg_supplier_api.max_participants_default', 999));
        foreach ($categories as $category) {
            $result[] = [
                'category' => $category,
                'minTicketAmount' => $category === 'ADULT' ? 1 : 0,
                'maxTicketAmount' => $globalMax,
                'groupSizeMin' => $category === 'GROUP' ? 1 : null,
                'groupSizeMax' => $category === 'GROUP' ? $globalMax : null,
                'bookingCategory' => 'STANDARD',
            ];
        }

        return $result;
    }

    /**
     * @return array{pricing_mode: string, time_mode: string}
     */
    private function resolveProductProfile(Tour $tour): array
    {
        $code = strtoupper((string) $tour->code);
        $name = strtoupper((string) $tour->name);
        $pricingMode = (str_contains($code, '-GRP') || str_contains($name, 'GROUP')) ? 'group' : 'individual';
        $timeMode = (str_contains($code, '-TR-') || str_contains($name, 'PERIOD')) ? 'time_period' : 'time_point';

        return [
            'pricing_mode' => $pricingMode,
            'time_mode' => $timeMode,
        ];
    }

    private function effectiveMaxParticipants(Tour $tour, CarbonImmutable $dateTime): ?int
    {
        $maxPax = TourDayCapacity::query()
            ->where('tour_id', $tour->id)
            ->whereDate('service_date', $dateTime->toDateString())
            ->value('max_pax');

        if ($maxPax !== null) {
            return (int) $maxPax;
        }

        return $tour->default_max_pax_per_day !== null ? (int) $tour->default_max_pax_per_day : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bookingItems
     */
    private function requestedParticipantsFromBookingItems(array $bookingItems): int
    {
        $participants = 0;
        foreach ($bookingItems as $item) {
            $category = strtoupper((string) ($item['category'] ?? ''));
            $count = max(0, (int) ($item['count'] ?? 0));
            if ($category === 'GROUP') {
                $participants += $count * max(1, (int) ($item['groupSize'] ?? 1));
            } else {
                $participants += $count;
            }
        }

        return max(0, $participants);
    }

    /**
     * @return array<string, mixed>
     */
    private function participantsConfigError(int $maxParticipants): array
    {
        return [
            'errorCode' => 'INVALID_PARTICIPANTS_CONFIGURATION',
            'errorMessage' => "The activity cannot be reserved for more than {$maxParticipants} participants",
            'participantsConfiguration' => [
                'min' => 1,
                'max' => $maxParticipants,
            ],
        ];
    }

    private function remainingVacancies(Tour $tour, CarbonImmutable $dateTime): int
    {
        $maxParticipants = $this->effectiveMaxParticipants($tour, $dateTime);
        if ($maxParticipants === null) {
            $maxParticipants = max(1, (int) config('gyg_supplier_api.max_participants_default', 999));
        }
        $booked = Booking::query()
            ->where('tour_id', $tour->id)
            ->whereDate('tour_start_at', $dateTime->toDateString())
            ->whereIn('status', self::COUNTED_BOOKING_STATUSES)
            ->sum('participants');

        return max(0, (int) $maxParticipants - (int) $booked);
    }

    /**
     * @return array<string, mixed>
     */
    private function invalidTicketCategoryError(string $category): array
    {
        return [
            'errorCode' => 'INVALID_TICKET_CATEGORY',
            'errorMessage' => 'One or more ticket categories are not supported',
            'ticketCategory' => $category,
        ];
    }
}
