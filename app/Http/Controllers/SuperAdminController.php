<?php

namespace App\Http\Controllers;

use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(): View
    {
        return view('super-admin.index', [
            'merchantCount' => Store::count(),
            'activeMerchantCount' => Store::where('status', 'active')->count(),
            'suspendedMerchantCount' => Store::where('status', 'suspended')->count(),
            'planCount' => SubscriptionPlan::where('is_active', true)->count(),
            'recentMerchants' => Store::with(['user', 'subscriptionPlan'])->latest()->limit(8)->get(),
            'recentLogs' => PlatformAuditLog::with(['actor', 'store'])->latest()->limit(8)->get(),
        ]);
    }

    public function merchants(Request $request): View
    {
        $merchants = Store::with(['user', 'subscriptionPlan'])
            ->when($request->user()->is_platform_admin && ! $request->user()->is_super_admin, fn ($query) => $query->whereIn('id', $request->user()->assignedStores()->select('stores.id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();

                $query->where(function ($merchantQuery) use ($search): void {
                    $merchantQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('super-admin.merchants', [
            'merchants' => $merchants,
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('monthly_price')->get(),
        ]);
    }

    public function systemSettings(): View
    {
        abort_unless(request()->user()->isSuperAdmin(), 403);

        return view('super-admin.settings', ['settings' => SystemSetting::current()]);
    }

    public function updateSystemSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'maintenance_mode' => ['boolean'],
        ]);
        $settings = SystemSetting::current();
        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('system', 'public');
        }
        unset($validated['logo']);
        $validated['maintenance_mode'] = $request->boolean('maintenance_mode');
        $settings->update($validated);

        return back()->with('status', 'Paramétrage système enregistré.');
    }

    public function platformAdmins(): View
    {
        abort_unless(request()->user()->isSuperAdmin(), 403);

        return view('super-admin.admins', [
            'admins' => User::where('is_platform_admin', true)->with('assignedStores')->latest()->get(),
            'stores' => Store::with('user')->latest()->get(),
        ]);
    }

    public function storePlatformAdmin(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', Rule::exists('stores', 'id')],
        ]);
        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_platform_admin' => true,
            'is_super_admin' => false,
        ]);
        $admin->assignedStores()->sync($validated['store_ids'] ?? []);

        return back()->with('status', 'Administrateur délégué créé.');
    }

    public function updatePlatformAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin() && $user->is_platform_admin, 403);
        $validated = $request->validate([
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', Rule::exists('stores', 'id')],
        ]);
        $user->assignedStores()->sync($validated['store_ids'] ?? []);

        return back()->with('status', 'Périmètre de l’administrateur mis à jour.');
    }

    public function destroyPlatformAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin() && $user->is_platform_admin, 403);
        $user->delete();

        return back()->with('status', 'Administrateur délégué supprimé.');
    }

    public function storeMerchant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'store_name' => ['required', 'string', 'max:255'],
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'subscription_ends_at' => ['nullable', 'date', 'after:today'],
        ]);

        $merchant = null;

        DB::transaction(function () use ($request, $validated, &$merchant): void {
            $merchant = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_super_admin' => false,
                'two_factor_enabled' => false,
            ]);
            $store = Store::create([
                'user_id' => $merchant->id,
                'name' => $validated['store_name'],
                'email' => $merchant->email,
                'status' => 'pending',
                'subscription_plan_id' => $validated['plan_id'] ?? null,
                'subscription_started_at' => now(),
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? now()->addMonth(),
                'free_plan_used_at' => $this->isOneTimePlan($validated['plan_id']) ? now() : null,
            ]);
            PlatformAuditLog::create([
                'actor_id' => $request->user()->id,
                'store_id' => $store->id,
                'action' => 'merchant.created',
                'description' => 'Création de la boutique '.$store->name,
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Compte marchand créé en attente de validation.');
    }

    public function assignPlan(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStore($request, $store);
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'subscription_ends_at' => ['nullable', 'date', 'after:today'],
        ]);
        $plan = isset($validated['plan_id'])
            ? SubscriptionPlan::findOrFail($validated['plan_id'])
            : null;

        if ($plan !== null && ! $plan->is_renewable && $store->free_plan_used_at !== null) {
            return back()->with('error', 'Le forfait gratuit est utilisable une seule fois et ne peut pas être renouvelé.');
        }

        $store->update([
            'subscription_plan_id' => $validated['plan_id'] ?? null,
            'subscription_started_at' => now(),
            'subscription_ends_at' => $validated['subscription_ends_at'] ?? now()->addMonth(),
            'subscription_expired_notified_at' => null,
            'status' => 'active',
            'suspended_at' => null,
            'free_plan_used_at' => ! $plan?->is_renewable && $plan !== null
                ? ($store->free_plan_used_at ?? now())
                : $store->free_plan_used_at,
        ]);
        PlatformAuditLog::create([
            'actor_id' => $request->user()->id,
            'store_id' => $store->id,
            'action' => 'merchant.plan_assigned',
            'description' => 'Forfait mis à jour pour '.$store->name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Forfait du marchand mis à jour.');
    }

    public function showMerchant(Store $store): View
    {
        $this->authorizeStore(request(), $store);

        return view('super-admin.merchant-show', [
            'store' => $store->load(['user', 'subscriptionPlan']),
        ]);
    }

    public function editMerchant(Store $store): View
    {
        $this->authorizeStore(request(), $store);

        return view('super-admin.merchant-edit', [
            'store' => $store->load('user'),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('monthly_price')->get(),
        ]);
    }

    public function updateMerchant(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStore($request, $store);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($store->user_id)],
            'store_name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'subscription_ends_at' => ['nullable', 'date', 'after:today'],
        ]);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        if (! $plan->is_renewable && $store->free_plan_used_at !== null && $store->subscription_plan_id !== $plan->id) {
            return back()->withInput()->with('error', 'Le forfait gratuit est utilisable une seule fois pour ce compte.');
        }

        DB::transaction(function () use ($request, $store, $validated, $plan): void {
            $store->user()->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                ...($request->filled('password') ? ['password' => Hash::make($validated['password'])] : []),
            ]);
            $store->update([
                'name' => $validated['store_name'],
                'email' => $validated['email'],
                'subscription_plan_id' => $validated['plan_id'],
                'subscription_started_at' => now(),
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? now()->addMonth(),
                'subscription_expired_notified_at' => null,
                'status' => 'active',
                'suspended_at' => null,
                'free_plan_used_at' => ! $plan->is_renewable
                    ? ($store->free_plan_used_at ?? now())
                    : $store->free_plan_used_at,
            ]);
        });

        return redirect()->route('super-admin.merchants.show', $store)->with('status', 'Compte marchand modifié.');
    }

    public function destroyMerchant(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStore($request, $store);
        DB::transaction(function () use ($request, $store): void {
            PlatformAuditLog::create([
                'actor_id' => $request->user()->id,
                'store_id' => $store->id,
                'action' => 'merchant.deleted',
                'description' => 'Suppression de la boutique '.$store->name,
                'ip_address' => $request->ip(),
            ]);
            $store->user()->delete();
        });

        return redirect()->route('super-admin.merchants')->with('status', 'Compte marchand supprimé.');
    }

    public function toggleMerchant(Request $request, Store $store): RedirectResponse
    {
        $this->authorizeStore($request, $store);
        $status = $store->status === 'active' ? 'suspended' : 'active';
        DB::transaction(function () use ($request, $store, $status): void {
            $store->update([
                'status' => $status,
                'suspended_at' => $status === 'suspended' ? now() : null,
            ]);
            PlatformAuditLog::create([
                'actor_id' => $request->user()->id,
                'store_id' => $store->id,
                'action' => $status === 'suspended' ? 'merchant.suspended' : 'merchant.reactivated',
                'description' => ($status === 'suspended' ? 'Suspension' : 'Réactivation').' de la boutique '.$store->name,
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', $status === 'suspended' ? 'Boutique suspendue.' : 'Boutique réactivée.');
    }

    public function plans(): View
    {
        abort_unless(request()->user()->isSuperAdmin(), 403);

        return view('super-admin.plans', ['plans' => SubscriptionPlan::withCount('stores')->latest()->get()]);
    }

    public function storePlan(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $validated = $this->validatePlan($request);
        $validated = $this->normalizePlan($request, $validated);
        SubscriptionPlan::create($validated);

        return back()->with('status', 'Forfait créé.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $validated = $this->validatePlan($request);
        $validated = $this->normalizePlan($request, $validated);
        $plan->update($validated);

        return back()->with('status', 'Forfait « '.$plan->name.' » modifié.');
    }

    public function destroyPlan(SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless(request()->user()->isSuperAdmin(), 403);
        if ($plan->stores()->exists()) {
            return back()->with('error', 'Ce forfait ne peut pas être supprimé car il est attribué à une ou plusieurs boutiques.');
        }

        $plan->delete();

        return back()->with('status', 'Forfait supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'annual_price' => ['required', 'integer', 'min:0'],
            'product_limit' => ['nullable', 'integer', 'min:1'],
            'ai_included' => ['boolean'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', 'in:mobile_money,card'],
            'included_modules' => ['nullable', 'array'],
            'included_modules.*' => ['string', Rule::in(['stock', 'pos', 'analytics', 'shop', 'crm', 'delivery', 'invoice', 'copilot'])],
            'included_modules_configured' => ['boolean'],
            'is_renewable' => ['boolean'],
        ]);
    }

    private function isOneTimePlan(?int $planId): bool
    {
        return $planId !== null
            && ! SubscriptionPlan::whereKey($planId)->value('is_renewable');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePlan(Request $request, array $validated): array
    {
        $validated['ai_included'] = $request->boolean('ai_included');
        if ($request->boolean('included_modules_configured')) {
            $validated['included_modules'] = array_values($request->input('included_modules', []));
        } else {
            unset($validated['included_modules']);
        }
        $validated['is_renewable'] = $request->boolean('is_renewable')
            && ((int) $validated['monthly_price'] > 0 || (int) $validated['annual_price'] > 0);

        return $validated;
    }

    public function logs(): View
    {
        abort_unless(request()->user()->isSuperAdmin(), 403);

        return view('super-admin.logs', ['logs' => PlatformAuditLog::with(['actor', 'store'])->latest()->paginate(25)]);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        abort_unless(
            $request->user()->isSuperAdmin()
                || ($request->user()->isPlatformAdmin() && $request->user()->assignedStores()->whereKey($store->id)->exists()),
            403
        );
    }
}
