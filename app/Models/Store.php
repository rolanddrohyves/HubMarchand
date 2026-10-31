<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'status',
        'subscription_plan_id',
        'subscription_started_at',
        'subscription_ends_at',
        'subscription_expired_notified_at',
        'free_plan_used_at',
        'suspended_at',
        'logo_path',
        'favicon_path',
        'email',
        'description',
        'phone',
        'website',
        'subdomain',
        'custom_domain',
        'address',
        'country',
        'currency',
        'timezone',
        'date_format',
        'payment_methods',
        'enabled_modules',
        'shop_theme',
        'shop_tagline',
        'shop_announcement',
        'homepage_banner_path',
        'primary_color',
        'homepage_blocks',
        'web_payment_methods',
        'cash_on_delivery',
    ];

    protected function casts(): array
    {
        return [
            'payment_methods' => 'array',
            'enabled_modules' => 'array',
            'homepage_blocks' => 'array',
            'web_payment_methods' => 'array',
            'cash_on_delivery' => 'boolean',
            'suspended_at' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'subscription_expired_notified_at' => 'datetime',
            'free_plan_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function couriers(): HasMany
    {
        return $this->hasMany(Courier::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function deliverySettlements(): HasMany
    {
        return $this->hasMany(DeliverySettlement::class);
    }

    public function b2bDocuments(): HasMany
    {
        return $this->hasMany(B2bDocument::class);
    }

    public function shopVisits(): HasMany
    {
        return $this->hasMany(ShopVisit::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function hasActiveSubscription(): bool
    {
        return ($this->status ?? 'active') === 'active'
            && ($this->subscription_ends_at === null || $this->subscription_ends_at->isFuture())
            && ($this->subscription_plan_id === null || $this->subscriptionPlan()->where('is_active', true)->exists());
    }

    public function hasModuleAccess(string $module): bool
    {
        $planModules = $this->subscriptionPlan?->included_modules;
        $storeModules = $this->enabled_modules;

        return ($planModules === null || in_array($module, $planModules, true))
            && ($storeModules === null || in_array($module, $storeModules, true));
    }

    public function hasExpiredSubscription(): bool
    {
        return $this->subscription_ends_at?->isPast() ?? false;
    }

    public function suspendIfSubscriptionExpired(): bool
    {
        if ($this->status !== 'active' || ! $this->hasExpiredSubscription()) {
            return false;
        }

        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'subscription_expired_notified_at' => $this->subscription_expired_notified_at ?? now(),
        ]);

        return true;
    }
}
