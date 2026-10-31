<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    protected $fillable = ['name', 'monthly_price', 'annual_price', 'product_limit', 'ai_included', 'payment_methods', 'included_modules', 'is_active', 'is_renewable'];

    protected function casts(): array
    {
        return [
            'annual_price' => 'integer',
            'product_limit' => 'integer',
            'ai_included' => 'boolean',
            'payment_methods' => 'array',
            'included_modules' => 'array',
            'is_active' => 'boolean',
            'is_renewable' => 'boolean',
        ];
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}
