<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LandingPricingPlan;
use App\Models\LandingPricingPlanFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminLandingPricingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return redirect()->route('root');
        }

        $plans = LandingPricingPlan::allWithFeaturesForDisplay();
        $allCategories = Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('apps-superadmin-landing-pricing', [
            'plans' => $plans,
            'allCategories' => $allCategories,
            'openPlanModalId' => $this->firstPlanIdWithValidationErrors(
                $request->session()->get('errors'),
                $plans
            ),
        ]);
    }

    /**
     * @param  Collection<int, LandingPricingPlan>  $plans
     */
    private function firstPlanIdWithValidationErrors(mixed $errors, Collection $plans): ?int
    {
        if (! $errors instanceof ViewErrorBag || ! $errors->any()) {
            return null;
        }
        $keys = $errors->getBag('default')->keys();
        foreach ($plans as $plan) {
            $prefix = 'plans.'.$plan->id.'.';
            foreach ($keys as $key) {
                if (str_starts_with((string) $key, $prefix)) {
                    return (int) $plan->id;
                }
            }
        }

        return null;
    }

    public function update(Request $request, LandingPricingPlan $plan): RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return redirect()->route('root');
        }

        $planKey = (string) $plan->id;
        $baseKey = 'plans.'.$planKey;

        $featuresInput = collect($request->input($baseKey.'.features', []))
            ->filter(fn (array $row): bool => filled($row['display_text'] ?? null))
            ->values()
            ->all();

        $allPlans = (array) $request->input('plans', []);
        $planPayload = (array) ($allPlans[$plan->id] ?? []);
        $planPayload['features'] = $featuresInput;
        $allPlans[$plan->id] = $planPayload;
        $request->merge(['plans' => $allPlans]);

        $validator = Validator::make($request->all(), [
            $baseKey.'.name' => ['required', 'string', 'max:255'],
            $baseKey.'.subtitle' => ['nullable', 'string', 'max:255'],
            $baseKey.'.price_monthly' => ['required', 'integer', 'min:0', 'max:999999'],
            $baseKey.'.price_yearly' => ['required', 'integer', 'min:0', 'max:999999'],
            $baseKey.'.is_popular' => ['nullable', 'in:0,1'],
            $baseKey.'.max_users_unlimited' => ['nullable', 'in:0,1'],
            $baseKey.'.max_users' => [
                Rule::requiredIf(fn (): bool => (string) $request->input($baseKey.'.max_users_unlimited', '0') !== '1'),
                'nullable',
                'integer',
                'min:1',
                'max:999999',
            ],
            $baseKey.'.icon_class' => [
                'required',
                'string',
                'max:128',
                Rule::in(LandingPricingPlan::allowedRemixIconValuesIncluding($plan->icon_class)),
            ],
            $baseKey.'.sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            $baseKey.'.features' => ['required', 'array', 'min:1'],
            $baseKey.'.features.*.display_text' => ['required', 'string', 'max:500'],
            $baseKey.'.features.*.is_included' => ['required', 'in:0,1'],
            $baseKey.'.category_slots_included' => ['required', 'integer', 'min:1', 'max:50'],
            $baseKey.'.extra_category_price_monthly' => ['required', 'integer', 'min:0', 'max:999999'],
            $baseKey.'.extra_category_price_yearly' => ['required', 'integer', 'min:0', 'max:999999'],
            $baseKey.'.allowed_category_ids' => ['nullable', 'array'],
            $baseKey.'.allowed_category_ids.*' => ['integer', Rule::exists('categories', 'id')],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('superadmin-landing-pricing.index')
                ->withInput()
                ->withErrors($validator);
        }

        $validated = $validator->validated();
        $row = $validated['plans'][$plan->id];

        $unlimited = (string) $request->input($baseKey.'.max_users_unlimited', '0') === '1';
        $maxUsers = $unlimited ? null : (int) $row['max_users'];

        $isPopular = (string) $request->input($baseKey.'.is_popular', '0') === '1';
        $iconClass = strtolower(trim((string) ($row['icon_class'] ?? '')));
        if ($iconClass === '') {
            $iconClass = 'ri-book-line';
        }

        DB::transaction(function () use ($plan, $row, $isPopular, $maxUsers, $iconClass, $featuresInput): void {
            if ($isPopular) {
                LandingPricingPlan::query()->whereKeyNot($plan->id)->update(['is_popular' => false]);
            }

            $plan->fill([
                'name' => $row['name'],
                'subtitle' => $row['subtitle'] ?? null,
                'price_monthly' => (int) $row['price_monthly'],
                'price_yearly' => (int) $row['price_yearly'],
                'is_popular' => $isPopular,
                'max_users' => $maxUsers,
                'icon_class' => strtolower($iconClass),
                'sort_order' => (int) ($row['sort_order'] ?? $plan->sort_order),
                'category_slots_included' => (int) ($row['category_slots_included'] ?? 1),
                'extra_category_price_monthly' => (int) ($row['extra_category_price_monthly'] ?? 0),
                'extra_category_price_yearly' => (int) ($row['extra_category_price_yearly'] ?? 0),
            ]);
            $plan->save();

            $allowedIds = collect((array) ($row['allowed_category_ids'] ?? []))
                ->map(fn ($v) => (int) $v)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $plan->allowedCategories()->sync($allowedIds);

            $plan->features()->delete();

            if ($featuresInput !== []) {
                $now = now();
                $rows = [];
                foreach ($featuresInput as $index => $featureRow) {
                    $rows[] = [
                        'landing_pricing_plan_id' => $plan->id,
                        'display_text' => (string) $featureRow['display_text'],
                        'is_included' => (($featureRow['is_included'] ?? '0') === '1') ? 1 : 0,
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                LandingPricingPlanFeature::query()->insert($rows);
            }
        });

        LandingPricingPlan::syncTenantSeatCapsFromPopularPlan();

        $message = 'Paket '.$plan->name.' berhasil disimpan.';
        if (LandingPricingPlan::query()->where('is_popular', true)->exists()) {
            $message .= ' Kuota seat tenant mengikuti max users paket Popular.';
        }

        return redirect()
            ->route('superadmin-landing-pricing.index')
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Pricing diperbarui',
                'message' => $message,
            ]);
    }
}
