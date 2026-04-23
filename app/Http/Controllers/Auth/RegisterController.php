<?php

namespace App\Http\Controllers\Auth;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Models\LandingPricingPlan;
use App\Models\Tenant;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    public function showRegistrationForm(Request $request)
    {
        $pricingPlans = LandingPricingPlan::query()
            ->select(['id', 'code', 'name', 'price_monthly', 'price_yearly', 'is_popular', 'sort_order', 'category_slots_included'])
            ->with([
                'allowedCategories' => static function ($query): void {
                    $query->select(['categories.id', 'categories.name'])
                        ->where('categories.is_active', true)
                        ->orderBy('categories.name');
                },
            ])
            ->orderBy('sort_order')
            ->get();
        $activeCategories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $selectedPlan = $this->resolveSelectedPlan($request);
        if ($selectedPlan === null) {
            $selectedPlan = $pricingPlans->firstWhere('is_popular', true) ?? $pricingPlans->first();
            if ($selectedPlan !== null) {
                $request->session()->put('selected_landing_plan_code', $selectedPlan->code);
            }
        }

        return view('auth.register', [
            'selectedPlan' => $selectedPlan,
            'selectedPlanCode' => $selectedPlan?->code,
            'pricingPlans' => $pricingPlans,
            'activeCategories' => $activeCategories,
        ]);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'tenant_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'selected_plan_code' => ['required', 'string', 'exists:landing_pricing_plans,code'],
            'category_ids' => ['required', 'array', 'min:1', 'max:20'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'avatar' => ['nullable', 'image' ,'mimes:jpg,jpeg,png','max:1024'],
        ]);

        $validator->after(function ($validator) use ($data): void {
            $planCode = strtolower(trim((string) ($data['selected_plan_code'] ?? '')));
            $selectedCategoryIds = collect((array) ($data['category_ids'] ?? []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $plan = LandingPricingPlan::query()
                ->with([
                    'allowedCategories' => static function ($query): void {
                        $query->select(['categories.id']);
                    },
                ])
                ->where('code', $planCode)
                ->first();

            if ($plan === null) {
                $validator->errors()->add('selected_plan_code', 'Paket tidak ditemukan.');

                return;
            }

            if ($selectedCategoryIds === []) {
                $validator->errors()->add('category_ids', 'Pilih minimal satu kategori bisnis.');

                return;
            }

            $maxCategories = max(1, (int) $plan->category_slots_included);
            if (count($selectedCategoryIds) > $maxCategories) {
                $validator->errors()->add(
                    'category_ids',
                    'Paket '.$plan->name.' hanya mengizinkan maksimal '.$maxCategories.' kategori.'
                );

                return;
            }

            foreach ($selectedCategoryIds as $categoryId) {
                if (! $plan->allowsCategoryId($categoryId)) {
                    $validator->errors()->add('category_ids', 'Ada kategori yang tidak tersedia untuk paket yang dipilih.');
                    break;
                }
            }
        });

        return $validator;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $request = request();
        $selectedPlan = $request instanceof Request
            ? $this->resolveSelectedPlan($request)
            : null;
        if ($selectedPlan === null) {
            $selectedPlan = LandingPricingPlan::query()
                ->orderByDesc('is_popular')
                ->orderBy('sort_order')
                ->first(['id', 'code', 'name', 'max_users']);
        }

        if (request()->hasFile('avatar')) {
            $avatar = request()->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = public_path('/images/');
            $avatar->move($avatarPath, $avatarName);
        } else {
            $avatarName = null;
        }

        return DB::transaction(function () use ($data, $avatarName, $selectedPlan) {
            $planMaxUsers = (int) ($selectedPlan?->max_users ?? 5);
            if ($planMaxUsers <= 0) {
                $planMaxUsers = 500;
            }

            $tenantName = trim((string) ($data['tenant_name'] ?? ''));
            if ($tenantName === '') {
                $tenantName = 'New Tenant';
            }
            $tenantName = Str::limit($tenantName, 120, '');
            $billingCycle = in_array((string) ($data['billing_cycle'] ?? ''), ['monthly', 'yearly'], true)
                ? (string) $data['billing_cycle']
                : 'monthly';
            $selectedCategoryIds = collect((array) ($data['category_ids'] ?? []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            $tenant = Tenant::query()->create([
                'code' => $this->generateUniqueTenantCode($tenantName),
                'name' => $tenantName,
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
                'max_users' => max(1, min(500, $planMaxUsers)),
                'landing_pricing_plan_id' => $selectedPlan?->id,
                'billing_cycle' => $billingCycle,
                'subscription_status' => 'active',
            ]);
            $tenant->categories()->sync($selectedCategoryIds);

            return User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'avatar' => $avatarName,
                'role' => 'tenant_admin',
                'status' => 'active',
                'subscription_status' => 'active',
                'seat_limit_reached' => false,
            ]);
        });
    }

    protected function registered(Request $request, $user): void
    {
        $selectedPlan = $this->resolveSelectedPlan($request);
        if ($selectedPlan !== null) {
            $request->session()->flash('system_alert', [
                'icon' => 'info',
                'title' => 'Plan dipilih',
                'message' => 'Akun dan tenant baru sudah terhubung ke paket '.$selectedPlan->name.'.',
            ]);
        }
    }

    private function resolveSelectedPlan(Request $request): ?LandingPricingPlan
    {
        $incomingCode = strtolower(trim((string) ($request->query('plan') ?? $request->input('selected_plan_code', ''))));
        $sessionCode = strtolower(trim((string) $request->session()->get('selected_landing_plan_code', '')));
        $planCode = $incomingCode !== '' ? $incomingCode : $sessionCode;

        if ($planCode === '') {
            $request->session()->forget('selected_landing_plan_code');

            return null;
        }

        $plan = LandingPricingPlan::query()
            ->where('code', $planCode)
            ->first(['id', 'code', 'name', 'price_monthly', 'price_yearly', 'is_popular', 'category_slots_included']);

        if ($plan === null) {
            $request->session()->forget('selected_landing_plan_code');

            return null;
        }

        $request->session()->put('selected_landing_plan_code', $plan->code);

        return $plan;
    }

    private function generateUniqueTenantCode(string $tenantName): string
    {
        $base = Str::slug(Str::limit($tenantName, 32, ''));
        if ($base === '') {
            $base = 'tenant';
        }
        $base = substr($base, 0, 48);

        $candidate = $base;
        $index = 1;
        while (Tenant::query()->where('code', $candidate)->exists()) {
            $suffix = '-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $candidate = substr($base, 0, max(1, 64 - strlen($suffix))).$suffix;
            $index++;
        }

        return $candidate;
    }
}
