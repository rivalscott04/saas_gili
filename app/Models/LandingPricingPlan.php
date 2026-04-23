<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/** Paket harga landing; paket Popular menyetel kuota seat semua tenant. */
class LandingPricingPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'subtitle',
        'price_monthly',
        'price_yearly',
        'is_popular',
        'max_users',
        'icon_class',
        'sort_order',
        'category_slots_included',
        'extra_category_price_monthly',
        'extra_category_price_yearly',
    ];

    protected function casts(): array
    {
        return [
            'is_popular' => 'boolean',
            'max_users' => 'integer',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'sort_order' => 'integer',
            'category_slots_included' => 'integer',
            'extra_category_price_monthly' => 'integer',
            'extra_category_price_yearly' => 'integer',
        ];
    }

    /**
     * Kategori bisnis yang boleh dipilih tenant untuk paket ini.
     * Kosong = semua kategori aktif diizinkan.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function allowedCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'landing_pricing_plan_allowed_categories',
            'landing_pricing_plan_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * Tanpa pivot = semua kategori aktif boleh.
     */
    public function restrictsCategories(): bool
    {
        if ($this->relationLoaded('allowedCategories')) {
            return $this->allowedCategories->isNotEmpty();
        }

        return $this->allowedCategories()->exists();
    }

    public function allowsCategoryId(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }
        if (! $this->restrictsCategories()) {
            return Category::query()->whereKey($categoryId)->where('is_active', true)->exists();
        }

        if ($this->relationLoaded('allowedCategories')) {
            return $this->allowedCategories->contains('id', $categoryId);
        }

        return $this->allowedCategories()->where('categories.id', $categoryId)->exists();
    }

    /**
     * @param  list<int>  $categoryIds
     */
    public function quoteForCategoryIds(array $categoryIds, bool $yearly): int
    {
        $ids = array_values(array_unique(array_values(array_filter(
            array_map(static fn ($v): int => (int) $v, $categoryIds),
            static fn (int $id): bool => $id > 0
        ))));
        foreach ($ids as $id) {
            if (! $this->allowsCategoryId($id)) {
                throw ValidationException::withMessages([
                    'category_ids' => 'Salah satu kategori tidak diizinkan untuk paket '.$this->name.'.',
                ]);
            }
        }

        return $this->quoteForCategoryCount(count($ids), $yearly);
    }

    public function quoteForCategoryCount(int $selectedCategoryCount, bool $yearly): int
    {
        $n = max(0, $selectedCategoryCount);
        $slots = max(1, (int) $this->category_slots_included);
        $extraSlots = max(0, $n - $slots);
        $base = $yearly ? (int) $this->price_yearly : (int) $this->price_monthly;
        $perExtra = $yearly
            ? (int) $this->extra_category_price_yearly
            : (int) $this->extra_category_price_monthly;

        return $base + $extraSlots * $perExtra;
    }

    /**
     * Ikon Remix untuk paket landing: value = class CSS, label untuk dropdown orang awam.
     *
     * @var list<array{value: string, label: string}>
     */
    public const REMIX_ICON_OPTIONS = [
        ['value' => 'ri-book-line', 'label' => 'Buku — starter / dasar'],
        ['value' => 'ri-medal-fill', 'label' => 'Medali — pro / unggulan'],
        ['value' => 'ri-stack-fill', 'label' => 'Tumpukan — enterprise / lengkap'],
        ['value' => 'ri-vip-crown-line', 'label' => 'Mahkota — premium'],
        ['value' => 'ri-rocket-line', 'label' => 'Roket — naik cepat'],
        ['value' => 'ri-shield-star-line', 'label' => 'Perisai — aman & terpercaya'],
        ['value' => 'ri-price-tag-3-line', 'label' => 'Label harga — penawaran'],
        ['value' => 'ri-store-3-line', 'label' => 'Toko — bisnis'],
        ['value' => 'ri-team-line', 'label' => 'Tim — kolaborasi'],
        ['value' => 'ri-lightbulb-flash-line', 'label' => 'Lampu — ide / kreatif'],
        ['value' => 'ri-global-line', 'label' => 'Globe — internasional'],
        ['value' => 'ri-palette-line', 'label' => 'Palet — fleksibel'],
        ['value' => 'ri-seedling-line', 'label' => 'Benih — pertumbuhan'],
        ['value' => 'ri-hand-heart-line', 'label' => 'Layanan — peduli'],
        ['value' => 'ri-suitcase-line', 'label' => 'Koper — profesional'],
        ['value' => 'ri-flight-takeoff-line', 'label' => 'Pesawat lepas landas — tur / travel'],
        ['value' => 'ri-ship-line', 'label' => 'Kapal — pelayaran'],
        ['value' => 'ri-map-pin-line', 'label' => 'Pin peta — lokasi'],
    ];

    /**
     * Nilai class yang boleh disimpan (katalog + nilai lama di DB jika belum ada di katalog).
     *
     * @return list<string>
     */
    public static function allowedRemixIconValuesIncluding(?string $legacyIconClass): array
    {
        $allowed = array_values(array_unique(array_map(
            static fn (array $row): string => strtolower($row['value']),
            self::REMIX_ICON_OPTIONS
        )));

        if ($legacyIconClass !== null && $legacyIconClass !== '') {
            $c = strtolower(trim($legacyIconClass));
            if (preg_match('/^ri-[a-z0-9-]+$/', $c) === 1 && ! in_array($c, $allowed, true)) {
                $allowed[] = $c;
            }
        }

        return $allowed;
    }

    /**
     * Samakan tenants.max_users dengan nilai max_users paket Popular (null = 500).
     * Tiap tenant tidak boleh di bawah jumlah user yang sudah ada.
     */
    public static function syncTenantSeatCapsFromPopularPlan(): void
    {
        $popular = static::query()->where('is_popular', true)->first();
        if ($popular === null) {
            return;
        }

        $cap = $popular->max_users;
        if ($cap === null) {
            $cap = 500;
        }
        $cap = min(500, max(1, (int) $cap));

        foreach (Tenant::query()->withCount('users')->cursor() as $tenant) {
            $min = max(1, (int) $tenant->users_count);
            $next = max($min, $cap);
            if ((int) $tenant->max_users !== $next) {
                $tenant->max_users = $next;
                $tenant->save();
            }
        }
    }

    /**
     * Paket dengan fitur terurut (maks. dua query: paket + fitur).
     *
     * @return Collection<int, self>
     */
    public static function allWithFeaturesForDisplay(): Collection
    {
        return static::query()
            ->select([
                'id',
                'code',
                'name',
                'subtitle',
                'price_monthly',
                'price_yearly',
                'is_popular',
                'max_users',
                'icon_class',
                'sort_order',
                'category_slots_included',
                'extra_category_price_monthly',
                'extra_category_price_yearly',
            ])
            ->with([
                'features' => static function ($query): void {
                    $query->select([
                        'id',
                        'landing_pricing_plan_id',
                        'display_text',
                        'is_included',
                        'sort_order',
                    ])->orderBy('sort_order');
                },
                'allowedCategories' => static function ($query): void {
                    $query->select(['categories.id', 'categories.code', 'categories.name'])
                        ->where('categories.is_active', true)
                        ->orderBy('categories.name');
                },
            ])
            ->orderBy('sort_order')
            ->get();
    }

    public function features(): HasMany
    {
        return $this->hasMany(LandingPricingPlanFeature::class)->orderBy('sort_order');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'landing_pricing_plan_id');
    }

    public function maxUsersLineHtml(): string
    {
        if ($this->max_users === null) {
            return '<b>Unlimited</b> Users';
        }

        return 'Upto <b>'.e((string) $this->max_users).'</b> Users';
    }
}
